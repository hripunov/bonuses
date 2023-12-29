<?php
namespace Bonuses\Controller\Front;

/**
* Контроллер истории бонусов пользователя
*/
class BonusPartnerCards extends \RS\Controller\AuthorizedFront
{   
    protected $theme;

    //Поля, которые следует ожидать из POST при добавлении карты
    protected $use_post_keys = array('card_id');

    function init()
    {
        parent::init();

        //Правильно определим шаблон
        $config       = \RS\Config\Loader::byModule($this);
        $this->theme  = \RS\Theme\Manager::getCurrentTheme('theme');

        if (!in_array($this->theme, array('default', 'perfume', 'fashion', 'young', 'flatlines'))) {
            $this->theme = $config['default_template'];
        }
    }

    function actionIndex()
    {
        $this->app->title->addSection(t('Бонусные карты партнера'));
        $this->app->breadcrumbs->addBreadCrumb(t('Бонусные карты партнера'));
                
        $page_size = 10;
        $page      = $this->url->get('p', TYPE_INTEGER, 1);
        
        $user = \RS\Application\Auth::getCurrentUser();
        if (!$user['is_bonuscard_partner']){
            $this->e404(t('Вы не являетесь партнером бонусных карт'));
        }

        $errors = array();
        $api  = new \Bonuses\Model\BonusCardApi();
        $list = $api->setFilter('partner_id', $user['id'])
                    ->setOrder('id DESC')
                    ->getList($page, $page_size);

        $paginator = new \RS\Helper\Paginator($page, $api->getListCount(), $page_size);
        $this->view->assign(array(
            'paginator' => $paginator,        
            'list' => $list,
            'errors' => $errors,
            'current_user' => $user,
        ));        

        return $this->result->setTemplate('%bonuses%/templates/'.$this->theme.'/bonuspartnercards.tpl');
    }


    /**
     * Добавление карты партнера
     */
    function actionAdd()
    {
        $this->app->title->addSection(t('Добавление бонусной карты партнера'));
        $this->app->breadcrumbs->addBreadCrumb(t('Добавление бонусной карты партнер'));

        $user = \RS\Application\Auth::getCurrentUser();
        if (!$user['is_bonuscard_partner']){
            $this->e404(t('Вы не являетесь партнером бонусных карт'));
        }

        $card = new \Bonuses\Model\Orm\BonusCard();
        $card->usePostKeys($this->use_post_keys);

        $errors = array();
        //Посмотрим данные
        if ($this->isMyPost()){
           if ($this->url->checkCsrf()){
               $card->checkData();
               $card['partner_id'] = $user['id'];

               if (!$card->hasError() && $card->save()) {
                   $this->view->assign(array(
                      'success' => true
                   ));
                   if (!$this->url->isAjax()){
                       $this->redirect($this->router->getUrl('bonuses-front-bonuspartnercards'));
                   }
               }else{
                   $errors[] = t($card->getErrorsStr());
               }
           }else{
               $errors[] = t('Неверный код CSRF');
           }
        }

        $this->view->assign(array(
            'card' => $card,
            'errors' => $errors
        ));

        return $this->result->setTemplate('%bonuses%/templates/'.$this->theme.'/bonuspartnercards_add.tpl');
    }
    
}
