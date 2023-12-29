<?php
namespace Bonuses\Config;
use Catalog\Model\Orm\Product;
use RS\Module\Exception;
use RS\Orm\ConfigObject;
use \RS\Orm\Type;
use RS\Router\Manager;
use Shop\Model\Cart;

/**
* Класс конфигурации модуля
*/
class File extends ConfigObject
{
    function _init()
    {
        parent::_init()->append(array(
            t('Бонусная программа'),
                'default_template' => new Type\Varchar(array(
                    'description' => t('Шаблон по умолчанию'),
                    'hint' => t('Если у вас нестандартная тема, то будет использован шаблон для указанной здесь темы'),
                    'listFromArray' => array(array(
                        'amazing' => t('Изумительная'),
                        'default' => t('Классическая'),
                        'fashion' => t('Молодежная'),
                        'flatlines' => t('Современная'),
                        'perfume' => t('Воздушная'),
                        'young' => t('Детская')
                    )),
                    'default' => 'amazing'
                )),
                'bonuses_for_register' => new Type\Integer(array(
                    'description' => t('Количество бонусов начисляемое за регистрацию'),
                    'hint' => t('0 - не начислять'),
                    'maxLength' => 11
                )),
                'bonuses_for_first_order' => new Type\Integer(array(
                    'description' => t('Количество бонусов начисляемое за первый заказ'),
                    'hint' => t('Остальные начисления для первого заказа будут проигнорированы. 0 - не начислять.'),
                    'maxLength' => 11
                )),
                'bonuses_for_subscribe' => new Type\Integer(array(
                    'description' => t('Количество бонусов начисляемое за подписку на рассылку'),
                    'hint' => t('0 - не начислять'),
                    'maxLength' => 11
                )),
                'bonuses_for_birthday' => new Type\Integer(array(
                    'description' => t('Количество бонусов начисляемое в день рождения'),
                    'hint' => t('0 - не начислять'),
                    'maxLength' => 11
                )),
                'bonuses_lifetime' => new Type\Integer(array(
                    'description' => t('Время жизни бонусов у пользователя с момента последней транзакции в днях'),
                    'hint' => t('Работает, только при включенном cron <br/>
                    0 - живут вечно<br/>
                    3 месяца - 90'),
                    'maxLength' => 11
                )),
                'bonuses_lifetime_notify' => new Type\Integer(array(
                    'description' => t('Через сколько дней отправить первое уведомнение о том, что у человека есть бонусы'),
                    'hint' => t('Работает, только при включенном cron, а также при включении опции жизни бонусов <br/>
                        0 - никогда<br/>
                        14 - через 14 дней после оформления заказа'),
                    'maxLength' => 11
                )),
                'bonuses_lifetime_notify2' => new Type\Integer(array(
                    'description' => t('Через сколько дней отправить второе уведомнение о том, что у человека есть бонусы'),
                    'hint' => t('Работает, только при включенном cron, а также при включении опции жизни бонусов <br/>
                            0 - никогда<br/>
                            14 - через 14 дней после оформления заказа'),
                    'maxLength' => 11
                )),
                'bonuses_for_order_as_table' => new Type\Integer(array(
                    'description' => t('Использование бонусов по правилам'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0),
                    'visible' => false
                )),
                'bonuses_for_order' => new Type\Integer(array(
                    'description' => t('Количество бонусов начисляемое за оформление заказа'),
                    'hint' => t('0 - ничего'),
                    'maxLength' => 11,
                    'attr' => [[
                        'size' => 5,
                    ]],
                    'template' => '%bonuses%/form/programm/bonuses_for_order.tpl',
                )),
                'bonuses_for_order_type' => new Type\Integer(array(
                    'description' => t('Единицы для для начисления бонусов'),
                    'maxLength' => 1,
                    'attr' => [[
                        'size' => 5,
                        'class' => 'bonusesForOrderType',
                    ]],
                    'listFromArray' => array(array(
                        'ед.',
                        'в % от суммы',
                    )),
                    'visible' => false
                )),
                'bonuses_for_order_rule_arr' => new Type\ArrayList(array(
                    'visible' => false,
                    'runtime' => false
                )),
                'bonuses_for_order_with_old' => new Type\Integer(array(
                    'description' => t('Учитывать в правилах бонусов сумму прошлых заказов'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0),
                    'visible' => false
                )),
                'bonuses_for_order_status' => new Type\Integer(array(
                    'description' => t('Статус заказа для начисления бонусов'),
                    'maxLength' => 11,
                    'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList')),
                )),
                'equal_bonuses_number' => new Type\Integer(array(
                    'description' => t('1 бонус = '),
                    'maxLength' => 11,
                    'template' => '%bonuses%/form/programm/equal_number.tpl',
                )),
                'can_use_coupon' => new Type\Integer(array(
                    'description' => t('Можно ли использовать купон на скидку совместно с бонусами?'),
                    'maxLength' => 11,
                    'checkboxview' => array(1, 0)
                )),
                'min_bonuses_add_to_bonuses' => new Type\Integer(array(
                    'description' => t('Минимальное количество бонусов для зачисления на лицевой счёт'),
                    'maxLength' => 11
                )),
                'min_product_cost' => new Type\Integer(array(
                    'description' => t('Минимальная стоимость товара, для начисления бонусов'),
                    'hint' => t('0 - не ограничено.'),
                    'maxLength' => 11,
                )),
                'max_percent_to_discount' => new Type\Integer(array(
                    'description' => t('Максимальное количество процентов от суммы заказа, которые можно оплатить бонусами'),
                    'hint' => t('0 - не использовать'),
                    'maxLength' => 11,
                )),
                'min_cart_summ' => new Type\Integer(array(
                    'description' => t('Минимальная сумма заказа, для использования бонусов'),
                    'hint' => t('0 - не ограничено'),
                    'maxLength' => 11,
                    'default' => 0
                )),
                'min_cart_summ_last' => new Type\Integer(array(
                    'description' => t('Минимальная сумма заказа, которая должна остаться после применения бонусов'),
                    'hint' => t('0 - не ограничено'),
                    'maxLength' => 11,
                    'default' => 0
                )),
                'use_min_max_cart_rules' => new Type\Integer(array(
                    'description' => t('Использовать правило минимальной суммы заказа, для использования бонусов к переводу на лицевой счёт'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'default_dir_bonuses_units' => new Type\Integer(array(
                    'description' => t('Количество бонусов начиляемое всем товарам, во всех категория'),
                    'maxLength' => 11,
                    'visible' => false
                )),
                'default_dir_bonuses_units_type' => new Type\Integer(array(
                    'description' => t('Тип начисления бонусов начиляемое всем товарам, во всех категория'),
                    'maxLength' => 1,
                    'listFromArray' => array(array(
                        'ед.',
                        'в % от цены товара',
                    )),
                    'visible' => false
                )),
                'use_only_for_price_groups' => new Type\ArrayList(array(
                    'description' => t('Использовать только для следующих групп цен'),
                    'Attr' => array(array(
                        'multiple' => true,
                        'size' => 5
                    )),
                    'runtime' => false,
                    'list' => array(array('\Catalog\Model\CostApi', 'staticSelectList')),
                )),
                'disable_product_by_action' => new Type\Integer(array(
                    'description' => t('Запретить начисление бонусов для товаров по акции?'),
                    'hint' => t('Действует только, если указана зачёркнутая цена в настройках модуля Каталог товаров. Смотрите поле - Старая(зачеркнутая) цена. Бонусы за товар с зачернутой ценой начислятся не будут и не будут показывать.'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'disable_use_product_by_action_in_cart' => new Type\Integer(array(
                    'description' => t('Запретить перевод бонусов за товар в скидку в корзине по акции?'),
                    'hint' => t('Действует только, если указана зачёркнутая цена в настройках модуля Каталог товаров. <br/> Смотрите поле - Старая(зачеркнутая) цена.'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'use_min_summ_to_apply_by_actions' => new Type\Integer(array(
                    'description' => t('Использовать правило минимальной суммы заказа, если есть товарвы по акции в корзине, только для товаров не по акции'),
                    'hunt' => t('Действует при применении предыдущей опции и указанной минимальной суммы для применения бонусов.'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'disable_payment_with_discount' => new Type\Integer(array(
                    'description' => t('Не показывать способы оплаты со скидкой, если скидка уже применена к заказу'),
                    'hint' => t('При оформлении заказа скрывает те способы оплаты, которые идут со скидкой, если к заказу уже применена какая либо скидка'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'disable_delivery_with_discount' => new Type\Integer(array(
                    'description' => t('Не показывать способы доставки со скидкой, если скидка уже применена к заказу'),
                    'hint' => t('При оформлении заказа скрывает те способы доставки, которые идут со скидкой, если к заказу уже применена какая либо скидка'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'orders_count_before_use' => new Type\Integer(array(
                    'description' => t('Сколько заказов нужно выполнить покупателю перед использованием бонусов?'),
                    'hint' => t('Учитываются заказы в статусе "Выполнен и закрыт" или производные от них'),
                    'maxLength' => 11,
                )),
                'disable_button_in_bonushistory' => new Type\Integer(array(
                    'description' => t('Отключить кнопку перевода бонусов на лицевой счет'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'disable_add_bonuses_if_already_have' => new Type\Integer(array(
                    'description' => t('Не начислять бонусы за заказ с уже применёнными бонусами'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'apply_only_for_brands' => new Type\ArrayList(array(
                    'description' => t('Разрешить использовать бонусы только для товаров следующих брендов'),
                    'hint' => t('Если не отмечено ничего, то не действует'),
                    'list' => [['\Catalog\Model\BrandApi', 'staticSelectList'], false],
                    'Attr' => array(array(
                        'multiple' => true,
                        'size' => 5
                    )),
                    'runtime' => false
                )),
            t('Дисконтная программа'),
                'discount_rule_arr' => new Type\ArrayList(array(
                    'visible' => false,
                    'runtime' => false
                )),
                '_discount_rule_' => new Type\UserTemplate('%bonuses%/form/discount/discount_system.tpl', null, array(
                     'maxLength' => 5000,
                )),
                'discount_order_status' => new Type\Integer(array(
                    'description' => t('Тип статуса заказа для<br/> примененения дисконтнай программы'),
                    'maxLength' => 11,
                    'list' => array(array('\Shop\Model\UserStatusApi', 'staticSelectList'), array(0 => '-Не выбрано-')),
                )),
                'discount_register_price_id' => new Type\Integer(array(
                    'description' => t('Тип цены, который<br/> устанавливается при регистрации '),
                    'maxLength' => 11,
                    'list' => array(array('\Catalog\Model\CostApi', 'staticSelectList'), true),
                )),
                'discount_admin_not_usage' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Не использовать правила для группы Администраторов и Супервизоров?'),
                    'checkboxview' => array(1, 0),
                )),
                'use_default_price_for_sale' => new Type\Integer(array(
                    'maxLength' => 1,
                    'description' => t('Использовать цену по умолчанию, товаров с зачернутой ценой'),
                    'hint' => t('У данных товаров цена будет подменена на ту, что по умолчанию.'),
                    'checkboxview' => array(1, 0),
                )),
            t('Бонусные карты'),
                'show_bonus_card_section' => new Type\Integer(array(
                    'description' => t('Показывать раздел с бонусными картами для пользователей?'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'show_bonus_card_register' => new Type\Integer(array(
                    'description' => t('Показывать поле с вводом бонусной карты при регистрации?'),
                    'maxLength' => 1,
                    'checkboxview' => array(1, 0)
                )),
                'bonuscard_cashback_for_activation' => new Type\Integer(array(
                    'description' => t('Размер начислений партнеру за активированную карту'),
                    'hint' => t('Количество бонусов. 0 - ничего не начислять'),
                    'maxLength' => 11,
                    'attr' => array(array(
                        'size' => 8
                    ))
                )),
                'bonuscard_cashback' => new Type\Integer(array(
                    'description' => t('Размер начислений партнеру за покупки'),
                    'hint' => t('Покупки с привлеченных через активации бонусных карт. 0 - ничего не начислять'),
                    'maxLength' => 11,
                    'attr' => array(array(
                       'size' => 8
                    )),
                    'template' => '%bonuses%/form/programm/bonuscard_cashback.tpl',
                )),
                'bonuscard_cashback_type' => new Type\Integer(array(
                    'description' => t('Тип использования начислений'),
                    'maxLength' => 1,
                    'listFromArray' => array(array(
                        'ед.',
                        '%',
                    )),
                    'visible' => false
                )),
                'bonuscard_cashback_days' => new Type\Integer(array(
                    'description' => t('Количество в днях сколько действуют начисления партнеру'),
                    'hint' => t('С момента активации карты привлеченного пользователя'),
                )),
                'min_partner_cashbackout' => new Type\Integer(array(
                    'description' => t('Минимальная сумма для вывода денег партнеру'),
                )),
                'partner_cashbackout_email' => new Type\Varchar(array(
                    'description' => t('E-mail куда будут присылаться письма о том, что требуется вывод средств'),
                    'hint' => t('Если не указано, то будет отправлено на E-mail указанный в настройках сайта'),
                )),
            t('Комментарии'),
                'add_bonus_for_comment_product' => new Type\Integer(array(
                    'description' => t('Количество бонусов за комментарий к товару'),
                    'hint' => t('0 - не начислять. Статус заказа должен быть таким как 
                    указано в опции "Статус заказа для начисления бонусов"'),
                )),
                'add_for_comment_for_buyed_product' => new Type\Integer(array(
                    'description' => t('Начислять бонусы за комментарий только за купленые товары?'),
                    'checkboxview' => array(1, 0)
                )),
                'add_for_comment_only_once' => new Type\Integer(array(
                    'description' => t('Начислять бонусы за комментарий одному человеку только один раз?'),
                    'checkboxview' => array(1, 0)
                )),
        ));
    }

    /**
     * Возвращает значения свойств по-умолчанию
     *
     * @return array
     * @throws Exception
     */
    public static function getDefaultValues()
    {
        return parent::getDefaultValues() + array(           
            'tools' => array(
                array(
                    'url' => Manager::obj()->getAdminUrl('updateUsersPrices', array(), 'bonuses-tools'),
                    'title' => t('Обновить пользователям назначенные цены'),
                    'description' => t('Обновляет типы цен у пользователей, согласно назначенным правилам'),
                    'confirm' => t('Вы действительно хотите обновить?')
                ),
                array(
                    'url' => Manager::obj()->getAdminUrl('updateRegisteredUsersPrices', array(), 'bonuses-tools'),
                    'title' => t('Проставить зарегистрированным пользователям тип скидочной цены'),
                    'description' => t('Обновляет типы цен у зарегистрированных пользователей, согласно соответствующему полю'),
                    'confirm' => t('Вы действительно хотите обновить?')
                ),
            )
        );
    }

    /**
     * Проверяет есть ли в заказе товары с зачеркнутой ценой
     *
     * @param Cart $cart - объект корзины
     * @param array $cart_data - данные корзины
     * @return boolean
     * @throws \RS\Exception
     */
    function isHaveOldPriceInCartPage($cart, $cart_data)
    {
        $product_items = $cart->getProductItems();
        $found = false;
        foreach ($product_items as $uniq=>$item){
            /**
             * @var Product $product
             */
            $product = $item['product'];
            if ($product->getOldCost(null, false) > 0){
                $found = true;
                break;
            }

            $sub_products = $cart_data['items'][$uniq]['sub_products'];

            if (!empty($sub_products)) {
                foreach ($sub_products as $subid => $sub_product) {
                    if ($sub_product['checked']) { //Если сопутствующий товар включен
                        $product = new Product($subid);
                        if ($product->getOldCost(null, false) > 0){
                            $found = true;
                            break(2);
                        }
                    }
                }
            }
        }

        return $found;
    }
}
