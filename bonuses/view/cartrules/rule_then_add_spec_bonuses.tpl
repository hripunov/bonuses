<div>Начислить бонусы на <span style="font-weight: bold;">указанные товары</span>
    {$this->getProductDialogHtml()}</div>
<div>в <select name="rule[then][%index%][discountType]">
        {foreach $this->handbookDiscountTypes() as $key=>$item}
            <option value="{$key}" {if $discountType == $key}selected{/if}>{$item}</option>
        {/foreach}
    </select></div>
<div> по следующим количественным правилам:
    <div><span style="font-weight: bold;">до </span> <input type="text" size="3" name="rule[then][%index%][quantity1]"
            value="{$quantity1}"> шт. — <input type="text" size="5" name="rule[then][%index%][amount1]"
            value="{$amount1}"> иначе</div>
    <div><span style="font-weight: bold;">до </span> <input type="text" size="3" name="rule[then][%index%][quantity2]"
            value="{$quantity2}"> шт. — <input type="text" size="5" name="rule[then][%index%][amount2]"
            value="{$amount2}"> иначе</div>
    <div><span style="font-weight: bold;">до </span> <input type="text" size="3" name="rule[then][%index%][quantity3]"
            value="{$quantity3}"> шт. — <input type="text" size="5" name="rule[then][%index%][amount3]"
            value="{$amount3}"> иначе </div>
    <div><input type="text" size="5" name="rule[then][%index%][amount4]" value="{$amount4}"></div>
</div>