{extends file="%THEME%/helper/wrapper/my-cabinet.tpl"}
{block name="content"}
    {addcss file="%bonuses%/bonuses.css"}
    {$config=ConfigLoader::byModule('bonuses')}
    {$bonuses=$current_user->getUserBonuses()}

    <div class="col">
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

            <h1 class="mb-lg-6 mb-sm-5 mb-4">{t}Ваши бонусы:{/t} {$bonuses}</h1>
            {if $bonuses && !$config.disable_button_in_bonushistory}
                <div class="bonusHistoryAmount balance tocenter">
                    <form method="POST">
                        <input type="submit" name="convert"
                               class="colorButton addFunds btn link-more btn btn-primary" value="{t}Перевести бонусы на баланс лицевого счёта{/t}"/>
                    </form>
                </div>
            {/if}
            {if $current_user->isCanCashoutBonuses()}
                <div class="bonusHistoryAmount balance tocenter">
                    <form method="POST">
                        <input type="submit" name="cashout" class="colorButton addFunds btn btn-primary link-more" value="{t}Запрос на вывод средств{/t}"/>
                    </form>
                </div>
            {/if}

            <h2 class="mb-lg-6 mb-sm-5 mb-4">{t}История операций с бонусами{/t}</h2>

            {if $list}
                <div>
                    {foreach $list as $item}
                        <div class="lk-balance-history">
                            <div class="col-md col-12 mb-3 mb-md-0">
                                <div class="me-3">№ {$item.id} от {$item.dateof|date_format:"d.m.Y H:i"}</div>
                            </div>
                            <div class="col ms-md-3 ms-0">
                                <div>{$item->reason}</div>
                                {if $item.amount>0}
                                    <div class="success-link fw-bold">+{$item.amount}</div>
                                {/if}
                                {if $item.amount<0}
                                    <div class="danger-link fw-bold">-{$item.amount}</div>
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
                {include file="%THEME%/paginator.tpl"}
            {else}
                {include file="%THEME%/helper/usertemplate/include/empty_list.tpl" reason="{t}Еще не было операций с бонусами{/t}"}
            {/if}
        </div>
    </div>
{/block}