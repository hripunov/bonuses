<?php
namespace Bonuses\Model\Filters\Type;

/**
 * Фильтр по пользователю партнеру. Отображается в виде поля c autocomplete.
 */
class Partner extends \RS\Html\Filter\Type\User
{
    function __construct($key, $title, $options = array())
    {
        $this->attr = array(
            'class' => 'w150'
        );
        parent::__construct($key, $title, $options);
        @$this->attr['class'] .= ' object-select';
    }

    /**
     * Возвращает текстовое значение фильтра
     *
     * @return string
     */
    function getTextValue()
    {
        $user = new \Users\Model\Orm\User($this->getValue());
        return $user->getFio();
    }

    /**
     * Возвращает URL для поиска пользователя
     *
     * @return string
     */
    function getRequestUrl()
    {
        return $this->request_url ?: \RS\Router\Manager::obj()->getAdminUrl('ajaxPartnerEmail', null, 'bonuses-tools');
    }

    /**
     * Устанавливает URL для поиска пользователя
     *
     * @param string $url - адрес
     * @return $this|\RS\Html\Filter\Type\User
     */
    function setRequestUrl($url)
    {
        $this->request_url = $url;
        return $this;
    }
}