<?php

namespace Bonuses\Model\Orm;
use Alerts\Model\Manager;
use Bonuses\Model\BonusCardApi;
use Bonuses\Model\Notice\CardActivated;
use \RS\Orm\Type;

/**
* Объект - Бонусная карта
*/
class BonusCard extends \RS\Orm\OrmObject
{
    protected static
        $table = 'bonuses_bonus_cards';

    protected $before_orm;
        
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),         
                'user_id' => new Type\User(array(
                    'description' => t('Пользователь'),
                    'index' => true,
                    'maxLength' => 11
                )),
                'card_id' => new Type\Varchar(array(
                    'description' => t('Номер карты'),
                    'Checker' => array('chkEmpty', 'Укажите номер карты'),
                    'maxLength' => 150,
                )),
                'e_mail' => new Type\Varchar(array(
                    'description' => t('E-mail'),
                    'hint' => t('Если не заполнено, то будет использован E-mail пользователя'),
                    'maxLength' => 20,
                )),
                'amount' => new Type\Integer(array(
                    'description' => t('Количество бонусов'),
                    'default' => 0,
                    'maxLength' => 11,
                )),
                'active' => new Type\Integer(array(
                    'description' => t('Активирована?'),
                    'maxLength' => 1,
                    'checkboxview' => array(1,0),
                    'default' => 0,
                )),
                'is_partner_card' => new Type\Integer(array(
                    'description' => t('Это карта партнера?'),
                    'maxLength' => 1,
                    'checkboxview' => array(1,0),
                    'default' => 0,
                )),
                'active_date' => new Type\Date(array(
                    'description' => t('Дата активации'),
                    'allownull' => true
                )),
                'activation_bonus_use' => new Type\Integer(array(
                    'description' => t('Бонусы начислены партнеру за активацию?'),
                    'visible' => false,
                    'hidden' => true,
                    'maxLength' => 1,
                    'checkboxview' => array(1,0),
                    'default' => 0,
                )),
                'use_bonus' => new Type\Integer(array(
                    'description' => t('Списать бонусы?'),
                    'runtime' => true,
                    'visible' => false,
                    'hidden' => true,
                    'maxLength' => 1,
                    'checkboxview' => array(1,0),
                    'default' => 0,
                )),
                'partner_id' => new Type\User(array(
                    'maxLength' => 11,
                    'default' => 0,
                    'index' => true,
                    'description' => t('Партнер бонусных карт')
                )),
                'create_date' => new Type\Date(array(
                    'description' => t('Дата создания'),
                    'hidden' => true,
                    'visible' => false,
                    'allownull' => true
                ))
        ));
        $this->addIndex(array('card_id'), self::INDEX_UNIQUE);
    }

    /**
     * Действия перед записью
     *
     * @param string $flag - insert или update
     * @return null
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    function beforeWrite($flag){
        if ($flag == $this::INSERT_FLAG){
            $this['create_date'] = date('Y-m-d'); //Запишем дату создания
        }

        $this->before_orm = new self((int)$this['id']);

        if ($this['active'] && !$this->before_orm['active'] && empty($this['active_date'])){
            $this['active_date'] = date('Y-m-d');
        }

        if (!empty($this['card_id'])){
            $this['card_id'] = trim(str_replace(' ', '', $this['card_id']));
        }

        $user = $this->getUser();
        if ($this['e_mail'] && !$this['user_id']){ //Если первая запись и пользователь неизвестен
            $user_id = \RS\Orm\Request::make()
                ->from(new \Users\Model\Orm\User())
                ->where(array(
                    'e_mail' => $this['e_mail']
                ))->exec()
                ->getOneField('id', false);
            if ($user_id){
                $this['user_id'] = $user_id;
            }
        }
        
        /**
        * Если известен пользователь, но не известен E-mail
        */
        if ($this['user_id'] && !$this['e_mail']){
            $this['e_mail'] = $user['e_mail'];
        }
        
        //Если карта активна и идёт флаг списать бонусы
        if ($this['use_bonus'] && $this['amount']){
            $api = new \Bonuses\Model\BonusApi();
            $api->addBonusesTransaction($user, $this['amount'], t('Зачисление бонусов с бонусной карты %0', array($this['card_id'])));
            $this['amount']  = 0;
        }

        if ($flag == $this::INSERT_FLAG){
            $current_user = $this->getUser();
            if ($current_user['is_bonuscard_partner']){
                $this['is_partner_card'] = 1;
            }
        }
    }

    /**
     * Действия перед записью
     *
     * @param string $flag - insert или update
     * @return null
     */
    function afterWrite($flag)
    {
        if ($this['active'] && !$this->before_orm['active']){
            //Отправим уведомление, что карта пользователя активирована
            $notice = new CardActivated();
            $notice->init($this);
            Manager::send($notice);

            //Начислим партнеру полагающиеся бонусы
            if ($this['partner_id']){
                $api = new BonusCardApi();
                $api->addBonusesToPartnerForActivatedCard($this);
            }
        }
    }


    /**
     * Возвращает номер карты форматированный
     *
     * @return string
     */
    function getCardId()
    {
        $len = mb_strlen($this['card_id']);
        if ($len > 6){
            return implode(" ", str_split($this['card_id'], 4));
        }
        return $this['card_id'];
    }
    
    /**
    * Возвращает объект пользователя
    * 
    * @return \Users\Model\Orm\User
    */
    function getUser()
    {
        return new \Users\Model\Orm\User($this['user_id']);
    }

    /**
     * Возвращает объект партнера бонусных карт
     *
     * @return \Users\Model\Orm\User
     */
    function getPartner()
    {
        return new \Users\Model\Orm\User($this['partner_id']);
    }


    /**
     * Возвращает E-mail для уведомлений
     *
     * @return string
     */
    function getEmail()
    {
        $user = $this->getUser();
        return $this['e_mail'] ? $this['e_mail'] : $user['e_mail'];
    }
}

