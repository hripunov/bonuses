<?php
namespace Bonuses\Controller\Front;
use Shop\Model\Orm\Order;

/**
* Контроллер бонусной карты
*/
class CartBonuses extends \RS\Controller\AuthorizedFront
{
    /**
     * Обновляет применение бонусов в оформлении заказа для шаблона где нет формы корзины
     */
    function actionUpdateBonusesApply()
    {
        $use_cart_bonuses = $this->request('use_cart_bonuses', TYPE_INTEGER, 0);
        $order = Order::currentOrder();
        $cart = $order->getCart();
        $cart->triggerChangeEvent();
        $appliedBonuses = isset($_SESSION['product_applied_bonuses']) ? $_SESSION['product_applied_bonuses'] : 0;
        if (!$use_cart_bonuses){
            $_SESSION['use_cart_bonuses'] = 0;
        }else{
            $_SESSION['use_cart_bonuses'] = 1;
        }
        return $this->result->setSuccess(true)->addSection('appliedBonuses', $appliedBonuses)->addMessage(t('Флаг обновлён'));
    }
}
