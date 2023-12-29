<?php
namespace Bonuses\Config;
use Bonuses\Model\Behavior\CatalogProduct;
use Bonuses\Model\Behavior\ShopOrder;
use Bonuses\Model\Behavior\UsersUser;
use Bonuses\Model\BonusApi;
use Bonuses\Model\BonusCardApi;
use Bonuses\Model\BonusCommentApi;
use Bonuses\Model\BonusDiscountApi;
use Bonuses\Model\BonusProductDiscountApi;
use Bonuses\Model\CashoutApi;
use Bonuses\Model\Orm\AddCommentBonusesToProduct;
use Bonuses\Model\Orm\BonusCard;
use Bonuses\Model\Orm\BonusesUsedToOrder;
use Catalog\Model\CostApi;
use Catalog\Model\Orm\Dir;
use Catalog\Model\Orm\Product;
use Catalog\Model\Orm\Typecost;
use Comments\Model\Orm\Comment;
use EmailSubscribe\Model\Orm\Email;
use PHPMailer\PHPMailer\Exception;
use RS\Application\Application;
use RS\Application\Auth;
use RS\Config\Loader;
use RS\Controller\Admin\Helper\CrudCollection;
use RS\Event\Event;
use RS\Helper\Mailer;
use RS\Html\Filter\Container;
use RS\Html\Filter\Line;
use RS\Html\Filter\Type\Select;
use RS\Html\Table\Element;
use RS\Html\Table\Type\Action\Action;
use RS\Html\Table\Type\Action\DropDown;
use RS\Html\Table\Type\Actions;
use RS\Html\Table\Type\StrYesno;
use RS\Html\Table\Type\Text;
use RS\Http\Request;
use \RS\Orm\Type;
use RS\Router\Manager;
use RS\Router\Route;
use Shop\Model\Cart;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\Discount;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\OrderItem;
use Shop\Model\Orm\Payment;
use Shop\Model\Orm\UserStatus;
use Shop\Model\UserStatusApi;
use Users\Model\Orm\User;
use Users\Model\Orm\UserGroup;

