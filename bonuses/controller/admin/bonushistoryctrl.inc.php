<?php

namespace Bonuses\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Filter,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Table;

/**
* Контроллер. история начисления и списания бонусов
*/
class BonusHistoryCtrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        parent::__construct(new \Bonuses\Model\BonusesHistoryApi());
    }
    
    function helperIndex()
    {        
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('История начисления и списания'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить операцию'))));
        $helper->setBottomToolbar($this->buttons(array('delete')));
        $helper->setListFunction('getList');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => 20))),                  
                new TableType\Text('reason', t('Название операции'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Userfunc('amount', t('Начислено'), function($value){
                    if ($value>0){
                        return "<span style='color:green'>".$value."</span>";
                    }
                }, array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Userfunc('amount', t('Списано'), function($value){
                    if ($value<0){
                        return "<span style='color:red'>".$value."</span>";
                    }
                }, array('LinkAttr' => array('class' => 'crud-edit'), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Datetime('dateof', t('Дата'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Usertpl('user_id', t('Покупатель'), '%bonuses%/form/programm/bonus_user_cell.tpl', array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),             
                new TableType\Text('id', '№', array('TdAttr' => array('class' => 'cell-sgray'))),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )
        )));
        //Опишем фильтр, который следует добавить
        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container( array( //Контейнер визуального фильтра
                'Lines' =>  array(
                    new Filter\Line( array('Items' => array( //Одна линия фильтров
                            new Filter\Type\Text('id','№', array('attr' => array('class' => 'w50'))), //Фильтр по ID
                            new Filter\Type\User('user_id','Пользователь', array('class' => 'w100')), 
                            new Filter\Type\Text('reason','Название операции', array('searchType' => '%like%')),
                            new Filter\Type\Date('dateof','Дата', array('ShowType' => true)), 
                        )
                    )),
                )
            )),
            'Caption' => 'Поиск по истории'
        )));
        
        return $helper;
    }
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать операцию {title}') : t('Добавить операцию'));
        $user_id  = $this->url->get('id', TYPE_INTEGER);
        $writeoff = $this->request('writeoff', TYPE_INTEGER, false); //Списать или начислить
        $bonuses  = $this->request('bonuses', TYPE_INTEGER, 0); //Количество бонуов для зачисления

        
        if ($writeoff !== false){ //Если пришёл тип операции 
            $user = new \Users\Model\Orm\User($user_id);
        
            $obj = $this->api->getElement();  
            $obj['user_id']  = $user['id'];
            $obj['writeoff'] = $writeoff;
            
            switch($writeoff){
                case 0: //Списать
                        $obj['reason'] = t('Списание средств администратором');
                        $obj['amount'] = $bonuses;
                        break;
                case 1: //Начислить
                        $obj['reason'] = t('Начисление средств администратором');
                        break;               
            }
            $obj['dateof'] = date("Y-m-d H:i:s");
        }
        
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
}


