<?php
namespace Bonuses\Controller\Front;
/**
* Контроллер бонусной карты
*/
class BonusCard extends \RS\Controller\AuthorizedFront
{
    protected
        $theme;

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
        $this->app->title->addSection(t('Моя бонусная карта'));
        $this->app->breadcrumbs->addBreadCrumb(t('Моя бонусная карта'));
        
        $api  = new \Bonuses\Model\BonusCardApi();
        $user = \RS\Application\Auth::getCurrentUser();
        $bonus_card = $api->getUserCardByUser($user);
        $active     = $this->request('active', TYPE_STRING, false);
        $use_bonus  = $this->request('use_bonus', TYPE_STRING, false);
            
        if ($bonus_card && $active){ //Активация карты
           $bonus_card['active'] = 1;
           $bonus_card->update();
           $this->redirect($this->router->getUrl('bonuses-front-bonuscard')); 
        }    
        
        if ($bonus_card && $use_bonus){ //Списание бонусов на лицевой счёт
           $bonus_card['use_bonus'] = 1;
           $bonus_card->update();
           $this->redirect($this->router->getUrl('bonuses-front-bonuscard'));   
        }  
    
        $this->view->assign(array(       
            'bonus_card' => $bonus_card
        ));        

        return $this->result->setTemplate( '%bonuses%/templates/'.$this->theme.'/bonuscard.tpl' );
    }

    /**
     * Добавление карты партнера
     */
    function actionAdd()
    {
        $this->app->title->addSection(t('Добавление бонусной карты'));
        $this->app->breadcrumbs->addBreadCrumb(t('Добавление бонусной карты'));

        $user = \RS\Application\Auth::getCurrentUser();

        $card = new \Bonuses\Model\Orm\BonusCard();
        $card->usePostKeys($this->use_post_keys);

        $errors = array();
        //Посмотрим данные
        if ($this->isMyPost()){
            if ($this->url->checkCsrf()){
                $bonus_card_number = htmlspecialchars(trim(str_replace(' ', '', $this->request('card_id', TYPE_STRING, ""))));
                //Поищем эту карту
                $card = \RS\Orm\Request::make()
                    ->from(new \Bonuses\Model\Orm\BonusCard())
                    ->where(array(
                        'card_id' => $bonus_card_number
                    ))->object();

                if ($card){
                    $card['user_id'] = $user['id'];
                    $card['active']  = 1;
                    $card->update();
                    $this->view->assign(array(
                        'success' => true
                    ));
                    if (!$this->url->isAjax()){
                        $this->redirect($this->router->getUrl('bonuses-front-bonuscard'));
                    }
                }else{
                    $card = new \Bonuses\Model\Orm\BonusCard();
                    $errors[] = t('Карта не найдена');
                }
            }else{
                $errors[] = t('Неверный код CSRF');
            }
        }

        $this->view->assign(array(
            'card' => $card,
            'errors' => $errors
        ));

        return $this->result->setTemplate('%bonuses%/templates/'.$this->theme.'/bonuscard_add.tpl');
    }
    
    
}
