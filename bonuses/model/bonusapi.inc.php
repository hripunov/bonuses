<?php
namespace Bonuses\Model;

use Bonuses\Model\Orm\AddOrderBonuses;
use Bonuses\Model\Orm\BonusesUsedToOrder;
use Bonuses\Model\Orm\BonusHistory;
use BonusesExport1C\Model\NotifyApi;
use Catalog\Model\CostApi;
use Catalog\Model\CurrencyApi;
use Catalog\Model\Orm\Product;
use ExternalApi\Model\Utils;
use Main\Model\Requester\ExternalRequest;
use RS\Application\Auth;
use RS\Db\Exception;
use RS\Module\AbstractModel\BaseModel;
use Shop\Model\Cart;
use Shop\Model\Orm\CartItem;
use Shop\Model\Orm\Discount;
use Shop\Model\Orm\Order;
use Shop\Model\TransactionApi;
use Users\Model\Orm\User;
use RS\Module\Manager as ModuleManager;

class BonusApi extends BaseModel
{
    const
        ADD_TEXT_REGISTER = 'Добавление бонусов за регистрацию',
        ADD_TEXT_BIRTHDAY = 'Добавление бонусов в честь дня рождения',
        ADD_TEXT_EXPAIRE  = 'Списание бонусов по истечению времени';

    protected $config;
    protected $shop_config; //Конфиг модуля магазина

    /**
     * @var Cart $cart
     */
    protected $cart;
    /**
     * @var BonusProductDiscountApi $discount_product_api
     */
    public $discount_product_api;
    public static $order_applyed_bonuses = array();

    /**
     * Конструктор
     * @throws \RS\Exception
     */
    function __construct()
    {
        $this->config = \RS\Config\Loader::byModule($this); //Получим текущий конфиг
        $this->shop_config = \RS\Config\Loader::byModule('shop'); //Получим конфиг магазина
        $this->discount_product_api = new BonusProductDiscountApi();
    }


