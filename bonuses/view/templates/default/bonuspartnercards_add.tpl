{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
<div class="bonusPartnerCardsWrapper flatlines form-style modal-body mobile-width-small">
    {if $url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
        <h2 data-dialog-options='{ "width": "380" }'>{t}Добавление карты{/t}</h2>
    {/if}

    {if $success}
        <div class="cardAddedSuccess text-center">{t}Карта успешно добавлена{/t}</div>
        <div class="text-center">
            <a href="{$router->getUrl('bonuses-front-bonuspartnercards', ['Act'=>'add'])}" class="formSave colorButton inDialog rs-in-dialog">{t}Добавить ещё карту{/t}</a>
        </div>
    {else}

        <form method="POST" action="{$router->getUrl('bonuses-front-bonuspartnercards', ['Act'=>'add'])}" class="authorization">
            {$this_controller->myBlockIdInput()}
            {csrf}
            <div class="dialogForm">
                {if !empty($errors)}
                    {foreach $errors as $error}
                        <p style="color:red">{$error}</p>
                    {/foreach}
                {/if}

                {$card->getPropertyView('card_id', ['placeholder' => t('Номер карты пользователя'), 'class' => 'login', 'autocomplete' => 'off'])}
            </div>
            <button type="submit" class="formSave">{t}Отправить{/t}</button>
        </form>
    {/if}
</div>