/**
* Класс предназначен для объявления событий, которые будет прослушивать данный модуль и обработчиков этих событий.
*/
class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('cron')
            ->bind('getmenus')
            ->bind('getroute')
            ->bind('api.cart.getcartdata.before')
            ->bind('api.cart.getcartdata.success')
            ->bind('api.cart.changeamount.success')
            ->bind('api.cart.remove.success')
            ->bind('api.cart.update.success')
            ->bind('api.product.get.success')
            ->bind('api.product.getlist.success')
            ->bind('api.product.getrecommendedlist.success')
            ->bind('api.favorite.getlist.success')
            ->bind('api.mobilesiteapp.config.success')
            ->bind('initialize')
            ->bind('cart.before.addorderdata')
            ->bind('cart.change')
            ->bind('cart.getcartdata')
            ->bind('checkout.payment.list')
            ->bind('checkout.delivery.list')
            ->bind('controller.exec.shop-admin-discountctrl.index')
            ->bind('controller.exec.users-admin-ctrl.index')
            ->bind('controller.afterexec.catalog-front-product')
            ->bind('orm.delete.catalog-typecost')
            ->bind('orm.init.shop-discount')
            ->bind('orm.init.shop-order')
            ->bind('orm.init.shop-payment')
            ->bind('orm.init.shop-delivery')
            ->bind('orm.init.catalog-product')
            ->bind('orm.init.catalog-dir')
            ->bind('orm.init.users-user')
            ->bind('orm.init.users-usergroup')
            ->bind('orm.init.emailsubscribe-email')
            ->bind('orm.beforewrite.shop-order')
            ->bind('orm.beforewrite.users-user')
            ->bind('orm.afterwrite.users-user')
            ->bind('orm.afterwrite.shop-order')
            ->bind('orm.afterwrite.catalog-dir')
            ->bind('orm.afterwrite.emailsubscribe-email')
            ->bind('orm.afterwrite.comments-comment')
            ->bind('orm.delete.shop-order')
            ->bind('cart.updateorderitems.after')
            ->bind('meter.recalculate')
            ->bind('product.getoffercost')
            ->bind('product.calculateusercost')
            ->bind('exchange.orderexport.after')
        ;
    }

    /**
     * Обрабатываем событие планировщика
     *
     * @param array $params - массив параметров со сведениями о времени
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public static function cron($params)
    {
        //Инициализируем маршруты, если желаем пользоваться роутером далее в коде. Например,
        Manager::obj()->initRoutes();

        $config = Loader::byModule('bonuses'); //Получим конфиг
        //Запускаем в полночь проверку на автоматический перевод статусов заказов,
        if (in_array(60, $params['minutes'])) { // проверяем на наличие нулевой минуты, что означает время 00:00:00
            $bonuses_api = new BonusApi();
            if ($config['bonuses_for_birthday']){ //Если есть начисления в честь дня рождения
                $bonuses_api->checkUsersForBirthday(date('Y-m-d'));
            }

            //Если включена опция списания бонусов по истечению времени
            if ($config['bonuses_lifetime']){
                $bonuses_api->writeOffNotUsedBonuses($config['bonuses_lifetime']);

                //Отправим уведомления о том, что у пользователя ещё остались бонусы
                if ($config['bonuses_lifetime_notify']){
                    $bonuses_api->notifyUsersAboutBonuses($config['bonuses_lifetime_notify']);
                }
                if ($config['bonuses_lifetime_notify2']){
                    $bonuses_api->notifyUsersAboutBonuses($config['bonuses_lifetime_notify2'], 2);
                }
            }
        }
    }

    /**
    * Расширим объекты дополнительными методами из класса
    * 
    */
    public static function initialize()
    {
        Product::attachClassBehavior(new CatalogProduct());
        User::attachClassBehavior(new UsersUser());
        Order::attachClassBehavior(new ShopOrder());
    }

    /**
     * Добавляем информацию о количестве непросмотренных заказов
     * во время вызова события пересчета счетчиков
     *
     * @param array $meters - параметры метрики
     * @return mixed
     *
     * @throws \RS\Exception
     */
    public static function meterRecalculate($meters)
    {
        $cashout_api = new CashoutApi();
        $cashout_meter_api = $cashout_api->getMeterApi();
        $meters[$cashout_meter_api->getMeterId()] = $cashout_meter_api->getUnviewedCounter();

        return $meters;
    }


    /**
     * Получаем список оплат при оформлении заказа
     *
     * @param array $data - массив данных
     * @return array
     * @throws \RS\Exception
     */
    public static function checkoutPaymentList($data)
    {
        /**
         * @var Payment[] $payment_list
         * @var Order $order
         */
        $payment_list = $data['list'];
        $order        = $data['order'];
        $config       = Loader::byModule('bonuses');

        //Посмотрим, если стоит опция скрывать список оплат если у заказа уже есть скидка
        if ($config['disable_payment_with_discount'] && !empty($payment_list)){
            $cartdata = $order->getCart()->getCartData(false, false);

            $have_discount = false; //Флаг если скидка применена
            if ($cartdata['total_discount'] > 0){
                $have_discount = true;
            }
            foreach ($cartdata['items'] as $uniq=>$item){
                if ($item['discount'] > 0){
                    $have_discount = true;
                }
            }

            foreach ($payment_list as $k=>$payment){
                if ($payment['commission'] < 0 && $have_discount){ //Если есть скидка
                    unset($payment_list[$k]);
                }
            }
        }
        return array(
            'list' => $payment_list,
            'order' => $order,
            'user' => $data['user']
        );
    }

    /**
     * Получаем список оплат при оформлении заказа
     *
     * @param array $data - массив данных
     * @return array
     * @throws \RS\Exception
     */
    public static function checkoutDeliveryList($data)
    {
        /**
         * @var Delivery[] $payment_list
         * @var Order $order
         */
        $delivery_list = $data['list'];
        $order         = $data['order'];
        $config        = Loader::byModule('bonuses');

        //Посмотрим, если стоит опция скрывать список оплат если у заказа уже есть скидка
        if ($config['disable_delivery_with_discount'] && !empty($delivery_list)){
            $cartdata = $order->getCart()->getCartData(false, false);

            $have_discount = false; //Флаг если скидка применена
            if ($cartdata['total_discount'] > 0){
                $have_discount = true;
            }
            foreach ($cartdata['items'] as $uniq=>$item){
                if ($item['discount'] > 0){
                    $have_discount = true;
                }
            }

            foreach ($delivery_list as $k=>$delivery){

                if ($delivery['extrachange_discount'] < 0 && $have_discount){ //Если есть скидка
                    unset($delivery_list[$k]);
                }
            }
        }
        return array(
            'list' => $delivery_list,
            'order' => $order,
            'user' => $data['user']
        );
    }



    /**
     * Действия после заполнения цены в товаре для комплектации
     *
     * @param array $data - массив данных
     * @return array
     */
    public static function productGetOfferCost($data)
    {
        $xcost = $data['offer_xcost'];
        $offer = $data['offer'];
        $offer_key = $data['offer_key'];
        /**
         * @var Product $product
         */
        $product = $data['product'];
        $config = Loader::byModule('bonuses');

        if ($config['use_default_price_for_sale'] && Auth::isAuthorize()){
            $default_cost_id = CostApi::getDefaultCostId(); //Цена по умолчанию
            $old_price_id    = CostApi::getOldCostId(); //Старая цена
            $current_user    = Auth::getCurrentUser();
            $user_cost_id    = CostApi::getUserCost($current_user);

            //Получим зачеркнутую цену
            if (isset($xcost[$old_price_id])){
                $old_cost = $xcost[$old_price_id];
                if (!empty($offer['oneprice']['use'])){ //Если одна цена на всех
                    $old_cost = $product->getCost($old_price_id, 0, false);
                }
            }

            //Подменим цены, если старая цена есть и она больше 0, но только для публичной части
            if (isset($xcost[$old_price_id]) && $old_cost>0 &&
                ($user_cost_id != $default_cost_id) &&
                !Manager::obj()->isAdminZone()){

                $default_cost = $xcost[$default_cost_id];
                foreach($xcost as $cost_id=>$cost){
                    if ($cost_id != $default_cost_id && $cost_id != $old_price_id){
                        $xcost[$cost_id] = $default_cost;
                    }
                }

                return array(
                    'offer_xcost' => $xcost,
                    'offer' => $offer,
                    'offer_key' => $offer_key,
                    'product' => $product
                );
            }
        }

        return array(
            'offer_xcost' => $xcost,
            'offer' => $offer,
            'offer_key' => $offer_key,
            'product' => $product
        );
    }


    /**
     * Действия после заполнения цены в товаре
     *
     * @param array $data - массив данных
     */
    public static function productCalculateUserCost($data)
    {
        $xcost   = $data['xcost'];
        /**
         * @var Product $product
         */
        $product = $data['product'];
        $config = Loader::byModule('bonuses');


        if ($config['use_default_price_for_sale'] && Auth::isAuthorize()){
            $default_cost_id = CostApi::getDefaultCostId(); //Цена по умолчанию
            $old_price_id    = CostApi::getOldCostId(); //Старая цена
            $current_user    = Auth::getCurrentUser();
            $user_cost_id    = CostApi::getUserCost($current_user);

            //Подменим цены, если старая цена есть и она больше 0, но только для публичной части
            if (isset($xcost[$old_price_id]) && $xcost[$old_price_id]>0 &&
                ($user_cost_id != $default_cost_id) &&
                !Manager::obj()->isAdminZone()){

                $default_cost = $xcost[$default_cost_id];
                foreach($xcost as $cost_id=>$cost){
                    if ($cost_id != $default_cost_id && $cost_id != $old_price_id){
                        $xcost[$cost_id] = $default_cost;
                    }
                }

                $product['xcost'] = $xcost;
                $product['_current_cost_id'] = $default_cost_id; //Установим цену по умолчанию для текущего товара
            }
        }
    }


    /**
     * Действия после отработки контроллера, но до выдачи результата
     *
     * @param array $params - Массив параметров
     */
    public static function controllerAfterExecCatalogFrontProduct($params)
    {
        //Посчитаем бонусы для каждого товарного предложения, если такое имеется
        /**
         * @var Product $product
         */
        $product = Manager::getCurrentRoute()->getExtra(\Catalog\Controller\Front\Product::ROUTE_EXTRA_PRODUCT);
        $offer_bonuses = array();
        if ($product && $product->isOffersUse()){
            //Подготовим объект для передачи в браузер
            foreach ($product['offers']['items'] as $n=>$offer){
                $offer_bonuses[$n] = $product->getBonusesWithOrderRules($n);
            }
        }else{
            $offer_bonuses[0] = $product->getBonusesWithOrderRules(0);
        }

        //Добавим сведения по сопутствующим товарам
        $product_concomitans = array();
        $concomitants = $product->getConcomitant();
        if ($product && !empty($concomitants)){
            foreach ($concomitants as $concomitant){
                /**
                 * @var Product $concomitant
                 */
                $product_concomitans[$concomitant['id']] = $concomitant->getBonusesWithOrderRules();
            }
        }

        $data = array();
        if (!empty($offer_bonuses)){
            $data['offer_bonuses'] = $offer_bonuses;
        }
        if (!empty($product_concomitans)){
            $data['product_concomitans'] = $product_concomitans;
        }

        if (!empty($data)){
            Application::getInstance()->addJsVar('bonuses', $data);
        }
    }


    /**
     * Добавляет информацию о бонусах к массиву со сведениями о корзине
     *
     * @param array $cart_data - готовые данные корзины
     * @return array
     * @throws \RS\Exception
     */
    public static function addBonusesInfoToCart($cart_data)
    {
        $user = Auth::getCurrentUser();
        $cart_data['user_bonuses']  = $user->getUserBonuses(); //Получим количество бонусов пользователя

        $cart = Cart::currentCart();
        $api  = new BonusApi();
        
        if (!isset($cart->is_cartbonus_action)) { //Добавим информацию о бонусах в корзину
            $cart_data = $api->addBonusInfoToCartData($cart, $cart_data, $user);
        }

        return $cart_data;
    }


    /**
     * Подвешиваемся на получение корзины для мобильного приложения, чтобы добавить в ответ дополнительные поля для бонусов
     *
     * @param array $data - массив данных
     * @return array
     * @throws \RS\Exception
     */
    public static function apiCartUpdateSuccess($data)
    {
        $data['result']['response']['cartdata'] = self::addBonusesInfoToCart($data['result']['response']['cartdata']);
        return $data;
    }

    /**
     * Подвешиваемся на событие перед получением корзины для мобильного приложения
     *
     * @param array $data - массив данных
     * @return array
     */
    public static function apiCartGetCartDataBefore($data)
    {
        $custom = Request::commonInstance()->request('custom', TYPE_ARRAY, array()); //Дополнительная секция
        if (!empty($custom) && isset($custom['use_cart_bonuses'])){
            $use_cart_bonuses = $custom['use_cart_bonuses']; //Флаг того, что нужно активировать бонусы
            //Добавим сессию если нужно.
            if ($use_cart_bonuses){
                $_SESSION['use_cart_bonuses'] = 1;
            }elseif ($use_cart_bonuses==="0"){
                unset($_SESSION['use_cart_bonuses']);
            }
        }

        $data['result']['response']['cartdata']['use_cart_bonuses'] = (isset($_SESSION['use_cart_bonuses']) && $_SESSION['use_cart_bonuses']);
        return $data;
    }


    /**
     * Подвешиваемся на получение корзины для мобильного приложения, чтобы добавить в ответ дополнительные поля для бонусов
     *
     * @param array $data - массив данных
     * @return array
     * @throws \RS\Exception
     */
    public static function apiCartGetCartDataSuccess($data)
    {
        $data['result']['response']['cartdata'] = self::addBonusesInfoToCart($data['result']['response']['cartdata']);
        return $data;
    }

    /**
     * Подвешиваемся на изменение кол-ва тавара в мобильном приложении, чтобы добавить в ответ дополнительные поля для бонусов
     *
     * @param $data - массив данных
     * @return array
     * @throws \RS\Exception
     */
    public static function apiCartChangeAmountSuccess($data)
    {
        $data['result']['response']['cartdata'] = self::addBonusesInfoToCart($data['result']['response']['cartdata']);
        return $data;
    }

    /**
     * Подвешиваемся на удаление тавара из корзины в мобильном приложении, чтобы добавить в ответ дополнительные поля для бонусов
     *
     * @param $data - массив данных
     * @return array
     * @throws \RS\Exception
     */
    public static function apiCartRemoveSuccess($data)
    {
        $data['result']['response']['cartdata'] = self::addBonusesInfoToCart($data['result']['response']['cartdata']);
        return $data;
    }

    /**
     * Подвешиваемся на получение списка товаров для мобильного приложения,
     *
     * @param array $data - массив данных
     * @return array
     */
    public static function apiFavoriteGetListSuccess($data)
    {
        if (!empty($data['result']['response']['list'])){
            $bonuses_api  = new BonusApi();
            $data['result']['response']['list'] = $bonuses_api->getBonusesForMobileProductsList($data['result']['response']['list']);    
        }
        return $data;
    }

    /**
     * Подвешиваемся на получение списка рекоммендуемых товаров для мобильного приложения
     * Добавление конфигурации модуля в приложение
     * Добавление ссылки на API методы в приложение
     *
     * @param array $data - массив данных
     * @return array
     */
    public static function apiMobilesiteAppConfigSuccess($data)
    {
        if (!empty($data['result']['response']['additional_fields']) &&
            isset($data['result']['response']['additional_fields']['user_info']) &&
            Auth::isAuthorize())
        {
            $user = Auth::getCurrentUser();
            $user_cost_id = CostApi::getUserCost($user);

            $user_info = $data['result']['response']['additional_fields']['user_info'];

            if ($user_cost_id != CostApi::getDefaultCostId()){
                //Подгрузим цену и посмотрим со скидкой ли она
                $user_cost = new \Catalog\Model\Orm\Typecost($user_cost_id);
                if (($user_cost['val_znak'] == "-") && ($user_cost['type'] == $user_cost::TYPE_AUTO)){ //Если у него цена стоит с минусом
                    $user_info[] = array(
                        'title' => t('Накопительная'),
                        'value' => "-".$user_cost['val']." ".(($user_cost['val_type'] == 'sum') ? t('ед.') : "%")
                    );
                }
            }

            //Добавим сведения по бонусам
            $user_info[] = array(
                'title' => t('Бонусы'),
                'value' => $user->getUserBonuses()
            );

            $data['result']['response']['additional_fields']['user_info'] = $user_info;
        }
        if (isset($data['result']['response'])) {
            // Добавим настройки модуля в приложение
            $data['result']['response']['other_configs']['bonuses'] = \ExternalApi\Model\Utils ::extractOrm(Loader::byModule('bonuses'));

            // Добавим ссылки на API методы в приложение
            $router = \RS\Router\Manager::obj();
            $data['result']['response']['api_urls']['userGetBonusesUrl']
                = $router->getUrl('externalapi-front-apigate', ['method'=>'user.getBonuses']);
        }
        return $data;
    }

    /**
     * Подвешиваемся на получение списка рекоммендуемых товаров для мобильного приложения,
     *
     * @param array $data - массив данных
     * @return array
     */
    public static function apiProductGetRecommendedListSuccess($data)
    {
        if (!empty($data['result']['response']['list'])){
            $bonuses_api  = new BonusApi();
            $data['result']['response']['list'] = $bonuses_api->getBonusesForMobileProductsList($data['result']['response']['list']);
        }
        return $data;
    }

    /**
     * Подвешиваемся на получение списка товаров для мобильного приложения,
     *
     * @param array $data - массив данных
     * @return array
     */
    public static function apiProductGetListSuccess($data)
    {
        if (!empty($data['result']['response']['list'])){
            $bonuses_api  = new BonusApi();
            $data['result']['response']['list'] = $bonuses_api->getBonusesForMobileProductsList($data['result']['response']['list']);    
        }
        return $data;
    }

    /**
     * Подвешиваемся на получение товара для мобильного приложения
     *
     * @param array $data - массив данных
     * @return array
     */
    public static function apiProductGetSuccess($data)
    {
        $product_data = $data['result']['response']['product'];
        $product = new Product($product_data['id']);
        $bonuses = 0;
        $order_bonuses = 0;
        if ($product->bonusesCanBeShown()){
            $bonuses = $product->getBonuses();
            $order_bonuses = $product->getBonusesWithOrderRules();
        }
        if (isset($data['result']['response']['product']['offers_list_info']['response']['offers'])) {
            foreach ($data['result']['response']['product']['offers_list_info']['response']['offers'] as $key => $offer) {
                $offer_bonuses = $product->getBonusesWithOrderRules($offer['id']);
                if ($offer_bonuses) {
                    $data['result']['response']['product']['offers_list_info']['response']['offers'][$key]['order_bonuses'] = $offer_bonuses;
                }
            }
        }
        $data['result']['response']['product']['bonuses'] = $bonuses;
        $data['result']['response']['product']['order_bonuses'] = $order_bonuses;
        return $data;
    }
    
    /**
    * Добавляем колонку с бонусами пользователя 
    * 
    * 
    * @param CrudCollection $helper - объект помошника
    * @param Event $event - событие
    */
    public static function controllerExecUsersAdminCtrlIndex($helper, $event)
    {
        /**
        * @var Element $table
        */
        $table       = $helper['table']->getTable();
        $columns     = $table->getColumns();

        /**
         * @var Actions $last_column
         */
        $last_column = array_pop($columns);
        
        $router = Manager::obj();
        //Добавим колонку с бонусом
        $columns[] = new Text('bonuses', t('Бонусы'), array('Sortable' => SORTABLE_BOTH, 'href' => $router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit') ));

        //Добавим действия с бонусами
        foreach ($table->getColumns() as $column) {
            if ($column instanceof Actions){
                $column->addAction(new Action($router->getAdminPattern('add', array(':id' => '~field~', 'writeoff' => 0), 'bonuses-bonushistoryctrl'), t('списать бонусы'), array('class' => 'decbalance crud-add zmdi zmdi-layers-off')), 0);
                $column->addAction(new Action($router->getAdminPattern('add', array(':id' => '~field~', 'writeoff' => 1), 'bonuses-bonushistoryctrl'), t('пополнить бонусы'), array('class' => 'incbalance crud-add zmdi zmdi-layers')), 0);
            }
        }

        //Добавим доп действия в выпадающий список
        if ($last_column  instanceof Actions){
            $actions = $last_column->getActions();
            /**
             * @var DropDown $last_action
             */
            $last_action = array_pop($actions);
            if ($last_action  instanceof DropDown){
                $last_action->addItem(array(
                    'title' => t('добавить дисконтную карту'),
                    'attr' => array(
                        'target' => '_blank',
                        '@href' => $router->getAdminPattern('add', array(':user_id' => '~field~'), 'bonuses-bonuscardctrl')
                    )
                ));
            }
        }

        $columns[] = $last_column;
        $table->setColumns($columns);
    }

    /**
     * Добавляем колонки для дополнительного поиска по списку и колонку
     *
     * @param CrudCollection $helper - объект помошника
     * @param Event $event - событие
     */
    public static function controllerExecShopAdminDiscountCtrlIndex($helper, $event)
    {
        /**
        * @var Element $table
        */
        $table       = $helper['table']->getTable();
        $columns     = $table->getColumns();
        $last_column = array_pop($columns);
        //Добавим колонку с бонусом
        $columns[]   = new StrYesno('is_bonus_discount', t('Из бонусов?'), array('Sortable' => SORTABLE_BOTH, 'href' => Manager::obj()->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit') ));
        $columns[]   = $last_column;
        
        $table->setColumns($columns);
        
        /**
        * @var Container $container
        */
        $container = $helper['filter']->getContainer();
        $container->addLine(new Line(array(
            'Items' => array(
              new \RS\Html\Filter\Type\User('bonus_user_id', t('Пользователь бонусов')),
              new Select('is_bonus_discount', t('Из бонусов?'), array(
                    '' => '-не выбрано-',
                    1 => 'Да',
                    0 => 'Нет'
              ))
            )
        )));
        
        $container->cleanItemsCache();
    }
    
    /**
    * Получает маршруты в системе
    * 
    * @param mixed $routes
    * @return Route[]
    */
    public static function getRoute($routes) 
    {
        //Моя история бонусов
        $routes[] = new Route('bonuses-front-bonushistory', array('/my/bonushistory/'), null, t('Моя история бонусов'));
        //История запросов на вывод
        $routes[] = new Route('bonuses-front-cashouthistory', array('/my/cashouthistory/'), null, t('История запросов на вывод'));
        //Моя бонусная карта
        $routes[] = new Route('bonuses-front-bonuscard', array('/my/bonuscard/'), null, t('Моя бонусная карта'));
        //Доп функции по бонусам
        $routes[] = new Route('bonuses-front-cartbonuses', array('/my/cartbonuses/'), null, t('Доп. функции по бонусам'), true);
        //Регистрация партнера для бонусных карт
        $routes[] = new Route('bonuses-front-bonuscardpartnerregister', array('/bonuscardpartnerregister/'), null, t('Регистрация партнера бонусных карт'));
        //Бонусные карты партнера бонусных карт
        $routes[] = new Route('bonuses-front-bonuspartnercards', array(
            '/my/bonuspartnercards/',
            '/my/bonuspartnercards/{Act:(add)}/',
        ), null, t('Бонусные карты партнера бонусных карт'));
        return $routes;
    }

    /**
     * Обработаем добавление скидки на весь заказ
     *
     * @param array $data - массив с данными
     * @return array
     * @throws \RS\Exception
     */
    public static function cartChange($data)
    {
        /**
        * @var Cart $cart
        */
        $cart        = $data['cart'];
        $cart_result = $cart->getCartData(false, false);
        $api         = new BonusApi();

        $shop_config = Loader::byModule('shop');
        $config      = Loader::byModule('bonuses');
        $discount_product_api = new BonusProductDiscountApi();

        $_SESSION['product_applied_bonuses'] = 0;
        if (
            $config['disable_use_product_by_action_in_cart'] ||
            !empty($config['apply_only_for_brands']) ||
            $shop_config['old_cost_delta_as_discount'] ||
            $discount_product_api->checkProductExistsWithMaxDiscount($cart)
        ){
            $use_cart_bonuses = Request::commonInstance()->request('use_cart_bonuses', TYPE_STRING, false); //Флаг того, что нужно активировать бонусы

            //Добавим сессию если нужно.
            if ($use_cart_bonuses){
                $_SESSION['use_cart_bonuses'] = 1;
            }elseif ($use_cart_bonuses === "0"){
                unset($_SESSION['use_cart_bonuses']);
                $discount_product_api->removeDiscountForEachProduct($cart);
            }

            if ($use_cart_bonuses || (isset($_SESSION['use_cart_bonuses']) && $_SESSION['use_cart_bonuses'])) {
                if (!isset($cart_result['errors']) || empty($cart_result['errors'])){
                    if (!$api->checkUserNotInRightGroupForDiscount()){
                        $user = Auth::getCurrentUser();
                        $api->convertBonusesToPersonalProductDiscounts($user->getUserBonuses(), $cart, $cart_result);
                    }
                }
            }
        }
    }

    /**
     * Обработаем добавление скидки на весь заказ
     *
     * @param array $data - массив с данными
     * @return array
     * @throws \RS\Exception
     */
    public static function cartBeforeAddOrderData($data)
    {
        /**
         * @var Cart $cart
         */
        $cart        = $data['cart'];
        $cart_result = $data['cart_result'];
        $api         = new BonusApi();

        //Если действиет опция только скидка распространения на весь заказ
        $shop_config = Loader::byModule('shop');
        $config = Loader::byModule('bonuses');
        $discount_product_api = new BonusProductDiscountApi();

        $use_cart_bonuses = Request::commonInstance()->request('use_cart_bonuses', TYPE_STRING, false); //Флаг того, что нужно активировать бонусы
        //Добавим сессию если нужно.
        if ($use_cart_bonuses){
            $_SESSION['use_cart_bonuses'] = 1;
        }elseif ($use_cart_bonuses === "0"){
            unset($_SESSION['use_cart_bonuses']);
        }

        if (
            !isset($cart->is_cartbonus_action) &&
            ($use_cart_bonuses || !empty($_SESSION['use_cart_bonuses']))
        ) {
            $cart->is_cartbonus_action = true; //Защита от дублирования
            $cart_result = $api->checkConvertBonuses($cart, $cart_result);

            if (
                !(
                    $config['disable_use_product_by_action_in_cart'] ||
                    !empty($config['apply_only_for_brands']) ||
                    $shop_config['old_cost_delta_as_discount'] ||
                    $discount_product_api->checkProductExistsWithMaxDiscount($cart)
                )
            ){
                if (!isset($cart_result['errors']) || empty($cart_result['errors'])){
                    $user = Auth::getCurrentUser();
                    $cart_result = $api->convertBonusesToOrderDiscount($user->getUserBonuses(), $cart, $cart_result);
                }
            }
            unset($cart->is_cartbonus_action);
        }else{
            $cart_result = $api->checkDisableUseProductOptionAndUseOldCostOption($cart_result);
            if (!empty($cart_result['errors'])){
                $cart_result['errors'] = array_unique($cart_result['errors']);
            }
        }

        //Запишем в данные сколько бонусов было использовано
        if (isset($_SESSION['product_applied_bonuses']) && !empty($_SESSION['product_applied_bonuses'])){
            $cart_result['order_bonuses_for_discount'] = $_SESSION['product_applied_bonuses'];
        }

        //Флаг, что можно использовать купон совместно общей скидкой
        $cart_result['order_discount_can_use_coupon'] = $config['can_use_coupon'];

        return array(
            'cart' => $cart,
            'cart_result' => $cart_result,
        );
    }
    
    /**
    * Добавляет сведения по бонусам в корзину
    *
    * @param array $data - массив с данными
    * @return array
     * @throws \RS\Exception
    */
    public static function cartGetCartData($data)
    {
        /**
        * @var Cart $cart
        */
        $cart        = $data['cart'];
        $cart_result = $data['cart_result'];
        $api         = new BonusApi();
        $user =
            $cart->getOrder()
                ? $cart->getOrder()->getUser()
                : Auth::getCurrentUser();
        
        if (!isset($cart->is_cartbonus_action)) { //Добавим информацию о бонусах в корзину
            $cart_result = $api->addBonusInfoToCartData($cart, $cart_result, $user);
        }
        
        return array(
            'cart' => $cart, 
            'cart_result' => $cart_result, 
        );
    }


    /**
     * Обновление списка товаров в заказе
     *
     * @param array $params - массив параметров
     * @throws \RS\Exception
     */
    public static function cartUpdateOrderItemsAfter($params)
    {
        /**
         * @var Cart $cart
         */
        $cart  = $params['cart'];
        $order = $cart->getOrder();
        if (!isset($cart->is_cartbonus_action)) {
            $cart->is_cartbonus_action = true; //Защита от дублирования
            $bonuses_api = new BonusApi();
            if ($order['apply_bonusrules'] && $order['apply_bonusrules_amount']){
                $cart_data = $bonuses_api->convertBonusesToOrderDiscount($order['apply_bonusrules_amount'], $cart);
            }else{
                $cart_data = $bonuses_api->discount_product_api->removeBeforeSettedDiscount($cart);
            }

            unset($cart->is_cartbonus_action);
        }
    }


    
    /**
    * Действия до записи пользователей
    * 
    * @param array $data - массив с данными
    */
    public static function ormBeforeWriteUsersUser($data)
    {
        $flag = $data['flag'];
        /**
        * @var User $user
        */
        $user = $data['orm'];
        
        //Сделаем так, чтобы лишьних записей не установленных небыло
        if ($user['birthday']=='0000-00-00'){
            $user['birthday'] = null;
        }
    }

    /**
     * Действия перед записью заказа
     *
     * @param array $data - массив данных
     * @throws \RS\Db\Exception
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    public static function ormBeforeWriteShopOrder($data)
    {
        $flag = $data['flag'];
        /**
        * @var Order $order
        */
        $order = $data['orm'];

        $config = Loader::byModule('bonuses');

        if ($flag == $order::INSERT_FLAG){ //Если идет вставка
            if (isset($_SESSION['use_cart_bonuses']) && $_SESSION['use_cart_bonuses']){
                $order['apply_bonusrules'] = 1;
                //Запишем данные по корзине
                if ($config['disable_use_product_by_action_in_cart']){
                    $bonus_discount = $order->getBonusesFromOrderDiscountForEachProduct();
                }else{
                    $cart_data = $order->getCart()->getCartData();
                    $bonus_discount = $cart_data['order_bonuses_for_discount'];
                }

                $order['apply_bonusrules_amount'] = $bonus_discount;
            }
        }

        $api = new BonusApi();
        //Проверим бонусы для списания
        if (!$api::haveBonusesApplyedToOrder($order)){
            if ($config['disable_use_product_by_action_in_cart']){
                $bonuses_to_apply = $order->getBonusesFromOrderDiscountForEachProduct();
            }else{
                $bonuses_to_apply = $order->getBonusesFromOrderDiscount();
            }
                    
            $user_bonuses = $order->getUser()->getUserBonuses(); 
            if ($bonuses_to_apply > $user_bonuses){
                $order->addError(t('Недостаточно бонусов для списания. Надо ещё %0', array($bonuses_to_apply-$user_bonuses)));   
            }   
        }

        // Если заказ отменён то удалим сведения о скидке на заказ
        if (in_array($order->getStatus()->id, UserStatusApi::getStatusesIdByType(UserStatus::STATUS_CANCELLED)) && $api->haveBonusesApplyedToOrder($order)) {
            $cart = $order->getCart();
            $order_discounts = $cart->getCartItemsByType(Cart::TYPE_ORDER_DISCOUNT);
            if (!empty($order_discounts)){
                foreach ($order_discounts as $uniq=>$order_discount){
                    $cart->removeItem($uniq);
                }
                $cart->cleanCache();
            }
        }
    }

    /**
     * Действия после записи комментария
     *
     * @param array $data - массив с данных
     * @throws \RS\Db\Exception
     */
    public static function ormAfterWriteCommentsComment($data)
    {
        /**
         * @var Comment $comment
         */
        $comment = $data['orm'];
        $flag = $data['flag'];

        $config = Loader::byModule('bonuses');
        $config_comment = Loader::byModule('comments');
        $bonusCommentApi = new BonusCommentApi();
        if (
            $config['add_bonus_for_comment_product'] &&
            $comment['type'] == '\Catalog\Model\CommentType\Product' &&
            Auth::isAuthorize() &&
            !$bonusCommentApi->isCommentBonusesApplied($comment)
        ){
            if ($config_comment['need_moderate'] && !$comment['moderated']){ //Если модерация не прошла
                return;
            }
            $product = new Product($comment['aid']);
            if ($product['id']){
                $bonusApi = new BonusApi();
                $text = t('Начисление бонусов за оставленный комментарий к товару %0', $product['title']);

                $comment_user = new User($comment['user_id']);
                //Проверяем есть ли товар в выполненом заказе, если нужно
                $need_add = !($config['add_for_comment_for_buyed_product']) || $bonusCommentApi->isProductExistInSuccessOrderForUser($product, $comment_user);

                //Проверяем, что только первый раз начислили
                if (
                    $need_add &&
                    $config['add_for_comment_only_once'] &&
                    $bonusCommentApi->isAddedBonusesForProductComment($product, $comment_user)
                ) {
                    $need_add = false;
                }
                if ($need_add && $comment_user['id']){
                    $bonusApi->addBonusesTransaction($comment_user, $config['add_bonus_for_comment_product'], $text, true, false);
                    $commentToProduct = new AddCommentBonusesToProduct();
                    $commentToProduct['comment_id'] = $comment['id'];
                    $commentToProduct['product_id'] = $product['id'];
                    $commentToProduct['user_id'] = $comment_user['id'];
                    $commentToProduct->insert();
                }
            }
        }
    }

    /**
     * Действия после записи заказа
     *
     * @param array $data - массив с данных
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public static function ormAfterWriteShopOrder($data)
    {
        $flag = $data['flag'];
        /**
        * @var Order $order
        */
        $order   = $data['orm'];
        $user    = $order->getUser();

        if ($user['id']){ //Только у тех у кого есть назначенный пользователь
            $bonuses = $order->getBonuses(true);
            $config  = Loader::byModule('bonuses');

            $api = new BonusApi();
            //Спишем бонусы если мы первоначально не списывали
            //var_dump($api::haveBonusesApplyedToOrder($order));
            if (!$api::haveBonusesApplyedToOrder($order)){
                if ($config['disable_use_product_by_action_in_cart']){
                    $bonuses_to_apply = $order->getBonusesFromOrderDiscountForEachProduct();
                }else{
                    $bonuses_to_apply = $order->getBonusesFromOrderDiscount();
                }

                //Если мы запретили начисление бонусов за заказ к которому уже применили
                if (
                    $config['disable_add_bonuses_if_already_have'] &&
                    $bonuses_to_apply > 0
                ){
                    $bonuses = 0;
                    $bonuses_to_apply = 0;
                    if (!$api->checkOrderBonusAddBefore($order['id'])){
                        $api->addOrderBonusInfoToHistory($order['id'], 0);
                    }
                }

                if ($bonuses_to_apply){
                    $api->addBonusesTransaction($user, $bonuses_to_apply, t('Списание бонусов за оформленный заказ №%0', $order['order_num']), false);
                    //Добавим запись о том, что применили бонусы и списали их
                    $bonuses_used_to_order = new BonusesUsedToOrder();
                    $bonuses_used_to_order['user_id']  = $user['id'];
                    $bonuses_used_to_order['order_id'] = $order['id'];
                    $bonuses_used_to_order['bonuses']  = $bonuses_to_apply;
                    $bonuses_used_to_order->insert();
                    $api::$order_applyed_bonuses = array(); //Обнулим кэш

                    unset($_SESSION['use_cart_bonuses']);
                }
            }

            //Посмотрим какой статус у заказа, и если что добавим бонусов
            if ($bonuses && ($order->getStatus()->id == $config['bonuses_for_order_status']) && (!$api->checkOrderBonusAddBefore($order['id']))){

                $api->addBonusesTransaction($user, $bonuses, t('Начисление бонусов за оформленный заказ №%0', $order['order_num']));
                $api->addOrderBonusInfoToHistory($order['id'], $bonuses);
            }

            //Если есть бонусные карты, то проверим нужно ли начислять за покупки партнеру
            if (($order->getStatus()->id == $config['bonuses_for_order_status']) &&
                !$order->isHadAddedBonusesToBonusCardPartner() &&
                $user->isHaveBonusCard()){
                $cards = $user->getBonusCards();
                /**
                 * @var BonusCard $card
                 */
                $card = reset($cards);
                if ($card['partner_id']){
                    $bonuscardapi = new BonusCardApi();
                    $bonuscardapi->addBonusesToPartnerForOrder($card, $order);
                }
            }

            //Если статус отменён и были потрачены бонусы, то вернём его не место
            if (in_array($order->getStatus()->id, UserStatusApi::getStatusesIdByType(UserStatus::STATUS_CANCELLED)) && $api->haveBonusesApplyedToOrder($order)){
                //Удалим скидки на заказ
                /**
                 * @var OrderItem $order_discount
                 */
                \RS\Orm\Request::make()
                    ->delete()
                    ->from(new OrderItem())
                    ->where(array(
                        'order_id' => $order['id'],
                        'type' => Cart::TYPE_ORDER_DISCOUNT,
                    ))->exec();

                //Вернём бонусы человеку
                $api->backBonusesAppledToOrder($order);

                if ($user->isHaveBonusCard()){
                    $cards = $user->getBonusCards();
                    /**
                     * @var BonusCard $card
                     */
                    $card = reset($cards);
                    if ($card['partner_id']) {
                        $partner = $card->getPartner();
                        $bonuscardapi = new BonusCardApi();
                        $bonuscardapi->backBonusesAppliedToPartnerByOrder($order, $partner);
                    }
                }
            }

            //Проверим, есть ли правила для дисконтной программы и установлен нужный статус
            if (!empty($config['discount_rule_arr'])){
                $discount_api = new BonusDiscountApi();
                $order_status = new UserStatus($config['discount_order_status']);

                if (in_array($config['discount_order_status'], (array)UserStatusApi::getStatusesIdByType($order_status['type']))){
                    $discount_api->applyDiscountRule($order);
                }
            }
            
        }
    }

    /**
     * Действия перед удалением заказа
     *
     * @param array $data - массив с данными
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public static function ormDeleteShopOrder($data)
    {
        /**
        * @var Order $order
        */
        $order = $data['orm'];
        
        $api = new BonusApi();
        //Вернём бонусы, если они были использованы
        if ($api->haveBonusesApplyedToOrder($order)){
            $api->backBonusesAppledToOrder($order);
        }
    }


    /**
     * Действия после записи подписки
     *
     * @param array $data - массив с данными
     * @throws \RS\Exception
     */
    public static function ormAfterWriteEmailSubscribeEmail($data)
    {
        $flag = $data['flag'];
        /**
         * @var Email $email
         */
        $email = $data['orm'];
        $config = \RS\Config\Loader::byModule('bonuses');
        if ($email['confirm'] && !empty($config['bonuses_for_subscribe'])){ //Если подписка подтверждена
            /**
             * @var User $user
             */
            $user = \RS\Orm\Request::make()
                            ->from(new User())
                            ->where([
                                'e_mail' => $email['email']
                            ])->object();

            if ($user['id']){
                $bonusApi = new BonusApi();
                $bonusApi->addBonusesTransaction($user, $config['bonuses_for_subscribe'], t('Начисление бонусов за подписку на рассылку по E-mail'));
            }
        }
    }


    /**
     * Действия после записи пользователей
     *
     * @param array $data - массив с данными
     */
    public static function ormAfterWriteCatalogDir($data)
    {
        $flag = $data['flag'];
        /**
        * @var Dir $dir
        */
        $dir = $data['orm'];
        
        if ($dir->isModified('bonuses_units')){
            \RS\Cache\Manager::obj()->invalidateByTags(CatalogProduct::CACHE_DIRS_INFO);
        }
        
        if (!$dir['id']){ //Если это категория все, то сохраним значения в конфиге
            $config = Loader::byModule('bonuses');
            $config['default_dir_bonuses_units']      = $dir['bonuses_units'];
            $config['default_dir_bonuses_units_type'] = $dir['bonuses_units_type'];
            $config->update();
        }
    }

    /**
     * Действия после записи пользователей
     *
     * @param array $data - массив с данными
     * @throws \RS\Exception
     */
    public static function ormAfterWriteUsersUser($data)
    {
        $flag = $data['flag'];
        /**
        * @var User $user
        */
        $user = $data['orm'];
        
        if ($flag == $user::INSERT_FLAG){ //Если пользователь только создаётся
            $config = Loader::byModule('bonuses'); //Получим конфиг

            //Если указана бонусная карта, то привяжем её к пользователю
            if (isset($_POST['bonus_card']) && !empty($_POST['bonus_card'])){
                $bonus_card_number = htmlspecialchars(trim(str_replace(' ', '', $_POST['bonus_card'])));
                $card = \RS\Orm\Request::make()
                    ->from(new BonusCard())
                    ->where(array(
                        'card_id' => $bonus_card_number
                    ))->object();

                $card['user_id'] = $user['id'];
                $card['active']  = 1;
                $card->update();
            }

        
            //Если нужно давать бонусы при регистрации
            if ($config['bonuses_for_register']){
               $api = new BonusApi();
               $api->addBonusesTransaction($user, $config['bonuses_for_register'], $api::ADD_TEXT_REGISTER); 
            }    
            
            //Если в дисконтной программе указано, что переключать цену при регистрации
            if ($config['discount_register_price_id']){ //Если установлена цена
                $user['user_cost'] = array(
                    \RS\Site\Manager::getSiteId() => $config['discount_register_price_id']
                );
                $user->update();
            }
        }    
    }

    /**
     * Удаление типа цены
     *
     * @param array $params - массив параметров
     * @throws Exception
     */
    public static function ormDeleteCatalogTypeCost($params)
    {
        /**
         * @var Typecost $typecost
         */
        $typecost = $params['orm'];

        $site_config = Loader::getSiteConfig(1);
        $admin_email = $site_config->admin_email;

        if (!empty($admin_email)){
            $phpMailer = new Mailer();
            $phpMailer->Subject = t('Вы удалили тип цены на сайте. Проверьте бонусную программу.');
            $phpMailer->addAddress($admin_email);
            $phpMailer->isHTML(true);
            $phpMailer->CharSet = 'utf-8';
            $phpMailer->Body = t('
                <p>Здраствуйте.</p> 
                <p>Вы только что, удалили тип цены %0 в своём интернет магазине. Пожалуйста, проверьте настройки бонусной программы в 
                Веб-сайт->Настройка модулей->Бонусная программа.</p>
                <p>С уважением, Ваш разработчик бонусной программы.</p>
            ', [$typecost['title']]);

            $phpMailer->send();
        }
    }


    /**
    * Расширяем объект скидки
    * 
    * @param Discount $discount - объект скидки
    */
    public static function ormInitShopDiscount(Discount $discount)
    {
        $discount->getPropertyIterator()->append(array(
            t('Начисление бонусов и скидок'),
                'is_bonus_discount' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Скидка сделанная из бонусов?'),
                    'checkboxview' => array(1,0),
                    'default' => 0,
                )),
                'bonus_user_id' => new Type\Integer(array(
                    'maxLength' => 11,
                    'description' => t('Пользователь, которому была присвоена скидка'),
                    'template' => '%bonuses%/form/discount/user.tpl',
                    'default' => 0,
                ))
        ));
    }

    /**
     * Расширяем объект оплата
     *
     * @param Payment $payment - объект скидки
     */
    public static function ormInitShopPayment(Payment $payment)
    {
        $payment->getPropertyIterator()->append(array(
            t('Начисление бонусов и скидок'),
                'bonuses' => new Type\Integer(array(
                    'maxLength' => 11,
                    'description' => t('Количество бонусов, которое начислить'),
                    'hint' => t('Будет начислено, после того как заказ будет выполнен и закрыт'),
                    'default' => 0,
                )),
        ));
    }

    /**
     * Расширяем объект доставки
     *
     * @param Delivery $delivery - объект скидки
     */
    public static function ormInitShopDelivery(Delivery $delivery)
    {
        $delivery->getPropertyIterator()->append(array(
            t('Начисление бонусов и скидок'),
                'bonuses' => new Type\Integer(array(
                    'maxLength' => 11,
                    'description' => t('Количество бонусов, которое начислить'),
                    'hint' => t('Будет начислено, после того как заказ будет выполнен и закрыт'),
                    'default' => 0,
                )),
        ));
    }

    /**
     * Расширяем объект подписки на E-mail
     *
     * @param Email $email - объект E-mail
     */
    public static function ormInitEmailSubscribeEmail(Email $email)
    {
        $email->getPropertyIterator()->append(array(
            t('Бонусы'),
                'bonuses_added' => new Type\Integer(array(
                    'description' => t('Бонусы за подписку начислены?'),
                    'maxLength' => 1,
                    'checkboxview' => [1, 0],
                    'default' => 0,
                )),
        ));
    }

    /**
     * Расширяем объект пользователя
     *
     * @param UserGroup $user_group - объект группы пользователя
     */
    public static function ormInitUsersUserGroup(UserGroup $user_group)
    {
        $user_group->getPropertyIterator()->append(array(
            t('Бонусные карты'),
                'bonuscard_cashback_for_activation' => new Type\Integer(array(
                    'description' => t('Размер начислений партнеру за активированную карту'),
                    'hint' => t('Количество бонусов. 0 - ничего не начислять'),
                    'maxLength' => 8,
                    'attr' => array(array(
                        'size' => 8
                    ))
                )),
                'bonuscard_cashback' => new Type\Integer(array(
                    'description' => t('Размер начислений партнеру за покупки'),
                    'hint' => t('Покупки с привлеченных через активации бонусных карт. 0 - ничего не начислять'),
                    'maxLength' => 11,
                    'attr' => array(array(
                        'size' => 8
                    )),
                    'default' => 0,
                    'template' => '%bonuses%/form/programm/bonuscard_cashback.tpl',
                )),
                'bonuscard_cashback_type' => new Type\Integer(array(
                    'description' => t('Тип использования начислений'),
                    'maxLength' => 1,
                    'default' => 0,
                    'listFromArray' => array(array(
                        'ед.',
                        '%',
                    )),
                    'visible' => false
                ))
        ));
    }
    
    /**
    * Расширяем объект пользователя
    * 
    * @param User $user - объект пользователя
    */
    public static function ormInitUsersUser(User $user)
    {
        $user->getPropertyIterator()->append(array(
            t('Основные'),
                'birthday' => new Type\Date(array(
                    'description' => t('Дата рождения'),
                    'default' => null,
                )),
                'bonus_card' => new Type\Varchar(array(
                    'description' => t('Бонусная карта'),
                    'maxLength' => 32,
                    'attr' => array(array(
                        'size' => 38
                    )),
                    'Checker' => array(array(__CLASS__, 'checkBonusCard'), ''),
                    'runtime' => true,
                    'visible' => false,
                )),
            t('Бонусы'),
                'bonuses' => new Type\Integer(array(
                    'description' => t('Количество бонусов'),
                    'allowempty' => false,
                    'visible' => false
                )),
                'orders_summ_before' => new Type\Decimal(array(
                    'description' => t('Сумма за заказы которые были оплачены в прошлой системе'),
                    'hint' => t('Используется для корректировки начисления накопительной системы'),
                    'default' => 0
                )),
            t('Бонусные карты'),
                'is_bonuscard_partner' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Является партнером дисконтных карт?'),
                    'hint' => t('Привлекает людей раздавая дисконтные карты'),
                    'CheckBoxView' => array(1, 0),
                    'index' => true,
                    'default' => 0
                )),
                'bonuscard_cashback_for_activation' => new Type\Integer(array(
                    'description' => t('Размер начислений партнеру за активированную карту'),
                    'hint' => t('Количество бонусов. 0 - ничего не начислять'),
                    'maxLength' => 8,
                    'attr' => array(array(
                        'size' => 8
                    ))
                )),
                'bonuscard_cashback' => new Type\Integer(array(
                    'description' => t('Размер начислений партнеру за покупки'),
                    'hint' => t('Покупки с привлеченных через активации бонусных карт. 0 - ничего не начислять'),
                    'maxLength' => 11,
                    'attr' => array(array(
                        'size' => 8
                    )),
                    'default' => 0,
                    'template' => '%bonuses%/form/programm/bonuscard_cashback.tpl',
                )),
                'bonuscard_cashback_type' => new Type\Integer(array(
                    'description' => t('Тип использования начислений'),
                    'maxLength' => 1,
                    'default' => 0,
                    'listFromArray' => array(array(
                        'ед.',
                        '%',
                    )),
                    'visible' => false
                ))
        ));
    }
    
    /**
    * Расширяем объект категории товаров
    * 
    * @param Dir $dir - объект пользователя
    */
    public static function ormInitCatalogDir(Dir $dir)
    {
        $dir->getPropertyIterator()->append(array(
            t('Начисление бонусов и скидок'),
                'bonuses_units' => new Type\Integer(array(
                    'description' => t('Количество бонусов'),
                    'maxLength' => 11,
                    'template' => '%bonuses%/form/dir/bonuses_units.tpl',
                    'metemplate' => '%bonuses%/form/dir/me_bonuses_units.tpl',
                    'default' => 0
                )),
                'bonuses_units_type' => new Type\Integer(array(
                    'description' => t('Тип начисления бонусов'),
                    'maxLength' => 1,
                    'listFromArray' => array(array(
                        '0' => 'ед.',
                        '1' => 'в % от цены товара',
                    )),
                    'default' => 0,
                    'visible' => false,
                    'mevisible' => true
                )),
        ));
    }


    /**
     * Расширяем объект заказа
     *
     * @param Order $order - объект заказа
     */
    public static function ormInitShopOrder(Order $order)
    {
        $config = Loader::byModule('bonuses');

        $order->getPropertyIterator()->append(array(
            t('Основные'),
                'apply_bonusrules' => new Type\Integer(array(
                    'description' => t('Применить "Правила бонусной системы"'),
                    'hint' => t('Только если включена опция -<br/> Запретить перевод бонусов за товар в скидку в корзине по акции?'),
                    'CheckboxView' => array(1, 0),
                    'default' => 0,
                    'userVisible' => $config['disable_use_product_by_action_in_cart'],
                    'meVisible' => false,
                )),
                'apply_bonusrules_amount' => new Type\Integer(array(
                    'description' => t('Количество примененных бонусов пользователя'),
                    'hint' => t('Только если включена опция -<br/> Запретить перевод бонусов за товар в скидку в корзине по акции?'),
                    'default' => 0,
                    'userVisible' => $config['disable_use_product_by_action_in_cart'],
                    'meVisible' => false,
                )),
        ));
    }
    
    /**
    * Расширяем объект товара
    * 
    * @param Product $product - объект пользователя
    */
    public static function ormInitCatalogProduct(Product $product)
    {
        $product->getPropertyIterator()->append(array(
            t('Начисление бонусов и скидок'),
                'bonuses_units' => new Type\Integer(array(
                    'description' => t('Количество бонусов'),
                    'maxLength' => 11,
                    'template' => '%bonuses%/form/product/bonuses_units.tpl',
                    'metemplate' => '%bonuses%/form/product/me_bonuses_units.tpl',
                    'default' => 0
                )),
                'bonuses_units_type' => new Type\Integer(array(
                    'description' => t('Тип начисления бонусов'),
                    'maxLength' => 1,
                    'listFromArray' => array(array(
                        'ед.',
                        'в % от цены товара',
                    )),
                    'default' => 0,
                    'visible' => false
                )),
                'discount_ignore' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Не учитывать в дисконтной программе?'),
                    'checkboxview' => [1,0],
                    'default' => 0,
                )),
                'bonuses_ignore' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Не участвовать в бонусной программе?'),
                    'hint' => t('За данный товар не будут начисляться бонусы и невозможно к данному товару применить бонусную программу для скидки'),
                    'checkboxview' => [1,0],
                    'default' => 0,
                )),
                'max_discount_percent' => new Type\Integer(array(
                    'maxLength' => 11,
                    'description' => t('Максимальный процент скидки на этот товар в корзине?'),
                    'hint' => t('Максимальный процент от цены товара, которую можно скинуть при применении бонусной программы'),
                    'default' => 0,
                ))
        ));
    }
    
    
    /**
    * Возвращает пункты меню этого модуля в виде массива
    * 
    */
    public static function getMenus($items)
    {
        $items[] = array(
                'title' => t('Бонусы'),
                'alias' => 'bonus',
                'link' => '%ADMINPATH%/bonuses-bonuscardctrl/',
                'sortn' => 10,
                'typelink' => 'link',
                'parent' => 'modules'
            );     
        $items[] = array(
                'title' => t('Бонусные карты'),
                'alias' => 'bonuscards',
                'link' => '%ADMINPATH%/bonuses-bonuscardctrl/',
                'sortn' => 0,
                'typelink' => 'link',
                'parent' => 'bonus'
            );     
        $items[] = array(
                'title' => t('История начислений и списаний'),
                'alias' => 'bonushistory',
                'link' => '%ADMINPATH%/bonuses-bonushistoryctrl/',
                'sortn' => 1,
                'typelink' => 'link',
                'parent' => 'bonus'
            );
        $items[] = array(
            'title' => t('Запросы на вывод средств'),
            'alias' => 'cashout',
            'link' => '%ADMINPATH%/bonuses-cashoutctrl/',
            'sortn' => 2,
            'typelink' => 'link',
            'parent' => 'bonus'
        );

        return $items;
    }

    /**
     * Действия после экспортированного заказа для 1С
     *
     * @param Order $order - заказ
     * @param \SimpleXMLElement $sxml - объект xml
     * @return bool(true) | string возвращает true в случае успеха, иначе текст ошибки
     */
    public static function exchangeOrderExportAfter($order, $sxml)
    {
        $i = $sxml->ЗначенияРеквизитов->ЗначениеРеквизита;

        if (!empty($i) && !empty($order['apply_bonusrules_amount'])) {
            $sxml->ЗначенияРеквизитов->ЗначениеРеквизита[$i]->Наименование = 'Применённые бонусы';
            $sxml->ЗначенияРеквизитов->ЗначениеРеквизита[$i++]->Значение   = $order['apply_bonusrules_amount'];
        }
    }


    /**
     * Проверяет пароль на соответствие требованиям безопасности
     *
     * @param User $_this - проверяемый ORM - объект
     * @param mixed $value - проверяемое значение
     * @param string $error - текст ошибки
     * @return bool(true) | string возвращает true в случае успеха, иначе текст ошибки
     */
    public static function checkBonusCard($_this, $value, $error)
    {
        if (isset($_POST['bonus_card']) && !empty($_POST['bonus_card'])) {
            //Проверим есть ли в системе такая бонусная карта.
            $card = \RS\Orm\Request::make()
                ->from(new BonusCard())
                ->where(array(
                    'card_id' => htmlspecialchars(trim(str_replace(' ', '', $_POST['bonus_card'])))
                ))->exec()
                ->fetchRow();

            if (!empty($card) && $card['user_id']){ //Если карта уже привязана
                return t('Данный номер карты уже привязан к другому покупателю');
            }

            if (!$card){
                return t('Такой дисконтной карты не зарегистрировано');
            }
        }
        return true;
    }
}