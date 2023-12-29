{assign var=bonuses_config value=ConfigLoader::byModule('bonuses')}
{$user_bonuses=$current_user->getUserBonuses()}
<div id="cartBonusesWrapper" class="cartBonusesWrapper">    
    {if $user_bonuses}
        {* Зарезервировано на случай необходимости
          <div class="success"></div>
        <ul class="errors">{* Сюда будут записываться ошибки блока, если они будут </ul>
        *}
        <b>Количество Ваших бонусов - <span class="cartBonuses">{$current_user->getUserBonuses()}</span></b>
        <div class="cartBonusesLinks">
            <input id="use_cart_bonuses" class="useForDiscount" type="checkbox" name="use_cart_bonuses" autocomplete="off" {if $bonuses_config.orders_count_before_use && !$current_user->isHaveCountOrders()}disabled{/if} {if $smarty.session.use_cart_bonuses}checked{/if} value="1">
            <label for="use_cart_bonuses">Перевести бонусы в скидку</label><br/>
            {* Зарезервировано на случай необходимости
             <a data-href="{$router->getUrl('bonuses-block-cartbonuses', ['action' => 'useBonusesForPersonalAccount', '_block_id' => $this_controller->getBlockId()])}" class="useForPersonalAccount cartBonusesLink">Перевести бонусы на лицевой счёт</a>
            *}
        </div>
    {/if}
</div>
<link type="text/css" rel="stylesheet" href="{$mod_css}bonuses.css"/>
<script type="text/javascript" src="{$mod_js}bonuses.js"></script>