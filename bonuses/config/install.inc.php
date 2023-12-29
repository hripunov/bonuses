<?php
namespace Bonuses\Config;

use Catalog\Model\Orm\Product;
use EmailSubscribe\Model\Orm\Email;
use RS\Exception;
use RS\Module\AbstractInstall;
use RS\Module\Manager;
use Shop\Model\Orm\Delivery;
use Shop\Model\Orm\Discount;
use Shop\Model\Orm\Order;
use Shop\Model\Orm\Payment;
use Users\Model\Orm\User;
use Users\Model\Orm\UserGroup;

/**
* Класс отвечает за установку и обновление модуля
*/
class Install extends AbstractInstall
{
    /**
     * Обновляет колонки в объектах
     *
     * @throws Exception
     */
    function updateOrmObjects()
    {
        $user = new User();
        Handlers::ormInitUsersUser($user);
        $user->dbUpdate();

        $usergroup = new UserGroup();
        Handlers::ormInitUsersUserGroup($usergroup);
        $usergroup->dbUpdate();

        $product = new Product();
        Handlers::ormInitCatalogProduct($product);
        $product->dbUpdate();

        if (Manager::staticModuleExists('shop') && Manager::staticModuleEnabled('shop')){
            $discount = new Discount();
            Handlers::ormInitShopDiscount($discount);
            $discount->dbUpdate();

            $delivery = new Delivery();
            Handlers::ormInitShopDelivery($delivery);
            $delivery->dbUpdate();

            $payment = new Payment();
            Handlers::ormInitShopPayment($payment);
            $payment->dbUpdate();

            $order = new Order();
            Handlers::ormInitShopOrder($order);
            $order->dbUpdate();
        }

        if (Manager::staticModuleExists('emailsubscribe') && Manager::staticModuleEnabled('emailsubscribe')){
            $email = new Email();
            Handlers::ormInitEmailSubscribeEmail($email);
            $email->dbUpdate();
        }
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    function install()
    {
        
        $result = parent::install();
        if ($result) { //Обновим пользователя, добавив поля  и товар обновим тоже
            $this->updateOrmObjects();
        }
        
        return $result;
    }

    /**
     * Функция обновления модуля, вызывается только при обновлении
     * @throws Exception|\ReflectionException
     */
    function update()
    {
        $result = parent::update();
        if ($result) { //Обновим пользователя и товар
            $this->updateOrmObjects();
        }
        return $result;
    }     
    
}
