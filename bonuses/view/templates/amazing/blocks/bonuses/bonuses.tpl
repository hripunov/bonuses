{addcss file="%bonuses%/bonuses.css"}
{assign var=bonuses_config value=ConfigLoader::byModule('bonuses')}
<div class="allBonusesWrapper {$bonuses_config.default_template}">
   {t}Количество Ваших бонусных баллов{/t} - <span class="bonuses">{$current_user->getUserBonuses()}</span>
</div>