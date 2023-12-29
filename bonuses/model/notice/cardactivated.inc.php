<?php
namespace Bonuses\Model\Notice;
/**
* Уведомление - Карта пользователя активирована (пользователю)
*/
class CardActivated extends \Alerts\Model\Types\AbstractNotice
    implements \Alerts\Model\Types\InterfaceSms
{
    /**
     * @var \Bonuses\Model\Orm\BonusCard $card
     */
    public $card;
    /**
     * @var \Users\Model\Orm\User $user
     */
    public $user;

    public function getDescription()
    {
        return t('Карта пользователя активирована (пользователю)');
    } 
    
    /**
    * Инициализация уведомления
    *         
    * @param \Bonuses\Model\Orm\BonusCard $card  - бонусная карта
    * @return void
    */
    function init($card)
    {
        $this->card = $card;
        $this->user = $card->getUser();
    }
    
    function getNoticeDataEmail()
    {
        
        $notice_data          = new \Alerts\Model\Types\NoticeDataEmail();
        $notice_data->email   = $this->card->getEmail();
        $notice_data->subject = t('Ваша бонусная карта активирована %0', array(\RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars    = $this;
        
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%bonuses%/notice/cardactivated.tpl';
    }

    function getNoticeDataSms()
    {
        
        $notice_data = new \Alerts\Model\Types\NoticeDataSms();

        if (!$this->user['phone']){
            return false;
        }
        
        $notice_data->phone     = $this->user['phone'];
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateSms()
    {
        return '%bonuses%/notice/cardactivated_sms.tpl';
    }
}

