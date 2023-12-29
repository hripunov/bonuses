<?php
namespace Bonuses\Model\Orm;
use \RS\Orm\Type;

/**
* Объект - Бонусы которые были начислены партнеру за заказ покупателя
*/
class AddOrderBonusesToPartner extends \RS\Orm\OrmObject
{
    protected static
        $table = 'bonuses_add_bonus_order_to_partner';
        
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
                'partner_id' => new Type\Integer(array(
                    'description' => t('Партнер, которому начислили'),
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

