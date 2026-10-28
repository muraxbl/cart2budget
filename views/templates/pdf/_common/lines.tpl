{*
 * Shared lines table. Expects $ctb_head_bg / $ctb_head_color / $ctb_zebra (color or empty).
 * Column widths must add up to 100%.
 *}
{strip}
{assign var=ctb_has_images value=false}
{foreach from=$budget.lines item=line}{if $line.image_data}{assign var=ctb_has_images value=true}{/if}{/foreach}
{assign var=w_name value=44}
{if $budget.has_reductions}{assign var=w_name value=$w_name-8}{/if}
{if $ctb_has_images}{assign var=w_name value=$w_name-7}{/if}
{assign var=th value="background-color: `$ctb_head_bg`; color: `$ctb_head_color`; font-weight: bold; border-bottom: 0.8pt solid `$ctb_head_color`;"}
<table style="width: 100%; font-size: 8pt;" cellpadding="4">
  <thead>
    <tr>
      {if $ctb_has_images}<th style="width: 7%; {$th}"></th>{/if}
      <th style="width: {$w_name}%; {$th}">{l s='Product' mod='carttobudget'}</th>
      <th style="width: 13%; {$th}">{l s='Reference' mod='carttobudget'}</th>
      <th style="width: 11%; {$th} text-align: right;">{l s='Unit price' mod='carttobudget'}</th>
      {if $budget.has_reductions}<th style="width: 8%; {$th} text-align: right;">{l s='Disc.' mod='carttobudget'}</th>{/if}
      <th style="width: 7%; {$th} text-align: center;">{l s='Qty' mod='carttobudget'}</th>
      <th style="width: 8%; {$th} text-align: right;">{l s='VAT' mod='carttobudget'}</th>
      <th style="width: 17%; {$th} text-align: right;">{l s='Total (tax excl.)' mod='carttobudget'}</th>
    </tr>
  </thead>
  <tbody>
    {foreach from=$budget.lines item=line name=pdflines}
      {if $ctb_zebra && $smarty.foreach.pdflines.iteration is even}{assign var=bg value=$ctb_zebra}{else}{assign var=bg value='#ffffff'}{/if}
      {assign var=td value="background-color: `$bg`; border-bottom: 0.3pt solid #dddddd;"}
      <tr nobr="true">
        {if $ctb_has_images}<td style="width: 7%; {$td}">{if $line.image_data}<img src="@{$line.image_data}" width="30" height="30">{/if}</td>{/if}
        <td style="width: {$w_name}%; {$td}">
          <b>{$line.name|escape:'html':'UTF-8'}</b>
          {if $line.attributes}<br><span style="color: #666666; font-size: 7pt;">{$line.attributes|escape:'html':'UTF-8'}</span>{/if}
        </td>
        <td style="width: 13%; {$td}">{$line.reference|escape:'html':'UTF-8'}</td>
        <td style="width: 11%; {$td} text-align: right;">{$line.price_tax_excl|escape:'html':'UTF-8'}</td>
        {if $budget.has_reductions}
          <td style="width: 8%; {$td} text-align: right;">{if $line.reduction_percent > 0}{$line.reduction_label|escape:'html':'UTF-8'}{else}-{/if}</td>
        {/if}
        <td style="width: 7%; {$td} text-align: center;">{$line.quantity|intval}</td>
        <td style="width: 8%; {$td} text-align: right;">{$line.tax_rate_label|escape:'html':'UTF-8'}</td>
        <td style="width: 17%; {$td} text-align: right;">{$line.total_tax_excl|escape:'html':'UTF-8'}</td>
      </tr>
    {/foreach}
  </tbody>
</table>
{/strip}
