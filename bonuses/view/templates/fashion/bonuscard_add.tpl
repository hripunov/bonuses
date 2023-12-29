{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
<div class="bonusPartnerCardsWrapper fashion form-style">

    <form method="POST" action="{$router->getUrl('bonuses-front-bonuscard', ['Act'=>'add'])}" class="authorization formStyle">
        <h1 data-dialog-options='{ "width": "460" }'>{t}Добавление карты{/t}</h1>
        <div class="forms">
            {if $success}
                <div class="cardAddedSuccess text-center">{t}Карта успешно добавлена{/t}</div>
                {if $url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
                    <script>
                        document.location.href = '{$router->getUrl('bonuses-front-bonuscard')}';
                    </script>
                {/if}
            {else}
                {$this_controller->myBlockIdInput()}
                {csrf}

                {if !empty($errors)}
                    {foreach $errors as $error}
                        <p style="color:red">{$error}</p>
                    {/foreach}
                {/if}

                {$card->getPropertyView('card_id', ['placeholder' => t('Номер карты'), 'class' => 'login', 'autocomplete' => 'off'])}
                <div class="buttons cboth">
                    <button type="submit" class="formSave button color">{t}Отправить{/t}</button>
                </div>
            {/if}
        </div>
    </form>

</div>