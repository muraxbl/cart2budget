<?php
/**
 * CartToBudget - public page of a shared cart / budget (access by token)
 *
 * index.php?fc=module&module=carttobudget&controller=budget&token=XXXX[&action=pdf]
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CarttobudgetBudgetModuleFrontController extends ModuleFrontController
{
    /** @var CartToBudget */
    public $module;

    public $ssl = true;

    /** @var CartToBudgetCart|null */
    protected $budget;

    public function init()
    {
        parent::init();

        $this->budget = CartToBudgetCart::getByToken(Tools::getValue('token'));

        $is_owner = $this->budget
            && $this->context->customer->isLogged()
            && (int) $this->budget->id_customer === (int) $this->context->customer->id;

        if (!$this->budget || (!$this->budget->share_active && !$is_owner)) {
            $this->budget = null;
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
        }
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->module->registerFrontAssets();
    }

    public function postProcess()
    {
        if (!$this->budget) {
            return;
        }

        $action = Tools::getValue('action');

        if ($action === 'pdf') {
            $template = null;
            if (Configuration::get('CTB_FRONT_TEMPLATE_CHOICE') && Tools::getValue('template')) {
                $template = (string) Tools::getValue('template');
            }
            $this->module->renderPdf($this->budget, Tools::getValue('inline') ? 'I' : true, $template);
            exit;
        }

        if ($action === 'addtocart' && Tools::isSubmit('submitAddBudgetToCart')) {
            if (Tools::getValue('static_token') !== Tools::getToken(false)) {
                $this->errors[] = $this->module->l('Invalid token, please reload the page.', 'budget');

                return;
            }
            if ($this->budget->isExpired()) {
                $this->errors[] = $this->module->l('This budget has expired.', 'budget');

                return;
            }

            $result = $this->module->addToShoppingCart($this->budget, (bool) Tools::getValue('replace'));
            if ($result['skipped']) {
                $this->warning[] = sprintf(
                    $this->module->l('Some lines could not be added to the cart: %s', 'budget'),
                    implode(', ', $result['skipped'])
                );
                $this->redirectWithNotifications($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
            }
            Tools::redirect($this->context->link->getPageLink('cart', null, null, ['action' => 'show']));
        }
    }

    public function initContent()
    {
        parent::initContent();

        if (!$this->budget) {
            $this->context->smarty->assign('ctb_budget', null);
            $this->setTemplate('module:carttobudget/views/templates/front/budget.tpl');

            return;
        }

        $presenter = new CartToBudgetPresenter($this->context);
        $data = $presenter->present($this->budget);

        $templates = [];
        if (Configuration::get('CTB_FRONT_TEMPLATE_CHOICE')) {
            $templates = CartToBudget::getPdfTemplates();
        }

        // The PDF download is a GET form: its query string must be sent as hidden fields
        $pdf_query = [];
        parse_str((string) parse_url($data['pdf_url'], PHP_URL_QUERY), $pdf_query);
        unset($pdf_query['template']);

        $this->context->smarty->assign([
            'ctb_budget' => $data,
            'ctb_pdf_action' => strtok($data['pdf_url'], '?'),
            'ctb_pdf_params' => $pdf_query,
            'ctb_templates' => $templates,
            'ctb_add_url' => $this->budget->getPublicUrl(['action' => 'addtocart']),
            'ctb_static_token' => Tools::getToken(false),
            'ctb_cart_has_products' => Validate::isLoadedObject($this->context->cart) && $this->context->cart->nbProducts() > 0,
        ]);

        $this->setTemplate('module:carttobudget/views/templates/front/budget.tpl');
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['robots'] = 'noindex, nofollow';
        $page['meta']['title'] = $this->budget
            ? $this->module->l('Budget', 'budget') . ' ' . $this->budget->getReference()
            : $this->module->l('Budget not found', 'budget');

        return $page;
    }
}
