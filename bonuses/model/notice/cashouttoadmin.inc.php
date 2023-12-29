<?php
namespace Bonuses\Model\Notice;
/**
* Уведомление - Зачисление бонусов (пользователю)
*/
class CashoutToAdmin extends \Alerts\Model\Types\AbstractNotice
    implements \Alerts\Model\Types\InterfaceEmail, \Alerts\Model\Types\InterfaceSms
{
    public
        $user,
        $bonuses;

    public function getDescription()
    {
        return t('Запрос на вывод средств (админстратору, бонусная программа)');
    } 
    
    /**
    * Инициализация уведомления
    *         
    * @param \Users\Model\Orm\User $partner  - пользователь партнер
    * @param integer $amount  - количество средств на вывод
    * @return void
    */
    function init($partner, $amount)
    {
        $this->partner = $partner;
        $this->amount  = $amount;
        
    }
    
    function getNoticeDataEmail()
    {
        $config = \RS\Config\Loader::getSiteConfig();

        $bonus_config = \RS\Config\Loader::byModule($this);

        $notice_data = new \Alerts\Model\Types\NoticeDataEmail();
        $notice_data->email     = $bonus_config['partner_cashbackout_email'] ? $bonus_config['partner_cashbackout_email'] : $config['admin_email'];
        $notice_data->subject   = t('Запрос на вывод средств партнером из бонусной программа %0', array(\RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%bonuses%/notice/cachout_toadmin.tpl';
    }

    function getNoticeDataSms()
    {
        $config = \RS\Config\Loader::getSiteConfig();
        if(!$config['admin_phone']) return;
        $notice_data = new \Alerts\Model\Types\NoticeDataSms();

        $notice_data->phone     = $config['admin_phone'];
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateSms()
    {
        return '%bonuses%/notice/cachout_toadmin_sms.tpl';
    }
}

