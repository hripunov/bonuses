<?php

namespace Bonuses\Model\Behavior;
use Bonuses\Model\BonusCardApi;
use Bonuses\Model\Orm\BonusCard;
use Bonuses\Model\Orm\Cashout;
use RS\Behavior\BehaviorAbstract;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\UserStatus;
use Shop\Model\UserStatusApi;
use Users\Model\Orm\User;

/**
* Объект - Расширения пользователя
*/
class UsersUser extends BehaviorAbstract
{

    protected $config;
    protected $user_bonus_cards = array(); //Массив бонусных карт пользователя

    /**
     * Конструктор класса
     */
    function __construct()
    {
        $this->config = \RS\Config\Loader::byModule($this);
    }

    /**
     * Получает бонусы пользователя
     *
     * @return integer
     */
    function getUserBonuses(): int
    {
        $user = $this->owner; //Расширяемый объект, в нашем случае - пользователь.
        return $user['bonuses'] ?: 0;
    }

    /**
     * Возвращает количество заказов пользователя
     *
     * @return integer
     */
    function getBonusOrdersCount(): int
    {
        static $orders_count; //Количество заказов

        if ($orders_count === null) {
            /**
             * @var User $user
             */
            $user = $this->owner;
            $status_ids = UserStatusApi::getStatusesIdByType(UserStatus::STATUS_SUCCESS);
            $orders_count = \RS\Orm\Request::make()
                ->select('COUNT(id) as CNT')
                ->from(new Order())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                    'user_id' => $user['id']
                ))
                ->whereIn('status', $status_ids)
                ->exec()
                ->getOneField('CNT', 0);
        }


        return (int)$orders_count;
    }

    /**
     * Возвращает true, если у пользователя достаточное количество заказов
     *
     * @return boolean
     */
    function isHaveCountOrders()
    {
        if ($this->config['orders_count_before_use']) {
            return ($this->getBonusOrdersCount() >= $this->config['orders_count_before_use']);
        }
        return true;
    }

    /**
     * Возвращает массив бонусных карт пользователя
     *
     * @return BonusCard[]
     */
    function getBonusCards()
    {
        /**
         * @var User $user
         */
        $user = $this->owner;
        if (!isset($this->user_bonus_cards[$user['id']])){
            $bonuscard_api = new BonusCardApi();
            $this->user_bonus_cards[$user['id']] = $bonuscard_api->setFilter('user_id', $user['id'])
                                                            ->getList();
        }

        return $this->user_bonus_cards[$user['id']];
    }

    /**
     * Проверяет есть ли бонусные карты у пользователя и если есть, то возвращает true
     *
     * @return boolean
     */
    function isHaveBonusCard()
    {
        $bonus_cards = $this->getBonusCards();
        return !empty($bonus_cards) ? true : false;
    }

    /**
     * Возвращает кэшбек за привлеченного пользователя по бонусной карте. Если передана сумма, то возвращает не число, а
     * подсчитанную сумму для заказа
     *
     * @return integer
     */
    function getBonusPartnerActivationCashback()
    {
        /**
         * @var User $user
         */
        $user = $this->owner;
        if ($user['bonuscard_cashback_for_activation'] > 0){
            return $user['bonuscard_cashback_for_activation'];
        }
        $groups = $user->getUserGroups(false);
        foreach ($groups as $group){
            if ($group['bonuscard_cashback_for_activation'] > 0){
                return $group['bonuscard_cashback_for_activation'];
            }
        }
        return $this->config['bonuscard_cashback_for_activation'];
    }


    /**
     * Возвращает кэшбек за привлеченного пользователя по бонусной карте. Если передана сумма, то возвращает не число, а
     * подсчитанную сумму для заказа
     * @param int $from_total - сумма от которой считать онечное число
     *
     * @return integer
     */
    function getBonusPartnerOrdersCashback($from_total = 0)
    {
        /**
         * @var User $user
         */
        $user = $this->owner;
        if ($user['bonuscard_cashback'] > 0){
            if ($from_total && $user['bonuscard_cashback_type'] == 1){
                return ceil($from_total * ($user['bonuscard_cashback']/100));
            }
            return $user['bonuscard_cashback'];
        }
        $groups = $user->getUserGroups(false);
        foreach ($groups as $group){
            if ($group['bonuscard_cashback'] > 0){
                if ($from_total && $group['bonuscard_cashback_type'] == 1){
                    return ceil($from_total * ($group['bonuscard_cashback']/100));
                }
                return $group['bonuscard_cashback'];
            }
        }

        if ($from_total && $this->config['bonuscard_cashback_type'] == 1){
            return ceil($from_total * ($this->config['bonuscard_cashback']/100));
        }
        return $this->config['bonuscard_cashback'];
    }

    /**
     * Возвращает true есть есть достаточно средств на вывод себе и запрос на вывод ранее небыл задан
     *
     * @return bool
     */
    function isCanCashoutBonuses()
    {
        /**
         * @var User $user
         */
        $user = $this->owner;

        $config = \RS\Config\Loader::byModule($this);

        //Если есть бонусы и запроса на вывод ещё нет
        if ($user['is_bonuscard_partner'] && ($user['bonuses'] > $config['min_partner_cashbackout'])){
            $cashout = \RS\Orm\Request::make()
                            ->from(new Cashout())
                            ->where(array(
                                'site_id' => \RS\Site\Manager::getSiteId(),
                                'partner_id' => $user['id'],
                                'enrolled' => 0
                            ))->object();

            if (!$cashout){
                return true;
            }
        }
        return false;
    }
}

