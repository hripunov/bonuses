{extends file="%alerts%/notice_template.tpl"}
{block name="content"}
    <p>{t}Уважаемый, администратор!{/t}</p>

    <p>{t partner=$data->partner->getFio() url=$url->getDomainStr() amount=$data->amount}На сайте %url создан запрос на вывод стредств %amount партнером %partner{/t}</p>

    <p>{t domain=$url->getDomainStr() url=$router->getAdminUrl(false, null, 'bonuses-cashoutctrl')}Перейти в <a href="%domain%url">раздел для вывода</a>{/t}.</p>

    <p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>
{/block}