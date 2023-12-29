{moduleinsert name="\Bonuses\Controller\Block\CartBonuses"}
{assign var=bonuses_config value=ConfigLoader::byModule('bonuses')}
{static_call var=user_cost callback=['\Catalog\Model\CostApi', 'getUserCost']}
{$can_be_show=true}
{if $bonuses_config.use_only_for_price_groups && !empty($bonuses_config.use_only_for_price_groups)}
    {if !in_array($user_cost, $bonuses_config.use_only_for_price_groups)}
        {$can_be_show=false}
    {/if}
{/if}
{if $is_auth}
    {if $cart_data.order_bonuses_for_discount}
        {$order_bonuses_for_discount=$cart_data.order_bonuses_for_discount}
        <p class="cartBonusesWrapper {$bonuses_config.default_template}">{t d=$order_bonuses_for_discount}Применено <b>%d</b> [plural:%d:бонусный|бонусных|бонусных] [plural:%d:балл|балла|баллов]{/t}</p>
    {/if}
    {if $bonuses_config.disable_use_product_by_action_in_cart && $bonuses_config->isHaveOldPriceInCartPage($cart, $cart_data)}
        <p class="cartBonusesWrapper {$bonuses_config.default_template}">{t}Бонусы не применяются к акционным и уцененным товарам!{/t}</p>
    {/if}

    {if $bonuses_config.orders_count_before_use && !$current_user->isHaveCountOrders()}
        <p class="cartBonusesWrapper {$bonuses_config.default_template}">{t ordcnt=$bonuses_config.orders_count_before_use}Использование бонусов доступно после %ordcnt [plural:%ordcnt:заказа|заказа|заказов]!{/t}</p>
    {/if}
{/if}