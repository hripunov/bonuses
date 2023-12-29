<?php
namespace Bonuses\Model\CsvSchema;
use \RS\Csv\Preset;

/**
* Схема экспорта/импорта справочника цен в CSV
*/
class BonusCard extends \RS\Csv\AbstractSchema
{
    function __construct()
    {
        parent::__construct(
            new Preset\Base(array(
                'ormObject' => new \Bonuses\Model\Orm\BonusCard(),
                'nullFields' => array('xml_id'),
                'excludeFields' => array(
                    'id', 'user_id'
                ),
                'savedRequest' => \Bonuses\Model\BonusCardApi::getSavedRequest('Bonuses\Controller\Admin\BonusCardCtrl_list'), //Объект запроса из сессии с параметрами текущего просмотра списка
                'multisite' => false,
                'searchFields' => array('card_id')
            )),
            array(
                new Preset\LinkedTable(array(
                    'ormObject' => new \Users\Model\Orm\User(),
                    'fields' => array('surname', 'name', 'midname'),
                    'titles' => array(
                        'surname' => t('Фамилия'),
                        'name' => t('Имя'),
                        'midname' => t('Отчество'),
                    ),
                    'idField' => 'id',
                    'multisite' => false,                
                    'linkForeignField' => 'user_id',
                    'linkPresetId' => 0,
                    'linkDefaultValue' => 0
                )),
            )
        );
    }
}