<?php
namespace Bonuses\Model;

use Catalog\Model\Orm\Product;
use Shop\Model\Cart;
use Shop\Model\Discounts\CartItemDiscount;
use Shop\Model\Orm\AbstractCartItem;

/**
 * Класс специально разработобанный для бонусных скидок для каждого товара
 */
class BonusProductDiscountApi
{
    const DISCOUNT_SOURCE_BONUSES = "bonuses_module";

    protected $config; //Конфиг модуля бонусов
    protected $shop_config; //Конфиг модуля магазина
    protected $catalog_config; //Конфиг модуля магазин

    function __construct()
    {
        $this->config         = \RS\Config\Loader::byModule($this); //Получим текущий конфиг
        $this->shop_config    = \RS\Config\Loader::byModule('shop'); //Получим конфиг магазина
        $this->catalog_config = \RS\Config\Loader::byModule('catalog'); //Получим текущий конфиг
    }

    /**
     * Подсчитывает общее количество за товары с учетом за товары которые являются товарами без зачернутой цены
     *
     * @param \Shop\Model\Cart $cart - корзина
     * @param array $cart_data - массив товаров
     * @return float
     * @throws \RS\Exception
     */
    function countCartDataTotalWithOutOldPriceItems(\Shop\Model\Cart $cart, $cart_data)
    {
        $total = 0;
        $product_items = $cart->getProductItemsWithConcomitants();

        foreach ($product_items as $uniq=>$item){
            /**
             * @var \Catalog\Model\Orm\Product $product
             */
            $product = $item['product'];
            /** @var AbstractCartItem $cartitem */
            $cartitem = $item['cartitem'];


            if ($cart->getMode() == $cart::MODE_SESSION){
                $product = new \Catalog\Model\Orm\Product($product['id']);
            }
            if ($this->isHaveOldCost($product) && $this->config['disable_use_product_by_action_in_cart']){
                $cartitem->setForbidDiscounts(true);
            }
            if (!$this->isHaveOldCost($product) || !$this->config['disable_use_product_by_action_in_cart'] || $this->shop_config['old_cost_delta_as_discount']){
                if (mb_stripos($uniq, 'concomitant') !== false){
                    $uniq_keys = explode("-", $uniq);
                    $total = $total + ($cart_data['items'][$uniq_keys[1]]['sub_products'][$uniq_keys[2]]['single_cost']*$cart_data['items'][$uniq_keys[1]]['sub_products'][$uniq_keys[2]]['amount']);
                }else{
                    $total = $total + ($cart_data['items'][$uniq]['single_cost']*$cart_data['items'][$uniq]['amount']);
                }
            }
        }

        return $total;
    }

    /**
     * Проверяет есть ли товары с максимально разрешенной скидкой у нас в корзине и возвращает true, если находит
     *
     * @param \Shop\Model\Cart $cart - объект корзины
     * @return boolean
     * @throws \RS\Exception
     */
    function checkProductExistsWithMaxDiscount(\Shop\Model\Cart $cart)
    {
        $in_basket = $cart->getProductItems();

        $found = false;
        foreach($in_basket as $n => $item) {
            /**
             * @var \Catalog\Model\Orm\Product $product
             */
            $product = $item['product'];

            if ($product['max_discount_percent']){
                $found = true;
                break;
            }

            if (!empty($sub_products)) { //Если есть сопутствующие товары
                foreach ($sub_products as $subid => $sub_product) {
                    if ($sub_product['checked']) {
                        $product = new \Catalog\Model\Orm\Product($subid);
                        if ($product['max_discount_percent']){
                            $found = true;
                            break(2);
                        }
                    }
                }
            }
        }
        return $found;
    }

    /**
     * Проверяет есть ли зачеркнутая цену у товара
     *
     * @param \Catalog\Model\Orm\Product $product - объект товара
     * @return boolean
     * @throws \RS\Db\Exception
     * @throws \RS\Event\Exception
     */
    private function isHaveOldCost($product)
    {
        if ($product->isHaveOldCost === null){ //Флажок проверки старой цены
            if (!$this->catalog_config['old_cost']){ //Если старая цена не указана
                $product->isHaveOldCost = false;
                return false;
            }
            if ($product->getOldCost(null, false) > 0 && ($product->getOldCost(null, false) != $product->getCost(null,null,false))){
                $product->isHaveOldCost = true;
                return true;
            }
            $product->isHaveOldCost = false;
            return false;
        }
        return $product->isHaveOldCost;
    }


