<?php
namespace Bonuses\Model;

class BonusDiscountApi extends \RS\Module\AbstractModel\BaseModel
{
    protected
        $config;
    
    function __construct()
    {
        $this->config = \RS\Config\Loader::byModule($this); //Получим текущий конфиг 
    }

    /**
     * Возвращает общую сумму оформленных пользователем заказов
     *
     * @param integer $user_id - id пользователя
     * @return float
     * @throws \RS\Db\Exception
     */
    private function getOrdersSummByUserId($user_id)
    {
        $status       = $this->config['discount_order_status'];
        $order_status = new \Shop\Model\Orm\UserStatus($status); 
        $statuses     = \Shop\Model\UserStatusApi::getStatusesIdByType($order_status['type']);
                 
        //Подзапрос       
        $subsql = \RS\Orm\Request::make()
                        ->select('P.id')
                        ->from(new \Catalog\Model\Orm\Product(), 'P')
                        ->where('P.discount_ignore=1')->toSql();
                        
        //Теперь уменьшим на величину товаров, которые находятся в исключении 
        $summ = \RS\Orm\Request::make()
                     ->select('SUM(OI.price) as summ')
                     ->from(new \Shop\Model\Orm\Order(), 'O')
                     ->join(new \Shop\Model\Orm\OrderItem(), 'O.id=OI.order_id', 'OI')
                     ->where(array(
                        'O.user_id' => $user_id, 
                        'O.site_id' => \RS\Site\Manager::getSiteId(), 
                     ))->whereIn('O.status', $statuses)  
                     ->whereIn('OI.type', array(
                         \Shop\Model\Orm\OrderItem::TYPE_PRODUCT,
                         \Shop\Model\Orm\OrderItem::TYPE_DELIVERY,
                     ))
                     ->where('OI.entity_id NOT IN ('.$subsql.')')
                     ->exec()->getOneField('summ', 0);

        $user = new \Users\Model\Orm\User($user_id);

        if ($user['orders_summ_before'] > 0){
            $summ += $user['orders_summ_before'];
        }
                     
        return $summ;
    }

    /**
     * Добавляет правило пользователю назначая цену, если сумма заказа попадает под правило применения дисконтной программы
     *
     * @param \Users\Model\Orm\User $user - объект пользователя
     * @throws \RS\Db\Exception
     */
    private function appleRuleByUser(\Users\Model\Orm\User $user)
    {
        //Если нужно проскочить для администраторов
        if ($this->config['discount_admin_not_usage'] && ($user->isAdmin() || $user->isSupervisor())){
            return;
        }
        //Посмотрим какая цена назначена у пользователя
        $user_cost = \Catalog\Model\CostApi::getUserCost($user);
        
        //Попробуем применить правила
        $summ             = $this->getOrdersSummByUserId($user['id']); //Общая сумма заказов
        $apply_rule_price = false; //Принимаемая цена    
        
        foreach($this->config['discount_rule_arr'] as $discount_rule){
            if ($discount_rule['summ']<$summ){
                $apply_rule_price = $discount_rule['price_id'];
            }
        }
        
        
        //Если принимаемая цена нашлась и она отличается от установленной ранее
        if ($apply_rule_price && ($apply_rule_price != $user_cost)){
            $this->setUserCost($user, $apply_rule_price);
        }
    }
    
    /**
    * Обновляет цену у пользователя, проставляя идентификатор и отправляя уведомление
    * 
    * @param \Users\Model\Orm\User $user - объект пользователя
    * @param integer $cost_id - id проставляемой цены
    */
    private function setUserCost($user, $cost_id)
    {
        if (!$user['user_cost']){
            $user['user_cost'] = array();
        }
        $user_cost = $user['user_cost'];
        $user_cost[\RS\Site\Manager::getSiteId()] = $cost_id;
        $user['user_cost'] = $user_cost;
        $user->update();
        //Генерируем сообщение о смене цены
        $notice = new \Bonuses\Model\Notice\DiscountToUser();
        $notice->init($user, new \Catalog\Model\Orm\Typecost($cost_id));
        \Alerts\Model\Manager::send($notice); 
    }
    
    
    /**
    * Применяет правила дисконтной программы к пользователю, если он под них подходит
    * 
    * @param \Shop\Model\Orm\Order $order - последний оформленный заказ
    * @return bool
    */
    function applyDiscountRule($order)
    {
        //Посмотрим пользователя и проверим, является ли он зарегистрированным
        $user = $order->getUser();
        if (!$user['id']){
            return false;
        }
        
        //Если правила есть
        if (!empty($this->config['discount_rule_arr'])){
            $this->appleRuleByUser($user);
        }
        return true;
    }
    
    
    /**
    * Обновляет у всех пользователей настройки типов цен, если есть правила в дисконтной программе
    * 
    */
    function updateUsersOrdersPriceTypes()
    {
        if (!empty($this->config['discount_rule_arr'])){ //Если есть правила начисления скидок
            //Получим всех пользователей
            $users = \RS\Orm\Request::make()
                            ->from(new \Users\Model\Orm\User()) 
                            ->objects();
            if (!empty($users)){      
                foreach ($users as $user){
                    $this->appleRuleByUser($user);
                }
            }
        }
    }
    
    
    /**
    * Обновляет у всех пользователей настройки типов цен, если есть правила в дисконтной программе
    * 
    */
    function updateRegisteredUsersPriceTypes()
    {
        if (!empty($this->config['discount_register_price_id'])){ //Если есть правила начисления скидок для зарегистрированных
            //Получим всех пользователей
            $users = \RS\Orm\Request::make()
                            ->from(new \Users\Model\Orm\User()) 
                            ->objects();
            $default_cost_id = \Catalog\Model\CostApi::getDefaultCostId();
            if (!empty($users)){      
                foreach ($users as $user){
                    //Посмотрим какая цена назначена у пользователя
                    $user_cost = \Catalog\Model\CostApi::getUserCost($user);
                    if ($user_cost==$default_cost_id){ //Если цена та что по умолчанию, то только в этом случае меняем
                        $this->setUserCost($user, $this->config['discount_register_price_id']);
                    }
                }
            }
        }
    }
}

