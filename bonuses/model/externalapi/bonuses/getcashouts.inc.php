<?php
namespace Bonuses\Model\ExternalApi\Bonuses;

use Bonuses\Model\CashoutApi;
use Comments\Model\Api;
use ExternalApi\Model\AbstractMethods\AbstractGetList;

/**
* Возвращает список бонусных отчислений
*/
class GetCashouts extends AbstractGetList
{
    const RIGHT_LOAD = 1;
    const FILTER_TYPE_AMOUNT_FILTER = 'afilter';
    const FILTER_TYPE_DATE_FILTER = 'datefilter';
    const FILTER_TYPE_DATE_CREATE_FILTER = 'createfilter';

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
        return ['id', 'id desc', 'amount', 'amount desc', 'active_date', 'active_date desc', 'create_date', 'create_date desc', 'user_id', 'user_id desc'];
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
            'partner_id' => [
                'title' => t('Партнер'),
                'type' => 'integer',
                'func' => self::FILTER_TYPE_EQ
            ],
            'amount' => [
                'title' => t('Количество средств для вывода от и до фильтр'),
                'type' => 'array',
                'func' => self::FILTER_TYPE_AMOUNT_FILTER
            ],
            'enrolled' => [
                'title' => t('Средства зачислены? 1 или 0'),
                'type' => 'integer',
                'func' => self::FILTER_TYPE_EQ
            ],
            'dateof' => [
                'title' => t('Дата создания от и до фильтр'),
                'type' => 'array',
                'func' => self::FILTER_TYPE_DATE_FILTER
            ],
            'dateof_enrolled' => [
                'title' => t('Дата зачисления от и до фильтр'),
                'type' => 'array',
                'func' => self::FILTER_TYPE_DATE_CREATE_FILTER
            ]
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
            $this->dao->setFilter('dateof', "'".$value['from']."'", '>=');
        }
        if (!empty($value['to'])){
            $this->dao->setFilter('dateof', "'".$value['to']."'", '<=');
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
    protected function makeFilterCreateFilter($key, $value, $filters, $filter_settings)
    {
        if (!empty($value['from'])){
            $this->dao->setFilter('dateof_enrolled', "'".$value['from']."'", '>=');
        }
        if (!empty($value['to'])){
            $this->dao->setFilter('dateof_enrolled', "'".$value['to']."'", '<=');
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
     * @return CashoutApi
     */
    public function getDaoObject()
    {
        return new CashoutApi();
    }


    /**
     * Возвращает список транзакций бонусов для вывода партнёру
     *
     * @example GET /api/methods/bonuses.getCashouts?filter[amount][from]=1&filter[amount][to]=100&filter[dateof][from]=2023-10-01&filter[dateof][to]=2023-10-12
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
     *              "id": 1,
     *              "user_id": 1,
     *              "card_id": "12345645645646677654",
     *              "amount": 100,
     *              "e_mail": "example@ya.ru",
     *              "active": 1,
     *              "is_partner_card": 0,
     *              "active_date": "2024-01-01",
     *              "activation_bonus_use": 0,
     *              "partner_id": 0,
     *           },
     *           {
     *              "id": 2,
     *              "user_id": 1,
     *              "card_id": "12345645645646677654",
     *              "amount": 100,
     *              "e_mail": "example@ya.ru",
     *              "active": 1,
     *              "is_partner_card": 0,
     *              "active_date": "2024-01-01",
     *              "activation_bonus_use": 0,
     *              "partner_id": 0,
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
        return $response;
    }
}
