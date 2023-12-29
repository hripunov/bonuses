{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
{$bonuses=$current_user->getUserBonuses()}
<div class="bonusHistoryWrapper ">
    <div class="bonusHistoryAmount">
        {t b=$bonuses}У Вас <b>%b</b> бонусных баллов{/t}
    </div>
    
    {if !empty($errors)}
        {foreach $errors as $error}
            <p style="color:red">{$error}</p>
        {/foreach}
    {/if}
    {if $success}
        <p style="color:green">{$success}</p>
    {/if}
    {if $bonuses && !$config.disable_button_in_bonushistory}
        <form method="POST" class="addMoneyForm">                    
            <input type="submit" name="convert" class="button color addFunds" value="{t}Перевести бонусы на баланс лицевого счёта{/t}"/>
        </form>
    {/if}
    {if $current_user->isCanCashoutBonuses()}
        <form method="POST" class="addMoneyForm">
            <input type="submit" name="cashout" class="button color addFunds" value="{t}Запрос на вывод средств{/t}"/>
        </form>
    {/if}
    <br><br>

    <h2><span>{t}История операций с бонусами{/t}</span></h2>

    <table class="orderList themeTable">
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th class="addFundsHead">{t}Зачислено{/t}</th>
            <th class="takeFundsHead">{t}Списано{/t}</th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$list item=item}
        <tr>
            <td class="date">№ {$item.id}<br>{$item.dateof|date_format:"d.m.Y H:i"}</td>
            <td class="message">{$item->reason}</td>
            <td>
                {* Приход *}
                {if $item.amount>0}
                    <span class="scost">{$item.amount}</span>
                {/if}
            </td>
            <td>
                {* Расход *}
                {if $item.amount<0}
                    <span class="tcost">{$item.amount}</span>
                {/if}
            </td>
        </tr>
    {/foreach}
    </tbody>
    </table>
    <br><br>
</div>
{include file="%THEME%/paginator.tpl"}
