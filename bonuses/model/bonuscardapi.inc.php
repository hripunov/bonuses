<?php
namespace Bonuses\Model;

use Bonuses\Model\Orm\BonusCard;

class BonusCardApi extends \RS\Module\AbstractModel\EntityList
{
    protected $config;

    function __construct()
    {
        parent::__construct(new BonusCard(),
        array(
            'multisite' => false,
            'nameField' => 'title'
        ));

        $this->config = \RS\Config\Loader::byModule('bonuses');
    }


    /**
     * Получает объект пользовательской карты по пользователю
     *
     * @param \Users\Model\Orm\User $user - пользователь
     * @return bool|\RS\Orm\AbstractObject
     * @throws \RS\Orm\Exception
     */
    function getUserCardByUser($user)
    {
        return \RS\Orm\Request::make()
            ->from(new BonusCard())
            ->where("user_id='".$user['id']."' OR e_mail='".$user['e_mail']."'")
            ->object();
    }

    /**
     * Начисляет бонусы партнеру за активированную пользователем карту
     *
     * @param Orm\BonusCard $card - бонусная карта
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    function addBonusesToPartnerForActivatedCard(BonusCard $card)
    {
        //Если есть партнер и у нас есть настройка начисления за привлеченного пользователя, начислим
        $partner = $card->getPartner();
        if (!$card['activation_bonus_use']){
            $active_date = strtotime($card['active_date']);
            $create_date = strtotime($card['create_date']);
            $delta = floor((abs($active_date - $create_date)) / (60 * 60 * 24)); //Количество дней разрыва

            if (($delta < $this->config['bonuscard_cashback_days']) && ($cashback = $partner->getBonusPartnerActivationCashback())){
                $api = new BonusApi();
                $api->addBonusesTransaction($partner, $cashback, t('Начисление партнеру за активированную карту %0', array($card->getCardId())), true);
            }

            \RS\Orm\Request::make()
                ->update()
                ->from($card)
                ->set(array(
                    'activation_bonus_use' => 1
                ))
                ->where(array(
                    'id' => $card['id']
                ))->exec();
        }
    }

    /**
     * Добавляет бонусы партнеру за заказ
     *
     * @param BonusCard $card_with_partner - карта пользователя
     * @param \Shop\Model\Orm\Order $order
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    function addBonusesToPartnerForOrder(BonusCard $card_with_partner, \Shop\Model\Orm\Order $order)
    {
        $partner     = $card_with_partner->getPartner();
        $active_date = strtotime($card_with_partner['active_date']);
        $today_date  = time();
        $delta = floor((abs($active_date - $today_date)) / (60 * 60 * 24)); //Количество дней разрыва

        if (($delta < $this->config['bonuscard_cashback_days']) && ($cashback = $partner->getBonusPartnerOrdersCashback($order['totalcost']))){
            $api = new BonusApi();
            $res = $api->addBonusesTransaction($partner, $cashback, t('Начисление бонусов за привелеченного покупателя. Заказа №%0', array($order['order_num'])), true);

            //Запишем сведения об операции
            if ($res){
                $addorderbonsestopartner = new \Bonuses\Model\Orm\AddOrderBonusesToPartner();
                $addorderbonsestopartner['order_id']   = $order['id'];
                $addorderbonsestopartner['partner_id'] = $partner['id'];
                $addorderbonsestopartner['bonuses']    = $cashback;
                $addorderbonsestopartner->insert();
            }
        }
    }

    /**
     * Возвращает бонусы которые были зачислены партнеру за покупку в заказа
     *
     * @param \Shop\Model\Orm\Order $order - объект заказа
     * @param \Users\Model\Orm\User $partner - объект партнера
     *
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    function backBonusesAppliedToPartnerByOrder($order, $partner)
    {
        //Ищем связь
        /**
         * @var \Bonuses\Model\Orm\AddOrderBonusesToPartner $row
         */
        $row = \RS\Orm\Request::make()
            ->from(new \Bonuses\Model\Orm\AddOrderBonusesToPartner())
            ->where(array(
                'site_id' => \RS\Site\Manager::getSiteId(),
                'partner_id' => $partner['id'],
                'order_id' => $order['id']
            ))->object();

        if ($row){
            //Отнимем бонусы
            $api = new BonusApi();
            $api->addBonusesTransaction($partner, $row['bonuses'], t('Возврат бонусов за отменённый заказ №%0 от привлеченого пользователя', array($order['order_num'])), false);

            //Удалим связь
            \RS\Orm\Request::make()
                ->delete()
                ->from(new \Bonuses\Model\Orm\BonusesUsedToOrder())
                ->where(array(
                    'site_id' => \RS\Site\Manager::getSiteId(),
                    'partner_id' => $partner['id'],
                    'order_id' => $order['id']
                ))->exec();
        }
    }
}

