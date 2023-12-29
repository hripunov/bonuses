<?php
namespace Bonuses\Controller\Block;
use \RS\Orm\Type;

/**
* Блок-контроллер Показа сколько бонусов у пользователя
*/
class UserBonuses extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Бонусы пользователя',
        $controller_description = 'Отображает количество бонусов пользователя';
    
    protected
        $theme,
        $default_params = array();
        
    /**
    * Возвращает правильный шаблон для назначения
    *     
    */
    function getRightTemplate()
    {
        //Правильно определим шаблон
        $config       = \RS\Config\Loader::byModule($this);
        $this->theme  = \RS\Theme\Manager::getCurrentTheme('theme');
        
        if (!in_array($this->theme, array('amazing','default', 'perfume', 'fashion', 'young'))) {
            $this->theme = $config['default_template'];
        }
        
        $this->default_params = array(
            'indexTemplate' => '%bonuses%/templates/'.$this->theme.'/blocks/bonuses/bonuses.tpl',
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
        //Если не авторизованы
        if (!\RS\Application\Auth::isAuthorize()){
            return false;
        }         
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
    
}