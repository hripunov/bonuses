<?php
namespace Bonuses\Controller\Admin;
use \RS\Html\Filter,
    \RS\Html\Table\Type as TableType,
    \RS\Html\Table;

/**
* Контроллер. история начисления и списания бонусов
*/
class BonusCardCtrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        parent::__construct(new \Bonuses\Model\BonusCardApi());
    }
    
    function helperIndex()
    {        
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Бонусные карты'));
        $helper->setBottomToolbar($this->buttons(array('delete')));
        $helper->addCsvButton('bonuses-bonuscard');
        $helper->setListFunction('getList');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => 20))),            
                new TableType\Userfunc('user_id', t('Покупатель'), function($value, \RS\Html\Table\Type\Userfunc $row){
                    $card = $row->getRow();
                    if ($card['user_id']){
                        return $card->getUser()->getFio();
                    }
                    return t('Нет');
                }, array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')))),
                new TableType\Userfunc('card_id', t('Номер карты'), function($value, \RS\Html\Table\Type\Userfunc $row){
                    return $row->getRow()->getCardId();
                }, array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('amount', t('Бонусы'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\StrYesno('active', t('Активирована?'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\StrYesno('is_partner_card', t('Это карта партнера?'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Userfunc('active_date', t('Дата активации'), function($value, \RS\Html\Table\Type\Userfunc $row){
                    $active_date = $row->getRow()->active_date;
                    return !empty($active_date) ? date('d.m.Y', strtotime($active_date)) : t('Нет');
                }, array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\User('partner_id', t('Партнер'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')))),
                new TableType\Actions('id', array(
                        new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                        new TableType\Action\DropDown(array(
                            array(
                                'title' => t('клонировать'),
                                'attr' => array(
                                    'class' => 'crud-add',
                                    '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                                )
                            ),
                            array(
                                'title' => t('активировать карту'),
                                'attr' => array(
                                    'class' => 'crud-get',
                                    '@href' => $this->router->getAdminPattern('activate', array(':id' => '~field~')),
                                )
                            ),
                            array(
                                'title' => t('деактивировать карту'),
                                'attr' => array(
                                    'class' => 'crud-get',
                                    '@href' => $this->router->getAdminPattern('deactivate', array(':id' => '~field~')),
                                )
                            )
                        ))

                    ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )
        )));

        //Опишем фильтр, который следует добавить
        $helper->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container( array( //Контейнер визуального фильтра
                'Lines' =>  array(
                    new Filter\Line( array('Items' => array( //Одна линия фильтров
                            new Filter\Type\Text('card_id','№ карты', array('searchType' => '%like%', 'attr' => array('class' => 'w100'))), //Фильтр по ID
                            new Filter\Type\User('user_id','Пользователь', array('class' => 'w100')), 
                            new Filter\Type\Text('e_mail','E-mail', array('searchType' => '%like%')), 
                            new Filter\Type\Select('active','Активирована?', array(
                                '' => t('Не важно'),
                                '1' => t('Активна'),
                                '0' => t('Не активна'),
                            )),
                            new Filter\Type\Select('is_partner_card','Это карта партнера?', array(
                                '' => t('Не важно'),
                                '1' => t('Да'),
                                '0' => t('Нет'),
                            )),
                            new Filter\Type\Date('active_date','Дата активации', array('ShowType' => true)),
                            new Filter\Type\Text('amount','Количество бонусов', array('attr' => array('class' => 'w50'))),
                            new \Bonuses\Model\Filters\Type\Partner('partner_id','Партнер', array('attr' => array('class' => 'w50'))),

                        )
                    )),
                )
            )),
        )));
        
        return $helper;
    }


    /**
     * Деактивирование карты
     */
    function actionDeActivate()
    {
        $id = $this->url->get('id', TYPE_INTEGER, 0);
        /**
         * @var \Bonuses\Model\Orm\BonusCard $card
         */
        $card = new \Bonuses\Model\Orm\BonusCard($id);

        $card['active'] = 0;
        $card->update();

        return $this->result->setSuccess(true)->addMessage(t('Карта деактивирована'));
    }


    /**
     * Активирование карты
     */
    function actionActivate()
    {
        $id = $this->url->get('id', TYPE_INTEGER, 0);
        /**
         * @var \Bonuses\Model\Orm\BonusCard $card
         */
        $card = new \Bonuses\Model\Orm\BonusCard($id);

        $card['active'] = 1;
        $card->update();

        return $this->result->setSuccess(true)->addMessage(t('Карта активирована'));
    }
    
    function actionAdd($primaryKey = null, $returnOnSuccess = false, $helper = null)
    {
        if (!$primaryKey){
            $obj = $this->api->getElement();
            $obj['user_id'] = $this->url->get('user_id', TYPE_INTEGER, 0);
        }

        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать бонусную карту {card_id}') : t('Добавить бонусную карту'));
        
        return parent::actionAdd($primaryKey, $returnOnSuccess, $helper);
    }
    
}


