<?php
namespace Bonuses\Model\Behavior;

use Bonuses\Model\BonusApi;
use Catalog\Model\Dirapi;
use Catalog\Model\Orm\Product;
use RS\Application\Auth;
use RS\Behavior\BehaviorAbstract;
use RS\Config\Loader;

/**
* Объект - Расширения товара
*/
class CatalogProduct extends BehaviorAbstract
{
    
    const
        CACHE_DIRS_INFO = 'bonus_dirs_info';

    /**
    * Получает настройки бонусов у директории
    * 
    * @param integer $dir_id - id директории
    * @param boolean $cache - включен кэш?
    * 
    * @return array
    */
    public static function getBonusesInfoFromDir($dir_id, $cache = true)
    {
        if ($cache) {
            return \RS\Cache\Manager::obj()
                        ->tags(self::CACHE_DIRS_INFO)
                        ->request(array(__CLASS__,'getBonusesInfoFromDir'), $dir_id, false);
        }else{
            $api   = new Dirapi();
            $paths = $api->getPathToFirst($dir_id);
            
            foreach($paths as $path){
                if ($path['bonuses_units']){
                    return array(
                       'bonuses_units' => $path['bonuses_units'],
                       'bonuses_units_type' => $path['bonuses_units_type'],
                    );
                    break;
                }
            }
            $config = Loader::byModule('bonuses');
            //Проверим установки и в конфиге
            if ($config['default_dir_bonuses_units']){
                return array(
                    'bonuses_units' => $config['default_dir_bonuses_units'],
                    'bonuses_units_type' => $config['default_dir_bonuses_units_type'],
                );
            } 
            return [];
        }
    }

    /**
     * Возвращает подсчитанные бонусы
     *
     * @param Product $product - объект товара
     * @param integer|null $offer - комплектация товара для, для которой нужно рассчитать бонусы
     * @param integer|string|null $cost_id - тип цены для которой рассчитываются бонусы
     * @param integer $bonuses_units - бонусные единицы
     * @param integer $bonuses_units_type - тип начисления бонусов
     *
     * @return integer
     */
    function getCalculatedBonuses(Product $product, $offer = null, $cost_id = null, $bonuses_units = 0, $bonuses_units_type = 0)
    {
         switch($bonuses_units_type){
            case 0:
                return $bonuses_units;
                break;
            case 1:
                $cost = $product->getCost($cost_id, $offer, false);
                $config = Loader::byModule('bonuses');
                //Если задана минимальная стоимость товара для бонусов
                if ($config['min_product_cost'] && ($config['min_product_cost']>$cost)){
                    return 0;
                }
                return ceil(($cost * ($bonuses_units/100))/$config['equal_bonuses_number']);
                break;
         }
    }

    /**
     * Получает бонусы, которые могут быть начислены за товар
     *
     * @param integer|null $offer - комплектация товара для, для которой нужно рассчитать бонусы
     * @param integer|string|null $cost_id - тип цены для которой рассчитываются бонусы
     *
     * @return integer
     */
    function getBonuses($offer = null, $cost_id = null)
    {
        $config         = Loader::byModule('bonuses');
        $catalog_config = Loader::byModule('catalog');
        /**
        * @var Product $product
        */
        $product = $this->owner; //Расширяемый объект, в нашем случае - Товар.

        if ($product['bonuses_ignore']){ // Если стоит опция "Не участвовать в бонусной программе?"
            return 0;
        }

        //Проверим настройки, если установлен запрет на товары по акции. Проверка по зачеркнутой цене
        if ($config['disable_product_by_action'] && $catalog_config['old_cost'] && ($product->getOldCost(null, false) > 0)){
            return 0;
        }

        if (!empty($product['bonuses_units'])){ //Если есть назначенные бонусы у товаров
            return $this->getCalculatedBonuses($product, $offer, $cost_id, $product['bonuses_units'], $product['bonuses_units_type']);
        }else{ //Проверим, есть ли назначенные бонусы у верней категории или категорий
            $bonus_info = $this->getBonusesInfoFromDir($product['maindir'], true);   
            if (!empty($bonus_info)){
                return $this->getCalculatedBonuses($product, $offer, $cost_id, $bonus_info['bonuses_units'], $bonus_info['bonuses_units_type']);    
            }
        }      
        return 0;
    }

