<?php
namespace Bonuses\Model\Notice;
/**
* Уведомление - Сколько осталось бонусов у пользователя (пользователю)
*/
class NotUsedBonuses extends \Alerts\Model\Types\AbstractNotice
    implements \Alerts\Model\Types\InterfaceEmail, \Alerts\Model\Types\InterfaceSms
{
    public
        $user,
        $bonuses,
        $last_date,
        $last_days,
        $notify_time;

    public function getDescription()
    {
        return t('Сколько осталось бонусов у пользователя (пользователю)');
    } 
    
    /**
    * Инициализация уведомления
    *         
    * @param \Users\Model\Orm\User $user  - пользователь
    * @param integer $bonuses - количество бонусов
    * @param string $last_date - дата списания бонусов
    * @param integer $notify_time - какой раз уведомления
    * @return void
    */
    function init($user, $bonuses, $last_date, $notify_time)
    {
        $this->user    = $user;
        $this->bonuses = $bonuses;

        $this->last_date   = $last_date;
        $this->last_days   = floor((strtotime($last_date) - time())/(60 * 60 * 24));
        $this->notify_time = $notify_time;
    }
    
    function getNoticeDataEmail()
    {
        
        $notice_data = new \Alerts\Model\Types\NoticeDataEmail();
        $notice_data->email     = $this->user['e_mail'];
        $notice_data->subject   = t('У Вас осталась скидка на сайте %0', array(\RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%bonuses%/notice/notusedbonuses.tpl';
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
        return '%bonuses%/notice/notusedbonuses_sms.tpl';
    }
}

