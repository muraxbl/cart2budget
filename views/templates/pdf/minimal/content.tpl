{*
 * Minimal template - body (black and white, printer friendly)
 *}
{strip}
{assign var=ctb_head_bg value='#ffffff'}
{assign var=ctb_head_color value='#000000'}
{assign var=ctb_zebra value=''}
{assign var=ctb_total_bg value='#ffffff'}
{assign var=ctb_total_color value='#000000'}

<table style="width: 100%; font-size: 8.5pt;" cellpadding="2">
  <tr>
    <td style="width: 50%;">
      {$budget.shop.name|escape:'html':'UTF-8'}<br>
      <span style="color: #555555;">{$budget.shop.address|escape:'html':'UTF-8'|nl2br}</span>
    </td>
    <td style="width: 50%; text-align: right;">
      {if $budget.recipient.company}{$budget.recipient.company|escape:'html':'UTF-8'}<br>{/if}
      {if $budget.recipient.name}{$budget.recipient.name|escape:'html':'UTF-8'}<br>{/if}
      {if $budget.recipient.vat}{$budget.recipient.vat|escape:'html':'UTF-8'}<br>{/if}
      <span style="color: #555555;">{$budget.recipient.address|escape:'html':'UTF-8'|nl2br}</span>
    </td>
  </tr>
</table>
<br><br>
<span style="font-size: 14pt;">{$budget.name|escape:'html':'UTF-8'}</span><br>
<span style="font-size: 8pt; color: #555555;">
  {l s='Date' mod='carttobudget'}: {$budget.date|escape:'html':'UTF-8'}
  {if $budget.valid_until} &middot; {l s='Valid until' mod='carttobudget'}: {$budget.valid_until|escape:'html':'UTF-8'}{/if}
</span>
<br><br>
{include file=$ctb_pdf_common|cat:'lines.tpl'}
<br><br>
{include file=$ctb_pdf_common|cat:'taxes.tpl'}
{include file=$ctb_pdf_common|cat:'notes.tpl'}
{/strip}
