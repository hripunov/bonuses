{addcss file="%bonuses%/bonuses.css"}
{assign var=cartdata value=$cart->getCartData()}
{assign var=bonuses_config value=ConfigLoader::byModule('bonuses')}
{static_call var=user_cost callback=['\Catalog\Model\CostApi', 'getUserCost']}
{$can_be_show=true}
{if $bonuses_config.use_only_for_price_groups && !empty($bonuses_config.use_only_for_price_groups)}
    {if !in_array($user_cost, $bonuses_config.use_only_for_price_groups)}
        {$can_be_show=false}
    {/if}
{/if}
{$bonuses=$order->getBonuses()}
{if $bonuses>0 && $can_be_show}
    <p class="cartBonusesWrapper {$bonuses_config.default_template}">{t d=$bonuses}Вы получите за оформленный заказ <b>%d</b> [plural:%d:бонусных|бонусных|бонусных] [plural:%d:балла|балла|баллов]{/t}</p>
{/if}