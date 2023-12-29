{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
{$bonuses=$current_user->getUserBonuses()}

{* Шаблон страницы просмотра баланса бонусов пользователя в личном кабинете *}

<div class="bonusHistoryWrapper flatlines personalAccount">
    {if !empty($errors)}
        {foreach $errors as $error}
            <p style="color:red">{$error}</p>
        {/foreach}
    {/if}
    {if $success}
        <p style="color:green">{$success}</p>
    {/if}

    {if $bonuses && !$config.disable_button_in_bonushistory}
    <div class="bonusHistoryAmount balance tocenter">
        <form method="POST">
            <input type="submit" name="convert" class="colorButton addFunds btn link-more" value="{t}Перевести бонусы на баланс лицевого счёта{/t}"/>
        </form>
    </div>
    {/if}
    {if $current_user->isCanCashoutBonuses()}
        <div class="bonusHistoryAmount balance tocenter">
            <form method="POST">
                <input type="submit" name="cashout" class="colorButton addFunds btn link-more" value="{t}Запрос на вывод средств{/t}"/>
            </form>
        </div>
    {/if}

    <div class="form-style">
        <ul class="nav nav-tabs hidden-xs hidden-sm">
            <li class="active">
                <a>{t}История операций с бонусами{/t}</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs">
                <span>{t}История операций{/t}</span>
            </div>
            <div>
                <h2 class="h2 t-balance_title">{t}Ваш баланс{/t}: <strong>{$bonuses}</strong> бонусных баллов</h2>
                <p class="visible-xs visible-sm text-center"><a href="{$router->getUrl('shop-front-mybalance', [Act=>addfunds])}">{t}Пополнить баланс{/t}</a></p>
                {if $list}
                    <ul class="t-balance-list">
                        {foreach $list as $item}
                            <li>
                                <div class="t-balance_left">№ {$item.id}<br> от {$item.dateof|date_format:"d.m.Y H:i"}</div>
                                <div class="t-balance_center">{$item->reason}</div>
                                {if $item.amount>0}
                                    <div class="t-balance_right green">{$item.amount}</div>
                                {/if}
                                {if $item.amount<0}
                                    <div class="t-balance_right red">{$item.amount}</div>
                                {/if}
                            </li>
                        {/foreach}
                    </ul>
                {/if}
            </div>
        </div>

        {include file="%THEME%/paginator.tpl"}
    </div>
</div>