    /**
     * Возвращает количество бонусов начиляемое за заказ
     *
     * @param float $total_unformatted - сумма всего
     */
    private function getBonusesForOrder($total_unformatted): array
    {
        $config = Loader::byModule('bonuses');
        if (!$config['bonuses_for_order_as_table']){ // Если это обычное начисление бонусов
            switch($config['bonuses_for_order_type']){
                case 0: //Единицы
                    return [
                        'bonuses_units' => $config['bonuses_for_order'],
                        'bonuses_units_type' => 0,
                    ];
                    break;
                case 1: //Проценты
                    return [
                        'bonuses_units' => $config['bonuses_for_order'],
                        'bonuses_units_type' => 1,
                    ];
                    break;
            }
        }else{ //Начисление по правилам
            if (!empty($config['bonuses_for_order_rule_arr'])){
                if (!empty($config['bonuses_for_order_with_old']) && Auth::isAuthorize()) { // Учитываем сумму прошлых заказов
                    $current_user = Auth::getCurrentUser();
                    $total_unformatted += BonusApi::calculateOldOrdersSum($current_user['id']);
                }

                $current_rule = null;
                foreach ($config['bonuses_for_order_rule_arr'] as $rule){
                    if ($total_unformatted >= $rule['from']){
                        $current_rule = $rule;
                        if (!empty($rule['to']) && ($total_unformatted > $rule['to'])){
                            $current_rule = null;
                        }
                    }
                }

                if (!empty($current_rule)){
                    switch($current_rule['bonuses_type']){
                        case 'ед.': //Единицы
                            return [
                                'bonuses_units' => $current_rule['bonuses'],
                                'bonuses_units_type' => 0,
                            ];
                            break;
                        case '%': //Проценты
                            return [
                                'bonuses_units' => $current_rule['bonuses'],
                                'bonuses_units_type' => 1,
                            ];
                            break;
                    }
                }
            }
        }

        return [
            'bonuses_units' => 0,
            'bonuses_units_type' => 0,
        ];
    }

    /**
     * Получает бонусы, которые могут быть начислены за товар плюс бонусы за заказ, если есть
     *
     * @param integer|null $offer - комплектация товара для, для которой нужно рассчитать бонусы
     * @param integer|string|null $cost_id - тип цены для которой рассчитываются бонусы
     *
     * @return integer
     */
    function getBonusesWithOrderRules($offer = null, $cost_id = null)
    {
        $config         = Loader::byModule('bonuses');
        $catalog_config = Loader::byModule('catalog');
        $bonuses = $this->getBonuses($offer, $cost_id);

        /**
         * @var Product $product
         */
        $product = $this->owner; //Расширяемый объект, в нашем случае - Товар.

        if ($product['bonuses_ignore']){ // Если стоит опция "Не участвовать в бонусной программе?"
            return 0;
        }

        // Проверим настройки, если установлен запрет на товары по акции. Проверка по зачеркнутой цене
        if ($config['disable_product_by_action'] && $catalog_config['old_cost'] && ($product->getOldCost(null, false) > 0)){
            return 0;
        }

        if ($config['bonuses_for_order'] || $config['bonuses_for_order_as_table']){
            $total_unformatted = $product->getCost($cost_id, $offer, false);
            $bonus_info = $this->getBonusesForOrder($total_unformatted);
            $bonuses += $this->getCalculatedBonuses($product, $offer, $cost_id, $bonus_info['bonuses_units'], $bonus_info['bonuses_units_type']);
        }
        return $bonuses;
    }

    /**
     * Возвращает true, если можно показывать сколько бонусов начислять для конкретного пользователя
     *
     * @return bool
     */
    function bonusesCanBeShown()
    {
        $config = Loader::byModule('bonuses');
        //Проверим настройки групп цен пользователя
        if ($config['use_only_for_price_groups'] && !empty($config['use_only_for_price_groups'])){
            $user_cost = \Catalog\Model\CostApi::getUserCost();
            if (!in_array($user_cost, $config['use_only_for_price_groups'])){ //Если такой цены для бонусов не определили
                return false;
            }
        }
        return true;
    }

    /**
     * Получает бонусы, которые могут быть начислены за товар в корзине по заявленной цене
     *
     * @param float $cost - цена товара
     * @return int
     */
    function getBonusesByCartCost($cost){
        $config         = Loader::byModule(__CLASS__);
        $catalog_config = Loader::byModule('catalog');

        /**
         * @var Product $product
         */
        $product = $this->owner;


        if ($product['bonuses_ignore']){ // Если стоит опция "Не участвовать в бонусной программе?"
            return 0;
        }

        if ($config['disable_product_by_action'] && $catalog_config['old_cost'] && ($product->getOldCost(null, false) > 0)){
            return 0;
        }

        if (!empty($product['bonuses_units'])) { //Если есть назначенные бонусы у товаров
            return $this->getCalculatedBonusesByCost($cost, $product['bonuses_units'], $product['bonuses_units_type']);
        }else{
            $bonus_info = $product->getBonusesInfoFromDir($product['maindir'], true);
            if (!empty($bonus_info)){
                return $this->getCalculatedBonusesByCost($cost, $bonus_info['bonuses_units'], $bonus_info['bonuses_units_type']);
            }
        }
        return 0;
    }

    /**
     * Возвращает подсчитанные бонусы по Цена
     *
     * @param float $cost - цена товара
     * @param integer $bonuses_units - бонусные единицы
     * @param integer $bonuses_units_type - тип начисления бонусов
     *
     * @return integer
     */
    function getCalculatedBonusesByCost($cost, $bonuses_units = 0, $bonuses_units_type = 0)
    {
        switch($bonuses_units_type){
            case 0:
                return $bonuses_units;
                break;
            case 1:
                $config = Loader::byModule('bonuses');
                //Если задана минимальная стоимость товара для бонусов
                if ($config['min_product_cost'] && ($config['min_product_cost']>$cost)){
                    return 0;
                }
                return ceil(($cost * ($bonuses_units/100))/$config['equal_bonuses_number']);
                break;
        }
    }
}