    /**
     * Применяет бонусы к элементу в корзине
     *
     * @param \Catalog\Model\Orm\Product $product - объект товара
     * @param integer $delta_discount - сколько бонусов осталось применить
     * @param integer $new_apply_bonuses - применено бонусов всего
     * @param array $cart_data - массив данных корзины
     * @param string $n - ключ элемента массива
     *
     * @return array
     * @throws \RS\Db\Exception
     * @throws \RS\Event\Exception
     */
    private function applyDiscountToProductInCartData($product, &$delta_discount, &$new_apply_bonuses, $cart_data, $n)
    {
        $cart_data['items'][$n]['discount'] = 0; //Сбросим, чтобы установить

        if ($delta_discount<=0){
            return $cart_data;
        }

        $cost     = $cart_data['items'][$n]['base_cost'];
        $discount = $cart_data['items'][$n]['discount'];

        $need_count_from_old_price = ($this->config['use_old_cost_to_count_discount'] && $this->isHaveOldCost($product));

        if ($need_count_from_old_price){//Если есть зачернутая цена и нужно считать от неё
            $old_cost = $product->getOldCost(null, false);
        }

        if (!$this->isHaveOldCost($product) || !$this->config['disable_use_product_by_action_in_cart'] || $this->config['use_old_cost_to_count_discount']){

            if ($cost > $delta_discount){ //Если сумма за товар больше
                $d = $discount + $delta_discount;

                if ($product['max_discount_percent'] > 0){ //Если размер скидки ограничен у товара
                    if ($need_count_from_old_price){
                        $old_d = ceil(($product['max_discount_percent']*$old_cost) / 100);

                        if ($old_d >= $cost){
                            $d = 0;
                        }else{
                            $d = abs(ceil(($old_cost-$cost)-$old_d));
                        }
                    }else{
                        $d = ceil(($product['max_discount_percent']*$cost) / 100);
                    }

                }elseif ($need_count_from_old_price){
                    $d = ceil($cost-($old_cost - $delta_discount));
                }
                if ($d > $delta_discount){ //Если скидка больше чем нужно
                    $d = $delta_discount;
                }
                $cart_data['items'][$n]['cost']     = $cost - $d;
                $cart_data['items'][$n]['discount'] = $d;
                if ($need_count_from_old_price) {
                    $delta = $old_cost - $cart_data['items'][$n]['cost'];
                    $new_apply_bonuses += $delta;
                    $delta_discount -= $delta;
                }else{
                    $new_apply_bonuses += $d;
                    $delta_discount -= $d;
                }
            }else{
                $minus = floor($cost);
                $d = $discount + $minus;

                if ($product['max_discount_percent'] > 0){ //Если размер скидки ограничен у товара
                    if ($need_count_from_old_price){
                        $old_d = ceil(($product['max_discount_percent']*$old_cost) / 100);

                        if ($old_d >= $cost){
                            $d = 0;
                        }else{
                            $d = abs(ceil(($old_cost-$cost)-$old_d));
                        }
                    }else{
                        $d = ceil(($product['max_discount_percent'] * $cost) / 100);
                    }
                }elseif ($need_count_from_old_price){
                    $d = ceil($cost-($old_cost - $delta_discount));
                }
                if ($d > $delta_discount){ //Если скидка больше чем нужно
                    $d = $delta_discount;
                }
                $cart_data['items'][$n]['cost']     = $cost - $d;
                $cart_data['items'][$n]['discount'] = $d;
                if ($need_count_from_old_price) {
                    $delta = $old_cost - $cart_data['items'][$n]['cost'];
                    $new_apply_bonuses += $delta;
                    $delta_discount -= $delta;
                }else{
                    $new_apply_bonuses += $d;
                    $delta_discount -= $d;
                }
            }
            $cart_data['total']      -= $d;
            $cart_data['total_base'] -= $d;
        }
        return $cart_data;
    }

