{*
 * CartToBudget - back office editor of a cart / budget
 *}
<div id="ctb-editor" data-ajax-url="{$ctb_ajax_url|escape:'html':'UTF-8'}">

  {* ---------------------------------------------------------------- Header *}
  <form method="post" action="{$ctb_form_action|escape:'html':'UTF-8'}" class="form-horizontal">
    <input type="hidden" name="id_carttobudget_cart" value="{if !$ctb_is_new}{$ctb_object->id|intval}{/if}">
    <div class="panel">
      <div class="panel-heading">
        <i class="icon-file-text"></i>
        {if $ctb_is_new}
          {l s='New budget' mod='carttobudget'}
        {else}
          {$ctb_data.reference|escape:'html':'UTF-8'} &mdash; {$ctb_object->name|escape:'html':'UTF-8'}
          {if $ctb_object->source == 'customer'}
            <span class="label label-default">{l s='Saved by customer' mod='carttobudget'}</span>
          {else}
            <span class="label label-info">{l s='Back office budget' mod='carttobudget'}</span>
          {/if}
          {if $ctb_data.expired}<span class="label label-danger">{l s='Expired' mod='carttobudget'}</span>{/if}
        {/if}
      </div>

      <div class="row">
        <div class="col-lg-6">
          <div class="form-group">
            <label class="control-label col-lg-4 required">{l s='Name' mod='carttobudget'}</label>
            <div class="col-lg-8">
              <input type="text" name="name" required maxlength="255" value="{$ctb_object->name|escape:'html':'UTF-8'}"
                     placeholder="{l s='e.g. Office lighting renovation' mod='carttobudget'}">
            </div>
          </div>

          <div class="form-group">
            <label class="control-label col-lg-4">{l s='Customer' mod='carttobudget'}</label>
            <div class="col-lg-8">
              <input type="hidden" name="id_customer" id="ctb-id-customer" value="{if $ctb_customer}{$ctb_customer.id|intval}{else}0{/if}">
              <div id="ctb-customer-selected" class="ctb-selected{if !$ctb_customer} hidden{/if}">
                <i class="icon-user"></i>
                <span class="ctb-selected-label">{if $ctb_customer}{$ctb_customer.name|escape:'html':'UTF-8'} &lt;{$ctb_customer.email|escape:'html':'UTF-8'}&gt;{/if}</span>
                <a href="#" class="ctb-clear-customer" title="{l s='Remove' mod='carttobudget'}"><i class="icon-remove"></i></a>
              </div>
              <div class="ctb-search-wrapper">
                <input type="text" id="ctb-customer-search" autocomplete="off"
                       placeholder="{l s='Search by name or e-mail (optional)' mod='carttobudget'}">
                <div class="ctb-results" id="ctb-customer-results"></div>
              </div>
              <p class="help-block">{l s='Optional. Budgets assigned to a customer also appear in their account.' mod='carttobudget'}</p>
              {if $ctb_customer && !$ctb_customer.access}
                <p class="help-block text-warning"><i class="icon-warning"></i> {l s='This customer cannot save carts, but will still see the budgets assigned by the shop.' mod='carttobudget'}</p>
              {/if}
            </div>
          </div>

          <div class="form-group">
            <label class="control-label col-lg-4">{l s='Recipient name' mod='carttobudget'}</label>
            <div class="col-lg-8"><input type="text" name="recipient_name" maxlength="255" value="{$ctb_object->recipient_name|escape:'html':'UTF-8'}"></div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4">{l s='Company' mod='carttobudget'}</label>
            <div class="col-lg-8"><input type="text" name="recipient_company" maxlength="255" value="{$ctb_object->recipient_company|escape:'html':'UTF-8'}"></div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4">{l s='VAT number' mod='carttobudget'}</label>
            <div class="col-lg-8"><input type="text" name="recipient_vat" maxlength="64" value="{$ctb_object->recipient_vat|escape:'html':'UTF-8'}"></div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4">{l s='E-mail' mod='carttobudget'}</label>
            <div class="col-lg-8"><input type="email" name="recipient_email" maxlength="255" value="{$ctb_object->recipient_email|escape:'html':'UTF-8'}"></div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="form-group">
            <label class="control-label col-lg-4">{l s='Address' mod='carttobudget'}</label>
            <div class="col-lg-8">
              <textarea name="recipient_address" rows="3">{$ctb_object->recipient_address|escape:'html':'UTF-8'}</textarea>
              <p class="help-block">{l s='Leave empty to use the first address of the customer.' mod='carttobudget'}</p>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4">{l s='Valid until' mod='carttobudget'}</label>
            <div class="col-lg-8">
              <input type="date" name="valid_until" class="fixed-width-xl" value="{if $ctb_object->valid_until && $ctb_object->valid_until != '0000-00-00'}{$ctb_object->valid_until|escape:'html':'UTF-8'}{/if}">
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4">{l s='PDF template' mod='carttobudget'}</label>
            <div class="col-lg-8">
              <select name="template" class="fixed-width-xl">
                {foreach from=$ctb_templates key=tpl_key item=tpl_label}
                  <option value="{$tpl_key|escape:'html':'UTF-8'}"{if $ctb_object->template == $tpl_key} selected{/if}>{$tpl_label|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
            </div>
          </div>
          {if $ctb_is_new}
            <div class="form-group">
              <label class="control-label col-lg-4">{l s='Language' mod='carttobudget'}</label>
              <div class="col-lg-8">
                <select name="id_lang" class="fixed-width-xl">
                  {foreach from=$ctb_languages item=lang}
                    <option value="{$lang.id_lang|intval}"{if $lang.id_lang == $ctb_default_lang} selected{/if}>{$lang.name|escape:'html':'UTF-8'}</option>
                  {/foreach}
                </select>
                <p class="help-block">{l s='Language of the shared link and the e-mail.' mod='carttobudget'}</p>
              </div>
            </div>
          {/if}
          <div class="form-group">
            <label class="control-label col-lg-4">{l s='Notes' mod='carttobudget'}</label>
            <div class="col-lg-8">
              <textarea name="notes" rows="4">{$ctb_object->notes|escape:'html':'UTF-8'}</textarea>
              <p class="help-block">{l s='Shown on the shared page and in the PDF.' mod='carttobudget'}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="panel-footer">
        <a href="{$ctb_back_url|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-back"></i> {l s='Back to list' mod='carttobudget'}</a>
        <button type="submit" name="submitCtbHeader" class="btn btn-default pull-right">
          <i class="process-icon-save"></i> {if $ctb_is_new}{l s='Create and add products' mod='carttobudget'}{else}{l s='Save' mod='carttobudget'}{/if}
        </button>
      </div>
    </div>
  </form>

  {if !$ctb_is_new}
  <div class="row">
    <div class="col-lg-8">

      {* ------------------------------------------------------------ Lines *}
      <form method="post" action="{$ctb_edit_url|escape:'html':'UTF-8'}">
        <div class="panel">
          <div class="panel-heading">
            <i class="icon-list"></i> {l s='Lines' mod='carttobudget'} <span class="badge">{count($ctb_data.lines)}</span>
          </div>

          {if $ctb_data.lines}
            <div class="table-responsive">
              <table class="table ctb-lines">
                <thead>
                  <tr>
                    <th></th>
                    <th>{l s='Product' mod='carttobudget'}</th>
                    <th class="text-center">{l s='Qty' mod='carttobudget'}</th>
                    <th class="text-right">{l s='Unit price (tax excl.)' mod='carttobudget'}</th>
                    <th class="text-right">{l s='Disc. %' mod='carttobudget'}</th>
                    <th class="text-right">{l s='VAT %' mod='carttobudget'}</th>
                    <th class="text-right">{l s='Total (tax excl.)' mod='carttobudget'}</th>
                    <th class="text-center"><i class="icon-trash" title="{l s='Delete' mod='carttobudget'}"></i></th>
                  </tr>
                </thead>
                <tbody>
                  {foreach from=$ctb_data.lines item=line name=lines}
                    <tr{if !$line.available} class="danger"{/if}>
                      <td class="ctb-thumb">
                        <input type="hidden" name="lines[{$line.id_line|intval}][position]" value="{$smarty.foreach.lines.iteration|intval}" class="ctb-position">
                        <span class="ctb-move">
                          <a href="#" class="ctb-move-up" title="{l s='Move up' mod='carttobudget'}"><i class="icon-caret-up"></i></a>
                          <a href="#" class="ctb-move-down" title="{l s='Move down' mod='carttobudget'}"><i class="icon-caret-down"></i></a>
                        </span>
                        {if $line.image_url}<img src="{$line.image_url|escape:'html':'UTF-8'}" alt="" width="45">{elseif $line.is_custom}<i class="icon-pencil icon-2x text-muted"></i>{/if}
                      </td>
                      <td>
                        {if $line.is_custom}
                          <input type="text" name="lines[{$line.id_line|intval}][custom_name]" value="{$line.name|escape:'html':'UTF-8'}" maxlength="255">
                          <input type="text" name="lines[{$line.id_line|intval}][custom_reference]" value="{$line.reference|escape:'html':'UTF-8'}" maxlength="64"
                                 class="input-sm ctb-mt" placeholder="{l s='Reference' mod='carttobudget'}">
                        {else}
                          <strong>{$line.name|escape:'html':'UTF-8'}</strong>
                          {if $line.attributes}<br><small class="text-muted">{$line.attributes|escape:'html':'UTF-8'}</small>{/if}
                          {if $line.reference}<br><small>{l s='Ref.' mod='carttobudget'} {$line.reference|escape:'html':'UTF-8'}</small>{/if}
                          {if !$line.available}<br><span class="label label-danger">{l s='Not available' mod='carttobudget'}</span>{/if}
                        {/if}
                      </td>
                      <td class="text-center"><input type="number" min="1" step="1" class="ctb-input-qty" name="lines[{$line.id_line|intval}][quantity]" value="{$line.quantity|intval}"></td>
                      <td class="text-right"><input type="text" class="ctb-input-num" name="lines[{$line.id_line|intval}][price_tax_excl]" value="{$line.price_input|escape:'html':'UTF-8'}"></td>
                      <td class="text-right"><input type="text" class="ctb-input-pct" name="lines[{$line.id_line|intval}][reduction_percent]" value="{$line.reduction_percent|floatval}"></td>
                      <td class="text-right"><input type="text" class="ctb-input-pct" name="lines[{$line.id_line|intval}][tax_rate]" value="{$line.tax_rate|floatval}" list="ctb-tax-rates"></td>
                      <td class="text-right">
                        <strong>{$line.total_tax_excl|escape:'html':'UTF-8'}</strong>
                        {if $line.reduction_percent > 0}<br><small class="text-muted">{$line.unit_price_tax_excl|escape:'html':'UTF-8'} / {l s='unit' mod='carttobudget'}</small>{/if}
                      </td>
                      <td class="text-center"><input type="checkbox" name="lines[{$line.id_line|intval}][delete]" value="1"></td>
                    </tr>
                  {/foreach}
                </tbody>
              </table>
            </div>
          {else}
            <div class="alert alert-info">{l s='This budget has no lines yet. Use the forms below to add products or free lines.' mod='carttobudget'}</div>
          {/if}

          {if $ctb_data.lines}
          <div class="panel-footer">
            <button type="submit" name="submitCtbUpdateLines" class="btn btn-default pull-right"><i class="process-icon-refresh"></i> {l s='Update lines' mod='carttobudget'}</button>
            <button type="submit" name="submitCtbRefreshPrices" class="btn btn-default"
                    onclick="return confirm('{l s='Replace prices and VAT rates with the current catalog values? Line discounts are kept.' mod='carttobudget' js=1}');">
              <i class="process-icon-update"></i> {l s='Use catalog prices' mod='carttobudget'}
            </button>
          </div>
          {/if}
        </div>
      </form>
      <datalist id="ctb-tax-rates">
        {foreach from=$ctb_tax_rates item=rate}<option value="{$rate|floatval}">{/foreach}
      </datalist>

      {* ------------------------------------------------------ Add product *}
      <form method="post" action="{$ctb_edit_url|escape:'html':'UTF-8'}" id="ctb-add-product-form">
        <div class="panel">
          <div class="panel-heading"><i class="icon-plus"></i> {l s='Add a product' mod='carttobudget'}</div>
          <input type="hidden" name="add_id_product" id="ctb-add-id-product" value="">
          <input type="hidden" name="add_id_product_attribute" id="ctb-add-id-product-attribute" value="0">
          <div class="row">
            <div class="col-lg-6">
              <label>{l s='Product' mod='carttobudget'}</label>
              <div class="ctb-search-wrapper">
                <input type="text" id="ctb-product-search" autocomplete="off" placeholder="{l s='Search by name, reference or EAN' mod='carttobudget'}">
                <div class="ctb-results" id="ctb-product-results"></div>
              </div>
              <div id="ctb-product-selected" class="ctb-selected hidden"><i class="icon-cube"></i> <span class="ctb-selected-label"></span></div>
            </div>
            <div class="col-lg-2">
              <label>{l s='Quantity' mod='carttobudget'}</label>
              <input type="number" name="add_quantity" min="1" step="1" value="1">
            </div>
            <div class="col-lg-2">
              <label>{l s='Price (tax excl.)' mod='carttobudget'}</label>
              <div class="input-group">
                <input type="text" name="add_price" id="ctb-add-price" placeholder="{l s='Catalog' mod='carttobudget'}">
                <span class="input-group-addon">{$ctb_currency_sign|escape:'html':'UTF-8'}</span>
              </div>
            </div>
            <div class="col-lg-2">
              <label>{l s='Discount' mod='carttobudget'}</label>
              <div class="input-group">
                <input type="text" name="add_reduction" value="0">
                <span class="input-group-addon">%</span>
              </div>
            </div>
          </div>
          <div class="panel-footer">
            <button type="submit" name="submitCtbAddProduct" class="btn btn-default pull-right" id="ctb-add-product-btn" disabled>
              <i class="process-icon-plus"></i> {l s='Add' mod='carttobudget'}
            </button>
          </div>
        </div>
      </form>

      {* -------------------------------------------------- Add custom line *}
      <form method="post" action="{$ctb_edit_url|escape:'html':'UTF-8'}">
        <div class="panel">
          <div class="panel-heading"><i class="icon-pencil"></i> {l s='Add a free line (service, shipping, installation...)' mod='carttobudget'}</div>
          <div class="row">
            <div class="col-lg-4">
              <label class="required">{l s='Description' mod='carttobudget'}</label>
              <input type="text" name="custom_name" maxlength="255" required>
            </div>
            <div class="col-lg-2">
              <label>{l s='Reference' mod='carttobudget'}</label>
              <input type="text" name="custom_reference" maxlength="64">
            </div>
            <div class="col-lg-1">
              <label>{l s='Qty' mod='carttobudget'}</label>
              <input type="number" name="custom_quantity" min="1" step="1" value="1">
            </div>
            <div class="col-lg-2">
              <label>{l s='Price (tax excl.)' mod='carttobudget'}</label>
              <input type="text" name="custom_price" value="0">
            </div>
            <div class="col-lg-1">
              <label>{l s='Disc. %' mod='carttobudget'}</label>
              <input type="text" name="custom_reduction" value="0">
            </div>
            <div class="col-lg-2">
              <label>{l s='VAT %' mod='carttobudget'}</label>
              <input type="text" name="custom_tax_rate" list="ctb-tax-rates" value="{if $ctb_tax_rates}{$ctb_tax_rates[0]|floatval}{else}0{/if}">
            </div>
          </div>
          <div class="panel-footer">
            <button type="submit" name="submitCtbAddCustom" class="btn btn-default pull-right"><i class="process-icon-plus"></i> {l s='Add line' mod='carttobudget'}</button>
          </div>
        </div>
      </form>
    </div>

    <div class="col-lg-4">

      {* ----------------------------------------------------------- Totals *}
      <div class="panel">
        <div class="panel-heading"><i class="icon-money"></i> {l s='Totals' mod='carttobudget'}</div>
        <table class="table ctb-totals">
          {if $ctb_data.has_reductions}
            <tr><td>{l s='Gross amount' mod='carttobudget'}</td><td class="text-right">{$ctb_data.totals.before_reduction|escape:'html':'UTF-8'}</td></tr>
            <tr><td>{l s='Discounts' mod='carttobudget'}</td><td class="text-right">- {$ctb_data.totals.reduction|escape:'html':'UTF-8'}</td></tr>
          {/if}
          <tr><td>{l s='Tax base' mod='carttobudget'}</td><td class="text-right">{$ctb_data.totals.tax_excl|escape:'html':'UTF-8'}</td></tr>
          {foreach from=$ctb_data.taxes item=tax}
            <tr class="ctb-tax-row">
              <td>{l s='VAT' mod='carttobudget'} {$tax.rate_label|escape:'html':'UTF-8'} <small class="text-muted">({l s='base' mod='carttobudget'} {$tax.base|escape:'html':'UTF-8'})</small></td>
              <td class="text-right">{$tax.amount|escape:'html':'UTF-8'}</td>
            </tr>
          {/foreach}
          <tr class="ctb-grand-total"><td>{l s='Total (tax incl.)' mod='carttobudget'}</td><td class="text-right">{$ctb_data.totals.tax_incl|escape:'html':'UTF-8'}</td></tr>
        </table>
      </div>

      {* ------------------------------------------------------------ Share *}
      <div class="panel">
        <div class="panel-heading"><i class="icon-share-alt"></i> {l s='Share' mod='carttobudget'}</div>
        {if $ctb_object->share_active}
          <div class="input-group">
            <input type="text" readonly id="ctb-public-url" value="{$ctb_data.public_url|escape:'html':'UTF-8'}" onclick="this.select();">
            <span class="input-group-btn">
              <button type="button" class="btn btn-default ctb-copy" data-target="#ctb-public-url" data-copied="{l s='Copied!' mod='carttobudget'}" title="{l s='Copy link' mod='carttobudget'}"><i class="icon-copy"></i></button>
              <a class="btn btn-default" href="{$ctb_data.public_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener" title="{l s='Open' mod='carttobudget'}"><i class="icon-external-link"></i></a>
            </span>
          </div>
          <p class="help-block">{l s='Anyone with this link can see the budget, download it as PDF and add it to their cart.' mod='carttobudget'}</p>
        {else}
          <div class="alert alert-warning">{l s='The shared link is disabled.' mod='carttobudget'}</div>
        {/if}

        <form method="post" action="{$ctb_edit_url|escape:'html':'UTF-8'}" class="ctb-inline-actions">
          <button type="submit" name="submitCtbToggleShare" class="btn btn-default">
            {if $ctb_object->share_active}<i class="icon-lock"></i> {l s='Disable link' mod='carttobudget'}{else}<i class="icon-unlock"></i> {l s='Enable link' mod='carttobudget'}{/if}
          </button>
          <button type="submit" name="submitCtbRegenerateToken" class="btn btn-default"
                  onclick="return confirm('{l s='The current link will stop working. Continue?' mod='carttobudget' js=1}');">
            <i class="icon-refresh"></i> {l s='New link' mod='carttobudget'}
          </button>
        </form>

        <hr>
        <form method="get" action="{$ctb_edit_url|escape:'html':'UTF-8'}" target="_blank" class="ctb-pdf-form">
          {* the current index parameters must be repeated in GET forms *}
          <input type="hidden" name="controller" value="AdminCartToBudget">
          <input type="hidden" name="token" value="{$smarty.get.token|escape:'html':'UTF-8'}">
          <input type="hidden" name="id_carttobudget_cart" value="{$ctb_object->id|intval}">
          <input type="hidden" name="updatecarttobudget_cart" value="1">
          <input type="hidden" name="downloadPdf" value="1">
          <label>{l s='PDF' mod='carttobudget'}</label>
          <div class="input-group">
            <select name="template">
              {foreach from=$ctb_templates key=tpl_key item=tpl_label}
                <option value="{$tpl_key|escape:'html':'UTF-8'}"{if $ctb_data.template == $tpl_key} selected{/if}>{$tpl_label|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <span class="input-group-btn">
              <button type="submit" name="inline" value="1" class="btn btn-default" title="{l s='Preview' mod='carttobudget'}"><i class="icon-eye"></i></button>
              <button type="submit" class="btn btn-primary" title="{l s='Download' mod='carttobudget'}"><i class="icon-download"></i></button>
            </span>
          </div>
        </form>
      </div>

      {* ------------------------------------------------------------ Email *}
      <form method="post" action="{$ctb_edit_url|escape:'html':'UTF-8'}">
        <div class="panel">
          <div class="panel-heading"><i class="icon-envelope"></i> {l s='Send by e-mail' mod='carttobudget'}</div>
          <div class="form-group">
            <label class="required">{l s='E-mail' mod='carttobudget'}</label>
            <input type="email" name="email_to" required value="{$ctb_data.recipient.email|escape:'html':'UTF-8'}">
          </div>
          <div class="form-group">
            <label>{l s='Name' mod='carttobudget'}</label>
            <input type="text" name="email_name" value="{$ctb_data.recipient.name|escape:'html':'UTF-8'}">
          </div>
          <div class="form-group">
            <label>{l s='Message' mod='carttobudget'}</label>
            <textarea name="email_message" rows="3"></textarea>
          </div>
          <div class="checkbox">
            <label><input type="checkbox" name="email_attach_pdf" value="1" checked> {l s='Attach the PDF' mod='carttobudget'}</label>
          </div>
          <div class="panel-footer">
            <button type="submit" name="submitCtbSendEmail" class="btn btn-default pull-right"><i class="process-icon-envelope"></i> {l s='Send' mod='carttobudget'}</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  {/if}
</div>
