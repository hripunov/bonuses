<?php
namespace Bonuses\Model\Behavior;

use Shop\Model\Orm\AbstractCartItem;
use Shop\Model\Orm\Order;

/**
* Объект - Расширения заказа
*/
class ShopOrder extends \RS\Behavior\BehaviorAbstract
{
    //
    //  Получение спец. бонусов
    //
    function getRuleBonuses() {
        $order = $this->owner;
        if ($order->rule_bonuses == 0) {
            return $order->rule_bonuses;
        }
        else {
            return -1;
        }
    }

    //
    //  Запись спец. бонусов
    //
    function setRuleBonuses($amount) {
        $this->owner->rule_bonuses = $amount;
    }

    /**
     * Получает бонусы, которые могут быть начислены за оформление заказа
     *
     * @param bool $afterWriteOrder - вызов из ormAfterWriteShopOrder?
     * @return integer
     * @throws \RS\Exception
     */
    function getBonuses($afterWriteOrder = false): int
    {
        /**
        * @var Order $order
        */
        $order = $this->owner; //Расширяемый объект, в нашем случае - Товар.
        $user = $order->getUser();
        $cart_data = $order->getCart()->getCartData();
        $config = \RS\Config\Loader::byModule('bonuses');

        $total_bonuses = $cart_data['total_bonuses'] + $order['rule_bonuses'];

        if ($order['payment']){ //Если есть оплата, проверим нужно ли начислять бонусы
            $payment = $order->getPayment();
            if ($payment['bonuses'] > 0){
                $total_bonuses += $payment['bonuses'];
            }
        }

        if ($order['delivery']){ //Если есть доставка, проверим нужно ли начислять бонусы
            $delivery = $order->getDelivery();
            if ($delivery['bonuses'] > 0){
                $total_bonuses += $delivery['bonuses'];
            }
        }

        if (
            !empty($config['bonuses_for_first_order']) &&
            $user['id'] > 0
        ){
            //Посмотрим сколько заказов было
            $cnt = \RS\Orm\Request::make()
                ->from(new Order())
                ->where([
                    'user_id' => $user['id'],
                ])->count();

            if ($cnt == 0 || ($cnt == 1 && $afterWriteOrder)){
                $total_bonuses = $config['bonuses_for_first_order'];
            }
        }
            
        return (int)$total_bonuses;
    }

    /**
     * Возвращает количество бонусов из скидки примененной к каждому товару
     *
     * @return integer
     * @throws \RS\Db\Exception
     * @throws \RS\Event\Exception
     * @throws \RS\Exception
     * @throws \RS\Orm\Exception
     */
    function getBonusesFromOrderDiscountForEachProduct()
    {
        /**
         * @var Order $order
         */
        $order = $this->owner; //Расширяемый объект, в нашем случае - Товар.
        $cart  = $order->getCart();
        
        $bonus_discount = 0;
        if ($cart){
            $in_basket = $cart->getProductItemsWithConcomitants();

            foreach ($in_basket as $uniq=>$item){
                /** @var AbstractCartItem $cartitem */
                $cartitem = $item['cartitem'];
                $discounts = $cartitem->getDiscounts();

                foreach($discounts as $discount){
                    if ($discount->getSource() == \Bonuses\Model\BonusProductDiscountApi::DISCOUNT_SOURCE_BONUSES){
                        $bonus_discount += round($discount->getAmountOfDiscount());
                    }
                }
            }
        }
        return $bonus_discount;
    }

    /**
     * Возвращает бонусы, которые долны быть применены к заказу из скидки на заказ
     *
     * @return integer
     * @throws \RS\Exception
     */
    function getBonusesFromOrderDiscount()
    {
        /**
         * @var Order $order
         */
        $order = $this->owner; //Расширяемый объект, в нашем случае - Товар.
        $cart  = $order->getCart();
        if ($cart){ //Если корзина в заказе присутствует
            $coupons = $cart->getCouponItems();
            if (!empty($coupons)){ //Если есть купоны, то значит нельзя с бонусами минипуляции проводить
                return 0;
            }
            $config = \RS\Config\Loader::byModule('bonuses');

            $cart_data = $cart->getCartData(false, false);
            $in_basket = $cart->getProductItemsWithConcomitants();

            $total_discount = 0;
            foreach ($in_basket as $uniq=>$item){
                /** @var AbstractCartItem $cartitem */
                $cartitem = $item['cartitem'];
                $discounts = $cartitem->getDiscounts();

                foreach($discounts as $discount){
                    if ($discount->getSource() == \Bonuses\Model\BonusProductDiscountApi::DISCOUNT_SOURCE_BONUSES){
                        $total_discount += round($discount->getAmountOfDiscount());
                    }
                }
            }

            if (!$total_discount && isset($order['apply_bonusrules_amount']) && $order['apply_bonusrules_amount']){
                $total_discount = $order['apply_bonusrules_amount'];
            }

            //Если нужно сконвертировать и применить бонусы
            if ($total_discount && isset($cart_data['order_bonuses_for_discount']) && $cart_data['order_bonuses_for_discount']){
                $bonuses = $total_discount / $config['equal_bonuses_number'];
                return $bonuses;
            }
        }
        return 0;
    }


    /**
     * Проверяет были ли начисления партнеру бонусных карт за заказ
     *
     * @return boolean
     * @throws \RS\Orm\Exception
     */
    function isHadAddedBonusesToBonusCardPartner()
    {
        /**
         * @var Order $order
         */
        $order = $this->owner;
        $topartner = \RS\Orm\Request::make()
                            ->from(new \Bonuses\Model\Orm\AddOrderBonusesToPartner())
                            ->where(array(
                                'site_id' => \RS\Site\Manager::getSiteId(),
                                'order_id' => $order['id'],
                            ))->object();

        return $topartner ? true : false;
    }
}

