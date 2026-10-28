{*
 * Modern template - header: full-width colour band
 *}
{strip}
<table style="width: 100%; background-color: {$ctb_color};" cellpadding="8">
  <tr>
    <td style="width: 50%; color: #ffffff;">
      {if $budget.shop.logo_data}
        <img src="@{$budget.shop.logo_data}" height="40">
      {else}
        <span style="font-size: 16pt; font-weight: bold;">{$budget.shop.name|escape:'html':'UTF-8'}</span>
      {/if}
    </td>
    <td style="width: 50%; text-align: right; color: #ffffff;">
      <span style="font-size: 20pt; font-weight: bold;">{l s='Budget' mod='carttobudget'}</span><br>
      <span style="font-size: 9pt;">{l s='No.' mod='carttobudget'} {$budget.reference|escape:'html':'UTF-8'}</span>
    </td>
  </tr>
</table>
{/strip}
