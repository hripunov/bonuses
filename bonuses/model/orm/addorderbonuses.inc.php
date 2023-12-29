<?php

namespace Bonuses\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - Бонусы которые были зачислены за заказ
*/
class AddOrderBonuses extends \RS\Orm\OrmObject
{
    protected static
        $table = 'bonuses_add_bonus_order';
        
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),         
                'site_id' => new Type\CurrentSite(),
                'order_id' => new Type\Integer(array(
                    'description' => t('Номер заказа'),
                    'index' => true,
                    'maxLength' => 11,
                )),
                'bonuses' => new Type\Integer(array(
                    'description' => t('Количество бонусов, которые было начислено'),
                    'maxLength' => 11,
                )),
        ));
    }
    
}