    /**
     * Применяет бонусы к подэлементу в корзине
     *
     * @param \Catalog\Model\Orm\Product $product - объект товара
     * @param integer $delta_discount - сколько бонусов осталось применить
     * @param integer $new_apply_bonuses - применено бонусов всего
     * @param array $cart_data - массив данных корзины
     * @param string $n - ключ элемента массива
     * @param string $subid - ключ подэлемента массива
     *
     * @return array
     * @throws \RS\Db\Exception
     * @throws \RS\Event\Exception
     */
    private function applyDiscountToSubProductInCartData($product, &$delta_discount, &$new_apply_bonuses, $cart_data, $n, $subid)
    {
        if (!$this->isHaveOldCost($product) || !$this->config['disable_use_product_by_action_in_cart'] || $this->config['use_old_cost_to_count_discount']){
            $cost     = $cart_data['items'][$n]['sub_products'][$subid]['base_cost'];
            $discount = $cart_data['items'][$n]['sub_products'][$subid]['discount'];

            if ($cost > $delta_discount){ //Если сумма за товар больше
                $d = $discount + $delta_discount;
                $delta_discount = 0;
                if ($product['max_discount_percent'] > 0){ //Если размер скидки ограничен у товара
                    $d = ceil(($product['max_discount_percent']*$cost) / 100);
                    $delta_discount -= $d;
                }
                $cart_data['items'][$n]['sub_products'][$subid]['cost']     = $cost - $d;
                $cart_data['items'][$n]['sub_products'][$subid]['discount'] = $d;
                $new_apply_bonuses += $d;
            }else{
                $minus = $delta_discount - floor($cost);
                $d = $discount + $minus;
                if ($product['max_discount_percent'] > 0){ //Если размер скидки ограничен у товара
                    $d = ceil(($product['max_discount_percent']*$cost) / 100);
                }
                $cart_data['items'][$n]['sub_products'][$subid]['cost']     = $cost - $d;
                $cart_data['items'][$n]['sub_products'][$subid]['discount'] = $d;
                $delta_discount -= $d;
                $new_apply_bonuses += $d;
            }
            $cart_data['total']      -= $d;
            $cart_data['total_base'] -= $d;
        }
        return $cart_data;
    }


