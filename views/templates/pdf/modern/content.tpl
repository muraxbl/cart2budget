{*
 * Modern template - body
 *}
{strip}
{assign var=ctb_head_bg value=$ctb_color}
{assign var=ctb_head_color value='#ffffff'}
{assign var=ctb_zebra value='#f4f6f9'}
{assign var=ctb_total_bg value=$ctb_color}
{assign var=ctb_total_color value='#ffffff'}

<table style="width: 100%; font-size: 8.5pt;" cellpadding="3">
  <tr>
    <td style="width: 34%;">
      <span style="font-size: 7pt; color: #888888;">{l s='ISSUED BY' mod='carttobudget'}</span><br>
      <b>{$budget.shop.name|escape:'html':'UTF-8'}</b><br>
      {$budget.shop.address|escape:'html':'UTF-8'|nl2br}
      {if $budget.shop.details}<br>{$budget.shop.details|escape:'html':'UTF-8'|nl2br}{/if}
    </td>
    <td style="width: 34%;">
      <span style="font-size: 7pt; color: #888888;">{l s='PREPARED FOR' mod='carttobudget'}</span><br>
      {if $budget.recipient.company}<b>{$budget.recipient.company|escape:'html':'UTF-8'}</b><br>{/if}
      {if $budget.recipient.name}{$budget.recipient.name|escape:'html':'UTF-8'}<br>{/if}
      {if $budget.recipient.vat}{l s='VAT number' mod='carttobudget'}: {$budget.recipient.vat|escape:'html':'UTF-8'}<br>{/if}
      {if $budget.recipient.address}{$budget.recipient.address|escape:'html':'UTF-8'|nl2br}<br>{/if}
      {$budget.recipient.email|escape:'html':'UTF-8'}
    </td>
    <td style="width: 32%; background-color: #f4f6f9;">
      <span style="font-size: 7pt; color: #888888;">{l s='DATE' mod='carttobudget'}</span><br>
      <b>{$budget.date|escape:'html':'UTF-8'}</b><br>
      <span style="font-size: 7pt; color: #888888;">{l s='VALID UNTIL' mod='carttobudget'}</span><br>
      <b>{if $budget.valid_until}{$budget.valid_until|escape:'html':'UTF-8'}{else}-{/if}</b><br>
      <span style="font-size: 7pt; color: #888888;">{l s='TOTAL' mod='carttobudget'}</span><br>
      <b style="font-size: 12pt; color: {$ctb_color};">{$budget.totals.tax_incl|escape:'html':'UTF-8'}</b>
    </td>
  </tr>
</table>
<br><br>
<span style="font-size: 12pt; font-weight: bold; color: {$ctb_color};">{$budget.name|escape:'html':'UTF-8'}</span>
<br><br>
{include file=$ctb_pdf_common|cat:'lines.tpl'}
<br><br>
{include file=$ctb_pdf_common|cat:'taxes.tpl'}
{include file=$ctb_pdf_common|cat:'notes.tpl'}
{/strip}
