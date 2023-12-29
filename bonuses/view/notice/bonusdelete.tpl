{extends file="%alerts%/notice_template.tpl"}
{block name="content"}
    <p>{t}Уважаемый, покупатель!{/t}</p>

    <p>{t url=$url->getDomainStr() bonuses=$data->bonuses}На сайте %url с Вас списано %bonuses бонусных баллов{/t}</p>

    <p>{t url=$router->getUrl('bonuses-front-bonushistory', [], true)}Подробнее можно узнать <a href="%url">по ссылке</a>{/t}.</p>

    <p>{t url=$url->getDomainStr()}Автоматическая рассылка %url.{/t}</p>
{/block}