<?php
namespace Bonuses\Controller\Front;
/**
* Контроллер истории бонусов пользователя
*/
class BonusHistory extends \RS\Controller\AuthorizedFront
{   
    protected
        $theme;
    
    function actionIndex()
    {
        $this->app->title->addSection(t('Моя история бонусов'));
        $this->app->breadcrumbs->addBreadCrumb(t('Моя история бонусов')); 
        
        //Правильно определим шаблон
        $config       = \RS\Config\Loader::byModule($this);
        $this->theme  = \RS\Theme\Manager::getCurrentTheme('theme');
        
        if (!in_array($this->theme, array('default', 'perfume', 'fashion', 'young', 'flatlines'))) {
            $this->theme = $config['default_template'];
        }       
                
        $page_size = 10;
        $page      = $this->url->get('p', TYPE_INTEGER, 1);
        
        $user    = \RS\Application\Auth::getCurrentUser();
        $config  = \RS\Config\Loader::byModule($this);
        
        $errors  = array();
        $success = false;
        $convert = $this->request('convert', TYPE_STRING, false);
        $bonuses = $user->getUserBonuses();
        if ($convert){ //Перевод бонусов на лицевой счёт
            if ($bonuses){ //Если есть бонусы
                $api = new \Bonuses\Model\BonusApi();
                if ($config['min_bonuses_add_to_bonuses']){ //Если нужно минимальное количество бонусов для зачисления
                    if ($bonuses<$config['min_bonuses_add_to_bonuses']){
                        $errors[] = t('Не достаточно бонусов для зачисления на лицевой счёт. Минимально нужно иметь %0.', array($config['min_bonuses_add_to_bonuses']));
                    }else{
                        $amount = $api->convertBonusesToPersonalAccount();
                        $user->load($user['id']);
                        $default_currency = \Catalog\Model\CurrencyApi::getDefaultCurrency();
                        $success = t('На Ваш лицевой счёт зачислено %0 %1.', array($amount, $default_currency['stitle']));
                        unset($_SESSION['use_cart_bonuses']);
                    }
                }else{
                    $amount = $api->convertBonusesToPersonalAccount();
                    $user->load($user['id']);
                    $default_currency = \Catalog\Model\CurrencyApi::getDefaultCurrency();
                    $success = t('На Ваш лицевой счёт зачислено %0 %1.', array($amount, $default_currency['stitle']));
                    unset($_SESSION['use_cart_bonuses']);
                }  
            }else{
                $errors[] = t('Не достаточно бонусов для зачисления на лицевой счёт. Минимально нужно иметь %0.', array(
                    $config['min_bonuses_add_to_bonuses'] ? $config['min_bonuses_add_to_bonuses'] : 1
                ));
            }
        }

        $cashout = $this->request('cashout', TYPE_STRING, false);
        if ($cashout) { //Перевод бонусов на лицевой счёт
            if ($bonuses && $user->isCanCashoutBonuses()){ //Если есть бонусы
                $cashout = new \Bonuses\Model\Orm\Cashout();
                $cashout['partner_id'] = $user['id'];
                $cashout['amount']     = $bonuses;
                $cashout->insert();
                $success = t('Запрос на вывод создан.');
            }else{
                $errors[] = t('Не достаточно бонусов для вывода средств. Минимально нужно иметь %0.', array(
                    $config['min_partner_cashbackout'] ? $config['min_partner_cashbackout'] : 1
                ));
            }
        }
        
        $historyApi = new \Bonuses\Model\BonusesHistoryApi();
        $historyApi->setFilter('user_id', $user['id']);
        
        $list = $historyApi->getList($page, $page_size, 'dateof desc');

        $paginator = new \RS\Helper\Paginator($page, $historyApi->getListCount(), $page_size);
        $this->view->assign(array(
            'paginator' => $paginator,        
            'list' => $list,
            'success' => $success,
            'errors' => $errors,
            'current_user' => $user,
        ));        

        return $this->result->setTemplate('%bonuses%/templates/'.$this->theme.'/bonushistory.tpl');
    }
    
    
}
