<?php
namespace Bonuses\Model\ExternalApi\Bonuses;

use Bonuses\Model\Orm\BonusCard;
use Comments\Model\Orm\Comment;
use ExternalApi\Model\AbstractMethods\AbstractAdd;
use ExternalApi\Model\Exception as ApiException;
use ExternalApi\Model\Utils;
use ExternalApi\Model\Validator\ValidateArray;
use Users\Model\Orm\User;

/**
* Получает баланс бонусов пользователя
*/
class AddBonusCard extends AbstractAdd
{
    public $use_post_keys = ['user_id', 'card_id', 'e_mail', 'amount', 'active', 'is_partner_card', 'activation_bonus_use', 'partner_id'];

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
            'card_id' => [
                '@title' => t('Номер карты'),
                '@type' => 'string',
                '@require' => true,
            ],
            'e_mail' => [
                '@title' => t('E-mail'),
                '@type' => 'string',
                '@require' => false,
            ],
            'amount' => [
                '@title' => t('Количество бонусов'),
                '@type' => 'integer',
                '@require' => true,
            ],
            'active' => [
                '@title' => t('Активирована?'),
                '@type' => 'integer',
                '@require' => true,
            ],
            'is_partner_card' => [
                '@title' => t('Это карта партнера?'),
                '@type' => 'integer',
                '@require' => false,
            ],
            'activation_bonus_use' => [
                '@title' => t('Бонусы начислены партнеру за активацию?'),
                '@type' => 'integer',
                '@require' => false,
            ],
            'partner_id' => [
                '@title' => t('Бонусы начислены партнеру за активацию?'),
                '@type' => 'integer',
                '@require' => false,
            ]
        ]);
    }

    /**
     * Создаёт бонусную карту
     *
     * @param array $data поля транзакции для сохранения #data-comment
     * @param string $client_name имя клиентского приложения
     * @param string $client_id id клиентского приложения
     * @param string $token Авторизационный токен
     * @return array
     * @throws ApiException
     * @throws \RS\Exception
     * @example POST /api/methods/bonuses.addBonusCard?data[user_id]=21196&data[amount]=100&data[card_id]=12345 64564 56466 77654&data[e_mail]=example@ya.ru&token=b45d2bc3e7149959f3ed7e94c1bc56a2984e6a86
     *
     * Ответ:
     * <pre>
     *     "response": {
     *          "success": true,
     *          "bonuscard": {
     *              "id": 1,
     *              "user_id": 1,
     *              "card_id": "12345 64564 56466 77654",
     *              "amount": 100,
     *              "e_mail": "example@ya.ru",
     *              "active": 1,
     *              "is_partner_card": 0,
     *              "active_date": "2024-01-01",
     *              "activation_bonus_use": 0,
     *              "partner_id": 0,
     *          }
     *  }
     * </pre>
     */
    protected function process($data, $client_name, $client_id, $token = null)
    {
        $save_data = $this->prepareData($data);

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
        if (empty((int)$data['card_id'])){
            throw new ApiException(t('Не указан номер карты'), ApiException::ERROR_WRITE_ERROR);
        }
        if (empty((int)$data['amount'])){
            throw new ApiException(t('Не указано количество'), ApiException::ERROR_WRITE_ERROR);
        }

        $this->object = $this->getOrmObject();
        if ($this->object->save(null, $save_data)) {
            return [
                'response' => [
                    'success' => true,
                    'bonuscard' => Utils::extractOrm($this->object)
                ]
            ];
        }

        throw new ApiException(t('Не удалось сохранить сущность бонусной карты: %0', [$this->object->getErrorsStr()]), ApiException::ERROR_WRITE_ERROR);
    }

    /**
     * Подготавливает данные для запроса на добавление комментария
     *
     * @param $data - данные
     * @return array
     */
    function prepareData($data)
    {
        $post_data = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->use_post_keys)) {
                $post_data[$key] = $value;
            }
        }

        return $post_data;
    }


    /**
     * Возвращает объект комментария
     *
     * @return Comment
     */
    public function getOrmObject()
    {
        return new BonusCard();
    }
}
