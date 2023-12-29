{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
{$is_dialog_wrap=$url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
<div class="bonusPartnerCardsWrapper flatlines form-style modal-body mobile-width-small">
    {if $is_dialog_wrap}
        <h2 class="h2">{t}Добавление карты{/t}</h2>
    {/if}

    {if $success}
        <div class="cardAddedSuccess text-center">{t}Карта успешно добавлена{/t}</div>
        {if $url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
            <script>
                document.location.href = '{$router->getUrl('bonuses-front-bonuscard')}';
            </script>
        {/if}
    {else}
        {if !empty($errors)}
            {foreach $errors as $error}
                <p style="color:red">{$error}</p>
            {/foreach}
        {/if}

        <form method="POST" action="{$router->getUrl('bonuses-front-bonuscard', ['Act'=>'add'])}">
            {$this_controller->myBlockIdInput()}
            {csrf}
            {$card->getPropertyView('card_id', ['placeholder' => t('Номер карты'), 'autocomplete' => 'off'])}

            <div class="form__menu_buttons mobile-flex">
                <button type="submit" class="link link-more">{t}Отправить{/t}</button>
            </div>
        </form>
    {/if}
</div>