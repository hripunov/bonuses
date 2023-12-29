{extends file="%THEME%/helper/wrapper/my-cabinet.tpl"}
{block name="content"}
    {addcss file="%bonuses%/bonuses.css"}
    {$config=ConfigLoader::byModule('bonuses')}

    {* Шаблон страницы просмотра баланса бонусов пользователя в личном кабинете *}
    <div class="bonusPartnerCardsWrapper flatlines personalAccount">
        <div class="form-style">
            <div class="tab-content">
                <div>
                    <h1 class="mb-lg-6 mb-sm-5 mb-4">{t}Партнерские бонусные карты{/t}</h1>
                    <div>
                        <a href="{$router->getUrl('bonuses-front-bonuspartnercards', ['Act'=>'add'])}" class="link link-more btn btn-primary inDialog rs-in-dialog">{t}Добавить карту{/t}</a>
                        {if !empty($list)}
                            <hr/>
                            <table class="bonusPartnerCardsTable">
                                <thead class="hidden-xs">
                                    <tr>
                                        <th class="hidden-xs">
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
                                            <td class="hidden-xs">
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
                                                {$card.amount} <span class="visible-xs">{t}бонусов{/t}</span>
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
            </div>

            {include file="%THEME%/paginator.tpl"}
        </div>
    </div>
{/block}