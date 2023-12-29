{addcss file="%bonuses%/bonuses_admin.css"}
{addjs file="%bonuses%/bonuses_admin.js"}
<input type="checkbox" name="bonuses_for_order_as_table" value="1" title="{t}Использовать начисление по правилам?{/t}" {if $elem.bonuses_for_order_as_table}checked{/if}/>
<div id="bonusesForOrderUsualWrapper" class="bonusesForOrderUsualWrapper" {if $elem.bonuses_for_order_as_table}style="display: none"{/if}>
    {include file=$field->getOriginalTemplate() field=$elem.__bonuses_for_order} {$elem.__bonuses_for_order_type->formView()}
</div>
<div id="bonusesForOrderByRulesTitle" class="bonusesForOrderByRulesTitle" {if !$elem.bonuses_for_order_as_table}style="display: none"{/if}>
    {t}по правилам{/t}
</div>
<div id="bonusesForOrderRulesWrapper" {if !$elem.bonuses_for_order_as_table}style="display: none"{/if}>
    <table  class="bonusesForOrderRulesWrapper otable">
        <thead>
            <tr>
                <th>
                    {t}от{/t}
                </th>
                <th>
                    {t}до{/t}
                </th>
                <th>
                    {t}кол-во{/t}
                </th>
                <th>
                    {t}ед.{/t}
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody id="bonusesForOrderRulesInsert">
            {if !empty($elem.bonuses_for_order_rule_arr)}
                {$m=0}
                {foreach $elem.bonuses_for_order_rule_arr as $rule}
                    <tr>
                        <td>
                            <input name="bonuses_for_order_rule_arr[{$m}][from]" type="number" min="0" value="{$rule.from}" placeholder="{t}от/свыше{/t}" size="9"/>
                        </td>
                        <td>
                            <input name="bonuses_for_order_rule_arr[{$m}][to]" type="number" min="0" value="{$rule.to}" placeholder="{t}до{/t}" size="9"/>
                        </td>
                        <td>
                            <input name="bonuses_for_order_rule_arr[{$m}][bonuses]" type="number" min="0" value="{$rule.bonuses}" placeholder="{t}Кол-во бонусов{/t}" size="9"/>
                        </td>
                        <td>
                            <select name="bonuses_for_order_rule_arr[{$m}][bonuses_type]" class="bonusesForOrderType">
                                <option value="ед." {if $rule.bonuses_type == 'ед.'}selected{/if}>
                                    {t}ед.{/t}
                                </option>
                                <option value="%" {if $rule.bonuses_type == '%'}selected{/if}>
                                    {t}% от суммы{/t}
                                </option>
                            </select>
                        </td>
                        <td>
                            <button type="button" class="closeBonusForOrderItem"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEwAACxMBAJqcGAAAALxJREFUOI3dkM0NwjAMhe2wQPLsdSBAgRFAjMjPCszAFQmxQKtOgFRzaaUKJYErvOOz/fnZRP+lEMJdRJa5uledArhmAV41AqhTEK86hUgDYFNM4VUjRBoRqUbeDEANYP3ezzmIMzuT2b5z7um67sTM+7ZtL8XtqSS5kwa5XGFixmRmxJxMWQSIyMLMjsy8Y6KtER1KKd6Hq/6J8zGw98oQACuINF41JsDzT/8gAI/U8CCvGn0It69O+R29AKhuM+UapPVgAAAAAElFTkSuQmCC" alt="Удалить"/></button>
                        </td>
                    </tr>
                    {$m=$m+1}
                {/foreach}
            {else}
                <tr class="empty-row">
                    <td colspan="5" align="center">
                        {t}Добавьте правило{/t}
                    </td>
                </tr>
            {/if}
        </tbody>
    </table>
    <div class="mb-20 mt-10">
        <input type="checkbox" name="bonuses_for_order_with_old" value="1" {if $elem.bonuses_for_order_with_old}checked{/if}/> - Учитывать сумму уже выполненных ранее заказов
    </div>
    <button id="addBonusesForOrderRule" class="btn btn-default" type="button" {if !$elem.bonuses_for_order_as_table}style="display: none"{/if}>
        {t}Добавить{/t}
    </button>
</div>