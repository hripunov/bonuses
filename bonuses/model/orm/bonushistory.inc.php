<?php

namespace Bonuses\Model\Orm;

use Bonuses\Model\BonusApi;
use RS\Orm\OrmObject;
use \RS\Orm\Type;
use Users\Model\Orm\User;

/**
 * Объект - История начислений бонусов
 */
class BonusHistory extends OrmObject
{
    protected static
        $table = 'bonuses_history';

    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
            'user_id' => new Type\User(array(
                'description' => t('Пользователь'),
                'index' => true,
                'maxLength' => 11,
                'Checker' => array('chkEmpty', 'Укажите пользователя'),
            )),
            'amount' => new Type\Integer(array(
                'description' => t('Количество бонусов'),
                'Checker' => array('chkEmpty', 'Укажите количество бонусов'),
                'maxLength' => 11,
            )),
            'reason' => new Type\Varchar(array(
                'description' => t('Название операции'),
                'Checker' => array('chkEmpty', 'Укажите название операции'),
            )),
            'dateof' => new Type\Datetime(array(
                'description' => t('Дата'),
                'index' => true,
            )),
            'extra' => new Type\Varchar(array(
                'description' => t('Доп. данные'),
            )),
            'writeoff' => new Type\Integer(array(
                'description' => t('Тип операции'),
                'runtime' => true,
                'maxLength' => 1,
                'listFromArray' => array(array(
                    1 => t('Начислить'),
                    0 => t('Списать'),
                ))
            )),
            'notify1' => new Type\Integer(array(
                'description' => t('Отправлено первое уведомление'),
                'default' => 0,
                'checkboxview' => array(1, 0)
            )),
            'notify2' => new Type\Integer(array(
                'description' => t('Отправлено второе уведомление'),
                'default' => 0,
                'checkboxview' => array(1, 0)
            )),
        ));

        $this->addIndex(array('user_id', 'amount', 'dateof'), self::INDEX_UNIQUE);
    }

    /**
     * Действия перед записью данных
     *
     * @param string $flag - insert или update
     */
    function beforeWrite($flag)
    {
        if ($flag == self::INSERT_FLAG && empty($this['dateof'])) {
            $this['dateof'] = date("Y-m-d H:i:s");
        }

        if ($this->isModified('writeoff') && !$this['writeoff']) { //Если флаг списания сразу стоит
            $user = $this->getUser();
            $this['amount'] = -$this['amount'];
            $abs_amount = abs($this['amount']);

            if ($user['bonuses'] < $abs_amount) { //Проверим можно ли списать сумму с балланса
                $this->addError(t('Невозможно списать бонусы (в количестве - %0). Недостаточно бонусов на баллансе.', array(
                    $abs_amount
                )));
                return false;
            }
        }
    }

    /**
     * Действия после записи данных
     *
     * @param string $flag - insert или update
     * @throws \RS\Db\Exception
     */
    function afterWrite($flag)
    {
        if ($this->isModified('writeoff')) { //Если флаг списания сразу стоит
            $api = new BonusApi();
            $api->writeOffUserBonusBalance($this->getUser(), $this['amount']);
        }
    }

    function getUser()
    {
        return new User($this['user_id']);
    }
}

