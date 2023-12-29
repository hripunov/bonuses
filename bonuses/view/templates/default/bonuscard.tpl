{addcss file="%bonuses%/bonuses.css"}
<div class="bonusCardWrapper">
    {if $bonus_card}
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
        <form class="form" method="POST">
            {if !$bonus_card.active}
                <input type="submit" name="active" class="formSave" value="Активировать"/>
            {else}
                <p class="amount">{t}Количество бонусных баллов на карте{/t} - {$bonus_card.amount}</p>
            {/if}
            
            {if $bonus_card.active && $bonus_card.amount}
                <input type="submit" name="use_bonus" class="formSave" value="Зачислить себе бонусы"/>
            {/if}
        </form>
    {else}
        <p>{t}К сожалению ни одной карты к Вам пока не привязано{/t}</p>

        <a href="{$router->getUrl('bonuses-front-bonuscard', ['Act'=>'add'])}" class="formSave inDialog rs-in-dialog">{t}Добавить карту{/t}</a>
    {/if}
</div>