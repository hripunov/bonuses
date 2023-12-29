{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
<div class="bonusPartnerCardsWrapper fashion form-style">


        <form method="POST" action="{$router->getUrl('bonuses-front-bonuscard', ['Act'=>'add'])}" class="authorization formStyle">
            <h2 data-dialog-options='{ "width": "460" }'>{t}Добавление карты{/t}</h2>
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

                        <div class="formLine">
                            <label class="fielName">{t}Номер карты{/t}</label><br>
                            {$card->getPropertyView('card_id', ['size' => 42, 'autocomplete' => 'off'])}
                        </div>
                        <input type="submit" class="formSave colorButton" value="{t}Отправить{/t}"/>

                {/if}
            </div>
        </form>

</div>