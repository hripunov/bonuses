<?php
namespace Bonuses\Model;

class BonusesHistoryApi extends \RS\Module\AbstractModel\EntityList
{
    
    
    function __construct()
    {
        parent::__construct(new \Bonuses\Model\Orm\BonusHistory(),
        array(
            'multisite' => false,
            'nameField' => 'amount',
            'defaultOrder' => 'dateof DESC'
        ));
    }    
    
    
    
}

