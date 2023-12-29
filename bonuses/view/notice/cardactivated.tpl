{extends file="%alerts%/notice_template.tpl"}
{block name="content"}
    <p>{t}Уважаемый, покупатель!{/t}</p>

    <p>{t card=$data->card->getCardId()}Ваша бонусная карта %card активирована.{/t}</p

    <p>{t url=$routet->getUrl('bonuses-front-bonuscard', [], true)}Перейдите в <a href="%url">личный кабинет бонусных карт</a>.{/t}</p>

    <p>{t}Автоматическая рассылка{/t} {$url->getDomainStr()}.</p>
{/block}