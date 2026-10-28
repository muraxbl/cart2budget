<?php
/**
 * CartToBudget - "My saved carts" page of the customer account
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CarttobudgetCartsModuleFrontController extends ModuleFrontController
{
    /** @var CartToBudget */
    public $module;

    public $ssl = true;
    public $auth = true;
    public $authRedirection = 'my-account';

    public function init()
    {
        parent::init();

        $id_customer = (int) $this->context->customer->id;
        if ($id_customer
            && !CartToBudget::isCustomerEnabled($id_customer)
            && !CartToBudget::countCustomerBudgets($id_customer)
        ) {
            Tools::redirect($this->context->link->getPageLink('my-account'));
        }
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->module->registerFrontAssets();
    }

    public function initContent()
    {
        parent::initContent();

        $id_customer = (int) $this->context->customer->id;
        $presenter = new CartToBudgetPresenter($this->context);

        $carts = [];
        foreach (CartToBudget::getCustomerCarts($id_customer) as $cart) {
            $data = $presenter->present($cart);
            $data['days_remaining'] = null;
            if ($cart->source === CartToBudgetCart::SOURCE_CUSTOMER && CartToBudget::getExpirationDays() > 0) {
                $data['days_remaining'] = max(0, (int) ceil(
                    (strtotime($cart->date_add) + CartToBudget::getExpirationDays() * 86400 - time()) / 86400
                ));
            }
            $data['share_active'] = (bool) $cart->share_active;
            $data['can_delete'] = $cart->source === CartToBudgetCart::SOURCE_CUSTOMER;
            $carts[] = $data;
        }

        $this->context->smarty->assign([
            'ctb_carts' => $carts,
            'ctb_enabled' => CartToBudget::isCustomerEnabled($id_customer),
            'ctb_expiration_days' => CartToBudget::getExpirationDays(),
            'ctb_max_carts' => CartToBudget::getMaxCarts(),
        ]);

        $this->setTemplate('module:carttobudget/views/templates/front/carts.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = [
            'title' => $this->module->l('My account', 'carts'),
            'url' => $this->context->link->getPageLink('my-account', true),
        ];
        $breadcrumb['links'][] = [
            'title' => $this->module->l('Saved carts', 'carts'),
            'url' => $this->context->link->getModuleLink('carttobudget', 'carts'),
        ];

        return $breadcrumb;
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->module->l('Saved carts', 'carts');
        $page['meta']['robots'] = 'noindex';

        return $page;
    }
}
