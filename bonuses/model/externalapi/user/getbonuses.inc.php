<?php
namespace Bonuses\Model\ExternalApi\User;

use Bonuses\Model\BonusesHistoryApi;
use ExternalApi\Model\AbstractMethods\AbstractGetList;
use ExternalApi\Model\Exception as ApiException;

/**
* Получает баланс бонусов пользователя
*/
class GetBonuses extends AbstractGetList
{
    /**
     * Возвращает объект выборки объектов
     *
     * @return BonusesHistoryApi
     */
    public function getDaoObject()
    {
        $dao = new BonusesHistoryApi();
        if ($user = $this->token->getUser()) {
            $dao->setFilter('user_id', $user->id);
        }
        return $dao;
    }

    /**
     * Возвращает возможные значения для сортировки
     *
     * @return array
     */
    public function getAllowableOrderValues()
    {
        return ['id', 'id desc', 'dateof', 'dateof desc'];
    }

    /**
     * Получает баланс бонусов пользователя
     *
     * @param string $token Авторизационный токен
     * @return array
     * @throws ApiException
     * @throws \RS\Exception
     * @example GET /api/methods/order.getBonuses?token=b45d2bc3e7149959f3ed7e94c1bc56a2984e6a86
     *
     * Ответ:
     * <pre>
     *     "response": {
     *          "user_bonuses": 10000000,
     *      }
     * </pre>
     *
     */
    protected function process($token, $filter = [], $sort = 'id desc', $page = '1', $pageSize = '20', $sections = ['user_bonuses', 'bonuses_list'])
    {
        if ($user = $this->token->getUser()) {
            if (in_array('bonuses_list', $sections)) {
                $result = parent::process($token, $filter, $sort, $page, $pageSize);
            }

            if (in_array('user_bonuses', $sections)) {
                $result['response']['user_bonuses'] = $user->getUserBonuses();
            }
            return $result;
        }
    }
}
