{extends file="%THEME%/helper/wrapper/my-cabinet.tpl"}
{block name="content"}
    {addcss file="%bonuses%/bonuses.css"}
    <div class="col">
        <div class="bonusCardWrapper flatlines personalAccount">

            {if $bonus_card}
                <div class="tocenter">
                    <div class="bonusCard">
                        <div class="title">
                            Номер Вашей карты
                        </div>
                        <div class="bonusCardTitle">
                            {$bonus_card->getCardId()}
                        </div>
                        <div class="cardBottom">
                        </div>
                    </div>

                    {if !$bonus_card.active}
                        <p class="noActive">Не активирована</p>
                    {/if}
                    {if $bonus_card.active}
                        <p class="amount">{t}Количество бонусных баллов на карте{/t} - {$bonus_card.amount}</p>
                    {/if}
                </div>
                <div class="bonusHistoryWrapper">
                <form class="form bonusHistoryAmount balance tocenter" method="POST">
                    {if !$bonus_card.active}
                        <input type="submit" name="active" class="formSave btn btn-primary link-more" value="Активировать"/>
                    {/if}

                    {if $bonus_card.active && $bonus_card.amount}
                        <input type="submit" name="use_bonus" class="colorButton addFunds btn btn-primary link-more" value="Зачислить себе бонусы"/>
                    {/if}
                </form>
                </div>
            {else}
                <p class="empty">{t}К сожалению ни одной карты к Вам пока не привязано{/t}
                    <a href="{$router->getUrl('bonuses-front-bonuscard', ['Act'=>'add'])}" class="link link-more inDialog rs-in-dialog">{t}Добавить карту{/t}</a>
                </p>
            {/if}
        </div>
    </div>
{/block}