{*
 * Shared VAT breakdown + totals block. Expects $ctb_head_bg / $ctb_head_color.
 *}
{strip}
<table style="width: 100%;" cellpadding="3" nobr="true">
  <tr>
    <td style="width: 52%;">
      <table style="width: 100%; font-size: 8pt;" cellpadding="3">
        <tr>
          <td style="width: 34%; background-color: {$ctb_head_bg}; color: {$ctb_head_color}; font-weight: bold;">{l s='VAT rate' mod='carttobudget'}</td>
          <td style="width: 33%; background-color: {$ctb_head_bg}; color: {$ctb_head_color}; font-weight: bold; text-align: right;">{l s='Tax base' mod='carttobudget'}</td>
          <td style="width: 33%; background-color: {$ctb_head_bg}; color: {$ctb_head_color}; font-weight: bold; text-align: right;">{l s='VAT amount' mod='carttobudget'}</td>
        </tr>
        {foreach from=$budget.taxes item=tax}
          <tr>
            <td style="border-bottom: 0.3pt solid #dddddd;">{$tax.rate_label|escape:'html':'UTF-8'}</td>
            <td style="border-bottom: 0.3pt solid #dddddd; text-align: right;">{$tax.base|escape:'html':'UTF-8'}</td>
            <td style="border-bottom: 0.3pt solid #dddddd; text-align: right;">{$tax.amount|escape:'html':'UTF-8'}</td>
          </tr>
        {/foreach}
      </table>
    </td>
    <td style="width: 8%;"></td>
    <td style="width: 40%;">
      <table style="width: 100%; font-size: 9pt;" cellpadding="3">
        {if $budget.has_reductions}
          <tr>
            <td style="width: 55%;">{l s='Gross amount' mod='carttobudget'}</td>
            <td style="width: 45%; text-align: right;">{$budget.totals.before_reduction|escape:'html':'UTF-8'}</td>
          </tr>
          <tr>
            <td>{l s='Discounts' mod='carttobudget'}</td>
            <td style="text-align: right;">- {$budget.totals.reduction|escape:'html':'UTF-8'}</td>
          </tr>
        {/if}
        <tr>
          <td style="width: 55%;">{l s='Subtotal (tax excl.)' mod='carttobudget'}</td>
          <td style="width: 45%; text-align: right;">{$budget.totals.tax_excl|escape:'html':'UTF-8'}</td>
        </tr>
        <tr>
          <td>{l s='Total VAT' mod='carttobudget'}</td>
          <td style="text-align: right;">{$budget.totals.tax|escape:'html':'UTF-8'}</td>
        </tr>
        <tr>
          <td style="background-color: {$ctb_total_bg}; color: {$ctb_total_color}; font-weight: bold; font-size: 11pt;">{l s='TOTAL' mod='carttobudget'}</td>
          <td style="background-color: {$ctb_total_bg}; color: {$ctb_total_color}; font-weight: bold; font-size: 11pt; text-align: right;">{$budget.totals.tax_incl|escape:'html':'UTF-8'}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
{/strip}
