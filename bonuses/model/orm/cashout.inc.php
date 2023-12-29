<?php
namespace Bonuses\Model\Orm;
use RS\Orm\OrmObject;
use RS\Orm\Type;

/**
* Объект - Запрос на вывод средств
*/
class Cashout extends OrmObject
{
    protected static
        $table = 'bonuses_cashout';

    protected $before_orm;

    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),         
                'site_id' => new Type\CurrentSite(),
                'partner_id' => new Type\User(array(
                    'description' => t('Партнер'),
                    'index' => true,
                    'maxLength' => 11,
                )),
                'amount' => new Type\Integer(array(
                    'description' => t('Количество средств для вывода'),
                    'maxLength' => 11,
                )),
                'enrolled' => new Type\Integer(array(
                    'description' => t('Средства зачислены?'),
                    'maxLength' => 1,
                    'default' => 0,
                    'checkboxview' => array(1, 0)
                )),
                'dateof' => new Type\Date(array(
                    'description' => t('Дата создания'),
                )),
                'dateof_enrolled' => new Type\Datetime(array(
                    'description' => t('Дата зачисления'),
                ))
        ));

        $this->addIndex(array('site_id', 'partner_id', 'enrolled'), self::INDEX_KEY);
    }

    /**
     * Действия перед записью
     *
     * @param string $save_flag - insert или update
     *
     * @return bool|false|null
     */
    function beforeWrite($save_flag)
    {
        if (empty($this['dateof']) || $this['dateof'] == '0000-00-00'){
            $this['dateof'] = date('Y-m-d');
        }

        if (empty($this['dateof_enrolled']) || $this['dateof_enrolled'] == '0000-00-00'){
            $this['dateof_enrolled'] = null;
        }

        $partner = $this->getPartner();
        $bonuses = $partner->getUserBonuses();
        if ($bonuses < $this['amount']){
            $this->addError(t('Не хватает бонусов у партнера. Текущее количество бонусов %0.', array($bonuses)));
            return false;
        }

        $this->before_orm = new self((int)$this['id']);
    }

    /**
     * Действия после записи
     *
     * @param string $save_flag - insert или update
     *
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    function afterWrite($save_flag)
    {
        if ($save_flag == $this::INSERT_FLAG){
            //Отправим уведомление администратору
            $notice = new \Bonuses\Model\Notice\CashoutToAdmin();
            $notice->init($this->getPartner(), $this['amount']);
            \Alerts\Model\Manager::send($notice);
        }

        if (!$this->before_orm['enrolled'] && $this['enrolled']){
            $bonusapi = new \Bonuses\Model\BonusApi();
            $bonusapi->addBonusesTransaction($this->getPartner(), $this['amount'], t('Списание бонусов из-за вывода средств'), false);
            $this['dateof_enrolled'] = date('Y-m-d H:i:s');
        }
    }

    /**
     * Возвращает объект партнера
     *
     * @return \Users\Model\Orm\User
     */
    function getPartner()
    {
        return new \Users\Model\Orm\User($this['partner_id']);
    }

}

