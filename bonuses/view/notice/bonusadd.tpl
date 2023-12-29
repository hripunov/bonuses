{extends file="%alerts%/notice_template.tpl"}
{block name="content"}
    <p>{t}Уважаемый, покупатель!{/t}</p>

    <p>{t url=$url->getDomainStr() b=$data->bonuses}На сайте %url Вам зачислено %b бонусных баллов{/t}</p>

    <p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>
{/block}