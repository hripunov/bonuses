{addjs file="tmpl.min.js" basepath="common"}

<table class="otable">
    <tr>
        <td class="otitle">
            {$elem->__discount_register_price_id->description}
        </td>
        <td>
            {include file=$elem.__discount_register_price_id->getRenderTemplate() field=$elem.__discount_register_price_id}
        </td>
    </tr>
    <tr>
        <td class="otitle">
            {$elem->__discount_order_status->description}
        </td>
        <td>
            {include file=$elem.__discount_order_status->getRenderTemplate() field=$elem.__discount_order_status}
        </td>
    </tr>
    <tr>
        <td class="otitle">
            {$elem->__discount_admin_not_usage->description}
        </td>
        <td>
            {include file=$elem.__discount_admin_not_usage->getRenderTemplate() field=$elem.__discount_admin_not_usage}
        </td>
    </tr>
    <tr>
        <td class="otitle">
            {t}Правила дисконтной программы{/t}
        </td>
        <td>
            {* Правила дисконтной программы *}
            <div class="discountSystemWrapper">
                {static_call var=prices callback=['\Catalog\Model\CostApi','staticSelectList']}
                <table id="discountSystemTable">
                    {$m=0}
                    {foreach $elem.discount_rule_arr as $discount_rule}
                        <tr>
                            <td>
                                {$m}.
                            </td>
                            <td>
                                {t}Если общая сумма заказов больше{/t}
                            </td>
                            <td>
                                <input class="discount_summ" type="text" name="discount_rule_arr[{$m}][summ]" value="{$discount_rule.summ}" size="6"/>
                            </td>
                            <td>
                                {t}то цена{/t}  
                            </td>
                            <td>
                                <select class="discount_price" name="discount_rule_arr[{$m}][price_id]">
                                    <option value="0">-Не выбрано-</option>
                                    {foreach $prices as $price_id=>$price}
                                        <option value="{$price_id}" {if $discount_rule.price_id==$price_id}selected{/if}>{$price}</option>
                                    {/foreach}
                                </select>
                            </td>
                            <td>
                                <a class="closeDiscountSystem"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEwAACxMBAJqcGAAAALxJREFUOI3dkM0NwjAMhe2wQPLsdSBAgRFAjMjPCszAFQmxQKtOgFRzaaUKJYErvOOz/fnZRP+lEMJdRJa5uledArhmAV41AqhTEK86hUgDYFNM4VUjRBoRqUbeDEANYP3ezzmIMzuT2b5z7um67sTM+7ZtL8XtqSS5kwa5XGFixmRmxJxMWQSIyMLMjsy8Y6KtER1KKd6Hq/6J8zGw98oQACuINF41JsDzT/8gAI/U8CCvGn0It69O+R29AKhuM+UapPVgAAAAAElFTkSuQmCC" alt="Удалить"/></a>
                            </td>
                        </tr>
                        {$m=$m+1}
                    {/foreach}
                </table>
                        
            </div>
            <a id="addDiscountItem" class="button">Добавить ещё</a>    
        </td>
    </tr>
    <tr>
        <td class="otitle">
            {$elem->__use_default_price_for_sale->description}
        </td>
        <td>
            {include file=$elem.__use_default_price_for_sale->getRenderTemplate() field=$elem.__use_default_price_for_sale}
        </td>
    </tr>
</table>




<script type="text/javascript">
    /**
    * Добавление строки
    */
    $("#addDiscountItem").on('click', function(){
        //Посчитаем сколько элементов уже, если и добавим нужную позицию
        var len  = $("#discountSystemTable tr").length;
        var num  = len+1;
        var item = $('<tbody><tr>'+
                '<td>'+
                    '<span class="num"></span>.'+
                '</td>'+
                '<td>'+
                    '{t}Если общая сумма заказов больше{/t}'+
                '</td>'+
                '<td>'+
                    '<input class="discount_summ" type="text" name="discount_rule_arr[0][summ]" value="0"/>'+
                '</td>'+
                '<td>'+
                    '{t}то цена{/t}'+
                '</td>'+
                '<td>'+
                    '<select class="discount_price" name="discount_rule_arr[0][price_id]">'+
                        '<option value="0">-Не выбрано-</option>'+
                        '{foreach $prices as $price_id=>$price}'+
                            '<option value="{$price_id}">{$price}</option>'+
                        '{/foreach}'+
                    '</select>'+
                '</td>'+
                '<td>'+
                    '<a class="closeDiscountSystem"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEwAACxMBAJqcGAAAALxJREFUOI3dkM0NwjAMhe2wQPLsdSBAgRFAjMjPCszAFQmxQKtOgFRzaaUKJYErvOOz/fnZRP+lEMJdRJa5uledArhmAV41AqhTEK86hUgDYFNM4VUjRBoRqUbeDEANYP3ezzmIMzuT2b5z7um67sTM+7ZtL8XtqSS5kwa5XGFixmRmxJxMWQSIyMLMjsy8Y6KtER1KKd6Hq/6J8zGw98oQACuINF41JsDzT/8gAI/U8CCvGn0It69O+R29AKhuM+UapPVgAAAAAElFTkSuQmCC" alt="Удалить"/></a>'+
                '</td>'+
            '</tr></tbody>');
        $(".discount_summ", item).attr('name', 'discount_rule_arr['+len+'][summ]');
        $(".discount_price", item).attr('name', 'discount_rule_arr['+len+'][price_id]');
        $(".num", item).html(num);
        $("#discountSystemTable").append(item.html());
    });

    /**
    * Удаление строки
    */
    $("body").on('click', ".closeDiscountSystem", function(){
        $(this).closest('tr').remove();
    });
</script>