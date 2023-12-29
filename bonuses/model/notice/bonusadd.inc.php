<?php
namespace Bonuses\Model\Notice;
/**
* Уведомление - Зачисление бонусов (пользователю)
*/
class BonusAdd extends \Alerts\Model\Types\AbstractNotice
    implements \Alerts\Model\Types\InterfaceEmail, \Alerts\Model\Types\InterfaceSms
{
    public
        $user,
        $bonuses;

    public function getDescription()
    {
        return t('Зачисление бонусов (пользователю)');
    } 
    
    /**
    * Инициализация уведомления
    *         
    * @param \Users\Model\Orm\User $user  - пользователь
    * @param integer $bonuses  - количество бонусов
    * @return void
    */
    function init($user, $bonuses)
    {
        $this->user = $user;
        $this->bonuses = $bonuses;
        
    }
    
    function getNoticeDataEmail()
    {
        
        $notice_data = new \Alerts\Model\Types\NoticeDataEmail();
        $notice_data->email     = $this->user['e_mail'];
        $notice_data->subject   = t('Зачисление бонусов на сайте %0', array(\RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%bonuses%/notice/bonusadd.tpl';
    }

    function getNoticeDataSms()
    {
        
        $notice_data = new \Alerts\Model\Types\NoticeDataSms();
        
        
        $notice_data->phone     = $this->user['phone'];
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateSms()
    {
        return '%bonuses%/notice/bonusadd_sms.tpl';
    }
}

