<?php
namespace Bonuses\Model\ExternalApi\Bonuses;

use Bonuses\Model\Orm\BonusHistory;
use ExternalApi\Model\AbstractMethods\AbstractGet;
use ExternalApi\Model\Exception as ApiException;
use ExternalApi\Model\Utils;
use Users\Model\Orm\User;

/**
* Получает баланс бонусов пользователя
*/
class GetBonusesByUserXmlId extends AbstractGet
{
    /**
     * Получает баланс бонусов пользователя по xml_id пользователя
     *
     * @param string $token Авторизационный токен
     * @param string $xml_id xml_id пользователя в системе ReadyScript
     * @return array
     * @throws ApiException
     * @throws \RS\Exception
     * @example GET /api/methods/bonuses.getBonusesByUserXmlId?token=b45d2bc3e7149959f3ed7e94c1bc56a2984e6a86&xml_id=21196
     *
     * Ответ:
     * <pre>
     *     "response": {
     *          "user_bonuses": 10000000,
     *          "user": {
     *              "id": 21196,
     *              "name": "Иван",
     *              "surname": "Иванов",
     *              "midname": "Иванович",
     *              "fio": "Иванов Иван Иванович",
     *              "e_mail": "ivanov@ya.ru",
     *              "login": "ivanov@ya.ru",
     *              "phone": "7999999999",
     *              "is_company": 0,
     *              "balance": 0,
     *              "company": "ООО Просвет",
     *              "company_inn": "8966520324481",
     *              "registration_ip": "127.0.0.1",
     *              "last_ip": "127.0.0.1",
     *              "creator_app_id": 0
     *          },
     *      }
     * </pre>
     *
     */
    protected function process($token, $xml_id)
    {
        if ($admin_user = $this->token->getUser()) {
            if (!$admin_user->isAdmin()){
                throw new ApiException(t('Для получения информации пользователь должен быть администратором'), ApiException::ERROR_WRITE_ERROR);
            }

            $user = \RS\Orm\Request::make()
                        ->from(new User())
                        ->where([
                            'xml_id' => $xml_id
                        ])->object();

            if (empty($user['id'])){
                throw new ApiException(t('Пользователь с данным XML_ID не найден'), ApiException::ERROR_WRITE_ERROR);
            }

            $result['response']['user_bonuses'] = $user->getUserBonuses();
            $result['response']['user'] = Utils::extractOrm($user);

            return $result;
        }

        throw new ApiException(t('Не удалось получить пользователя'), ApiException::ERROR_WRITE_ERROR);
    }

    public function getOrmObject()
    {
        return new BonusHistory();
    }
}
