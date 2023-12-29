<?php
namespace Bonuses\Model\Orm;
use RS\Orm\OrmObject;
use \RS\Orm\Type;

/**
* Объект - Бонусы которые были начислены пользователю за оставленный комментарий
*/
class AddCommentBonusesToProduct extends OrmObject
{
    protected static
        $table = 'bonuses_add_bonus_comment_to_product';
        
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'comment_id' => new Type\Integer(array(
                    'description' => t('id комментария'),
                    'maxLength' => 11,
                )),
                'product_id' => new Type\Integer(array(
                    'description' => t('id товара'),
                    'maxLength' => 11,
                )),
                'user_id' => new Type\Integer(array(
                    'description' => t('id пользователя'),
                    'maxLength' => 11,
                )),
        ));
        $this->addIndex(['comment_id', 'product_id'], self::INDEX_KEY);
        $this->addIndex(['product_id', 'user_id'], self::INDEX_KEY);
    }
    
}

