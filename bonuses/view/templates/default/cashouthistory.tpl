{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
{$bonuses=$current_user->getUserBonuses()}
<div class="bonusHistoryWrapper">
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
    {if $current_user->isCanCashoutBonuses()}
        <form method="POST">
            <input type="submit" name="cashout" class="formSave" value="{t}Запрос на вывод средств{/t}"/>
        </form>
    {/if}
    <br><br>
    <h2><span>{t}Запросы на вывод средств{/t}</span></h2>

    <table class="orderList balanceTable">
    <thead>
        <tr>
            <th></th>
            <th class="addFundsHead">{t}Запрошено{/t}</th>
            <th class="takeFundsHead">{t}Исполнено?{/t}</th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$list item=item}
        <tr>
            <td class="date">
                Создано<br>{$item.dateof|date_format:"d.m.Y"}
                {if $item.enrolled}<br>
                    Исполнено<br>{$item.dateof_enrolled|date_format:"d.m.Y"}
                {/if}
            </td>
            <td>
                <span class="scost">{$item.amount}</span>
            </td>
            <td>
                {if $item.enrolled}
                    {t}Да{/t}
                {else}
                    {t}Нет{/t}
                {/if}
            </td>
        </tr>
    {/foreach}
    </tbody>
    </table>
    <br><br>
</div>
{include file="%THEME%/paginator.tpl"}
