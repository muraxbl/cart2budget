{*
 * Minimal template - header: small logo, thin rule
 *}
{strip}
<table style="width: 100%; border-bottom: 0.5pt solid #000000;" cellpadding="2">
  <tr>
    <td style="width: 50%;">
      {if $budget.shop.logo_data}
        <img src="@{$budget.shop.logo_data}" height="30">
      {else}
        <span style="font-size: 12pt;">{$budget.shop.name|escape:'html':'UTF-8'}</span>
      {/if}
    </td>
    <td style="width: 50%; text-align: right; font-size: 9pt;">
      {l s='Budget' mod='carttobudget'} {$budget.reference|escape:'html':'UTF-8'}<br>
      {$budget.date|escape:'html':'UTF-8'}
    </td>
  </tr>
</table>
{/strip}
