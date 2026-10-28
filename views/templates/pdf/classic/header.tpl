{*
 * Classic template - header: logo on the left, budget title and number on the right
 *}
{strip}
<table style="width: 100%;" cellpadding="2">
  <tr>
    <td style="width: 55%;">
      {if $budget.shop.logo_data}
        <img src="@{$budget.shop.logo_data}" style="height: 45px;" height="45">
      {else}
        <span style="font-size: 16pt; font-weight: bold;">{$budget.shop.name|escape:'html':'UTF-8'}</span>
      {/if}
    </td>
    <td style="width: 45%; text-align: right;">
      <span style="font-size: 18pt; font-weight: bold; color: {$ctb_color};">{l s='BUDGET' mod='carttobudget'}</span><br>
      <span style="font-size: 9pt;">{$budget.reference|escape:'html':'UTF-8'} &middot; {$budget.date|escape:'html':'UTF-8'}</span>
    </td>
  </tr>
</table>
{/strip}
