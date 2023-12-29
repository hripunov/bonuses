{assign var=bonuses_config value=ConfigLoader::byModule('bonuses')}
{assign var=shop_config value=ConfigLoader::byModule('shop')}
{$user_bonuses=$current_user->getUserBonuses()}
{$routeId = $router->getCurrentRoute()->getId()}
{$can_show_trigger=$routeId != 'shop-front-checkout' || !($shop_config['checkout_type'] == 'one_page' && $routeId == 'shop-front-checkout')}
<div id="cartBonusesWrapper" class="cartBonusesWrapper {$bonuses_config.default_template}">
    {if $user_bonuses && $can_show_trigger}
        {* Зарезервировано на случай необходимости
          <div class="success"></div>
        <ul class="errors">{* Сюда будут записываться ошибки блока, если они будут </ul>
        *}
        <b>Количество Ваших бонусов - <span class="cartBonuses {$bonuses_config.default_template}">{$current_user->getUserBonuses()}</span></b>
        <div class="cartBonusesLinks {$bonuses_config.default_template}">
            <input id="use_cart_bonuses" class="useForDiscount updateConfirmPage" type="checkbox" name="use_cart_bonuses" autocomplete="off" data-action="{$router->getUrl('bonuses-front-cartbonuses', ['Act' => 'updateBonusesApply', 'ajax' => 1])}" {if $bonuses_config.orders_count_before_use && !$current_user->isHaveCountOrders()}disabled{/if} {if $smarty.session.use_cart_bonuses}checked{/if} value="1">
            <label for="use_cart_bonuses">Перевести бонусы в скидку</label><br/>
        </div>
    {/if}
</div>
<link type="text/css" rel="stylesheet" href="{$mod_css}bonuses.css"/>
<script type="text/javascript" src="{$mod_js}bonuses.js"></script>