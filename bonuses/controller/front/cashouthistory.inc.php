<?php
namespace Bonuses\Controller\Front;
/**
* Контроллер истории бонусов пользователя
*/
class CashoutHistory extends \RS\Controller\AuthorizedFront
{   
    protected
        $theme;
    
    function actionIndex()
    {
        $this->app->title->addSection(t('Запросы на вывод средств'));
        $this->app->breadcrumbs->addBreadCrumb(t('Запросы на вывод средств'));
        
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
        $bonuses = $user->getUserBonuses();

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
        
        $historyApi = new \Bonuses\Model\CashoutApi();
        $historyApi->setFilter('partner_id', $user['id']);
        
        $list = $historyApi->getList($page, $page_size, 'dateof desc');

        $paginator = new \RS\Helper\Paginator($page, $historyApi->getListCount(), $page_size);
        $this->view->assign(array(
            'paginator' => $paginator,        
            'list' => $list,
            'success' => $success,
            'errors' => $errors,
            'current_user' => $user,
        ));        

        return $this->result->setTemplate('%bonuses%/templates/'.$this->theme.'/cashouthistory.tpl');
    }
    
    
}
