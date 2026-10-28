{*
 * CartToBudget - public page of a shared cart / budget
 *}
{extends file='page.tpl'}

{block name='page_title'}
  {if $ctb_budget}
    {l s='Budget' mod='carttobudget'} {$ctb_budget.reference}
  {else}
    {l s='Budget not found' mod='carttobudget'}
  {/if}
{/block}

{block name='page_content'}
  {if !$ctb_budget}
    <div class="alert alert-warning">
      {l s='This budget does not exist or is no longer shared.' mod='carttobudget'}
    </div>
  {else}
    <div class="ctb-budget">
      <div class="ctb-budget-head">
        <div class="ctb-budget-shop">
          {if $ctb_budget.shop.logo_url}
            <img src="{$ctb_budget.shop.logo_url}" alt="{$ctb_budget.shop.name}" class="ctb-budget-logo">
          {/if}
          <div class="ctb-budget-shop-data">
            <strong>{$ctb_budget.shop.name}</strong><br>
            {$ctb_budget.shop.address|escape:'html':'UTF-8'|nl2br nofilter}
            {if $ctb_budget.shop.phone}<br>{$ctb_budget.shop.phone}{/if}
            {if $ctb_budget.shop.email}<br>{$ctb_budget.shop.email}{/if}
          </div>
        </div>
        <dl class="ctb-budget-data">
          <dt>{l s='Number' mod='carttobudget'}</dt><dd>{$ctb_budget.reference}</dd>
          <dt>{l s='Name' mod='carttobudget'}</dt><dd>{$ctb_budget.name}</dd>
          <dt>{l s='Date' mod='carttobudget'}</dt><dd>{$ctb_budget.date}</dd>
          {if $ctb_budget.valid_until}
            <dt>{l s='Valid until' mod='carttobudget'}</dt>
            <dd>{$ctb_budget.valid_until}{if $ctb_budget.expired} <span class="ctb-badge ctb-badge-danger">{l s='Expired' mod='carttobudget'}</span>{/if}</dd>
          {/if}
          {if $ctb_budget.recipient.name || $ctb_budget.recipient.company}
            <dt>{l s='For' mod='carttobudget'}</dt>
            <dd>
              {if $ctb_budget.recipient.company}{$ctb_budget.recipient.company}<br>{/if}
              {$ctb_budget.recipient.name}
              {if $ctb_budget.recipient.vat}<br>{l s='VAT number' mod='carttobudget'}: {$ctb_budget.recipient.vat}{/if}
            </dd>
          {/if}
        </dl>
      </div>

      {if $ctb_budget.expired}
        <div class="alert alert-warning">{l s='This budget has expired. Prices and availability may have changed.' mod='carttobudget'}</div>
      {/if}

      <div class="table-responsive">
        <table class="table ctb-budget-table">
          <thead>
            <tr>
              <th colspan="2">{l s='Product' mod='carttobudget'}</th>
              <th class="text-right">{l s='Unit price' mod='carttobudget'}</th>
              {if $ctb_budget.has_reductions}<th class="text-right">{l s='Disc.' mod='carttobudget'}</th>{/if}
              <th class="text-center">{l s='Qty' mod='carttobudget'}</th>
              <th class="text-right">{l s='VAT' mod='carttobudget'}</th>
              <th class="text-right">{l s='Total (tax excl.)' mod='carttobudget'}</th>
              <th class="text-right">{l s='Total (tax incl.)' mod='carttobudget'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$ctb_budget.lines item=line}
              <tr{if !$line.available} class="ctb-unavailable"{/if}>
                <td class="ctb-budget-thumb">
                  {if $line.image_url}<img src="{$line.image_url}" alt="" loading="lazy">{/if}
                </td>
                <td>
                  {if $line.product_url && $line.available}
                    <a href="{$line.product_url}">{$line.name}</a>
                  {else}
                    {$line.name}
                  {/if}
                  {if $line.attributes}<br><small>{$line.attributes}</small>{/if}
                  {if $line.reference}<br><small class="text-muted">{l s='Ref.' mod='carttobudget'} {$line.reference}</small>{/if}
                  {if !$line.available}<br><small class="text-danger">{l s='Currently not available' mod='carttobudget'}</small>{/if}
                </td>
                <td class="text-right" data-label="{l s='Unit price' mod='carttobudget'}">{$line.price_tax_excl}</td>
                {if $ctb_budget.has_reductions}
                  <td class="text-right" data-label="{l s='Disc.' mod='carttobudget'}">{if $line.reduction_percent > 0}{$line.reduction_label}{else}-{/if}</td>
                {/if}
                <td class="text-center" data-label="{l s='Qty' mod='carttobudget'}">{$line.quantity|intval}</td>
                <td class="text-right" data-label="{l s='VAT' mod='carttobudget'}">{$line.tax_rate_label}</td>
                <td class="text-right" data-label="{l s='Total (tax excl.)' mod='carttobudget'}">{$line.total_tax_excl}</td>
                <td class="text-right" data-label="{l s='Total (tax incl.)' mod='carttobudget'}"><strong>{$line.total_tax_incl}</strong></td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>

      <div class="ctb-budget-bottom">
        <div class="ctb-budget-notes">
          {if $ctb_budget.notes}
            <h4>{l s='Notes' mod='carttobudget'}</h4>
            <p>{$ctb_budget.notes|escape:'html':'UTF-8'|nl2br nofilter}</p>
          {/if}
        </div>

        <div class="ctb-budget-summary">
          <table class="table">
            {if $ctb_budget.has_reductions}
              <tr><td>{l s='Gross amount' mod='carttobudget'}</td><td class="text-right">{$ctb_budget.totals.before_reduction}</td></tr>
              <tr><td>{l s='Discounts' mod='carttobudget'}</td><td class="text-right">- {$ctb_budget.totals.reduction}</td></tr>
            {/if}
            <tr><td>{l s='Subtotal (tax excl.)' mod='carttobudget'}</td><td class="text-right">{$ctb_budget.totals.tax_excl}</td></tr>
          </table>

          <table class="table ctb-taxes">
            <thead>
              <tr>
                <th>{l s='VAT' mod='carttobudget'}</th>
                <th class="text-right">{l s='Base' mod='carttobudget'}</th>
                <th class="text-right">{l s='Amount' mod='carttobudget'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$ctb_budget.taxes item=tax}
                <tr>
                  <td>{$tax.rate_label}</td>
                  <td class="text-right">{$tax.base}</td>
                  <td class="text-right">{$tax.amount}</td>
                </tr>
              {/foreach}
            </tbody>
          </table>

          <table class="table">
            <tr><td>{l s='Total VAT' mod='carttobudget'}</td><td class="text-right">{$ctb_budget.totals.tax}</td></tr>
            <tr class="ctb-grand-total"><td>{l s='Total (tax incl.)' mod='carttobudget'}</td><td class="text-right">{$ctb_budget.totals.tax_incl}</td></tr>
          </table>
        </div>
      </div>

      <div class="ctb-budget-actions">
        <form method="get" action="{$ctb_pdf_action}" class="ctb-pdf-form">
          {foreach from=$ctb_pdf_params key=param item=value}
            <input type="hidden" name="{$param}" value="{$value}">
          {/foreach}
          {if $ctb_templates}
            <select name="template" class="form-control">
              {foreach from=$ctb_templates key=tpl_key item=tpl_label}
                <option value="{$tpl_key}"{if $tpl_key == $ctb_budget.template} selected{/if}>{$tpl_label}</option>
              {/foreach}
            </select>
          {/if}
          <button type="submit" class="btn btn-secondary">
            <i class="material-icons">picture_as_pdf</i> {l s='Download PDF' mod='carttobudget'}
          </button>
        </form>

        {if !$ctb_budget.expired}
          <form method="post" action="{$ctb_add_url}" class="ctb-add-form">
            <input type="hidden" name="static_token" value="{$ctb_static_token}">
            <input type="hidden" name="submitAddBudgetToCart" value="1">
            {if $ctb_cart_has_products}
              <label class="ctb-replace">
                <input type="checkbox" name="replace" value="1">
                {l s='Empty my current cart first' mod='carttobudget'}
              </label>
            {/if}
            <button type="submit" class="btn btn-primary">
              <i class="material-icons">add_shopping_cart</i> {l s='Add to cart' mod='carttobudget'}
            </button>
          </form>
        {/if}
      </div>

      {if $ctb_budget.footer_text}
        <p class="ctb-budget-footer">{$ctb_budget.footer_text|escape:'html':'UTF-8'|nl2br nofilter}</p>
      {/if}
    </div>
  {/if}
{/block}
