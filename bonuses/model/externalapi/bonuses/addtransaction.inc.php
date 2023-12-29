<?php
namespace Bonuses\Model\ExternalApi\Bonuses;

use Bonuses\Model\BonusApi;
use Bonuses\Model\Orm\BonusHistory;
use ExternalApi\Model\AbstractMethods\AbstractAdd;
use ExternalApi\Model\Exception as ApiException;
use ExternalApi\Model\Utils;
use ExternalApi\Model\Validator\ValidateArray;
use Users\Model\Orm\User;

/**
* Получает баланс бонусов пользователя
*/
class AddTransaction extends AbstractAdd
{
    /**
     * Форматирует комментарий, полученный из PHPDoc
     *
     * @param string $text - комментарий
     * @return string
     */
    protected function prepareDocComment($text, $lang)
    {
        $text = parent::prepareDocComment($text, $lang);

        //Валидатор для пользователя
        $validator = $this->getCommentValidator();
        $text = preg_replace_callback('/\#data-comment/', function() use($validator) {
            return $validator->getParamInfoHtml();
        }, $text);


        return $text;
    }

    /**
     * Возвращает валидатор для комментария
     *
     * @return ValidateArray
     */
    private function getCommentValidator()
    {
        return new ValidateArray([
            'user_id' => [
                '@title' => t('Пользователь'),
                '@type' => 'integer',
                '@require' => true,
            ],
            'amount' => [
                '@title' => t('Количество бонусов'),
                '@type' => 'integer',
                '@require' => true,
            ],
            'reason' => [
                '@title' => t('Название операции'),
                '@type' => 'string',
                '@require' => true,
            ],
            'extra' => [
                '@title' => t('Доп. данные'),
                '@type' => 'string',
                '@require' => false,
            ],
        ]);
    }

    /**
     * Создаёт бонусную транзакцию
     *
     * @param array $data поля транзакции для сохранения #data-comment
     * @param string $client_name имя клиентского приложения
     * @param string $client_id id клиентского приложения
     * @param string $token Авторизационный токен
     * @return array
     * @throws ApiException
     * @throws \RS\Exception
     * @example POST /api/methods/bonuses.addTransaction?data[user_id]=21196&data[amount]=100&data[reason]=Начисление от продажи пользователем&data[extra]=Доп. данные, если есть&token=b45d2bc3e7149959f3ed7e94c1bc56a2984e6a86
     *
     * Ответ:
     * <pre>
     *     "response": {
     *          "success": true,
     *          "transaction": {
     *              "id": 1,
     *              "user_id": 1,
     *              "amount": 100,
     *              "reason": "Начисление от продажи пользователем",
     *              "dateof": "2024-01-01 11:41:53",
     *              "extra": "Доп. данные, если есть",
     *              "notify1": 0,
     *              "notify2": 0,
     *          }
     *  }
     * </pre>
     */
    protected function process($data, $client_name, $client_id, $token = null)
    {
        if (!$this->token){
            throw new ApiException(t('Токен обязателен'), ApiException::ERROR_WRITE_ERROR);
        }

        $user = $this->token->getUser();
        if (!$user->isAdmin()){
            throw new ApiException(t('Для записи пользователь должен быть администратором'), ApiException::ERROR_WRITE_ERROR);
        }

        $user = new User($data['user_id']);
        if (empty($user['id'])){
            throw new ApiException(t('Пользователь не найден'), ApiException::ERROR_WRITE_ERROR);
        }
        if (empty((int)$data['amount'])){
            throw new ApiException(t('Не указано количество'), ApiException::ERROR_WRITE_ERROR);
        }

        $bonusesApi = new BonusApi();
        if ($bonusesApi->addBonusesTransaction($user, abs((int)$data['amount']), $data['reason'], $data['amount'] > 0, false, $data['extra'] ?: 'fromApi')){
            $bonusHistory = $bonusesApi->last_history_row;
            return [
                'response' => [
                    'success' => true,
                    'transaction' => Utils::extractOrm($bonusHistory),
                    'user' => Utils::extractOrm($user),
                    'afterUserBonuses' => $user->getBonuses(),
                ]
            ];
        }

        throw new ApiException(t('Не удалось сохранить сущность транзакции'), ApiException::ERROR_WRITE_ERROR);
    }

    /**
     * Возвращает объект комментария
     *
     * @return BonusHistory
     */
    public function getOrmObject()
    {
        return new BonusHistory();
    }
}
