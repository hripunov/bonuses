/**
 * Обновляем порядковый номер в name аттрибуте
 */
function updateBonusesForOrderNums()
{
    const rowsWrapper = $('#bonusesForOrderRulesInsert');
    let n = 0;
    let reg = new RegExp('bonuses_for_order_rule_arr\\[\\d?\\]', 'i');
    $('tr', rowsWrapper).each(function(){
        $('input', $(this)).each(function(){
            let name = $(this).attr('name');
            name = name.replace(reg, 'bonuses_for_order_rule_arr[' + n + ']');
            $(this).attr('name', name);
        });
        $('select', $(this)).each(function(){
            console.log('n',  n);
            let name = $(this).attr('name');
            console.log('name',  name);
            name = name.replace(reg, 'bonuses_for_order_rule_arr[' + n + ']');
            console.log('reg',  reg);
            console.log('name',  name);
            $(this).attr('name', name);
        });
        n++;
    });

}

$(document).ready(function(){
    /////////////////////////////// Бонусы за заказ ///////////////////////////////////////
    /**
     * Переключение начисления по правилам
     */
    $('body').on('change', '[name="bonuses_for_order_as_table"]', function(){
        const checked = $(this).prop('checked');
        $('#bonusesForOrderByRulesTitle').toggle(checked);
        $('#bonusesForOrderUsualWrapper').toggle(!checked);
        $('#bonusesForOrderRulesWrapper').toggle(checked);
        $('#addBonusesForOrderRule').toggle(checked);
    }).on('click', '.closeBonusForOrderItem', function(){
        $(this).closest('tr').remove();
        updateBonusesForOrderNums();
        return false;
    }).on('click', '#addBonusesForOrderRule', function(){
        const rowsWrapper = $('#bonusesForOrderRulesInsert');
        const emptyRow = $(".empty-row", rowsWrapper);
        if (emptyRow.length){
            emptyRow.remove();
        }
        rowsWrapper.append(`
            <tr>
                <td>
                    <input name="bonuses_for_order_rule_arr[][from]" type="number" min="0" value="0" placeholder="${lang.t('от/свыше')}" size="9"/>
                </td>
                <td>
                    <input name="bonuses_for_order_rule_arr[][to]" type="number" min="0" placeholder="${lang.t('до')}" size="9"/>
                </td>
                <td>
                    <input name="bonuses_for_order_rule_arr[][bonuses]" type="number" min="0" placeholder="${lang.t('Кол-во бонусов')}" size="9"/>
                </td>
                <td>
                    <select name="bonuses_for_order_rule_arr[][bonuses_type]" class="bonusesForOrderType">
                        <option value="ед.">
                            ${lang.t('ед.')}
                        </option>
                        <option value="%">
                            ${lang.t('% от суммы')}
                        </option>
                    </select>
                </td>
                <td>
                    <button type="button" class="closeBonusForOrderItem"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEwAACxMBAJqcGAAAALxJREFUOI3dkM0NwjAMhe2wQPLsdSBAgRFAjMjPCszAFQmxQKtOgFRzaaUKJYErvOOz/fnZRP+lEMJdRJa5uledArhmAV41AqhTEK86hUgDYFNM4VUjRBoRqUbeDEANYP3ezzmIMzuT2b5z7um67sTM+7ZtL8XtqSS5kwa5XGFixmRmxJxMWQSIyMLMjsy8Y6KtER1KKd6Hq/6J8zGw98oQACuINF41JsDzT/8gAI/U8CCvGn0It69O+R29AKhuM+UapPVgAAAAAElFTkSuQmCC" alt="Удалить"/></button>
                </td>
            </tr>
        `);
        updateBonusesForOrderNums();
    });
});
