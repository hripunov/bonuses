<?php
namespace Bonuses\Model\ExternalApi\Bonuses;

use Bonuses\Model\BonusesHistoryApi;
use Comments\Model\Api;
use ExternalApi\Model\AbstractMethods\AbstractGetList;

/**
* Возвращает список бонусных транзакций
*/
class GetTransactions extends AbstractGetList
{
    const RIGHT_LOAD = 1;
    const FILTER_TYPE_AMOUNT_FILTER = 'afilter';
    const FILTER_TYPE_DATE_FILTER = 'datefilter';

    protected $token_require = true;
    
    /**
    * Возвращает комментарии к кодам прав доступа
    * 
    * @return [
    *     КОД => КОММЕНТАРИЙ,
    *     КОД => КОММЕНТАРИЙ,
    *     ...
    * ]
    */
    public function getRightTitles()
    {
        return [
            self::RIGHT_LOAD => t('Загрузка списка объектов')
        ];
    }
    
    /**
    * Возвращает возможные значения для сортировки
    * 
    * @return array
    */
    public function getAllowableOrderValues()
    {
        return ['id', 'id desc', 'amount', 'amount desc', 'dateof', 'dateof desc', 'user_id', 'user_id desc'];
    }

    /**
     * Возвращает возможный ключи для фильтров
     *
     * @return [
     *   'поле' => [
     *       'title' => 'Описание поля. Если не указано, будет загружено описание из ORM Объекта'
     *       'type' => 'тип значения',
     *       'func' => 'постфикс для функции makeFilter в текущем классе, которая будет готовить фильтр, например eq',
     *       'values' => [возможное значение1, возможное значение2]
     *   ]
     * ]
     */
    public function getAllowableFilterKeys()
    {
        return [
            'id' => [
                'title' => t('ID транзакции. Одно значение или массив значений'),
                'type' => 'integer[]',
                'func' => self::FILTER_TYPE_IN
            ],
            'amount' => [
                'title' => t('Количество бонусов от и до фильтр'),
                'type' => 'array',
                'func' => self::FILTER_TYPE_AMOUNT_FILTER
            ],
            'dateof' => [
                'title' => t('Количество бонусов от и до фильтр'),
                'type' => 'array',
                'func' => self::FILTER_TYPE_DATE_FILTER
            ],
            'reason' => [
                'title' => t('Название операции'),
                'type' => 'string',
                'func' => self::FILTER_TYPE_LIKE,
            ],
            'extra' => [
                'title' => t('Доп. данные'),
                'type' => 'string',
                'func' => self::FILTER_TYPE_LIKE,
            ],
            'notify1' => [
                'title' => t('Отправлено первое уведомление? 1 или 0'),
                'type' => 'integer',
                'func' => self::FILTER_TYPE_EQ
            ],
            'notify2' => [
                'title' => t('Отправлено второе уведомление? 1 или 0'),
                'type' => 'integer',
                'func' => self::FILTER_TYPE_EQ
            ],
        ];
    }

    /**
     * Устанавливает фильтр по секции afilter - количество бонусов от и до
     *
     * @param string $key - секция фильтров
     * @param array $value - значение фильтров секции
     * @param array $filters - все фильтры
     * @param array $filter_settings - настройки фильтров
     * @return array
     */
    protected function makeFilterAFilter($key, $value, $filters, $filter_settings)
    {
        if (!empty($value['from'])){
            $this->dao->setFilter('amount', $value['from'], '>=');
        }
        if (!empty($value['to'])){
            $this->dao->setFilter('amount', $value['to'], '<=');
        }
        return [];
    }

    /**
     * Устанавливает фильтр по секции datefilter - количество бонусов от и до
     *
     * @param string $key - секция фильтров
     * @param array $value - значение фильтров секции
     * @param array $filters - все фильтры
     * @param array $filter_settings - настройки фильтров
     * @return array
     */
    protected function makeFilterDateFilter($key, $value, $filters, $filter_settings)
    {
        if (!empty($value['from'])){
            $this->dao->setFilter('dateof', "'".$value['from']." 00:00:00'", '>=');
        }
        if (!empty($value['to'])){
            $this->dao->setFilter('dateof', "'".$value['to']."' 23:59:59", '<=');
        }
        return [];
    }

    /**
     * Возвращает список типов комментария
     *
     * @return array
     * @throws \RS\Exception
     */
    function getFilterByType()
    {
        $api_types = Api::getTypeList();
        $types = [];

        if ($api_types) {
            foreach ($api_types as $type => $annotation) {
                $types[] = quotemeta($type);
            }
        }
        return $types;
    }

    /**
     * Возвращает объект, который позволит производить выборку товаров
     *
     * @return BonusesHistoryApi
     */
    public function getDaoObject()
    {
        return new BonusesHistoryApi();
    }


    /**
     * Возвращает список транзакций бонусов
     *
     * @example GET /api/methods/bonuses.getTransactions?filter[amount][from]=1&filter[amount][to]=100&filter[dateof][from]=2023-10-01&filter[dateof][to]=2023-10-12
     *
     * Ответ:
     *
     * <pre>{
     *  "response": {
     *      "summary": {
     *          "page": 1,
     *          "pageSize": 1000,
     *          "total": "2"
     *      },
     *      "list": [
     *          {
     *               "id": "16", // id в ReadyScript
     *               "user_id": "7", // id пользователя в ReadyScript
     *               "amount": "44", // Количество бонусов
     *               "reason": "Начисление бонусов за оформленный заказ №391", // Причина
     *               "dateof": "2022-10-19 16:40:00", // Дата создания
     *               "extra": null, // Доп. данные
     *               "notify1": "0", // Отправлено первое уведомление?
     *               "notify2": "0" // Отправлено второе уведомление?
     *           },
     *           {
     *               "id": "23",
     *               "user_id": "1",
     *               "amount": "-100",
     *               "reason": "Списание средств администратором",
     *               "dateof": "2022-12-06 20:33:04",
     *               "extra": null,
     *               "notify1": "0",
     *               "notify2": "0"
     *           },
     *       ]
     *    }
     * }
     * </pre>
     *
     * @param string $token Авторизационный токен
     * @param array $filter фильтр по параметрам. Возможные ключи: #filters-info
     * @param string $sort Сортировка комментариев по параметрам. Возможные значения #sort-info
     * @param integer $page Номер страницы
     * @param integer $pageSize Количество элементов на страницу
     *
     *
     * @return array Возвращает список объектов и связанные с ним сведения.
     * @throws \ExternalApi\Model\Exception
     */
    protected function process($token = null, $filter = [], $sort = "id", $page = 1, $pageSize = 1000)
    {
        $response = parent::process($token, $filter, $sort, $page, $pageSize);
        $response['response']['list'] = array_map(function($item) {
            unset($item['writeoff']);
            return $item;
        }, $response['response']['list']);
        return $response;
    }
}
