<?php
namespace Bonuses\Controller\Block;
use Bonuses\Model\BonusApi;
use Catalog\Model\CostApi;
use Catalog\Model\CurrencyApi;
use RS\Application\Auth;
use RS\Config\Loader;
use RS\Controller\StandartBlock;
use RS\Theme\Manager;
use Shop\Model\Cart;
use Shop\Model\Orm\Order;

/**
* Блок-контроллер Показа сколько бонусов у пользователя
*/
class CartBonuses extends StandartBlock
{
    protected static
        $controller_title = 'Бонусы в корзине',
        $controller_description = 'Отображает бонусы в корзине и позволяет использовать бонусы в корзине';
    
    protected
        $theme,
        $default_params = [];
        
    /**
    * Возвращает правильный шаблон для назначения
    *     
    */
    function getRightTemplate()
    {
        //Правильно определим шаблон
        $config       = Loader::byModule($this);
        $this->theme  = Manager::getCurrentTheme('theme');
        
        if (!in_array($this->theme, array('amazing', 'default', 'perfume', 'fashion', 'young'))) {
            $this->theme = $config['default_template'];
        }
        
        $this->default_params = array(
            'indexTemplate' => '%bonuses%/templates/'.$this->theme.'/blocks/cart/bonuses.tpl',
        );
    }
    
    function init()
    {
         $this->getRightTemplate();
    }
        
    function getParamObject()
    {
        $this->getRightTemplate();
        return parent::getParamObject();
    }

    /**
    * Отображение результата блока
    * 
    */
    function actionIndex()
    {
        $config = Loader::byModule($this);
        $api    = new BonusApi();
        
        if ($config['use_only_for_price_groups'] && !empty($config['use_only_for_price_groups'])){//Проверим если нужно в какой ценовой группе пользователь
            $user    = Auth::getCurrentUser();
            $cost_id = CostApi::getUserCost($user);
            if (!in_array($cost_id, $config['use_only_for_price_groups'])){ //Если Группа отличается от указанной
                return false;
            }
        }
        
        if ($action = $this->request('action', TYPE_STRING, false)){  
            if ($config['use_min_max_cart_rules']){
                $cart        = Cart::currentCart();
                $cart_result = $api->checkConvertBonuses($cart);
                if (isset($cart_result['errors']) && !empty($cart_result['errors'])){
                   return $this->result->setSuccess(false)->addEMessage(implode(",", $cart_result['errors'])); 
                } 
            }
            
            switch($action){
                //Зарезервировано на случай списания с лицевого счёта
                case "useBonusesForPersonalAccount":      
                    $amount = $api->convertBonusesToPersonalAccount();
                    if ($amount===false){
                        $this->result->setSuccess(false)->addEMessage($api->getErrorsStr());
                    }else{
                        $default_currency = CurrencyApi::getDefaultCurrency();
                        $this->result->setSuccess(true)->addMessage(t('На Ваш лицевой счёт зачислено %0 %1.', array($amount, $default_currency['stitle'])));
                    }
                    return $this->result;
                    break;
            }
        }

        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }


}