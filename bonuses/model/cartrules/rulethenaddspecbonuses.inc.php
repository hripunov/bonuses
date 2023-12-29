<?php

declare(strict_types=1);

namespace Bonuses\Model\CartRules;

use Bonuses\Model\BonusApi;
use CartRules\Model\RulesApi;
use CartRules\Model\Rules\AbstractThenRule;
use Catalog\Model\Api as ProductApi;
use Catalog\Model\DirApi;
use Catalog\Model\ProductDialog;
use RS\Exception as RSException;
use RS\View\Engine as ViewEngine;
use Shop\Model\Cart;
use Shop\Model\Orm\Order;
use Shop\Model\Discounts\CartItemDiscount;
use Shop\Model\Orm\AbstractCartItem;

/**
 * Действие - применить указанную скидку к указанным товарам
 * размер скидки может быть вычисляемым выражением
 * товары указываются через ProductDialog
 */
class RuleThenAddSpecBonuses extends AbstractThenRule
{
    const DISCOUNT_TYPE_PERCENT = 'percent';
    const DISCOUNT_TYPE_BASE_COST = 'base_cost';

    const DISCOUNT_USER_COST = 'percent';
    const DISCOUNT_SALE_COST = 'base_cost';

    protected $dialog;
    protected $discountType;

    protected $quantity1;
    protected $amount1;

    protected $quantity2;
    protected $amount2;

    protected $quantity3;
    protected $amount3;

    protected $amount4;

    /**
     * Возвращает идентификатор правила
     *
     * @return string
     */
    public function getId(): string
    {
        return 'thenAddSpecBonuses';
    }

    /**
     * Возвращает наименование правила
     *
     * @return string
     */
    public function getName(): string
    {
        return t('Начислить особые бонусы...');
    }

    /**
     * Загружает дополнительные сведенья для визуального отображения отображения правила
     *
     * @return array
     */
    public function getAdditionalVisualData()
    {
        $data = [];
        if (!empty($this->dialog['group'])) {
            $dir_api = new DirApi();
            $dir_api->setFilter('id', $this->dialog['group'], 'in');
            $data['dirs'] = $dir_api->getList();
        } else {
            $data['dirs'] = [];
        }
        if (!empty($this->dialog['product'])) {
            $product_api = new ProductApi();
            $product_api->setFilter('id', $this->dialog['product'], 'in');
            $data['products'] = $product_api->getList();
        } else {
            $data['products'] = [];
        }
        return $data;
    }

    /**
     * Возвращает шаблон отображения
     *
     * @return string
     */
    public function getThenTemplate(): string
    {
        return '%bonuses%/cartrules/rule_then_add_spec_bonuses.tpl';
    }

    /**
     * Возвращает текст для отображения в списке
     *
     * @return string
     * @throws \SmartyException
     */
    public function getThenTextView(): string
    {
        $view = new ViewEngine();
        $view->assign($this->getVisualData());
        return $view->fetch('%bonuses%/cartrules/rule_then_add_spec_bonuses_text_view.tpl');
    }

    /**
     * Проверяет корректность заполнения параметров правила, возвращает массив ошибок
     *
     * @return string[]
     */
    public function validateThenParams(): array
    {
        $errors = [];
        if (empty($this->dialog)) {
            $errors[] = t('не указаны товары');
        }
        return $errors;
    }

