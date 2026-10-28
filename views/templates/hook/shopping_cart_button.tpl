{*
 * CartToBudget - "save cart" box on the cart page
 *}
<div id="ctb-save-box" class="card ctb-save-box">
  <h5 class="ctb-save-title">
    <i class="material-icons">save</i>
    {l s='Save cart for later' mod='carttobudget'}
  </h5>
  <p class="ctb-save-text">{l s='Save your current cart to buy it later or share it as a budget.' mod='carttobudget'}</p>
  <div class="ctb-save-form">
    <input type="text" id="ctb-cart-name" class="form-control" maxlength="255"
           placeholder="{l s='Cart name (optional)' mod='carttobudget'}">
    <button type="button" id="ctb-save-cart-btn" class="btn btn-primary">
      {l s='Save cart' mod='carttobudget'}
    </button>
  </div>
  <a href="{$ctb_list_url}" class="ctb-view-carts">
    {l s='View saved carts' mod='carttobudget'} &rarr;
  </a>
</div>
