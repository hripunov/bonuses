{extends file="%THEME%/helper/wrapper/my-cabinet.tpl"}
{block name="content"}
    {addcss file="%bonuses%/bonuses.css"}
    {$config=ConfigLoader::byModule('bonuses')}
    {$bonuses=$current_user->getUserBonuses()}

    <div class="col">
        {* Шаблон страницы просмотра баланса бонусов пользователя в личном кабинете *}

        <div class="bonusHistoryWrapper amazing personalAccount">
            {if !empty($errors)}
                {foreach $errors as $error}
                    <p style="color:red">{$error}</p>
                {/foreach}
            {/if}
            {if $success}
                <p style="color:green">{$success}</p>
            {/if}

            <h1 class="mb-lg-6 mb-sm-5 mb-4">{t}Запросы на вывод средств{/t}</h1>
            {if $current_user->isCanCashoutBonuses()}
                <div class="bonusHistoryAmount balance tocenter">
                    <form method="POST">
                        <input type="submit" name="cashout" class="colorButton addFunds btn btn-primary link-more" value="{t}Запрос на вывод средств{/t}"/>
                    </form>
                </div>
            {/if}

            <div class="form-style">
                <div class="tab-content">
                    <div>
                        <h2 class="mb-lg-6 mb-sm-5 mb-4">{t}Ваш баланс{/t}: <strong>{$bonuses}</strong> бонусных баллов</h2>
                        {if $list}
                            <h2 class="mb-lg-6 mb-sm-5 mb-4">{t}История операций{/t}</h2>
                            <div>
                                {foreach $list as $item}
                                    <div class="lk-balance-history">
                                        <div class="col-md col-12 mb-3 mb-md-0">
                                            <div class="me-3">
                                                Создано<br>{$item.dateof|date_format:"d.m.Y"}
                                                {if $item.enrolled}<br>
                                                    Исполнено<br>{$item.dateof_enrolled|date_format:"d.m.Y"}
                                                {/if}
                                            </div>
                                        </div>
                                        <div class="col ms-md-3 ms-0">
                                            <div>{$item->reason}</div>
                                            <div class="success-link fw-bold">
                                                {$item.amount}<br/>
                                                Зачислено?<br/>
                                                {if $item.enrolled}
                                                    {t}Да{/t}
                                                {else}
                                                    {t}Нет{/t}
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                                {include file="%THEME%/paginator.tpl"}
                            </div>
                        {else}
                            {include file="%THEME%/helper/usertemplate/include/empty_list.tpl" reason="{t}Операций ещё небыло{/t}"}
                        {/if}
                    </div>
                </div>


            </div>
        </div>
    </div>
{/block}