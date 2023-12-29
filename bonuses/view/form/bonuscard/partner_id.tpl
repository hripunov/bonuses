{$user_partner=$elem->getUserPartner()}
<b>
    {if $user_partner.id}
        <a href="{$router->getAdminUrl('edit', ['id'=>$elem.partner_id], 'users-ctrl')}" target="_blank">{$user_partner->getFio()}</a>
    {else}
        {t}Пользователь друг был ранее удален{/t}
    {/if}
</b>