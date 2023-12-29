{$config=ConfigLoader::byModule('bonuses')}
{if $config.show_bonus_card_register}
    {if $config.default_template == 'default'}
        <tbody>
            <tr>
                <td class="key">{t}Дисконтная карта{/t}</td>
                <td class="value">
                    {$user->getPropertyView('bonus_card')}
                    <div class="help">{t}Карта выданая магазином. Если нет, то пропустите.{/t}</div>
                </td>
            </tr>
        </tbody>
    {elseif $config.default_template == 'fashion'}
        <div class="half fright">
            <div class="formLine">
                <label class="fieldName">{t}Дисконтная карта{/t}</label>
                {$user->getPropertyView('bonus_card')}
            </div>
        </div>
    {elseif $config.default_template == 'flatlines'}
        <div class="form-group">
            <label class="label-sup">{t}Дисконтная карта{/t}</label>
            {$user->getPropertyView('bonus_card')}
        </div>
    {elseif $config.default_template == 'perfume'}
        <div class="half fright">
            <div class="formLine">
                <label class="fielName">{t}Дисконтная карта{/t}</label><br>
                {$user->getPropertyView('bonus_card')}
            </div>
        </div>
    {elseif $config.default_template == 'young'}
        <div class="half fright">
            <div class="formLine">
                <label class="fielName">{t}Дисконтная карта{/t}</label><br>
                {$user->getPropertyView('bonus_card')}
            </div>
        </div>
    {/if}
{/if}