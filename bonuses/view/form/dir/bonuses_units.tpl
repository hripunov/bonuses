{if $elem.id==0}
    {assign var=bonuses_config value=ConfigLoader::byModule('bonuses')}
    <input type="text" name="bonuses_units" value="{$bonuses_config.default_dir_bonuses_units}"/> 
    
    <select size="1" name="bonuses_units_type">
        <option class="lev_0" {if $bonuses_config.default_dir_bonuses_units_type==0}selected="selected"{/if} value="0" data-value="ед.">ед.</option>
        <option class="lev_1" {if $bonuses_config.default_dir_bonuses_units_type==1}selected="selected"{/if} value="1" data-value="в % от цены товара">в % от цены товара</option>

    </select>
{else}

    {include file=$field->getOriginalTemplate() field=$elem.__bonuses_units} {$elem.__bonuses_units_type->formView()}
{/if}
