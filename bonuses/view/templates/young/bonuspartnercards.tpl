{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}

{* Шаблон страницы просмотра баланса бонусов пользователя в личном кабинете *}
<div class="bonusPartnerCardsWrapper young personalAccount">
    <div class="form-style">
        <div class="tab-content">
            <div>
                {if !empty($list)}
                    <hr/>
                    <table class="bonusPartnerCardsTable">
                        <thead class="hide-mobile">
                            <tr>
                                <th>
                                    №
                                </th>
                                <th>
                                    {t}Карта{/t}
                                </th>
                                <th>
                                    {t}Статус{/t}
                                </th>
                                <th class="bonuses">
                                    {t}Бонусы{/t}
                                </th>
                                <th>
                                    {t}Покупатель{/t}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {$m=0}
                            {foreach $list as $card}
                                <tr>
                                    <td class="hide-mobile">
                                        {$m=$m+1}
                                        {$m}.
                                    </td>
                                    <td>
                                        {$card->getCardId()}
                                    </td>
                                    <td>
                                        {if $card.active}
                                            <span class="cardActive">{t}Активна{/t}</span>
                                        {else}
                                            <span class="cardDeactive">{t}Не активна{/t}</span>
                                        {/if}
                                    </td>
                                    <td class="bonuses">
                                        {$card.amount} <span class="hide-desktop">{t}бонусов{/t}</span>
                                    </td>
                                    <td>
                                        {if $card.user_id}
                                            {$card->getUser()->surname}
                                        {else}
                                            {t}Не привязан{/t}
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                {/if}
            </div>
        </div>

        {include file="%THEME%/paginator.tpl"}

        <div class="buttons cboth">
            <a href="{$router->getUrl('bonuses-front-bonuspartnercards', ['Act'=>'add'])}" class="formSave colorButton inDialog rs-in-dialog">{t}Добавить карту{/t}</a>
        </div>
    </div>
</div>