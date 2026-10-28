{*
 * Shared PDF footer: legal text + shop data
 *}
{strip}
<table style="width: 100%; border-top: 0.5pt solid #cccccc;" cellpadding="2">
  <tr>
    <td style="width: 100%; text-align: center; font-size: 7pt; color: #555555;">
      {if $budget.footer_text}{$budget.footer_text|escape:'html':'UTF-8'|nl2br}<br>{/if}
      {$budget.shop.name|escape:'html':'UTF-8'}{if $budget.shop.address} &middot; {$budget.shop.address|escape:'html':'UTF-8'|replace:"\n":' - '}{/if}
      {if $budget.shop.phone} &middot; {$budget.shop.phone|escape:'html':'UTF-8'}{/if}
      {if $budget.shop.email} &middot; {$budget.shop.email|escape:'html':'UTF-8'}{/if}
    </td>
  </tr>
</table>
{/strip}
