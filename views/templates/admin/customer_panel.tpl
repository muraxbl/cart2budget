{*
 * CartToBudget - panel on the back office customer page (hook displayAdminCustomers)
 *}
<div class="col-md-12">
<div class="card panel ctb-customer-panel">
  <h3 class="card-header panel-heading">
    <i class="icon-shopping-cart"></i>
    {l s='Saved carts and budgets' mod='carttobudget'}
    <span class="badge">{count($ctb_carts)}</span>
  </h3>
  <div class="card-body">
    <p>
      {l s='Saved carts feature:' mod='carttobudget'}
      {if $ctb_enabled}
        <span class="label label-success badge badge-success">{l s='Enabled' mod='carttobudget'}</span>
      {else}
        <span class="label label-danger badge badge-danger">{l s='Disabled' mod='carttobudget'}</span>
      {/if}
      {if !$ctb_has_override}<small class="text-muted">({l s='shop default' mod='carttobudget'})</small>{/if}
      <a class="btn btn-default btn-outline-secondary btn-sm" href="{$ctb_toggle_url|escape:'html':'UTF-8'}">
        {if $ctb_enabled}{l s='Disable for this customer' mod='carttobudget'}{else}{l s='Enable for this customer' mod='carttobudget'}{/if}
      </a>
      <a class="btn btn-primary btn-sm" href="{$ctb_new_url|escape:'html':'UTF-8'}">
        {l s='New budget for this customer' mod='carttobudget'}
      </a>
    </p>

    {if $ctb_carts}
      <table class="table">
        <thead>
          <tr>
            <th>{l s='Name' mod='carttobudget'}</th>
            <th>{l s='Origin' mod='carttobudget'}</th>
            <th class="text-right">{l s='Total (tax incl.)' mod='carttobudget'}</th>
            <th>{l s='Created' mod='carttobudget'}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$ctb_carts item=cart}
            <tr>
              <td>{$cart.name|escape:'html':'UTF-8'}</td>
              <td>{if $cart.source == 'admin'}{l s='Budget' mod='carttobudget'}{else}{l s='Saved by customer' mod='carttobudget'}{/if}</td>
              <td class="text-right">{$cart.total|escape:'html':'UTF-8'}</td>
              <td>{$cart.date_add|escape:'html':'UTF-8'}</td>
              <td class="text-right"><a class="btn btn-default btn-sm" href="{$cart.edit_url|escape:'html':'UTF-8'}">{l s='Open' mod='carttobudget'}</a></td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    {/if}
  </div>
</div>
</div>
