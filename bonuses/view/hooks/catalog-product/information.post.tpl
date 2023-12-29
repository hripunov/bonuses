{addcss file="%bonuses%/bonuses.css"}
{addjs file="%bonuses%/bonuses.js"}
{assign var=bonuses_config value=ConfigLoader::byModule('bonuses')}
{$bonuses=$product->getBonusesWithOrderRules()}
{if $bonuses && $product->bonusesCanBeShown()}
    <p class="bonusesProductAmount {$bonuses_config['default_template']}">{t d=$bonuses}<b>+ <span id="productBonuses">%d</span></b> [plural:%d:бонусный|бонусных|бонусных] [plural:%d:балл|балла|баллов]{/t}</p>
{/if}