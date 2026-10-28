{*
 * Classic template - body
 *}
{strip}
{assign var=ctb_head_bg value='#eeeeee'}
{assign var=ctb_head_color value='#333333'}
{assign var=ctb_zebra value=''}
{assign var=ctb_total_bg value=$ctb_color}
{assign var=ctb_total_color value='#ffffff'}

<table style="width: 100%; font-size: 8.5pt;" cellpadding="4">
  <tr>
    <td style="width: 48%; border: 0.3pt solid #cccccc;">
      <b style="color: {$ctb_color};">{$budget.shop.name|escape:'html':'UTF-8'}</b><br>
      {$budget.shop.address|escape:'html':'UTF-8'|nl2br}
      {if $budget.shop.details}<br>{$budget.shop.details|escape:'html':'UTF-8'|nl2br}{/if}
      {if $budget.shop.phone}<br>{l s='Phone' mod='carttobudget'}: {$budget.shop.phone|escape:'html':'UTF-8'}{/if}
      {if $budget.shop.email}<br>{$budget.shop.email|escape:'html':'UTF-8'}{/if}
    </td>
    <td style="width: 4%;"></td>
    <td style="width: 48%; border: 0.3pt solid #cccccc;">
      <b style="color: {$ctb_color};">{l s='Customer' mod='carttobudget'}</b><br>
      {if $budget.recipient.company}{$budget.recipient.company|escape:'html':'UTF-8'}<br>{/if}
      {if $budget.recipient.name}{$budget.recipient.name|escape:'html':'UTF-8'}<br>{/if}
      {if $budget.recipient.vat}{l s='VAT number' mod='carttobudget'}: {$budget.recipient.vat|escape:'html':'UTF-8'}<br>{/if}
      {if $budget.recipient.address}{$budget.recipient.address|escape:'html':'UTF-8'|nl2br}<br>{/if}
      {if $budget.recipient.email}{$budget.recipient.email|escape:'html':'UTF-8'}{/if}
    </td>
  </tr>
</table>
<br><br>
<table style="width: 100%; font-size: 8.5pt;" cellpadding="3">
  <tr>
    <td style="width: 25%;"><b>{l s='Budget number' mod='carttobudget'}</b><br>{$budget.reference|escape:'html':'UTF-8'}</td>
    <td style="width: 25%;"><b>{l s='Date' mod='carttobudget'}</b><br>{$budget.date|escape:'html':'UTF-8'}</td>
    <td style="width: 25%;"><b>{l s='Valid until' mod='carttobudget'}</b><br>{if $budget.valid_until}{$budget.valid_until|escape:'html':'UTF-8'}{else}-{/if}</td>
    <td style="width: 25%;"><b>{l s='Reference' mod='carttobudget'}</b><br>{$budget.name|escape:'html':'UTF-8'}</td>
  </tr>
</table>
<br><br>
{include file=$ctb_pdf_common|cat:'lines.tpl'}
<br><br>
{include file=$ctb_pdf_common|cat:'taxes.tpl'}
{include file=$ctb_pdf_common|cat:'notes.tpl'}
{/strip}