    /**
     * Проверяет были ли добавлены бонусы за оформленый заказ
     *
     * @param integer $order_id - id заказа
     * @return AddOrderBonuses|false
     * @throws \RS\Orm\Exception
     */
    function checkOrderBonusAddBefore($order_id)
    {
        return \RS\Orm\Request::make()
            ->from(new AddOrderBonuses())
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId(),
                'order_id' => $order_id,
            ))
            ->object();
    }

    /**
     * Добавляет запись о том, что добавлены бонусы к заказу
     *
     * @param integer $order_id - id заказа
     * @param integer $bonuses - количество бонусов
     */
    function addOrderBonusInfoToHistory(int $order_id, int $bonuses)
    {
        $add_order_bonus = new AddOrderBonuses();
        $add_order_bonus['order_id'] = $order_id;
        $add_order_bonus['bonuses']  = $bonuses;
        $add_order_bonus->insert();
    }

    /**
     * Проводит транзакцию по зачислению или списанию бонусов
     *
     * @param User $user - объект пользователя
     * @param integer $amount - количество баллов к зачислению или списанию
     * @param string $reason - причина списания или зачисления
     * @param boolean $type - true это зачислить, false это списать
     * @param string|false $date - Y-m-d H:i:s, если задано, то будет записано на эту дату
     * @param string|null $extra - экстра данные
     *
     * @return boolean
     * @throws Exception
     */
    function addBonusesTransaction(
        User $user,
        int $amount,
        string $reason,
        bool $type = true,
        $date = false,
        string $extra = null
    )
    {
        if (!$type && ($user['bonuses'] < $amount)){ //Проверим можно ли списать сумму с баланса
            $this->addError(t('Невозможно списать бонусы (в количестве - %0). Недостаточно бонусов на балансе.', [
                $amount
            ]));
            return false;
        }

        if ($this->config['use_only_for_price_groups'] && !empty($this->config['use_only_for_price_groups'])){ // Проверим если нужно в какой ценовой группе пользователь
            $cost_id = CostApi::getUserCost($user);
            if (!in_array($cost_id, $this->config['use_only_for_price_groups'])){ //Если Группа отличается от указанной
                $this->addError(t('Ваша ценовая группа не позволяет применить бонусы.'));
                return false;
            }
        }

        // Определим можно ли выполнить транзакцию
        $can_do_transaction = true;
        if ( // Проверим можно ли списать сумму с баланса
            ModuleManager::staticModuleExists('bonusesexport1c') &&
            ModuleManager::staticModuleEnabled('bonusesexport1c') &&
            $this->config['export_only_1c'] &&
            $extra != 'fromApi'
        ){
            $can_do_transaction = false;
        }

        if ( // Проверим можно ли списать сумму с баланса
            ModuleManager::staticModuleExists('bonusesexport1c') &&
            ModuleManager::staticModuleEnabled('bonusesexport1c') &&
            !$can_do_transaction
        ){
            $exportNotifyApi = new NotifyApi();
            $exportNotifyApi->sendNotifyTo1C('addTransaction', [
                'user' => Utils::extractOrm($user),
                'transaction' => $this->last_history_row,
            ], ExternalRequest::METHOD_POST);
            return 0;
        }

        // Добавим строчку в историю
        $res = $this->addHistoryRow($user['id'], $type ? $amount : -$amount, $reason, $date, $extra);
        return $res ? $this->writeOffUserBonusBalance($user, $type ? $amount : -$amount) : 0;
    }


    /**
     * Списывает или начисляет баланс пользователю
     *
     * @param User $user - пользователь
     * @param integer $amount - количество бонусов для зачисления или списания
     *
     * @return integer
     * @throws Exception
     */
    function writeOffUserBonusBalance($user, $amount)
    {
        $q = \RS\Orm\Request::make()
            ->update()
            ->from(new User())
            ->where(array(
                'id' => $user['id']
            ));

        if ($amount>=0){ //Начислям
            $q->set('bonuses = bonuses + '.$amount);
            $user['bonuses'] = $user['bonuses']+$amount;

            //Отправляем уведомления, если бонусов больше 0
            if ($amount>0){
                $notice = new \Bonuses\Model\Notice\BonusAdd();
                $notice->init($user, $amount);
                \Alerts\Model\Manager::send($notice);
            }
        }else{ //Списываем        
            $q->set('bonuses = bonuses - '.abs($amount));
            $user['bonuses'] = $user['bonuses']-abs($amount);
            $notice = new \Bonuses\Model\Notice\BonusDelete();
            $notice->init($user, abs($amount));
            \Alerts\Model\Manager::send($notice);
        }
        $q->exec();
        return $user['bonuses'];
    }

    /**
     * Добавляет запись в историю о выполненой операции с бонусами по баллансу
     *
     * @param integer $user_id - id пользователя
     * @param integer $amount - количество бонусов
     * @param string $reason - причина зачисления или списания
     * @param string|false $date - Y-m-d H:i:s, если задано, то будет записано на эту дату
     * @param string|null $extra - экстра данные
     *
     * @return false
     */
    private function addHistoryRow(
        int $user_id,
        int $amount,
        string $reason,
        $date = false,
        string $extra = null
    ): bool {
        $bonus_history = new BonusHistory();
        $bonus_history['user_id'] = $user_id;
        $bonus_history['amount']  = $amount;
        $bonus_history['reason']  = $reason;
        if ($date){
            $bonus_history['dateof'] = $date;
        }
        if ($extra){
            $bonus_history['extra'] = $extra;
        }
        $res = $bonus_history->insert();
        $this->last_history_row = $bonus_history;
        return $res;
    }

    /**
     * Проверяет добавлена ли запись в историю о зачислении бонусов за дни рождения в текущем году
     *
     * @param integer $user_id - id пользователя
     * @param string $birthday_date - дата рождения в формате Y-m-d
     * @return BonusHistory|false
     * @throws \RS\Orm\Exception
     */
    function checkBirthDayAddedFundsBefore($user_id, $birthday_date)
    {
        if ($birthday_date > date('Y-m-d')){
            return true;
        }

        //Посмотрим есть ли запись в БД о дне рождении
        $result = \RS\Orm\Request::make()
            ->from(new BonusHistory())
            ->where(array(
                'user_id' => $user_id,
                'reason' => t(self::ADD_TEXT_BIRTHDAY),
            ))
            ->where("dateof >= '".$birthday_date." 00:00:00' AND dateof <= '".$birthday_date." 23:59:59'")
            ->object();

        return $result;
    }

    /**
     * Возвращает массив сведений о пользователях и их не списанных бонусах
     *
     * @return array
     * @throws Exception
     */
    private function getUsersNotUsedBonuses()
    {
        return \RS\Orm\Request::make()
            ->from(new User(), 'U')
            ->where('U.bonuses > 0')
            ->exec()
            ->fetchSelected('id', array(
                'id',
                'bonuses'
            ));

    }

    /**
     * Возвращает последнюю запись из истории бонусов
     *
     * @param integer $user_id - id пользователя
     * @return BonusHistory|false
     * @throws \RS\Orm\Exception
     */
    private function getLastestUserBonusHistoryPlusItem($user_id)
    {
        return \RS\Orm\Request::make()
            ->from(new BonusHistory())
            ->where(array(
                'user_id' => $user_id
            ))
            ->where('amount > 0')
            ->orderby('id DESC')
            ->object();
    }

    /**
     * Списывает бонусы которые пользователь не использовал в течении определённого периода в днях
     *
     * @param integer $expire_in_days - период в днях в течении, которого должны списаться бонусы
     * @return mixed
     * @throws Exception
     * @throws \RS\Orm\Exception
     */
    function writeOffNotUsedBonuses($expire_in_days)
    {
        $check_date = time() - ($expire_in_days * 60 * 60 * 24);

        //Получим пользователей с несписанными бонусами.
        $users_to_bonuses = $this->getUsersNotUsedBonuses();

        if (!empty($users_to_bonuses)){
            foreach ($users_to_bonuses as $user_id => $data){
                //Получим последнее пополнение
                $item = $this->getLastestUserBonusHistoryPlusItem($user_id);

                if ($item && (strtotime($item['dateof']) < $check_date)){
                    $user = new User($user_id);
                    $this->addBonusesTransaction($user, $data['bonuses'], t('Списание бонусов по истечению их срока активности'), false);
                    $user['bonuses'] = 0;
                    $user->update();
                }
            }
        }
    }

    /**
     * Отправляет уведомление пользователю о том, что у него остались бонусы
     *
     * @param integer $after_days - сколько дней
     * @param integer $notify_time - какой раз уведомления
     * @throws Exception
     * @throws \RS\Orm\Exception
     */
    function notifyUsersAboutBonuses($after_days, $notify_time = 1)
    {
        //Получим пользователей с несписанными бонусами.
        $users_to_bonuses = $this->getUsersNotUsedBonuses();

        if (!empty($users_to_bonuses)) {
            foreach ($users_to_bonuses as $user_id => $data) {
                //Получим последнее пополнение
                $item = $this->getLastestUserBonusHistoryPlusItem($user_id, $notify_time);
                $check_date = strtotime($item['dateof']) + ($after_days * 60 * 60 * 24);
                if ($item && !$item['notify'.$notify_time] && (time() > $check_date)){
                    $last_date = date('d.m.Y', strtotime($item['dateof']) + ($this->config['bonuses_lifetime'] * 60 * 60 * 24));

                    //Уведомим
                    $last_days = floor((strtotime($last_date) - time())/(60 * 60 * 24));
                    if ($last_days > 0){
                        $notice = new \Bonuses\Model\Notice\NotUsedBonuses();
                        $notice->init(new User($user_id), $data['bonuses'], $last_date, $notify_time);
                        \Alerts\Model\Manager::send($notice);
                    }

                    //Обновим сведения о нотификации
                    $item['notify'.$notify_time] = 1;
                    $item->update();
                }
            }
        }
    }

    /**
     * Проверяет можно ли конвертировать бонусы в скидку или на лицевой счёт и проверяет возможность использования бонусов
     *
     * @param Cart $cart - объект корзины
     * @param array|false $cart_data - массив с данными корзины
     *
     * @return array
     * @throws Exception
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    function checkConvertBonuses(Cart $cart, $cart_data = false)
    {
        if (!$cart_data){
            $cart_data = $cart->getCartData();
        }
        $cart_total = $cart_data['total'];
        if (
            $this->config['min_cart_summ'] &&
            (
                $this->config['disable_product_by_action'] ||
                $this->config['disable_use_product_by_action_in_cart']
            )
        ){
            $product_items = $cart->getProductItems();

            foreach ($product_items as $uniq=>$item){
                /**
                 * @var Product $product
                 */
                $product = $item['product'];
                $old_cost = $product->getOldCost(null, false);
                if ($old_cost > 0){
                    $all_cost = $cart_data['items'][$uniq]['cost'];
                    $cart_total -= $all_cost;
                }
            }
        }

        $user = Auth::getCurrentUser();
        //Проверим, если можно ли использовать бонусы, если нужно посчитать заказы
        if ($this->config['orders_count_before_use'] && !$user->isHaveCountOrders()){
            $error = t('Использование бонусов доступно после %ordcnt [plural:%ordcnt:заказа|заказа|заказов]!', array('ordcnt' => $this->config['orders_count_before_use']));
            $cart_data['errors'][] = $error;
            $cart_data['has_error'] = true;
        }

        //Если нельзя использовать купон вместе с бонусами, то проверим его наличие.
        if (!$this->config['can_use_coupon']){
            $coupons = $cart->getCartItemsByType(Cart::TYPE_COUPON);
            if (!empty($coupons)){
                $error = t('Нельзя использовать купон вместе с бонусами');
                $cart_data['errors'][] = $error;
                $cart_data['has_error'] = true;
            }
        }

        //Проверим есть ли бонусы вообще у пользователя
        if ($user_bonuses = $user->getUserBonuses()){
            //Если задана минимальная сумма заказа, которая должна остаться после применения бонусов
            if ($_SESSION['use_cart_bonuses'] && $this->config['min_cart_summ_last'] && ($this->config['min_cart_summ_last'] > $cart_total)){
                $default_currency = CurrencyApi::getDefaultCurrency(); //Получаем валюту по умолчанию
                if ($this->config['disable_use_product_by_action_in_cart'] && $this->config->isHaveOldPriceInCartPage($cart, $cart_data)){
                    $cart_data['errors'][] = t('Бонусы не применяются к акционным и уцененным товарам!');
                    $cart_data['has_error'] = true;
                }
                $error = t('Минимальная сумма, которая должна остаться после применения бонусов %0 %1', array(\RS\Helper\CustomView::cost($this->config['min_cart_summ_last']), $default_currency['stitle']));
                $cart_data['errors'][] = $error;
                $cart_data['has_error'] = true;
                return $cart_data;
            }

            if (isset($_SESSION['product_applied_bonuses'])){
                $cart_total = $cart_total + $_SESSION['product_applied_bonuses'];
            }

            //Если задана минимальная сумма заказа для использования бонусов
            if ($this->config['min_cart_summ'] && ($this->config['min_cart_summ'] > $cart_total)){
                $default_currency = CurrencyApi::getDefaultCurrency(); //Получаем валюту по умолчанию
                if ($this->config['disable_use_product_by_action_in_cart'] && $this->config->isHaveOldPriceInCartPage($cart, $cart_data)){
                    $cart_data['errors'][] = t('Бонусы не применяются к акционным и уцененным товарам!');
                    $cart_data['has_error'] = true;
                }
                $error = t('Минимальная сумма для использования бонусов должна быть %0 %1', array(\RS\Helper\CustomView::cost($this->config['min_cart_summ']), $default_currency['stitle']));
                $cart_data['errors'][] = $error;
                $cart_data['has_error'] = true;
            }


            return $cart_data;
        }
        $error = t('Не хватает бонусов для использования');
        $cart->addUserError($error, true, 'not_enough_bonuses');
        unset($cart->is_cartbonus_action);
        $cart_data['errors'][] = $error;
        $cart_data['has_error'] = true;
        return $cart_data;
    }


    /**
     * Добавляет сведения о скидке на весь заказ в корзине
     *
     * @param array $cart_data - данные корзины
     * @param integer $apply_bonuses - добавленные бонусы
     * @param integer $amount - Скидка общая
     *
     * @return array
     */
    private function addOrderDiscountToCartData($cart_data, $apply_bonuses, $amount)
    {
        $cart_data['order_bonuses_for_discount'] = $apply_bonuses;
        $cart_data['order_discount']             = $amount;
        $cart_data['order_discount_extra']       = t('(Использовано бонусов - %0)', array($apply_bonuses));
        $cart_data['order_discount_unformatted'] = $amount;

        return $cart_data;
    }

    /**
     * Проверяет находится ли пользователь в нужной группе для применения бонусов в скмдку
     *
     * @return bool
     */
    function checkUserNotInRightGroupForDiscount()
    {
        $user    = Auth::getCurrentUser();
        $cost_id = CostApi::getUserCost($user);
        if (empty($this->config['use_only_for_price_groups'])){
            return false;
        }
        if (!in_array($cost_id, (array)$this->config['use_only_for_price_groups'])){ //Если Группа отличается от указанной
            return true;
        }
        return false;
    }

    /**
     * Возвращает процент скидки от наличия бонусов
     *
     * @param float $total - общая сумма корзины
     * @param float $amount - сумма скидки
     * @return float|int
     */
    private function getPercentFromCartDataTotal($total, $amount)
    {
        return ($total > 0) ? floor(($amount * 100) / $total) : 0;
    }

    /**
     * Проверяет минимальный процент скидки, для применения бонусов
     *
     * @param float $percent - расчитанный процент скидки
     * @param float $total - общая сумма корзины
     * @param array $cart_data - массив данных корзины
     *
     * @return array
     */
    private function checkMinPercentToDiscount($percent, $total, $cart_data)
    {
        if ($this->config['min_percent_to_discount'] && ($percent < $this->config['min_percent_to_discount'])){
            //Рассчитаем сколько бонусов нам нужно, для нашего процента
            $percent_bonuses = floor(($total * $this->config['min_percent_to_discount'] / 100) / $this->config['equal_bonuses_number']);
            $error = t('Для перевода в скидку нужно иметь не менее %0 бонусных баллов', array($percent_bonuses));
            // $cart->addUserError($error, true, 'convert_to_bonuses');
            $cart_data['errors'][] = $error;
            $cart_data['has_error'] = true;
        }
        return $cart_data;
    }

    private function getMaxPercentParamsToDiscount($percent, $apply_bonuses, $total, $amount)
    {
        if ($this->config['max_percent_to_discount'] && ($percent > $this->config['max_percent_to_discount'])){
            $percent = $this->config['max_percent_to_discount'];
            $apply_bonuses = floor(($total * $percent) / 100) / $this->config['equal_bonuses_number'];
            $amount = $apply_bonuses * $this->config['equal_bonuses_number'];
        }
        return array(
            'percent' => $percent,
            'apply_bonuses' => $apply_bonuses,
            'amount' => $amount,
        );
    }

    /**
     * Возвращает параметры для применения скидок к корзине. При этом делает все необходимое проверки
     *
     * @param $bonuses
     * @param $cart_data_total
     * @param $cart_data
     * @return array
     */
    private function getDataParamsToApplyDiscount($bonuses, $cart_data_total, $cart_data)
    {
        $apply_bonuses = $bonuses;
        //Подсчитаем процент скидки
        $amount  = $bonuses * $this->config['equal_bonuses_number'];
        $percent = $this->getPercentFromCartDataTotal($cart_data_total, $amount);

        $cart_data = $this->checkMinPercentToDiscount($percent, $cart_data_total, $cart_data);

        //Если задан максимальный размер скидки
        $data = $this->getMaxPercentParamsToDiscount($percent, $apply_bonuses, $cart_data_total, $amount);
        $percent = $data['percent'];
        $apply_bonuses = $data['apply_bonuses'];
        $amount = $data['amount'];


        //Размер скидки не должен превышать 100%
        if ($percent > 100){
            $apply_bonuses = floor($cart_data_total / $this->config['equal_bonuses_number']);
            $amount        = $apply_bonuses * $this->config['equal_bonuses_number'];
        }
        return array(
            'cart_data' => $cart_data,
            'apply_bonuses' => $apply_bonuses,
            'amount' => $amount,
            'percent' => $percent
        );
    }

    /**
     * Проверяет опцию использования отлючения применения скидок в корзине и учет закернутой цены как скидки на товары
     *
     * @param array $cart_data - массив данных корзины
     * @return array
     */
    function checkDisableUseProductOptionAndUseOldCostOption($cart_data)
    {
        $this->shop_config = \RS\Config\Loader::byModule('shop');
        if ($this->config['disable_use_product_by_action_in_cart'] && $this->shop_config['old_cost_delta_as_discount']){
            $error = t('Опция "Запретить перевод бонусов за товар в скидку в корзине по акции? в модуле Бонусов" и "Считать разницу от старой цены как скидку на товар" в модуле магазин не совместимы. Выберите, что-то одно');
            $cart_data['errors'][] = $error;
            $cart_data['has_error'] = true;
        }
        return $cart_data;
    }

    /**
     * Конвертирует бонусы в значение скидки на весь заказ, проверяет возможность конвертации
     *
     * @param integer $bonuses - количество бонусов
     * @param Cart $cart - объект корзины
     * @param array|false $cart_data - массив с данными корзины
     *
     * @return array
     * @throws \RS\Exception
     */
    function convertBonusesToOrderDiscount($bonuses, Cart $cart, $cart_data = false){
        if (!$cart_data){
            $cart->is_cartbonus_action = true; //Защита от рекурсии
            $cart_data = $cart->getCartData(false, false);
            unset($cart->is_cartbonus_action);
        }

        if ($this->checkUserNotInRightGroupForDiscount()){
            return $cart_data;
        }
        $cart_data_total = $cart_data['total'];

        $data = $this->getDataParamsToApplyDiscount($bonuses, $cart_data_total, $cart_data);

        $cart_data     = $data['cart_data'];
        $apply_bonuses = $data['apply_bonuses'];
        $amount        = $data['amount'];

        if (!empty($cart_data)){
            $cart_data = $this->addOrderDiscountToCartData($cart_data, $apply_bonuses, $amount);
        }

        return $cart_data;
    }


    /**
     * Конвертирует бонусы в значение скидки на весь заказ, проверяет возможность конвертации
     *
     * @param integer $bonuses - количество бонусов
     * @param Cart $cart - объект корзины
     * @param array|false $cart_data - массив с данными корзины
     *
     * @return array
     * @throws \RS\Exception
     */
    function convertBonusesToPersonalProductDiscounts($bonuses, Cart $cart, $cart_data = false){
        if (!$cart_data){
            $cart_data = $cart->getCartData(false, false);
        }

        $cart_data_total = $cart_data['total'];
        if (!empty($cart_data)){
            $cart_data_total = $this->discount_product_api->countCartDataTotalWithOutOldPriceItems($cart, $cart_data);
        }

        $data = $this->getDataParamsToApplyDiscount($bonuses, $cart_data_total, $cart_data);
        $cart_data     = $data['cart_data'];
        $apply_bonuses = $data['apply_bonuses'];

        if (!empty($cart_data)){
            $cart_data = $this->discount_product_api->addOrderDiscountToEachProduct($cart, $cart_data, $apply_bonuses);
        }

        return $cart_data;
    }


    /**
     * Конвертирует бонусы в значение скидки, проверяет возможность коныртации
     *
     * @param integer $bonuses - количество бонусов
     * @param Cart $cart - объект корзины
     * @param array|false $cart_data - массив с данными корзины
     *
     * @return array
     * @throws Exception
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     */
    function convertBonusesToDiscountPercent($bonuses, Cart $cart, $cart_data = false){
        if (!$cart_data){
            $cart->is_cartbonus_action = true; //Защита от рекурсии
            $cart_data = $cart->getCartData();
            unset($cart->is_cartbonus_action);
        }

        $amount  = $bonuses * $this->config['equal_bonuses_number'];

        //Подсчитаем процент скидки
        $percent = floor(($amount * 100)/$cart_data['total_unformatted']);

        if ($percent<1){
            //Рассчитаем сколько бонусов нам нужно, для одного процента
            $one_percent_bonuses = floor(($cart_data['total_unformatted']/100)/$this->config['equal_bonuses_number']);
            $error = t('Для перевода в скидку нужно иметь не менее %0 бонусных баллов', array($one_percent_bonuses));
            $cart->addUserError($error, true, 'convert_to_bonuses');
            $cart_data['errors'][] = $error;
        }

        if ($this->config['min_percent_to_discount'] && ($percent<$this->config['max_percent_to_discount'])){
            //Рассчитаем сколько бонусов нам нужно, для нашего процента
            $percent_bonuses = floor(($cart_data['total_unformatted'] * $this->config['min_percent_to_discount']/100)/$this->config['equal_bonuses_number']);
            $error = t('Для перевода в скидку нужно иметь не менее %0 бонусных баллов', array($percent_bonuses));
            $cart->addUserError($error, true, 'convert_to_bonuses');
            $cart_data['errors'][] = $error;
        }


        //Если задан максимальный размер скидки
        if ($this->config['max_percent_to_discount'] && ($percent>$this->config['max_percent_to_discount'])){
            $percent = $this->config['max_percent_to_discount'];
            $bonuses = floor(($cart_data['total_unformatted']*$percent)/100)/$this->config['equal_bonuses_number'];
        }


        //Размер скидки не должен превышать 100%
        if ($percent>100){
            $percent = 100;
            $bonuses = floor($cart_data['total_unformatted']/$this->config['equal_bonuses_number']);
        }


        //Если ошибок не найдено, добавим нашу скидку к корзине
        if (empty($cart_data['errors'])){
            $discount = $this->createDiscount($percent);
            $add_result = $cart->addCoupon($discount['code']);

            if ($add_result===true){ //Если купон успешно добавлен
                $cart->is_cartbonus_action = true; //Защита от рекурсии  
                $cart_data = $cart->getCartData();
                $user = Auth::getCurrentUser();

                $this->addBonusesTransaction($user, $bonuses, t('Использование бонусов в качестве скидки. Скидочный купон - %0', array($discount['code'])), false);
                unset($cart->is_cartbonus_action);
            }else{
                $cart->addUserError($add_result, true, 'discount_add');
                $cart_data['errors'][] = $add_result;
            }
        }



        return $cart_data;
    }


    /**
     * Создаёт скидку из бонусов
     *
     * @param integer $percent - процент скидки
     * @return Discount
     */
    private function createDiscount($percent)
    {
        $user = Auth::getCurrentUser();

        $discount = new Discount();
        $discount['is_bonus_discount'] = 1;
        $discount['bonus_user_id']     = $user['id'];
        $discount['code']         = "bonus_".$discount->generateCode();
        $discount['descr']        = t('Скидка сгенерированная из бонусов пользователя %0 id=%1', array($user->getFio(), $user['id']));
        $discount['active']       = 1;
        $discount['period']       = 'forever';
        $discount['round']        = 1;
        $discount['uselimit']     = 1;
        $discount['oneuserlimit'] = 1;

        if ($this->config['min_cart_summ']){ //Если бонусы ограничены минимальной суммой заказа
            $discount['min_order_price'] = $this->config['min_cart_summ'];
        }
        if ($this->config['bonuses_lifetime']){ //Если бонусы ограничен по времени
            $discount['timelimit'] = 'forever';
            $discount['endtime']   = date('Y-m-d H:i:s', time() + ($this->config['bonuses_lifetime'] * 24 * 60 * 60));
        }
        $discount['discount']      = $percent;
        $discount['discount_type'] = '%';
        $discount->insert();
        return $discount;
    }




    /**
     * Переводит бонусы на лицевой счёт пользователя
     *
     * @return boolean
     */
    function convertBonusesToPersonalAccount()
    {
        $user = Auth::getCurrentUser();

        if ($this->config['use_only_for_price_groups'] && !empty($this->config['use_only_for_price_groups'])){ //Проверим если нужно в какой ценовой группе пользователь
            $cost_id = CostApi::getUserCost($user);
            if (!in_array($cost_id, $this->config['use_only_for_price_groups'])){ //Если Группа отличается от указанной
                $this->addError(t('Ваша ценовая группа не позволяет конвертировать бонусы.'));
                return false;
            }
        }

        $bonuses = $user->getUserBonuses();
        if (!$bonuses){
            $this->addError(t('Не достаточно бонусов для зачисления на лицевой счёт. Минимально нужно иметь %0.', array(1)));
            return false;
        }
        //Если включена опция - Минимальное количество бонусов для зачисления на лицевой счёт
        if ($this->config['min_bonuses_add_to_bonuses'] && ($this->config['min_bonuses_add_to_bonuses']>$bonuses)){
            $this->addError(t('Не достаточно бонусов для зачисления на лицевой счёт. Минимально нужно иметь %0.', array($this->config['min_bonuses_add_to_bonuses'])));
            return false;
        }
        //Сумма зачисляемая на лицевой счёт
        $amount   = $bonuses * $this->config['equal_bonuses_number'];
        $transApi = new TransactionApi();
        $transApi->addFunds($user['id'], $amount, false, t('Пополнение балланса из бонусных баллов'));
        //Спишем бонусы
        $this->addBonusesTransaction($user, $bonuses, t('Списание бонусов при переводе на лицевой счёт'), false);
        return $amount;
    }

    /**
     * Возвращает количество бонусов начиляемое за заказ
     *
     * @param float $total_unformatted - сумма всего
     * @param int $total_bonuses - количество бонусов расчитанное ранее
     */
    private function getBonusesForOrder($total_unformatted, $total_bonuses)
    {
        if (!$this->config['bonuses_for_order_as_table']){ // Если это обычное начисление бонусов
            switch($this->config['bonuses_for_order_type']){
                case 0: //Единицы
                    $total_bonuses += $this->config['bonuses_for_order'];
                    break;
                case 1: //Проценты
                    $total_bonuses += ceil($total_unformatted * ($this->config['bonuses_for_order']/100));
                    break;
            }
        }else{ //Начисление по правилам
            if (!empty($this->config['bonuses_for_order_rule_arr'])){
                $cost_to_check = $total_unformatted;
                $router = \RS\Router\Manager::obj();
                if ($router->isAdminZone()) { //Если нужно получить для админ панели
                    $order = new Order(\RS\Http\Request::commonInstance()->request('id', TYPE_INTEGER, 0));
                    $current_user = $order->getUser();
                }else{
                    $current_user = Auth::getCurrentUser();
                }
                if (!empty($this->config['bonuses_for_order_with_old'])) { // Учитываем сумму прошлых заказов
                    if ($current_user['id'] > 0){
                        $cost_to_check += BonusApi::calculateOldOrdersSum($current_user['id']);
                    }
                }

                $current_rule = null;
                foreach ($this->config['bonuses_for_order_rule_arr'] as $rule){
                    if ($cost_to_check >= $rule['from']){
                        $current_rule = $rule;
                        if (!empty($rule['to']) && ($cost_to_check > $rule['to'])){
                            $current_rule = null;
                        }
                    }
                }

                if (!empty($current_rule)){
                    switch($current_rule['bonuses_type']){
                        case 'ед.': //Единицы
                            $total_bonuses += $current_rule['bonuses'];
                            break;
                        case '%': //Проценты
                            $total_bonuses += ceil($total_unformatted * ($current_rule['bonuses']/100));
                            break;
                    }
                }
            }
        }

        return $total_bonuses;
    }

    /**
     * Добавляет информацию о бонусах в сведения о корзине
     *
     * @param Cart $cart - объект корзины
     * @param array $cart_data - массив с данными корзины
     * @param User $user - массив с данными корзины
     * @return array
     * @throws \RS\Exception
     */
    function addBonusInfoToCartData(Cart $cart, $cart_data, $user)
    {
        $catalog_config = \RS\Config\Loader::byModule('catalog');
        $this->cart = $cart;
        $this->cart->is_cartbonus_action = true; //Защита от рекурсии
        $products = $cart->getProductItems();
        $cartdata = $cart->getCartData(false);

        $total_unformatted = $cart_data['total_unformatted'];

        $total_bonuses = 0;
        //Переберём товары и добавим информацию по начисленным бонусам к каждому товару
        foreach ($cartdata['items'] as $uniq=>$item){
            /**
             * @var Product $product
             */
            $product  = $products[$uniq]['product'];
            /**
             * @var CartItem $cartitem
             */
            $cartitem = $products[$uniq]['cartitem'];
            $product  = new Product($product['id']);

            $bonuses  = $product->getBonusesByCartCost($item['cost']);
            if (
                ($this->config['disable_product_by_action'] &&
                $catalog_config['old_cost'] &&
                ($product->getOldCost(null, false) > 0)) ||
                $product['bonuses_ignore']
            ){
                $total_unformatted -= ($item['cost']);
            }

            //Добавим бонусы по сопутствующие если такие имеются
            $concomitants = $product->getConcomitant();
            if (!empty($concomitants)){

                foreach($item['sub_products'] as $id=>$sub_product_data){
                    $sub_product = $concomitants[$id];
                    if ($sub_product_data['checked']){
                        $sub_product  = new Product($sub_product['id']);
                        $bonuses += $sub_product->getBonusesByCartCost($sub_product_data['cost']);

                        if (
                            ($this->config['disable_product_by_action'] &&
                            $catalog_config['old_cost'] &&
                            ($sub_product->getOldCost(null, false) > 0)) ||
                            $sub_product['bonuses_ignore']
                        ){
                            $total_unformatted -= ($sub_product_data['cost']);
                        }
                    }
                }

            }

            //$cart_data['items'][$uniq]['bonuses'] = $bonuses;
            $total_bonuses += $bonuses;
        }

        //Добавим бонусные баллы за оформленный заказ, если нужно
        if ($this->config['bonuses_for_order'] || $this->config['bonuses_for_order_as_table']){
            $total_bonuses = $this->getBonusesForOrder($total_unformatted, $total_bonuses);
        }
        if (!empty($this->config['bonuses_for_first_order']) && $user['id'] > 0){
            //Посмотрим сколько заказов было
            $cnt = \RS\Orm\Request::make()
                ->from(new Order())
                ->where([
                    'user_id' => $user['id'],
                ])->count();
            if ($cnt < 1){
                $total_bonuses = $this->config['bonuses_for_first_order'];
            }
        }

        unset($this->cart->is_cartbonus_action);
        $cart_data['total_bonuses'] = $total_bonuses;
        return $cart_data;
    }


    /**
     * Проверяет были применены бонусы к заказу после его записи или нет
     *
     * @param Order $order - объект заказа
     * @return mixed
     */
    public static function haveBonusesApplyedToOrder($order)
    {
        $user = $order->getUser();

        $cache_key = $order['id']."_".$user->id;
        if (!isset(self::$order_applyed_bonuses[$cache_key])){
            $row = \RS\Orm\Request::make()
                ->from(new BonusesUsedToOrder())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                    'user_id' => $user->id,
                    'order_id' => $order['id']
                ))->object();
            self::$order_applyed_bonuses[$cache_key] = $row ? true : false;
        }

        return self::$order_applyed_bonuses[$cache_key];
    }

    /**
     * Возвращает пользователю бонусы использованные в заказе
     *
     * @param Order $order - объект заказа
     * @throws Exception
     */
    function backBonusesAppledToOrder($order)
    {
        //Ищем связь
        /**
         * @var BonusesUsedToOrder $row
         */
        $row = \RS\Orm\Request::make()
            ->from(new BonusesUsedToOrder())
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId(),
                'user_id' => $order->getUser()->id,
                'order_id' => $order['id']
            ))->object();
        //Вернём бонусы    
        $this->addBonusesTransaction($order->getUser(), $row['bonuses'], t('Возврат бонусов за отменённый заказ №%0', array($order['order_num'])));
        //Удалим связь
        \RS\Orm\Request::make()
            ->delete()
            ->from(new BonusesUsedToOrder())
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId(),
                'user_id' => $order->getUser()->id,
                'order_id' => $order['id']
            ))->exec();

    }

    /**
     * Добавляет информацию о количестве бонусов для списка товаров подгружаемых для мобильного приложения
     *
     * @param array $list - список с информацией о товарах
     * @return array
     */
    function getBonusesForMobileProductsList($list = array())
    {
        $ids = array(); //Массив идентификаторов товаров
        foreach($list as $product_info){
            $ids[] = $product_info['id'];
        }
        $products = \RS\Orm\Request::make()
            ->from(new Product())
            ->whereIn('id', $ids)
            ->where(array(
                'public' => 1
            ))->objects();

        if ($products){
            foreach ($products as $product){
                foreach($list as &$product_info){
                    if ($product_info['id'] == $product['id']){
                        /**
                         * @var Product $product
                         */
                        $bonuses = 0;
                        if ($product->bonusesCanBeShown()){
                            $bonuses = $product->getBonuses();
                        }
                        $product_info['bonuses'] = $bonuses;
                    }
                }
            }
        }
        return $list;
    }


    /**
     * @Проверяет пользователей в назначенную дату, есть у день рождения у человека
     *
     * @param string $current_date - Y-m-d
     *
     * @throws Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    function checkUsersForBirthday($current_date)
    {
        $users =\RS\Orm\Request::make()
            ->from(new User())
            ->where("DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(NOW(), '%m-%d')")
            ->objects();


        if (!empty($users)){
            $config = \RS\Config\Loader::byModule($this);
            foreach ($users as $user){
                //Посмотрим, было ли зачислени ранее и можно ли зачислить
                if (!$this->checkBirthDayAddedFundsBefore($user['id'], $current_date)){
                    $this->addBonusesTransaction($user, $config['bonuses_for_birthday'], $this::ADD_TEXT_BIRTHDAY, true, $current_date.date(" H:i:s"));
                }
            }
        }
    }

    /**
     * Возвращает сумму за заказы ранее оформленные
     *
     * @param int $user_id - id пользователя
     * @param int|null $site_id - id сайта
     * @return float
     */
    public static function calculateOldOrdersSum(int $user_id, int $site_id = null): float
    {
        if (empty($site_id)){
            $site_id = \RS\Site\Manager::getSiteId();
        }
        $config = \RS\Config\Loader::byModule(__CLASS__);
        static $old_total;
        if ($old_total === null){
            $old_total = \RS\Orm\Request::make()
                ->select('SUM(totalcost) as total')
                ->from(new Order())
                ->where([
                    'site_id' => $site_id,
                    'user_id' => $user_id,
                    'status' => $config['bonuses_for_order_status'],
                ])->exec()
                ->getOneField('total', 0);
        }
        return $old_total;
    }
}

