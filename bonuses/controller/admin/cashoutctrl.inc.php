<?php
namespace Bonuses\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Filter,
    \RS\Html\Table;

/**
* Контроллер. средствавывода
*/
class CashoutCtrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        parent::__construct(new \Bonuses\Model\CashoutApi());
    }
    
    function helperIndex()
    {        
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Запросы на вывод средств'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить запрос'))));
        $helper->setBottomToolbar($this->buttons(array('delete')));
        $helper->setListFunction('getList');
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => 20))),
                new TableType\Viewed(null, $this->api->getMeterApi()),
                new TableType\User('partner_id', t('Партнер'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Text('amount', t('Сумма'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\StrYesno('enrolled', t('Средства зачислены?'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Datetime('dateof', t('Дата создания'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Datetime('dateof_enrolled', t('Дата зачисления'), array('LinkAttr' => array('class' => 'crud-edit'), 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
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
                            new \Bonuses\Model\Filters\Type\Partner('partner_id', t('Партнер')),
                            new Filter\Type\Text('amount', t('Сумма'), array('showtype' => true)),
                            new Filter\Type\Select('enrolled', t('Средства зачислены?'), array(
                                '' => t('-Не важно-'),
                                '1' => t('Да'),
                                '0' => t('Нет'),
                            )),
                            new Filter\Type\DateRange('dateof',t('Дата создания')),
                            new Filter\Type\DateRange('dateof_enrolled',t('Дата зачисления')),
                        )
                    )),
                )
            )),
            'Caption' => t('Поиск')
        )));
        
        return $helper;
    }
    
}


