<?php
/**
 * CartToBudget - enable / disable the "save cart" feature for each customer
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminCartToBudgetCustomersController extends ModuleAdminController
{
    /** @var CartToBudget */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'customer';
        $this->className = 'Customer';
        $this->identifier = 'id_customer';
        $this->lang = false;
        $this->list_no_link = true;
        $this->allow_export = true;
        $this->_defaultOrderBy = 'id_customer';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $default = (int) Configuration::get('CTB_DEFAULT_ACCESS');

        $this->_select = '
            IFNULL(ctb.`active`, ' . $default . ') AS ctb_enabled,
            IF(ctb.`id_customer` IS NULL, 0, 1) AS ctb_override,
            (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'carttobudget_cart` cc
                WHERE cc.`id_customer` = a.`id_customer`) AS ctb_carts';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'carttobudget_customer` ctb ON (ctb.`id_customer` = a.`id_customer`)';
        $this->_where = 'AND a.`deleted` = 0';

        $this->fields_list = [
            'id_customer' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'firstname' => [
                'title' => $this->l('First name'),
            ],
            'lastname' => [
                'title' => $this->l('Last name'),
            ],
            'email' => [
                'title' => $this->l('E-mail'),
            ],
            'company' => [
                'title' => $this->l('Company'),
            ],
            'ctb_enabled' => [
                'title' => $this->l('Saved carts'),
                'type' => 'bool',
                'align' => 'center',
                'havingFilter' => true,
                'callback' => 'renderAccess',
                'orderby' => true,
            ],
            'ctb_override' => [
                'title' => $this->l('Setting'),
                'type' => 'select',
                'list' => [0 => $this->l('Shop default'), 1 => $this->l('Custom')],
                'havingFilter' => true,
                'align' => 'center',
                'callback' => 'renderOverride',
            ],
            'ctb_carts' => [
                'title' => $this->l('Carts / budgets'),
                'align' => 'center',
                'search' => false,
                'class' => 'fixed-width-sm',
            ],
            'date_add' => [
                'title' => $this->l('Registration'),
                'type' => 'date',
                'filter_key' => 'a!date_add',
            ],
        ];

        $this->bulk_actions = [
            'enableAccess' => ['text' => $this->l('Enable saved carts'), 'icon' => 'icon-check'],
            'disableAccess' => ['text' => $this->l('Disable saved carts'), 'icon' => 'icon-remove'],
            'resetAccess' => ['text' => $this->l('Use shop default'), 'icon' => 'icon-undo'],
        ];
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['settings'] = [
            'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->module->name,
            'desc' => $this->l('Settings'),
            'icon' => 'process-icon-cogs',
        ];

        parent::initPageHeaderToolbar();
    }

    public function initContent()
    {
        $this->informations[] = Configuration::get('CTB_DEFAULT_ACCESS')
            ? $this->l('Shop default: saved carts are ENABLED for customers without a custom setting.')
            : $this->l('Shop default: saved carts are DISABLED for customers without a custom setting.');

        parent::initContent();
    }

    public function renderAccess($enabled, $row)
    {
        $url = self::$currentIndex . '&token=' . $this->token . '&toggleAccess=1&id_customer=' . (int) $row['id_customer'];
        $title = $enabled ? $this->l('Enabled - click to disable') : $this->l('Disabled - click to enable');

        return '<a class="list-action-enable ' . ($enabled ? 'action-enabled' : 'action-disabled') . '" href="' . Tools::safeOutput($url) . '" title="' . $title . '">'
            . '<i class="icon-check' . ($enabled ? '' : ' hidden') . '"></i>'
            . '<i class="icon-remove' . ($enabled ? ' hidden' : '') . '"></i></a>';
    }

    public function renderOverride($override, $row)
    {
        if (!$override) {
            return '<span class="text-muted">' . $this->l('Shop default') . '</span>';
        }
        $url = self::$currentIndex . '&token=' . $this->token . '&resetAccess=1&id_customer=' . (int) $row['id_customer'];

        return '<span class="label label-info">' . $this->l('Custom') . '</span> '
            . '<a href="' . Tools::safeOutput($url) . '" title="' . $this->l('Use shop default') . '"><i class="icon-undo"></i></a>';
    }

    public function postProcess()
    {
        $id_customer = (int) Tools::getValue('id_customer');

        if ($id_customer && (Tools::getValue('toggleAccess') || Tools::getValue('resetAccess'))) {
            if (Tools::getValue('resetAccess')) {
                CartToBudget::setCustomerAccess($id_customer, null);
            } else {
                CartToBudget::setCustomerAccess($id_customer, !CartToBudget::isCustomerEnabled($id_customer));
            }
            $this->redirectBack();
        }

        return parent::postProcess();
    }

    /**
     * When called from the customer page panel, go back there.
     */
    protected function redirectBack()
    {
        if (Tools::getValue('back') === 'customer' && !empty($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            if (parse_url($referer, PHP_URL_HOST) === Tools::getHttpHost(false, false, true)) {
                Tools::redirectAdmin($referer);
            }
        }

        Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=5');
    }

    public function processBulkEnableAccess()
    {
        return $this->bulkSetAccess(true);
    }

    public function processBulkDisableAccess()
    {
        return $this->bulkSetAccess(false);
    }

    public function processBulkResetAccess()
    {
        return $this->bulkSetAccess(null);
    }

    protected function bulkSetAccess($value)
    {
        if (!is_array($this->boxes) || empty($this->boxes)) {
            $this->errors[] = $this->l('You must select at least one customer.');

            return false;
        }
        foreach ($this->boxes as $id_customer) {
            CartToBudget::setCustomerAccess((int) $id_customer, $value);
        }

        Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=5');
    }
}
