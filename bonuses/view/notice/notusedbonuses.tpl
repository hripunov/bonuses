{extends file="%alerts%/notice_template.tpl"}
{block name="content"}
    <p>{t}Уважаемый, покупатель!{/t} </p>

    <p>{t url=$url->getDomainStr() bonuses=$data->bonuses}У Вас на сайте %url сейчас %bonuses бонусных баллов.{/t}</p>

    {if $data->notify_time == 1}
        <p>{t}Это отличный шанс получить скидку. Не упустите его.{/t}</p>
    {else}
        <p>{t}Осталось совсем немного, чтобы использовать свою скидку.{/t}</p>
    {/if}
    <p>{t lastdays=$data->last_days lastdate=$data->last_date}Ваши бонусы сгорят через %lastdays дней(я) (%lastdate).{/t}</p>
{/block}
