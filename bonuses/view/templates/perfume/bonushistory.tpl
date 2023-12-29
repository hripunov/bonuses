{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
{$bonuses=$current_user->getUserBonuses()}
<div class="bonusHistoryWrapper personalAccount">

    <div class="bonusHistoryAmount balance tocenter">
        {t b=$bonuses}У Вас <strong>%b</strong> бонусных баллов{/t}
        {if $bonuses && !$config.disable_button_in_bonushistory}
            <form method="POST">
                <input type="submit" name="convert" class="colorButton addFunds" value="{t}Перевести бонусы на баланс лицевого счёта{/t}"/>
            </form>
        {/if}
        {if $current_user->isCanCashoutBonuses()}
            <form method="POST">
                <input type="submit" name="cashout" class="colorButton addFunds" value="{t}Запрос на вывод средств{/t}"/>
            </form>
        {/if}
    </div>

    
    {if !empty($errors)}
        {foreach $errors as $error}
            <p style="color:red">{$error}</p>
        {/foreach}
    {/if}
    {if $success}
        <p style="color:green">{$success}</p>
    {/if}
    {if ($bonuses)}
        
    {/if}
    <br><br>
    <h2><span>{t}История операций с бонусами{/t}</span></h2>

    <table class="orderList balanceTable themeTable">
    <thead>
        <tr>
            <td></td>
            <td></td>
            <td class="addFundsHead">{t}Зачислено{/t}</td>
            <td class="takeFundsHead">{t}Списано{/t}</td>
        </tr>
    </thead>
    <tbody>
    {foreach from=$list item=item}
        <tr>
            <td class="date">№ {$item.id}<br>{$item.dateof|date_format:"d.m.Y H:i"}</td>
            <td class="message">{$item->reason}</td>
            <td class="in">
                {* Приход *}
                {if $item.amount>0}
                    <span class="scost">{$item.amount}</span>
                {/if}
            </td>
            <td class="out">
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
