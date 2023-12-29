<?php
namespace Bonuses\Controller\Admin;

/**
* Содержит действия по обслуживанию
*/                       
class Tools extends \RS\Controller\Admin\Front
{
    /**
     * @var \Users\Model\Api $users_api
     */
    public $users_api;

    function actionUpdateUsersPrices()
    {
        $api = new \Bonuses\Model\BonusDiscountApi();
        $count = $api->updateUsersOrdersPriceTypes();
        
        return $this->result->setSuccess(true)->addMessage(t('Сведения обновлены'));
    }
    
    function actionUpdateRegisteredUsersPrices()
    {
        $api = new \Bonuses\Model\BonusDiscountApi();
        $count = $api->updateRegisteredUsersPriceTypes();
        
        return $this->result->setSuccess(true)->addMessage(t('Сведения обновлены'));
    }

    /**
     * Возвращает список пользователей, которые соответствуют условиям
     *
     * @param string $term - строка поиска
     * @param array $fields - поля, по которым осущесвлять частичный поиск
     * @return \Users\Model\Orm\User[] $user
     * @throws \RS\Orm\Exception
     */
    private function getPartnersLike($term, array $fields)
    {
        $q = \RS\Orm\Request::make();
        $words = explode(" ", $term);
        if (count($words)==1){
            foreach ($fields as $field) {
                $q->where("$field like '%#term%'", array('term' => $term), 'OR');
            }
        }else{ //Если несколько полей, проверяем по ФИО
            foreach ($words as $word) {
                if (!empty($word)){
                    $q->where("CONCAT(`surname`, `name`, `midname`) like '%#term%'", array('term' => $word), 'AND');
                }
            }
        }

        $q->where = "(".$q->where.")";

        $q->from(new \Users\Model\Orm\User())
            ->where(array(
                'is_bonuscard_partner' => 1
            ));

        return $q->objects();
    }

    /**
     * Возвращает партнера по E-mail
     *
     * @return false|string
     * @throws \RS\Db\Exception
     */
    function actionAjaxPartnerEmail()
    {
        $term = $this->url->request('term', TYPE_STRING);
        $list = $this->getPartnersLike($term, array('login', 'surname', 'name', 'company', 'company_inn', 'phone'));
        $json = array();
        $i=0;
        foreach ($list as $user)
        {
            if ($i >= 5) break;
            $i= $i+1;
            $json[] = array(
                'label' => $user['surname'].' '.$user['name'].' '.$user['midname'],
                'id' => $user['id'],
                'email' => $user['e_mail'],
                'desc' => t('Логин').':'.$user['login'].
                    ($user['company'] ? t(" ; {$user['company']}(ИНН:{$user['company_inn']})") : '')
            );

        }


        return json_encode($json);
    }
}