{*
 * CartToBudget - "My saved carts" (customer account)
 *}
{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='My saved carts and budgets' mod='carttobudget'}
{/block}

{block name='page_content'}
  <div class="ctb-carts">
    {if $ctb_carts}
      {foreach from=$ctb_carts item=cart}
        <article class="card ctb-cart" data-id="{$cart.id|intval}">
          <header class="ctb-cart-header">
            <div class="ctb-cart-info">
              <h3 class="ctb-cart-title">
                <i class="material-icons">{if $cart.source == 'admin'}request_quote{else}shopping_cart{/if}</i>
                {$cart.name}
              </h3>
              <div class="ctb-cart-meta">
                <span>{$cart.reference}</span>
                <span>{l s='Saved on:' mod='carttobudget'} {$cart.date}</span>
                {if $cart.source == 'admin'}
                  <span class="ctb-badge ctb-badge-info">{l s='Budget from the shop' mod='carttobudget'}</span>
                  {if $cart.valid_until}
                    <span class="ctb-badge {if $cart.expired}ctb-badge-danger{else}ctb-badge-success{/if}">
                      {if $cart.expired}{l s='Expired' mod='carttobudget'}{else}{l s='Valid until' mod='carttobudget'} {$cart.valid_until}{/if}
                    </span>
                  {/if}
                {elseif $cart.days_remaining !== null}
                  {if $cart.days_remaining > 1}
                    <span class="ctb-badge ctb-badge-success">{l s='Expires in %d days' sprintf=[$cart.days_remaining] mod='carttobudget'}</span>
                  {else}
                    <span class="ctb-badge ctb-badge-warning">{l s='Expires today' mod='carttobudget'}</span>
                  {/if}
                {/if}
              </div>
            </div>
            <div class="ctb-cart-total">
              <span class="ctb-cart-total-label">{l s='Total (tax incl.)' mod='carttobudget'}</span>
              <strong>{$cart.totals.tax_incl}</strong>
            </div>
          </header>

          {if $cart.lines}
            <ul class="ctb-cart-lines">
              {foreach from=$cart.lines item=line}
                <li class="ctb-cart-line">
                  {if $line.image_url}
                    <img src="{$line.image_url}" alt="{$line.name}" loading="lazy">
                  {else}
                    <span class="ctb-no-image"><i class="material-icons">image</i></span>
                  {/if}
                  <div class="ctb-cart-line-info">
                    <span class="ctb-cart-line-name">{$line.name}</span>
                    {if $line.attributes}<small>{$line.attributes}</small>{/if}
                    <small>
                      {if $line.reference}{l s='Ref.' mod='carttobudget'} {$line.reference} &middot; {/if}
                      {$line.quantity|intval} &times; {$line.unit_price_tax_incl}
                    </small>
                  </div>
                </li>
              {/foreach}
            </ul>
          {/if}

          <footer class="ctb-cart-actions">
            {if $cart.share_active}
              <div class="ctb-share">
                <input type="text" readonly class="form-control ctb-share-url" value="{$cart.public_url}">
                <button type="button" class="btn btn-secondary ctb-copy-link" title="{l s='Copy link' mod='carttobudget'}">
                  <i class="material-icons">link</i>
                </button>
              </div>
            {/if}
            <a class="btn btn-secondary" href="{$cart.public_url}" target="_blank" rel="noopener">
              <i class="material-icons">visibility</i> {l s='View budget' mod='carttobudget'}
            </a>
            <a class="btn btn-secondary" href="{$cart.pdf_url}">
              <i class="material-icons">picture_as_pdf</i> PDF
            </a>
            <button type="button" class="btn btn-primary ctb-btn-restore" data-id="{$cart.id|intval}">
              <i class="material-icons">restore</i> {l s='Restore' mod='carttobudget'}
            </button>
            {if $cart.can_delete}
              <button type="button" class="btn btn-danger ctb-btn-delete" data-id="{$cart.id|intval}">
                <i class="material-icons">delete</i> {l s='Delete' mod='carttobudget'}
              </button>
            {/if}
          </footer>
        </article>
      {/foreach}
    {else}
      <div class="ctb-empty">
        <i class="material-icons">shopping_cart</i>
        <h2>{l s='No saved carts yet' mod='carttobudget'}</h2>
        <p>{l s='Add products to your cart and save it from the cart page.' mod='carttobudget'}</p>
        <a href="{$urls.pages.index}" class="btn btn-primary">{l s='Start shopping' mod='carttobudget'}</a>
      </div>
    {/if}

    {if $ctb_enabled}
      <p class="ctb-limits">
        {if $ctb_expiration_days > 0}{l s='Saved carts expire after %d days.' sprintf=[$ctb_expiration_days] mod='carttobudget'}{/if}
        {if $ctb_max_carts > 0}{l s='Maximum %d saved carts.' sprintf=[$ctb_max_carts] mod='carttobudget'}{/if}
      </p>
    {/if}
  </div>
{/block}
