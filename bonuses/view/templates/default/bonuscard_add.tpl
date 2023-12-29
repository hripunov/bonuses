{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
<div class="bonusPartnerCardsWrapper flatlines form-style modal-body mobile-width-small">
    {if $url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
        <h2 data-dialog-options='{ "width": "380" }'>{t}Добавление карты{/t}</h2>
    {/if}

    {if $success}
        <div class="cardAddedSuccess text-center">{t}Карта успешно добавлена{/t}</div>
        {if $url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
            <script>
                document.location.href = '{$router->getUrl('bonuses-front-bonuscard')}';
            </script>
        {/if}
    {else}
        <form method="POST" action="{$router->getUrl('bonuses-front-bonuscard', ['Act'=>'add'])}" class="authorization">
            {$this_controller->myBlockIdInput()}
            {csrf}
            <div class="dialogForm">
                {if !empty($errors)}
                    {foreach $errors as $error}
                        <p style="color:red">{$error}</p>
                    {/foreach}
                {/if}

                {$card->getPropertyView('card_id', ['placeholder' => t('Номер карты'), 'class' => 'login', 'autocomplete' => 'off'])}
            </div>
            <button type="submit" class="formSave">{t}Отправить{/t}</button>
        </form>
    {/if}
</div>