    /**
     * Применяет правило к корзине, возвращает изменённую информацию о товарах в корзине
     *
     * @param Cart $cart - объект корзины
     * @return void
     * @throws RSException
     */
    public function applyRule(Cart $cart): void
    {
        $dialog_products = $this->dialog['product'] ?? [];
        $dialog_groups = $this->dialog['group'] ?? [];
        $dialog_all = in_array(0, $dialog_groups);
        if (!$dialog_all) {
            $dialog_groups = DirApi::getChildsId($dialog_groups);
        }

        $total_bonuses = 0;
        foreach ($cart->getProductItemsWithConcomitants() as $item) {
            /** @var AbstractCartItem $cart_item */
            $cart_item = $item[Cart::CART_ITEM_KEY];
            $product = $cart_item->getEntity();
            $product->fillCategories();

            $order = $cart->getOrder();

            if (!$cart_item->getForbidDiscounts() && ($dialog_all || array_intersect($product['xdir'], $dialog_groups) || in_array($product['id'], $dialog_products))) {
                switch ($this->discountType) {

                    //
                    //  НАЧАЛО ЧАСТИ, НА КОТОРУЮ МОЖНО НЕ СМОТРЕТЬ
                    //
                    case self::DISCOUNT_TYPE_PERCENT:
                        if ($this->quantity1 != NULL) {
                            if ($cart_item->amount < $this->quantity1) {
                                $discount = new CartItemDiscount((int)$this->amount1, CartItemDiscount::UNIT_PERCENT, RulesApi::DISCOUNT_SOURCE_CART_RULES);
                                $cart_item->addDiscount($discount);
                            } else if ($this->quantity2 != NULL) {
                                if ($cart_item->amount < $this->quantity2) {
                                    $discount = new CartItemDiscount((int)$this->amount2, CartItemDiscount::UNIT_PERCENT, RulesApi::DISCOUNT_SOURCE_CART_RULES);
                                    $cart_item->addDiscount($discount);
                                } else if ($this->quantity3 != NULL) {
                                    if ($cart_item->amount < $this->quantity3) {
                                        $discount = new CartItemDiscount((int)$this->amount3, CartItemDiscount::UNIT_PERCENT, RulesApi::DISCOUNT_SOURCE_CART_RULES);
                                        $cart_item->addDiscount($discount);
                                    } else {
                                        $discount = new CartItemDiscount((int)$this->amount4, CartItemDiscount::UNIT_PERCENT, RulesApi::DISCOUNT_SOURCE_CART_RULES);
                                        $cart_item->addDiscount($discount);
                                    }
                                } else {
                                    $discount = new CartItemDiscount((int)$this->amount4, CartItemDiscount::UNIT_PERCENT, RulesApi::DISCOUNT_SOURCE_CART_RULES);
                                    $cart_item->addDiscount($discount);
                                }
                            } else {
                                $discount = new CartItemDiscount((int)$this->amount4, CartItemDiscount::UNIT_PERCENT, RulesApi::DISCOUNT_SOURCE_CART_RULES);
                                $cart_item->addDiscount($discount);
                            }
                        } else {
                            $discount = new CartItemDiscount((int)$this->amount4, CartItemDiscount::UNIT_PERCENT, RulesApi::DISCOUNT_SOURCE_CART_RULES);
                            $cart_item->addDiscount($discount);
                        }
                        break;
                    //
                    //  КОНЕЦ ЧАСТИ, НА КОТОРУЮ МОЖНО НЕ СМОТРЕТЬ
                    //
                    case self::DISCOUNT_TYPE_BASE_COST:
                        if ($this->quantity1 != NULL) {
                            if ($cart_item->amount < $this->quantity1) {
                                $total_bonuses += $cart_item->amount * (int)$this->amount1;
                            } else if ($this->quantity2 != NULL) {
                                if ($cart_item->amount < $this->quantity2) {
                                    $total_bonuses += $cart_item->amount * (int)$this->amount2;
                                } else if ($this->quantity3 != NULL) {
                                    if ($cart_item->amount < $this->quantity3) {
                                        $total_bonuses += $cart_item->amount * (int)$this->amount3;
                                    } else {
                                        $total_bonuses += $cart_item->amount * (int)$this->amount4;
                                    }
                                } else {
                                    $total_bonuses += $cart_item->amount * (int)$this->amount4;
                                }
                            } else {
                                $total_bonuses += $cart_item->amount * (int)$this->amount4;
                            }
                        } else {
                            $total_bonuses += $cart_item->amount * (int)$this->amount4;
                        }
                        break;
                }
            }
        }
        if ($total_bonuses > 0) {
            $order['rule_bonuses'] = $total_bonuses;
        }
    }

    /**
     * Возвращает html диалога выбора товаров
     *
     * @return string
     */
    public function getProductDialogHtml(): string
    {
        $product_dialog = new ProductDialog('rule[then][%index%][dialog]', false, $this->dialog);
        return $product_dialog->getHtml();
    }

    /**
     * Возвращает значение из справочника типов скидок по ключу
     *
     * @param string $key - ключ типа скидки
     * @return string
     */
    public static function handbookDiscountTypesStr($key)
    {
        $cmp = self::handbookDiscountTypes();
        return (isset($cmp[$key])) ? $cmp[$key] : '';
    }

    /**
     * Возвращает список типов скидок
     *
     * @return array
     */
    public static function handbookDiscountTypes(): array
    {
        return [
            self::DISCOUNT_TYPE_PERCENT => '%',
            self::DISCOUNT_TYPE_BASE_COST => t('в базовой валюте'),
        ];
    }
}