    /**
     * Удаляет скидку бонусной программы из корзины
     *
     * @param Cart $cart - корзина
     * @return array
     * @throws \RS\Db\Exception
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    function removeDiscountForEachProduct(\Shop\Model\Cart $cart)
    {
        $in_basket = $cart->getProductItemsWithConcomitants();

        foreach ($in_basket as $basket_item) {
            /** @var AbstractCartItem $cart_item */
            $cart_item = $basket_item['cartitem'];
            $cart_item->removeDiscountsBySource(self::DISCOUNT_SOURCE_BONUSES);
        }
        return $in_basket;
    }

    /**
     * Добавляет сведения о скидке на весь заказ в корзине
     *
     * @param \Shop\Model\Cart $cart - Объект корзины
     * @param array $cart_data - данные корзины
     * @param integer $apply_bonuses - добавленные бонусы
     *
     * @return array
     * @throws \RS\Exception
     */
    function addOrderDiscountToEachProduct(\Shop\Model\Cart $cart, $cart_data, $apply_bonuses)
    {
        $in_basket = $this->removeDiscountForEachProduct($cart);

        $new_apply_bonuses = 0;

        $remaining_discount_limit = $cart->getItemsRemainingDiscountLimit();
        $linked_remaining_discount_limit = [];
        foreach ($in_basket as $uniq => $basket_item) {
            /** @var AbstractCartItem $cart_item */
            $cart_item = $basket_item['cartitem'];

            if (!$cart_item->getForbidDiscounts()) {
                $linked_remaining_discount_limit[$uniq] = $remaining_discount_limit[$uniq];
            }
        }

        $coupon_discounts = Cart::evenlyAllocateTheSum($apply_bonuses, $linked_remaining_discount_limit);

        foreach ($coupon_discounts as $uniq => $discount_amount) {
            /** @var AbstractCartItem $cart_item */
            $cart_item = $in_basket[$uniq]['cartitem'];

            if (!$cart_item->getForbidDiscounts()) {
                $discount = new CartItemDiscount($coupon_discounts[$uniq], CartItemDiscount::UNIT_BASE_COST, self::DISCOUNT_SOURCE_BONUSES);
                $discount->setFlagAlwaysAddDiscount();

                $new_apply_bonuses += $discount->getAmountOfDiscount();
                $cart_item->addDiscount($discount);
            }
        }

        $apply_bonuses = $new_apply_bonuses;

        $_SESSION['product_applied_bonuses'] = $apply_bonuses;
        return $cart_data;
    }

    /**
     * Возвращает уже имеющиеся параметры у товара в корзине
     *
     * @param \Shop\Model\Orm\CartItem $item - уникальный идентификатор
     *
     * @return array
     */
    private function getOldUpdateData($item)
    {
        return array(
            //Сохраним сопутствующие товары, если они были
            'personal_discount' => $item->getExtraParam('personal_discount', 0),
            'concomitant' => $item->getExtraParam('sub_products', array()),
            'concomitant_amount' => $item->getExtraParam('sub_products_amount', array()),
            'sub_products' => $item->getExtraParam('sub_products', array()),
            'additional_product_uniq' => $item->getExtraParam('additional_uniq', null)
        );
    }

    /**
     * Добавляет данные о скидке к товарам в корзине, чтобы потом было сохранение
     *
     * @param \Shop\Model\Cart $cart - объект корзины
     * @param array $cart_data - данные корзины
     * @param array $product_items - данные о товарах в корзине
     */
    private function addDiscountExtraDataToCartData(\Shop\Model\Cart $cart, $cart_data, $product_items)
    {
        $new_extra = array();
        foreach($product_items as $n => $item) {
            /**
             * @var \Shop\Model\Orm\CartItem $cartitem
             */
            $cartitem = $item['cartitem'];
            $discount = isset($cart_data['items'][$n]['discount']) ? $cart_data['items'][$n]['discount'] : 0; // Посмотрим скидки

            $new_extra[$n] = $this->getOldUpdateData($cartitem);
            $new_extra[$n]['personal_discount'] = $discount;
            $new_extra[$n]['discount'] = $discount;

            $sub_products = $cart_data['items'][$n]['sub_products'];

            if (!empty($sub_products)) {
                foreach ($sub_products as $subid=>$sub_product){
                    $discount = isset($cart_data['items'][$n]['sub_products'][$subid]['discount'] ) ? $cart_data['items'][$n]['sub_products'][$subid]['discount']  : 0; // Посмотрим скидки
                    $new_extra[$n]['concomitant_discount'][$subid] = $discount;
                }
            }
        }

        $cart->update($new_extra, null, false);
    }


    /**
     * Убирает предыдущие установленные скидки
     *
     * @param \Shop\Model\Cart $cart - объект корзины
     * @param array $cart_data - массив объектов корзины
     *
     * @return array
     * @throws \RS\Exception
     */
    function removeBeforeSettedDiscount(\Shop\Model\Cart $cart, $cart_data = false)
    {
        if (!$cart_data){
            $cart->is_cartbonus_action = true; //Защита от рекурсии
            $cart_data = $cart->getCartData(false, false);
            unset($cart->is_cartbonus_action);
        }

        $new_extra = array();
        $in_basket = $cart->getProductItems();
        foreach($in_basket as $n => $item) {
            $cost     = $cart_data['items'][$n]['cost'];
            $discount = $cart_data['items'][$n]['discount'];
            /**
             * @var \Shop\Model\Orm\CartItem $cartitem
             */
            $cartitem = $item['cartitem'];

            $new_extra[$n] = $this->getOldUpdateData($cartitem);

            if ($discount > 0){
                $d = $cost + $discount;
                $new_extra[$n]['personal_discount'] = 0;
                $new_extra[$n]['discount'] = 0;
                $cart_data['items'][$n]['cost']     = $d;
                $cart_data['items'][$n]['discount'] = 0;

                $cart_data['total']      += $discount;
                $cart_data['total_base'] += $discount;
            }

            $sub_products = $cart_data['items'][$n]['sub_products'];
            if (!empty($sub_products)) {

                foreach ($sub_products as $subid => $sub_product) {
                    $cost     = $cart_data['items'][$n]['sub_products'][$subid]['cost'];
                    $discount = $cart_data['items'][$n]['sub_products'][$subid]['discount'];

                    $d = $cost + $discount;
                    $new_extra[$n]['concomitant_discount'][$subid] = $discount;
                    $cart_data['items'][$n]['sub_products'][$subid]['cost']     = $d;
                    $cart_data['items'][$n]['sub_products'][$subid]['discount'] = 0;

                    $cart_data['total']      += $discount;
                    $cart_data['total_base'] += $discount;
                }
            }

        }

        $cart->update($new_extra, null, false);

        $cart_data['total_discount'] = 0;
        $cart_data['total_discount_unformatted'] = 0;

        return $cart_data;
    }
}