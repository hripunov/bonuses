<?php
namespace Bonuses\Model;

class CashoutApi extends \RS\Module\AbstractModel\EntityList
                implements \Main\Model\NoticeSystem\HasMeterInterface
{
    const METER_CASHOUT = 'rs-admin-menu-cashout';

    /**
     * Конструктор
     * @throws \RS\Exception
     */
    function __construct()
    {
        parent::__construct(new \Bonuses\Model\Orm\Cashout(),
            array(
                'multisite' => true,
                'defaultOrder' => 'dateof DESC'
            ));
    }

    function getMeterApi($user_id = null)
    {
        return new \Main\Model\NoticeSystem\MeterApi($this->obj_instance,
            self::METER_CASHOUT,
            $this->getSiteContext(),
            $user_id);
    }

    /**
     * Возвращает количество непрочитанных предварительных заказов
     *
     * @param integer|null $user_id ID пользователя. Если null, то будет использован текущий пользователь
     * @return integer
     */
    function getNewCounter($user_id = null)
    {
        $readed_items_api = new ReadedItemApi($this->getSiteContext(), $user_id);
        return $readed_items_api->getUnreadCount($this->obj_instance, self::METER_CASHOUT);
    }
}

