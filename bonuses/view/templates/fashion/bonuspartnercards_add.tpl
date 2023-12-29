{addcss file="%bonuses%/bonuses.css"}
{$config=ConfigLoader::byModule('bonuses')}
<div class="bonusPartnerCardsWrapper fashion form-style">


        <form method="POST" action="{$router->getUrl('bonuses-front-bonuspartnercards', ['Act'=>'add'])}" class="authorization formStyle">
            <h1 data-dialog-options='{ "width": "460" }'>{t}Добавление карты{/t}</h1>
            <div class="forms">
                {if $success}

                    <div class="cardAddedSuccess text-center">{t}Карта успешно добавлена{/t}</div>
                    <div class="text-center">
                        <a href="{$router->getUrl('bonuses-front-bonuspartnercards', ['Act'=>'add'])}" class="formSave button color inDialog rs-in-dialog">{t}Добавить ещё карту{/t}</a>
                    </div>
                {else}
                    {$this_controller->myBlockIdInput()}
                    {csrf}

                        {if !empty($errors)}
                            {foreach $errors as $error}
                                <p style="color:red">{$error}</p>
                            {/foreach}
                        {/if}

                        {$card->getPropertyView('card_id', ['placeholder' => t('Номер карты пользователя'), 'class' => 'login', 'autocomplete' => 'off'])}
                        <div class="buttons cboth">
                            <button type="submit" class="formSave button color">{t}Отправить{/t}</button>
                        </div>

                {/if}
            </div>
        </form>

</div>