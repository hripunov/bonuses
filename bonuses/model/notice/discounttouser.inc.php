<?php
namespace Bonuses\Model\Notice;
/**
* Уведомление - Применение цены со скидкой (пользователю)
*/
class DiscountToUser extends \Alerts\Model\Types\AbstractNotice
    implements \Alerts\Model\Types\InterfaceEmail
{
    public
        $user,
        $bonuses;

    public function getDescription()
    {
        return t('Применение цены со скидкой (пользователю)');
    } 
    
    /**
    * Инициализация уведомления
    *         
    * @param \Users\Model\Orm\User $user - пользователь
    * @param \Catalog\Model\Orm\Typecost $user_cost - Тип цены пользователя
    * @return void
    */
    function init($user, $user_cost)
    {
        $this->user = $user;
        $this->user_cost = $user_cost;
    }
    
    function getNoticeDataEmail()
    {
        
        $notice_data          = new \Alerts\Model\Types\NoticeDataEmail();
        $notice_data->email   = $this->user['e_mail'];
        $notice_data->subject = t('Ваша цена со скидкой в %0', array(\RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars    = $this;
        
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%bonuses%/notice/discounttouser.tpl';
    }
}

