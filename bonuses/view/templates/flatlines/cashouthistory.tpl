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
                <a>{t}Запросы на вывод средств{/t}</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="visible-xs visible-sm hidden-md hidden-lg mobile_nav-tabs">
                <span>{t}История операций{/t}</span>
            </div>
            <div>
                <h2 class="h2 t-balance_title">{t}Ваш баланс{/t}: <strong>{$bonuses}</strong> бонусных баллов</h2>
                {if $list}
                    <ul class="t-balance-list">
                        {foreach $list as $item}
                            <li>
                                <div class="t-balance_left">
                                    Создано<br>{$item.dateof|date_format:"d.m.Y"}
                                    {if $item.enrolled}<br>
                                        Исполнено<br>{$item.dateof_enrolled|date_format:"d.m.Y"}
                                    {/if}
                                </div>
                                <div class="t-balance_center">{$item.amount}</div>
                                <div class="t-balance_right">
                                    Зачислено?<br/>
                                    {if $item.enrolled}
                                        {t}Да{/t}
                                    {else}
                                        {t}Нет{/t}
                                    {/if}
                                </div>
                            </li>
                        {/foreach}
                    </ul>
                {/if}
            </div>
        </div>

        {include file="%THEME%/paginator.tpl"}
    </div>
</div>