{extends "%THEME%/helper/wrapper/dialog/standard.tpl"}

{block "title"}{t}Добавить карту{/t}{/block}
{block "body"}
    {addcss file="%bonuses%/bonuses.css"}
    {$config=ConfigLoader::byModule('bonuses')}
    {$is_dialog_wrap=$url->request('dialogWrap', $smarty.const.TYPE_INTEGER)}
    <div class="modal-body">
        <div class="bonusPartnerCardsWrapper amazing form-style">
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
                    <div class="row">
                        <div class="col mb-3">
                            {$card->getPropertyView('card_id', ['placeholder' => t('Номер карты'), 'autocomplete' => 'off'])}
                        </div>
                    </div>
                    <div class="form__menu_buttons mobile-flex">
                        <button type="submit" class="link link-more btn btn-primary">{t}Отправить{/t}</button>
                    </div>
                </form>
            {/if}
        </div>
    </div>
{/block}