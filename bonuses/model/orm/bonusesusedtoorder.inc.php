<?php

namespace Bonuses\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - История начислений бонусов
*/
class BonusesUsedToOrder extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'bonuses_used_to_order';
        
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            t('Основные'),
                'site_id' => new Type\CurrentSite(),
                'user_id' => new Type\User(array(
                    'description' => t('Пользователь'),
                    'index' => true,
                    'maxLength' => 11,
                    'Checker' => array('chkEmpty', 'Укажите пользователя'),
                )),
                'order_id' => new Type\Integer(array(
                    'description' => t('id заказа'),
                    'Checker' => array('chkEmpty', 'Укажите количество бонусов'),
                    'maxLength' => 11,
                )),
                'bonuses' => new Type\Integer(array(
                    'description' => t('Количество бонусов'),
                    'maxLength' => 11,
                )),
        ));
        $this->addIndex(array('site_id', 'user_id', 'order_id'), self::INDEX_UNIQUE);
    }
}

