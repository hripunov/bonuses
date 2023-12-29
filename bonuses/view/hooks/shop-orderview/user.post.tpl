{addcss file="%bonuses%/bonuses_admin.css"}
{if $user.id > 0}
    {$bonuses=$user->getUserBonuses()}
    <table class="otable">
        <tr>
            <td class="otitle">
                {t}Бонусы{/t}:
            </td>
            <td>
                <a href="{$router->getAdminUrl('', ['f'=>['id'=>$user.id]], 'users-ctrl')}" target="_blank">{$bonuses}</a>&nbsp;&nbsp;&nbsp;
                <a href="{$router->getAdminUrl('', ['id'=>$user.id, 'writeoff' => 1, 'do' => 'add'], 'bonuses-bonushistoryctrl')}" class="btn btn-success" target="_blank">{t}Пополнить{/t}</a>&nbsp;&nbsp;&nbsp;
                {if $bonuses > 0}
                    <a href="{$router->getAdminUrl('', ['id'=>$user.id, 'writeoff' => 0, 'bonuses' => $bonuses, 'do' => 'add'], 'bonuses-bonushistoryctrl')}" target="_blank">{t}Списать{/t}</a>
                {/if}
            </td>
        </tr>
        <tr>
            <td class="otitle">
                {t}Дисконтные карты{/t}:
            </td>
            <td>
                {$cards=$user->getBonusCards()}
                {if !empty($cards)}
                    {foreach $cards as $card}
                        <a href="{$router->getAdminUrl('', ['id'=>$card.id, 'do' => 'edit'], 'bonuses-bonuscardctrl')}" target="_blank">{$card->getCardId()}</a> <span class="bonusCardRound {if $card.active}active{/if}" title="{if $card.active}Активна{else}Не активна{/if}"></span><br/>
                    {/foreach}
                    <br/>
                {/if}
                <a href="{$router->getAdminUrl('', ['user_id'=>$user.id, 'do' => 'add'], 'bonuses-bonuscardctrl')}" class="btn btn-success" target="_blank">{t}Добавить{/t}</a>
            </td>
        </tr>
    </table>
{/if}