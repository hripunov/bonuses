<?php
namespace Bonuses\Config;

use Catalog\Model\Orm;
use RS\Db\Adapter as DbAdapter;
use RS\Module\AbstractPatches;
use RS\Orm\Request as OrmRequest;
use Site\Model\Api as SiteApi;
use Users\Model\Orm\User;

/**
* Патчи к модулю
*/
class Patches extends AbstractPatches
{
    /**
    * Возвращает список имен существующих патчей
    */
    function init()
    {
        return array(
            '604',
        );
    }

    /**
     * Заменяем неверно установленные значения для поля alias у характеристик
     */
    function beforeUpdate604()
    {
        $offset = 0;
        $limit = 100;

        $q = \RS\Orm\Request::make()
            ->from(new \Bonuses\Model\Orm\BonusHistory())
            ->limit($limit)
            ->orderby('id DESC');

        while($items = $q->offset($offset)->objects(null, 'dateof', true)){
            foreach ($items as $k=>$items_inside){
                if (count($items_inside) > 1){
                    foreach ($items_inside as $k=>$item){
                        if ($k > 0){
                            $item->delete();
                        }
                    }
                }
            }

            $offset += $limit;
        }
    }
}