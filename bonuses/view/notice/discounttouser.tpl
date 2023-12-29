{extends file="%alerts%/notice_template.tpl"}
{block name="content"}
    <p>{t}Уважаемый, покупатель!{/t}</p>

    <p>{t}Спасибо за совершаемые у нас покупки. Мы рады сообщить Вам, что в рамках дисконтной программы Вы получаете возможность покупать со скидкой{/t}:</p>

    <p>
    {if $data->user_cost.type=='auto'}
        {$data->user_cost.val_znak} {$data->user_cost.val}{if $data->user_cost.val_type=='sum'}{t}едениц{/t}{else}%{/if}
    {else}
        {t}Ваша цена{/t} {$data->user_cost.title}
    {/if}
    </p>
{/block}