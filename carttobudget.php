<?php
/**
 * CartToBudget for PrestaShop 1.7 / 8
 *
 * - Customers can save carts and restore them later (feature can be enabled per customer).
 * - The back office can build carts / budgets with any product, share them through a
 *   private token link and download them as PDF budgets with different templates.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CartToBudgetCart.php';
require_once __DIR__ . '/classes/CartToBudgetPresenter.php';
require_once __DIR__ . '/classes/HTMLTemplateCartToBudget.php';

class CartToBudget extends Module
{
    const DEFAULT_TEMPLATE = 'classic';

    /** Default configuration values */
    const DEFAULTS = [
        'CTB_DEFAULT_ACCESS' => 1,
        'CTB_EXPIRATION_DAYS' => 30,
        'CTB_MAX_CARTS' => 10,
        'CTB_DEFAULT_TEMPLATE' => 'classic',
        'CTB_VALIDITY_DAYS' => 15,
        'CTB_REFERENCE_PREFIX' => 'PRE-',
        'CTB_APPLY_PRICES' => 1,
        'CTB_FRONT_TEMPLATE_CHOICE' => 0,
        'CTB_PDF_SHOW_IMAGES' => 1,
        'CTB_PDF_COLOR' => '#1f3a5f',
    ];

    public function __construct()
    {
        $this->name = 'carttobudget';
        $this->tab = 'front_office_features';
        $this->version = '2.0.1';
        $this->author = 'Luis C.';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cart to Budget');
        $this->description = $this->l('Saved carts for customers and shareable PDF budgets built from the back office.');
        $this->confirmUninstall = $this->l('All saved carts and budgets will be deleted. Continue?');
    }

    /* ------------------------------------------------------------------
     * Install / uninstall
     * ------------------------------------------------------------------ */

    public function install()
    {
        $sql_ok = include __DIR__ . '/sql/install.php';
        if (!$sql_ok) {
            return false;
        }

        if (!parent::install()
            || !$this->registerHook('displayShoppingCart')
            || !$this->registerHook('displayShoppingCartAfterSummary')
            || !$this->registerHook('displayCustomerAccount')
            || !$this->registerHook('displayAdminCustomers')
            || !$this->registerHook('actionFrontControllerSetMedia')
            || !$this->installTabs()
        ) {
            return false;
        }

        foreach (self::DEFAULTS as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        $footer = [];
        foreach (Language::getLanguages(false) as $lang) {
            $footer[$lang['id_lang']] = $lang['iso_code'] === 'es'
                ? 'Presupuesto sin valor contractual hasta su aceptación. Precios sujetos a disponibilidad de stock.'
                : 'This budget is not binding until accepted. Prices subject to stock availability.';
        }
        Configuration::updateValue('CTB_PDF_FOOTER', $footer);

        return true;
    }

    public function uninstall()
    {
        include __DIR__ . '/sql/uninstall.php';

        foreach (array_keys(self::DEFAULTS) as $key) {
            Configuration::deleteByName($key);
        }
        Configuration::deleteByName('CTB_PDF_FOOTER');

        return $this->uninstallTabs() && parent::uninstall();
    }

    protected function getTabsDefinition()
    {
        return [
            'AdminCartToBudget' => ['en' => 'Carts & budgets', 'es' => 'Carritos y presupuestos'],
            'AdminCartToBudgetCustomers' => ['en' => 'Saved carts access', 'es' => 'Acceso a carritos guardados'],
        ];
    }

    protected function installTabs()
    {
        $id_parent = (int) Tab::getIdFromClassName('AdminParentCustomer');

        foreach ($this->getTabsDefinition() as $class_name => $names) {
            if (Tab::getIdFromClassName($class_name)) {
                continue;
            }
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $class_name;
            $tab->module = $this->name;
            $tab->id_parent = $id_parent;
            $tab->name = [];
            foreach (Language::getLanguages(false) as $lang) {
                $tab->name[$lang['id_lang']] = isset($names[$lang['iso_code']]) ? $names[$lang['iso_code']] : $names['en'];
            }
            if (!$tab->add()) {
                return false;
            }
        }

        return true;
    }

    protected function uninstallTabs()
    {
        foreach (array_keys($this->getTabsDefinition()) as $class_name) {
            $id_tab = (int) Tab::getIdFromClassName($class_name);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }

        return true;
    }

    /* ------------------------------------------------------------------
     * Configuration page
     * ------------------------------------------------------------------ */

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitCartToBudgetConfig')) {
            $output .= $this->processConfiguration();
        }

        $output .= $this->renderConfigurationForm();


        return $output;
    }

    protected function processConfiguration()
    {
        $errors = [];

        $expiration = Tools::getValue('CTB_EXPIRATION_DAYS');
        $max_carts = Tools::getValue('CTB_MAX_CARTS');
        $validity = Tools::getValue('CTB_VALIDITY_DAYS');
        $color = Tools::getValue('CTB_PDF_COLOR');
        $template = Tools::getValue('CTB_DEFAULT_TEMPLATE');

        if (!Validate::isUnsignedInt($expiration)) {
            $errors[] = $this->l('Expiration days must be a positive number (0 = never).');
        }
        if (!Validate::isUnsignedInt($max_carts)) {
            $errors[] = $this->l('Maximum carts must be a positive number (0 = unlimited).');
        }
        if (!Validate::isUnsignedInt($validity)) {
            $errors[] = $this->l('Budget validity must be a positive number of days.');
        }
        if ($color && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors[] = $this->l('The PDF color must be a hexadecimal value like #1f3a5f.');
        }
        if (!array_key_exists($template, self::getPdfTemplates())) {
            $errors[] = $this->l('Unknown PDF template.');
        }

        if ($errors) {
            return $this->displayError(implode('<br>', $errors));
        }

        Configuration::updateValue('CTB_DEFAULT_ACCESS', (int) Tools::getValue('CTB_DEFAULT_ACCESS'));
        Configuration::updateValue('CTB_EXPIRATION_DAYS', (int) $expiration);
        Configuration::updateValue('CTB_MAX_CARTS', (int) $max_carts);
        Configuration::updateValue('CTB_VALIDITY_DAYS', (int) $validity);
        Configuration::updateValue('CTB_DEFAULT_TEMPLATE', $template);
        Configuration::updateValue('CTB_REFERENCE_PREFIX', Tools::substr(preg_replace('/[^A-Za-z0-9_\-\/]/', '', (string) Tools::getValue('CTB_REFERENCE_PREFIX')), 0, 16));
        Configuration::updateValue('CTB_APPLY_PRICES', (int) Tools::getValue('CTB_APPLY_PRICES'));
        Configuration::updateValue('CTB_FRONT_TEMPLATE_CHOICE', (int) Tools::getValue('CTB_FRONT_TEMPLATE_CHOICE'));
        Configuration::updateValue('CTB_PDF_SHOW_IMAGES', (int) Tools::getValue('CTB_PDF_SHOW_IMAGES'));
        Configuration::updateValue('CTB_PDF_COLOR', $color ?: self::DEFAULTS['CTB_PDF_COLOR']);

        $footer = [];
        foreach (Language::getLanguages(false) as $lang) {
            $footer[$lang['id_lang']] = (string) Tools::getValue('CTB_PDF_FOOTER_' . $lang['id_lang']);
        }
        Configuration::updateValue('CTB_PDF_FOOTER', $footer, true);

        return $this->displayConfirmation($this->l('Settings updated.'));
    }

    protected function renderConfigurationForm()
    {
        $templates = [];
        foreach (self::getPdfTemplates() as $key => $label) {
            $templates[] = ['id' => $key, 'name' => $label];
        }

        $switch = function ($name, $label, $desc = '') {
            return [
                'type' => 'switch',
                'label' => $label,
                'name' => $name,
                'desc' => $desc,
                'is_bool' => true,
                'values' => [
                    ['id' => $name . '_on', 'value' => 1, 'label' => $this->l('Yes')],
                    ['id' => $name . '_off', 'value' => 0, 'label' => $this->l('No')],
                ],
            ];
        };

        $fields_form = [
            [
                'form' => [
                    'legend' => ['title' => $this->l('Customer saved carts'), 'icon' => 'icon-shopping-cart'],
                    'input' => [
                        $switch(
                            'CTB_DEFAULT_ACCESS',
                            $this->l('Enabled for all customers by default'),
                            $this->l('Customers without a specific setting use this value. You can enable or disable each customer in Customers > Saved carts access.')
                        ),
                        [
                            'type' => 'text',
                            'label' => $this->l('Expiration (days)'),
                            'name' => 'CTB_EXPIRATION_DAYS',
                            'class' => 'fixed-width-sm',
                            'desc' => $this->l('Carts saved by customers are deleted after this number of days. 0 = never.'),
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Maximum carts per customer'),
                            'name' => 'CTB_MAX_CARTS',
                            'class' => 'fixed-width-sm',
                            'desc' => $this->l('0 = unlimited.'),
                        ],
                    ],
                    'submit' => ['title' => $this->l('Save')],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => $this->l('Budgets and PDF'), 'icon' => 'icon-file-text'],
                    'input' => [
                        [
                            'type' => 'select',
                            'label' => $this->l('Default PDF template'),
                            'name' => 'CTB_DEFAULT_TEMPLATE',
                            'options' => ['query' => $templates, 'id' => 'id', 'name' => 'name'],
                            'desc' => $this->l('Add your own templates in views/templates/pdf/[name]/ (or in your theme: modules/carttobudget/views/templates/pdf/[name]/).'),
                        ],
                        [
                            'type' => 'color',
                            'label' => $this->l('PDF accent color'),
                            'name' => 'CTB_PDF_COLOR',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Budget number prefix'),
                            'name' => 'CTB_REFERENCE_PREFIX',
                            'class' => 'fixed-width-md',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Default validity (days)'),
                            'name' => 'CTB_VALIDITY_DAYS',
                            'class' => 'fixed-width-sm',
                            'desc' => $this->l('Used to prefill the "valid until" date of new back office budgets. 0 = no date.'),
                        ],
                        $switch('CTB_PDF_SHOW_IMAGES', $this->l('Show product images in the PDF')),
                        $switch(
                            'CTB_APPLY_PRICES',
                            $this->l('Keep budget prices when added to cart'),
                            $this->l('When a back office budget is added to a cart, its custom prices and discounts are applied to that cart only.')
                        ),
                        $switch(
                            'CTB_FRONT_TEMPLATE_CHOICE',
                            $this->l('Let visitors choose the PDF template'),
                            $this->l('Shows a template selector next to the download button of the shared page.')
                        ),
                        [
                            'type' => 'textarea',
                            'label' => $this->l('PDF footer / conditions'),
                            'name' => 'CTB_PDF_FOOTER',
                            'lang' => true,
                            'autoload_rte' => false,
                            'rows' => 4,
                        ],
                    ],
                    'submit' => ['title' => $this->l('Save')],
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->languages = $this->context->controller->getLanguages();
        $helper->submit_action = 'submitCartToBudgetConfig';

        $values = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $values[$key] = Tools::getValue($key, Configuration::get($key));
        }
        foreach (Language::getLanguages(false) as $lang) {
            $values['CTB_PDF_FOOTER'][$lang['id_lang']] = Tools::getValue(
                'CTB_PDF_FOOTER_' . $lang['id_lang'],
                Configuration::get('CTB_PDF_FOOTER', $lang['id_lang'])
            );
        }
        $helper->tpl_vars = ['fields_value' => $values];

        return $helper->generateForm($fields_form);
    }


    /* ------------------------------------------------------------------
     * Hooks
     * ------------------------------------------------------------------ */

    public function hookActionFrontControllerSetMedia($params)
    {
        $controller = $this->context->controller;
        $php_self = isset($controller->php_self) ? $controller->php_self : '';

        if ($php_self === 'cart' && self::isCustomerEnabled((int) $this->context->customer->id)) {
            $this->registerFrontAssets();
        }
    }

    public function registerFrontAssets()
    {
        $controller = $this->context->controller;
        $controller->registerStylesheet('module-carttobudget-front', 'modules/' . $this->name . '/views/css/front.css', ['media' => 'all', 'priority' => 150]);
        $controller->registerJavascript('module-carttobudget-front', 'modules/' . $this->name . '/views/js/front.js', ['position' => 'bottom', 'priority' => 150]);

        Media::addJsDef([
            'carttobudget' => [
                'ajaxUrl' => $this->context->link->getModuleLink($this->name, 'ajax'),
                'listUrl' => $this->context->link->getModuleLink($this->name, 'carts'),
                'cartUrl' => $this->context->link->getPageLink('cart', null, null, ['action' => 'show']),
                'token' => Tools::getToken(false),
                'i18n' => [
                    'saving' => $this->l('Saving...'),
                    'save' => $this->l('Save cart'),
                    'success' => $this->l('Cart saved'),
                    'successDetails' => $this->l('You will find it in your saved carts.'),
                    'error' => $this->l('Error'),
                    'connectionError' => $this->l('Could not connect to the server.'),
                    'limitTitle' => $this->l('Limit reached'),
                    'viewCarts' => $this->l('View saved carts'),
                    'confirm' => $this->l('Confirm'),
                    'cancel' => $this->l('Cancel'),
                    'restoreTitle' => $this->l('Restore cart'),
                    'restoreMessage' => $this->l('Do you want to restore this cart?'),
                    'restoreDetails' => $this->l('Your current cart will be replaced.'),
                    'restore' => $this->l('Restore'),
                    'restoring' => $this->l('Restoring...'),
                    'restored' => $this->l('Cart restored'),
                    'redirecting' => $this->l('Redirecting...'),
                    'deleteTitle' => $this->l('Delete cart'),
                    'deleteMessage' => $this->l('Delete this cart?'),
                    'deleteDetails' => $this->l('This action cannot be undone.'),
                    'delete' => $this->l('Delete'),
                    'deleting' => $this->l('Deleting...'),
                    'deleted' => $this->l('Cart deleted'),
                    'linkCopied' => $this->l('Link copied to the clipboard'),
                ],
            ],
        ]);
    }

    /**
     * Standard cart page hook (Classic and Hummingbird themes).
     */
    public function hookDisplayShoppingCart($params)
    {
        return $this->renderSaveCartBox();
    }

    /**
     * Non-standard hook provided by some custom themes.
     */
    public function hookDisplayShoppingCartAfterSummary($params)
    {
        return $this->renderSaveCartBox();
    }

    protected function renderSaveCartBox()
    {
        // Themes may call both cart hooks: render the box only once per page
        static $rendered = false;

        if ($rendered
            || !$this->context->customer->isLogged()
            || !self::isCustomerEnabled((int) $this->context->customer->id)
        ) {
            return '';
        }
        $rendered = true;

        $this->context->smarty->assign([
            'ctb_list_url' => $this->context->link->getModuleLink($this->name, 'carts'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/shopping_cart_button.tpl');
    }

    public function hookDisplayCustomerAccount($params)
    {
        if (!self::isCustomerEnabled((int) $this->context->customer->id)
            && !self::countCustomerBudgets((int) $this->context->customer->id)
        ) {
            return '';
        }

        $this->context->smarty->assign([
            'ctb_list_url' => $this->context->link->getModuleLink($this->name, 'carts'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/my_account_link.tpl');
    }

    /**
     * Panel on the back office customer page: access switch + customer carts.
     */
    public function hookDisplayAdminCustomers($params)
    {
        $id_customer = isset($params['id_customer']) ? (int) $params['id_customer'] : (int) Tools::getValue('id_customer');
        if (!$id_customer) {
            return '';
        }

        $link = $this->context->link;
        $carts = Db::getInstance()->executeS(
            'SELECT `id_carttobudget_cart`, `name`, `source`, `total_tax_incl`, `id_currency`, `date_add`
            FROM `' . _DB_PREFIX_ . 'carttobudget_cart`
            WHERE `id_customer` = ' . $id_customer . '
            ORDER BY `date_add` DESC'
        );
        $edit_base = $link->getAdminLink('AdminCartToBudget') . '&updatecarttobudget_cart&id_carttobudget_cart=';
        foreach ((array) $carts as &$cart) {
            $cart['edit_url'] = $edit_base . (int) $cart['id_carttobudget_cart'];
            $cart['total'] = CartToBudgetPresenter::formatPrice(
                $cart['total_tax_incl'],
                new Currency((int) $cart['id_currency'] ?: (int) Configuration::get('PS_CURRENCY_DEFAULT'))
            );
        }
        unset($cart);

        $this->context->smarty->assign([
            'ctb_enabled' => self::isCustomerEnabled($id_customer),
            'ctb_has_override' => self::getCustomerAccessOverride($id_customer) !== null,
            'ctb_toggle_url' => $link->getAdminLink('AdminCartToBudgetCustomers') . '&toggleAccess=1&id_customer=' . $id_customer . '&back=customer',
            'ctb_new_url' => $link->getAdminLink('AdminCartToBudget') . '&addcarttobudget_cart&id_customer=' . $id_customer,
            'ctb_carts' => $carts,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/customer_panel.tpl');
    }

    /* ------------------------------------------------------------------
     * Per-customer access
     * ------------------------------------------------------------------ */

    /**
     * @return bool|null null when the customer uses the shop default
     */
    public static function getCustomerAccessOverride($id_customer)
    {
        $value = Db::getInstance()->getValue(
            'SELECT `active` FROM `' . _DB_PREFIX_ . 'carttobudget_customer` WHERE `id_customer` = ' . (int) $id_customer
        );

        return $value === false || $value === null ? null : (bool) $value;
    }

    public static function isCustomerEnabled($id_customer)
    {
        if (!(int) $id_customer) {
            return false;
        }
        $override = self::getCustomerAccessOverride($id_customer);

        return $override === null ? (bool) Configuration::get('CTB_DEFAULT_ACCESS') : $override;
    }

    /**
     * @param int $id_customer
     * @param bool|null $enabled null removes the override (back to shop default)
     */
    public static function setCustomerAccess($id_customer, $enabled)
    {
        $id_customer = (int) $id_customer;
        if ($enabled === null) {
            return Db::getInstance()->delete('carttobudget_customer', 'id_customer = ' . $id_customer);
        }

        return Db::getInstance()->insert(
            'carttobudget_customer',
            ['id_customer' => $id_customer, 'active' => (int) (bool) $enabled, 'date_upd' => date('Y-m-d H:i:s')],
            false,
            true,
            Db::REPLACE
        );
    }

    /* ------------------------------------------------------------------
     * Customer saved carts
     * ------------------------------------------------------------------ */

    public static function getExpirationDays()
    {
        return (int) Configuration::get('CTB_EXPIRATION_DAYS');
    }

    public static function getMaxCarts()
    {
        return (int) Configuration::get('CTB_MAX_CARTS');
    }

    /**
     * Deletes carts saved by customers older than the expiration delay.
     * Back office budgets never expire automatically.
     */
    public static function cleanExpiredCarts($id_customer = null)
    {
        $days = self::getExpirationDays();
        if ($days <= 0) {
            return 0;
        }

        $limit = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $where = '`source` = \'' . pSQL(CartToBudgetCart::SOURCE_CUSTOMER) . '\' AND `date_add` < \'' . pSQL($limit) . '\'';
        if ($id_customer) {
            $where .= ' AND `id_customer` = ' . (int) $id_customer;
        }

        $ids = Db::getInstance()->executeS('SELECT `id_carttobudget_cart` FROM `' . _DB_PREFIX_ . 'carttobudget_cart` WHERE ' . $where);
        foreach ((array) $ids as $row) {
            $cart = new CartToBudgetCart((int) $row['id_carttobudget_cart']);
            $cart->delete();
        }

        return count((array) $ids);
    }

    public static function countActiveCartsByCustomer($id_customer)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'carttobudget_cart`
            WHERE `id_customer` = ' . (int) $id_customer . '
            AND `source` = \'' . pSQL(CartToBudgetCart::SOURCE_CUSTOMER) . '\''
        );
    }

    public static function countCustomerBudgets($id_customer)
    {
        if (!(int) $id_customer) {
            return 0;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'carttobudget_cart`
            WHERE `id_customer` = ' . (int) $id_customer . '
            AND `source` = \'' . pSQL(CartToBudgetCart::SOURCE_ADMIN) . '\''
        );
    }

    public static function canSaveMoreCarts($id_customer)
    {
        self::cleanExpiredCarts($id_customer);
        $max = self::getMaxCarts();

        return $max <= 0 || self::countActiveCartsByCustomer($id_customer) < $max;
    }

    /**
     * Carts visible in "My account": the customer's own saved carts and the
     * budgets the shop assigned to them.
     *
     * @return CartToBudgetCart[]
     */
    public static function getCustomerCarts($id_customer)
    {
        self::cleanExpiredCarts($id_customer);

        $only_budgets = !self::isCustomerEnabled($id_customer);
        $rows = Db::getInstance()->executeS(
            'SELECT `id_carttobudget_cart` FROM `' . _DB_PREFIX_ . 'carttobudget_cart`
            WHERE `id_customer` = ' . (int) $id_customer
            . ($only_budgets ? ' AND `source` = \'' . pSQL(CartToBudgetCart::SOURCE_ADMIN) . '\'' : '') . '
            ORDER BY `date_add` DESC'
        );

        $carts = [];
        foreach ((array) $rows as $row) {
            $carts[] = new CartToBudgetCart((int) $row['id_carttobudget_cart']);
        }

        return $carts;
    }

    /**
     * Saves the current shopping cart for the customer.
     *
     * @return array
     */
    public function saveCurrentCart($id_customer, $cart_name = null)
    {
        $cart = $this->context->cart;

        if (!Validate::isLoadedObject($cart) || !$cart->nbProducts()) {
            return ['error' => true, 'message' => $this->l('Your cart is empty.')];
        }

        if (!self::canSaveMoreCarts($id_customer)) {
            return [
                'error' => true,
                'message' => sprintf(
                    $this->l('You can have up to %d saved carts. Please delete some carts first.'),
                    self::getMaxCarts()
                ),
                'limit_reached' => true,
                'active_carts' => self::countActiveCartsByCustomer($id_customer),
                'max_carts' => self::getMaxCarts(),
            ];
        }

        $saved = new CartToBudgetCart();
        $saved->id_customer = (int) $id_customer;
        $saved->source = CartToBudgetCart::SOURCE_CUSTOMER;
        $saved->name = $cart_name ?: $this->l('Saved cart') . ' ' . date('d/m/Y H:i');
        $saved->id_currency = (int) $cart->id_currency;
        $saved->id_lang = (int) $cart->id_lang;
        $saved->template = Configuration::get('CTB_DEFAULT_TEMPLATE');
        $saved->share_active = true;

        if (!$saved->add()) {
            return ['error' => true, 'message' => $this->l('Error saving cart.')];
        }

        $id_address = (int) $cart->id_address_delivery ?: null;
        foreach ($cart->getProducts() as $product) {
            if (isset($product['rate'])) {
                $rate = (float) $product['rate'];
            } else {
                $rate = Configuration::get('PS_TAX') ? (float) Tax::getProductTaxRate((int) $product['id_product'], $id_address) : 0;
            }
            $saved->addProduct(
                (int) $product['id_product'],
                (int) $product['id_product_attribute'],
                (int) $product['cart_quantity'] ?: (int) $product['quantity'],
                (float) $product['price'],
                $rate
            );
        }
        $saved->updateTotals();

        return ['success' => true, 'id' => (int) $saved->id];
    }

    public static function deleteCustomerCart($id, $id_customer)
    {
        $cart = new CartToBudgetCart((int) $id);
        if (!Validate::isLoadedObject($cart)
            || (int) $cart->id_customer !== (int) $id_customer
            || $cart->source !== CartToBudgetCart::SOURCE_CUSTOMER
        ) {
            return false;
        }

        return $cart->delete();
    }

    /**
     * Makes sure the visitor has a cart stored in database.
     */
    public static function ensureContextCart(Context $context)
    {
        if (Validate::isLoadedObject($context->cart) && $context->cart->id) {
            return $context->cart;
        }

        $cart = new Cart();
        $cart->id_lang = (int) $context->language->id;
        $cart->id_currency = (int) $context->currency->id;
        $cart->id_shop = (int) $context->shop->id;
        $cart->id_shop_group = (int) $context->shop->id_shop_group;
        $cart->id_guest = (int) $context->cookie->id_guest;
        if ($context->customer->isLogged()) {
            $cart->id_customer = (int) $context->customer->id;
            $cart->secure_key = $context->customer->secure_key;
            $id_address = (int) Address::getFirstCustomerAddressId((int) $context->customer->id);
            $cart->id_address_delivery = $id_address;
            $cart->id_address_invoice = $id_address;
        }
        $cart->add();

        $context->cart = $cart;
        $context->cookie->id_cart = (int) $cart->id;
        $context->cookie->write();

        return $cart;
    }

    /**
     * Puts the lines of a saved cart / budget into the visitor's shopping cart.
     *
     * @return array ['added' => int, 'skipped' => string[]]
     */
    public function addToShoppingCart(CartToBudgetCart $budget, $replace = true)
    {
        $context = $this->context;
        $cart = self::ensureContextCart($context);
        $presenter = new CartToBudgetPresenter($context);
        $data = $presenter->present($budget);

        if ($replace) {
            foreach ($cart->getProducts() as $product) {
                $cart->deleteProduct(
                    (int) $product['id_product'],
                    (int) $product['id_product_attribute'],
                    isset($product['id_customization']) ? (int) $product['id_customization'] : 0
                );
            }
        }

        $apply_prices = Configuration::get('CTB_APPLY_PRICES')
            && $budget->source === CartToBudgetCart::SOURCE_ADMIN
            && !$budget->isExpired();

        $added = 0;
        $skipped = [];
        $raw_lines = [];
        foreach ($budget->getLines() as $raw) {
            $raw_lines[(int) $raw['id_carttobudget_cart_line']] = $raw;
        }

        foreach ($data['lines'] as $line) {
            if ($line['is_custom']) {
                $skipped[] = $line['name'];
                continue;
            }

            $product = new Product((int) $line['id_product'], false, (int) $context->language->id);
            if (!Validate::isLoadedObject($product) || !$product->active || !$product->available_for_order) {
                $skipped[] = $line['name'];
                continue;
            }

            if ($apply_prices) {
                $this->applyBudgetPrice($cart, $budget, $raw_lines[$line['id_line']]);
            }

            $result = $cart->updateQty(
                max((int) $line['quantity'], (int) $product->minimal_quantity),
                (int) $line['id_product'],
                (int) $line['id_product_attribute'] ?: null,
                false,
                'up',
                0,
                null,
                true
            );

            if ($result === false || (is_numeric($result) && $result < 0)) {
                $skipped[] = $line['name'];
            } else {
                ++$added;
            }
        }

        $cart->update();

        return ['added' => $added, 'skipped' => $skipped];
    }

    /**
     * Cart-scoped specific price, the same mechanism the back office uses when
     * editing prices of a customer cart.
     */
    protected function applyBudgetPrice(Cart $cart, CartToBudgetCart $budget, array $line)
    {
        $id_product = (int) $line['id_product'];
        $id_product_attribute = (int) $line['id_product_attribute'];

        SpecificPrice::deleteByIdCart((int) $cart->id, $id_product, $id_product_attribute);

        list($catalog_price) = $budget->getCatalogPrice($id_product, $id_product_attribute, $line['quantity']);
        $unit_net = (float) $line['price_tax_excl'] * (1 - (float) $line['reduction_percent'] / 100);
        if (abs($unit_net - $catalog_price) < 0.0001) {
            return true;
        }

        $specific_price = new SpecificPrice();
        $specific_price->id_cart = (int) $cart->id;
        $specific_price->id_product = $id_product;
        $specific_price->id_product_attribute = $id_product_attribute;
        $specific_price->id_shop = (int) $cart->id_shop;
        $specific_price->id_shop_group = 0;
        $specific_price->id_currency = 0;
        $specific_price->id_country = 0;
        $specific_price->id_group = 0;
        $specific_price->id_customer = (int) $cart->id_customer;
        $specific_price->from_quantity = 1;
        $specific_price->price = (float) $line['price_tax_excl'];
        $specific_price->reduction_type = 'percentage';
        $specific_price->reduction_tax = 1;
        $specific_price->reduction = (float) $line['reduction_percent'] / 100;
        $specific_price->from = '0000-00-00 00:00:00';
        $specific_price->to = '0000-00-00 00:00:00';

        return $specific_price->add();
    }

    /* ------------------------------------------------------------------
     * PDF templates
     * ------------------------------------------------------------------ */

    /**
     * @return array [folder => label]
     */
    public static function getPdfTemplates()
    {
        $module = Module::getInstanceByName('carttobudget');
        $labels = [
            'classic' => $module->l('Classic'),
            'modern' => $module->l('Modern'),
            'minimal' => $module->l('Minimal'),
        ];

        $dirs = [
            __DIR__ . '/views/templates/pdf/',
            _PS_THEME_DIR_ . 'modules/carttobudget/views/templates/pdf/',
        ];

        $templates = [];
        foreach ($dirs as $dir) {
            foreach ((array) glob($dir . '*', GLOB_ONLYDIR) as $path) {
                $folder = basename($path);
                if ($folder[0] === '_' || !file_exists($path . '/content.tpl') || !Validate::isModuleName($folder)) {
                    continue;
                }
                $templates[$folder] = isset($labels[$folder]) ? $labels[$folder] : Tools::ucfirst(str_replace(['_', '-'], ' ', $folder));
            }
        }

        return $templates;
    }

    public static function resolveTemplateName($template)
    {
        $templates = self::getPdfTemplates();
        if ($template && isset($templates[$template])) {
            return $template;
        }
        $default = Configuration::get('CTB_DEFAULT_TEMPLATE');

        return isset($templates[$default]) ? $default : self::DEFAULT_TEMPLATE;
    }

    /**
     * Theme override first, then the module template, then the shared "_common" folder.
     */
    public static function getPdfTemplatePath($template, $file)
    {
        $candidates = [
            _PS_THEME_DIR_ . 'modules/carttobudget/views/templates/pdf/' . $template . '/' . $file . '.tpl',
            __DIR__ . '/views/templates/pdf/' . $template . '/' . $file . '.tpl',
            _PS_THEME_DIR_ . 'modules/carttobudget/views/templates/pdf/_common/' . $file . '.tpl',
            __DIR__ . '/views/templates/pdf/_common/' . $file . '.tpl',
            __DIR__ . '/views/templates/pdf/' . self::DEFAULT_TEMPLATE . '/' . $file . '.tpl',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return __DIR__ . '/views/templates/pdf/_common/empty.tpl';
    }

    /**
     * @param CartToBudgetCart $budget
     * @param bool|string $display true = download, false = return the binary, 'I' = inline
     * @param string|null $template force a template for this rendering only
     */
    public function renderPdf(CartToBudgetCart $budget, $display = true, $template = null)
    {
        if ($template) {
            $budget->template = self::resolveTemplateName($template);
        }

        $this->context->smarty->assign('ctb_color', Configuration::get('CTB_PDF_COLOR') ?: self::DEFAULTS['CTB_PDF_COLOR']);
        $pdf = new PDF($budget, 'CartToBudget', $this->context->smarty);

        return $pdf->render($display);
    }

    /* ------------------------------------------------------------------
     * E-mail
     * ------------------------------------------------------------------ */

    public function sendShareEmail(CartToBudgetCart $budget, $email, $name = '', $message = '', $attach_pdf = true)
    {
        if (!Validate::isEmail($email)) {
            return false;
        }

        $id_lang = (int) $budget->id_lang ?: (int) $this->context->language->id;
        $presenter = new CartToBudgetPresenter($this->context);
        $data = $presenter->present($budget);

        $attachment = null;
        if ($attach_pdf) {
            $attachment = [
                'content' => $this->renderPdf($budget, false),
                'name' => HTMLTemplateCartToBudget::sanitizeFilename($data['reference'] . '-' . $budget->name) . '.pdf',
                'mime' => 'application/pdf',
            ];
        }

        $vars = [
            '{recipient_name}' => $name ?: $data['recipient']['name'],
            '{budget_reference}' => $data['reference'],
            '{budget_name}' => $budget->name,
            '{budget_total}' => $data['totals']['tax_incl'],
            '{budget_valid_until}' => $data['valid_until'] ?: '-',
            '{budget_url}' => $data['public_url'],
            '{budget_pdf_url}' => $data['pdf_url'],
            '{message}' => trim($message) === '' ? '' : '<div style="margin:0 0 16px;padding:12px;background-color:#f8f9fa;border-left:3px solid #1f3a5f;">'
                . nl2br(Tools::safeOutput($message)) . '</div>',
            '{message_txt}' => strip_tags($message),
        ];

        $subject = sprintf($this->l('Your budget %s'), $data['reference']);

        return (bool) Mail::Send(
            $id_lang,
            'carttobudget_share',
            $subject,
            $vars,
            $email,
            $name ?: null,
            null,
            null,
            $attachment,
            null,
            __DIR__ . '/mails/',
            false,
            (int) $budget->id_shop
        );
    }
}
