<?php
/**
 * CartToBudget - back office list and editor of carts / budgets
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminCartToBudgetController extends ModuleAdminController
{
    /** @var CartToBudget */
    public $module;

    /** @var CartToBudgetCart */
    protected $object;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'carttobudget_cart';
        $this->className = 'CartToBudgetCart';
        $this->identifier = 'id_carttobudget_cart';
        $this->lang = false;
        $this->_defaultOrderBy = 'id_carttobudget_cart';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->_select = '
            IF(a.`id_customer` > 0, CONCAT(c.`firstname`, " ", c.`lastname`), a.`recipient_name`) AS customer_name,
            (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'carttobudget_cart_line` l
                WHERE l.`id_carttobudget_cart` = a.`id_carttobudget_cart`) AS lines_count';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`)';

        $this->fields_list = [
            'id_carttobudget_cart' => [
                'title' => $this->l('Number'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'callback' => 'renderReference',
            ],
            'name' => [
                'title' => $this->l('Name'),
            ],
            'customer_name' => [
                'title' => $this->l('Customer / recipient'),
                'havingFilter' => true,
            ],
            'source' => [
                'title' => $this->l('Origin'),
                'type' => 'select',
                'list' => [
                    CartToBudgetCart::SOURCE_ADMIN => $this->l('Back office budget'),
                    CartToBudgetCart::SOURCE_CUSTOMER => $this->l('Saved by customer'),
                ],
                'filter_key' => 'a!source',
                'callback' => 'renderSource',
            ],
            'lines_count' => [
                'title' => $this->l('Lines'),
                'align' => 'center',
                'search' => false,
                'class' => 'fixed-width-xs',
            ],
            'total_tax_incl' => [
                'title' => $this->l('Total (tax incl.)'),
                'type' => 'price',
                'align' => 'text-right',
                'search' => false,
            ],
            'share_active' => [
                'title' => $this->l('Shared link'),
                'type' => 'bool',
                'align' => 'center',
                'callback' => 'renderShare',
                'filter_key' => 'a!share_active',
            ],
            'valid_until' => [
                'title' => $this->l('Valid until'),
                'type' => 'date',
                'callback' => 'renderValidUntil',
            ],
            'date_add' => [
                'title' => $this->l('Created'),
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ],
        ];

        $this->addRowAction('edit');
        $this->addRowAction('pdf');
        $this->addRowAction('duplicate');
        $this->addRowAction('delete');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js');
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_budget'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('New budget'),
                'icon' => 'process-icon-new',
            ];
            $this->page_header_toolbar_btn['settings'] = [
                'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->module->name,
                'desc' => $this->l('Settings'),
                'icon' => 'process-icon-cogs',
            ];
        } elseif ($this->display === 'edit' && Validate::isLoadedObject($this->loadObject(true))) {
            $this->page_header_toolbar_btn['public'] = [
                'href' => $this->object->getPublicUrl(),
                'desc' => $this->l('Public page'),
                'icon' => 'process-icon-preview',
                'target' => true,
            ];
            $this->page_header_toolbar_btn['pdf'] = [
                'href' => $this->getEditUrl((int) $this->object->id) . '&downloadPdf=1',
                'desc' => $this->l('Download PDF'),
                'icon' => 'process-icon-save-date',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    /* ------------------------------------------------------------------
     * List callbacks and row actions
     * ------------------------------------------------------------------ */

    public function renderList()
    {
        CartToBudget::cleanExpiredCarts();

        return parent::renderList();
    }

    public function renderReference($id, $row)
    {
        return Tools::safeOutput((string) Configuration::get('CTB_REFERENCE_PREFIX') . str_pad((string) (int) $id, 6, '0', STR_PAD_LEFT));
    }

    public function renderSource($source, $row)
    {
        if ($source === CartToBudgetCart::SOURCE_ADMIN) {
            return '<span class="label label-info">' . $this->l('Budget') . '</span>';
        }

        return '<span class="label label-default">' . $this->l('Customer') . '</span>';
    }

    public function renderShare($active, $row)
    {
        return $active
            ? '<i class="icon-check text-success" title="' . $this->l('Link active') . '"></i>'
            : '<i class="icon-remove text-danger" title="' . $this->l('Link disabled') . '"></i>';
    }

    public function renderValidUntil($date, $row)
    {
        if (empty($date) || $date === '0000-00-00') {
            return '<span class="text-muted">-</span>';
        }
        $label = Tools::displayDate($date);
        if (strtotime($date . ' 23:59:59') < time()) {
            return '<span class="label label-danger">' . $label . '</span>';
        }

        return $label;
    }

    public function displayPdfLink($token, $id, $name = null)
    {
        return '<a href="' . Tools::safeOutput($this->getEditUrl((int) $id) . '&downloadPdf=1') . '" title="PDF">'
            . '<i class="icon-file-text"></i> PDF</a>';
    }

    public function displayDuplicateLink($token, $id, $name = null)
    {
        return '<a href="' . Tools::safeOutput(self::$currentIndex . '&' . $this->identifier . '=' . (int) $id . '&duplicateBudget=1&token=' . $this->token) . '"'
            . ' onclick="return confirm(\'' . addslashes($this->l('Duplicate this budget?')) . '\');">'
            . '<i class="icon-copy"></i> ' . $this->l('Duplicate') . '</a>';
    }

    /* ------------------------------------------------------------------
     * Editor
     * ------------------------------------------------------------------ */

    protected function getEditUrl($id)
    {
        return self::$currentIndex . '&' . $this->identifier . '=' . (int) $id . '&update' . $this->table . '&token=' . $this->token;
    }

    public function renderForm()
    {
        /** @var CartToBudgetCart $budget */
        $budget = $this->loadObject(true);
        if (!$budget) {
            return '';
        }

        $is_new = !Validate::isLoadedObject($budget);
        if ($is_new) {
            $budget->source = CartToBudgetCart::SOURCE_ADMIN;
            $budget->template = Configuration::get('CTB_DEFAULT_TEMPLATE');
            $budget->id_customer = (int) Tools::getValue('id_customer');
            $validity = (int) Configuration::get('CTB_VALIDITY_DAYS');
            $budget->valid_until = $validity > 0 ? date('Y-m-d', strtotime('+' . $validity . ' days')) : '';
        }

        $customer = $budget->id_customer ? new Customer((int) $budget->id_customer) : null;
        $presenter = new CartToBudgetPresenter($this->context);

        $tax_rates = Db::getInstance()->executeS(
            'SELECT DISTINCT `rate` FROM `' . _DB_PREFIX_ . 'tax` WHERE `active` = 1 AND `deleted` = 0 ORDER BY `rate` DESC'
        );

        $this->context->smarty->assign([
            'ctb_is_new' => $is_new,
            'ctb_object' => $budget,
            'ctb_data' => $is_new ? null : $presenter->present($budget),
            'ctb_customer' => $customer && Validate::isLoadedObject($customer) ? [
                'id' => (int) $customer->id,
                'name' => $customer->firstname . ' ' . $customer->lastname,
                'email' => $customer->email,
                'company' => $customer->company,
                'access' => CartToBudget::isCustomerEnabled((int) $customer->id),
            ] : null,
            'ctb_templates' => CartToBudget::getPdfTemplates(),
            'ctb_languages' => Language::getLanguages(true),
            'ctb_default_lang' => (int) Configuration::get('PS_LANG_DEFAULT'),
            'ctb_tax_rates' => array_map(function ($r) {
                return (float) $r['rate'];
            }, (array) $tax_rates),
            'ctb_form_action' => self::$currentIndex . '&token=' . $this->token
                . ($is_new ? '&add' . $this->table : '&' . $this->identifier . '=' . (int) $budget->id . '&update' . $this->table),
            'ctb_edit_url' => $is_new ? '' : $this->getEditUrl((int) $budget->id),
            'ctb_back_url' => self::$currentIndex . '&token=' . $this->token,
            'ctb_ajax_url' => self::$currentIndex . '&token=' . $this->token . '&ajax=1',
            'ctb_currency_sign' => (new Currency((int) $budget->id_currency ?: (int) Configuration::get('PS_CURRENCY_DEFAULT')))->sign,
        ]);

        return $this->module->display(_PS_MODULE_DIR_ . $this->module->name . '/' . $this->module->name . '.php', 'views/templates/admin/editor.tpl');
    }

    /* ------------------------------------------------------------------
     * Actions
     * ------------------------------------------------------------------ */

    public function postProcess()
    {
        if ($this->ajax) {
            return parent::postProcess();
        }

        if (Tools::isSubmit('submitCtbHeader')) {
            return $this->processHeader();
        }

        if (Tools::getValue('duplicateBudget')) {
            return $this->processDuplicate();
        }

        $id = (int) Tools::getValue($this->identifier);
        $budget = $id ? new CartToBudgetCart($id) : null;
        if ($budget && Validate::isLoadedObject($budget)) {
            if (Tools::getValue('downloadPdf')) {
                $this->module->renderPdf($budget, Tools::getValue('inline') ? 'I' : true, Tools::getValue('template') ?: null);
                exit;
            }
            if (Tools::isSubmit('submitCtbAddProduct')) {
                return $this->processAddProduct($budget);
            }
            if (Tools::isSubmit('submitCtbAddCustom')) {
                return $this->processAddCustomLine($budget);
            }
            if (Tools::isSubmit('submitCtbUpdateLines')) {
                return $this->processUpdateLines($budget);
            }
            if (Tools::getValue('deleteLine')) {
                $budget->deleteLine((int) Tools::getValue('deleteLine'));

                return $this->afterLineChange($budget);
            }
            if (Tools::isSubmit('submitCtbRefreshPrices')) {
                $budget->refreshCatalogPrices((bool) Tools::getValue('keep_reductions', 1));

                return $this->afterLineChange($budget);
            }
            if (Tools::isSubmit('submitCtbToggleShare')) {
                $budget->share_active = !$budget->share_active;
                $budget->update();

                return $this->afterLineChange($budget);
            }
            if (Tools::isSubmit('submitCtbRegenerateToken')) {
                $budget->regenerateToken();

                return $this->afterLineChange($budget);
            }
            if (Tools::isSubmit('submitCtbSendEmail')) {
                return $this->processSendEmail($budget);
            }
        }

        return parent::postProcess();
    }

    protected function processHeader()
    {
        $id = (int) Tools::getValue($this->identifier);
        $budget = new CartToBudgetCart($id ?: null);
        if ($id && !Validate::isLoadedObject($budget)) {
            $this->errors[] = $this->l('Budget not found.');

            return false;
        }

        $name = trim((string) Tools::getValue('name'));
        $email = trim((string) Tools::getValue('recipient_email'));
        $valid_until = trim((string) Tools::getValue('valid_until'));
        $id_customer = (int) Tools::getValue('id_customer');

        if ($name === '' || !Validate::isGenericName($name)) {
            $this->errors[] = $this->l('Please enter a valid name.');
        }
        if ($email !== '' && !Validate::isEmail($email)) {
            $this->errors[] = $this->l('Invalid recipient e-mail.');
        }
        if ($valid_until !== '' && !Validate::isDate($valid_until)) {
            $this->errors[] = $this->l('Invalid validity date.');
        }
        if ($id_customer && !Validate::isLoadedObject(new Customer($id_customer))) {
            $this->errors[] = $this->l('Customer not found.');
        }
        foreach (['recipient_name', 'recipient_company', 'recipient_vat'] as $field) {
            $value = trim((string) Tools::getValue($field));
            if ($value !== '' && !Validate::isGenericName($value)) {
                $this->errors[] = sprintf($this->l('Invalid value for field %s.'), $field);
            }
        }
        foreach (['recipient_address', 'notes'] as $field) {
            if (!Validate::isCleanHtml((string) Tools::getValue($field))) {
                $this->errors[] = sprintf($this->l('Invalid value for field %s.'), $field);
            }
        }

        if ($this->errors) {
            $this->display = $id ? 'edit' : 'add';

            return false;
        }

        $budget->name = $name;
        $budget->id_customer = $id_customer;
        $budget->recipient_name = trim((string) Tools::getValue('recipient_name'));
        $budget->recipient_company = trim((string) Tools::getValue('recipient_company'));
        $budget->recipient_email = $email;
        $budget->recipient_vat = trim((string) Tools::getValue('recipient_vat'));
        $budget->recipient_address = trim((string) Tools::getValue('recipient_address'));
        $budget->notes = trim((string) Tools::getValue('notes'));
        $budget->valid_until = $valid_until !== '' ? $valid_until : null;
        $budget->template = CartToBudget::resolveTemplateName(Tools::getValue('template'));

        if (!$id) {
            $budget->source = CartToBudgetCart::SOURCE_ADMIN;
            $budget->id_employee = (int) $this->context->employee->id;
            $budget->share_active = true;
            $budget->id_lang = (int) Tools::getValue('id_lang') ?: (int) Configuration::get('PS_LANG_DEFAULT');
            $budget->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        if (!$budget->save()) {
            $this->errors[] = $this->l('Error while saving the budget.');
            $this->display = $id ? 'edit' : 'add';

            return false;
        }

        Tools::redirectAdmin($this->getEditUrl((int) $budget->id) . '&conf=' . ($id ? 4 : 3));
    }

    protected function processAddProduct(CartToBudgetCart $budget)
    {
        $id_product = (int) Tools::getValue('add_id_product');
        $id_product_attribute = (int) Tools::getValue('add_id_product_attribute');
        $quantity = max(1, (int) Tools::getValue('add_quantity', 1));
        $price = trim((string) Tools::getValue('add_price'));
        $reduction = (float) str_replace(',', '.', (string) Tools::getValue('add_reduction', 0));

        $product = new Product($id_product);
        if (!Validate::isLoadedObject($product)) {
            $this->errors[] = $this->l('Please select a product.');
            $this->display = 'edit';

            return false;
        }
        if ($product->hasAttributes() && !$id_product_attribute) {
            $id_product_attribute = (int) Product::getDefaultAttribute($id_product);
        }

        $budget->addProduct(
            $id_product,
            $id_product_attribute,
            $quantity,
            $price !== '' ? (float) str_replace(',', '.', $price) : null,
            null,
            $reduction
        );

        return $this->afterLineChange($budget);
    }

    protected function processAddCustomLine(CartToBudgetCart $budget)
    {
        $name = trim((string) Tools::getValue('custom_name'));
        if ($name === '' || !Validate::isGenericName($name)) {
            $this->errors[] = $this->l('Please enter a valid description for the line.');
            $this->display = 'edit';

            return false;
        }

        $budget->addCustomLine(
            $name,
            (string) Tools::getValue('custom_reference'),
            (int) Tools::getValue('custom_quantity', 1),
            (float) str_replace(',', '.', (string) Tools::getValue('custom_price', 0)),
            (float) str_replace(',', '.', (string) Tools::getValue('custom_tax_rate', 0)),
            (float) str_replace(',', '.', (string) Tools::getValue('custom_reduction', 0))
        );

        return $this->afterLineChange($budget);
    }

    protected function processUpdateLines(CartToBudgetCart $budget)
    {
        $lines = Tools::getValue('lines');
        if (is_array($lines)) {
            foreach ($lines as $id_line => $data) {
                if (!is_array($data)) {
                    continue;
                }
                if (!empty($data['delete'])) {
                    $budget->deleteLine((int) $id_line);
                    continue;
                }
                $budget->updateLine((int) $id_line, array_intersect_key($data, array_flip([
                    'quantity', 'price_tax_excl', 'reduction_percent', 'tax_rate', 'custom_name', 'custom_reference', 'position',
                ])));
            }
        }

        return $this->afterLineChange($budget);
    }

    protected function processSendEmail(CartToBudgetCart $budget)
    {
        $email = trim((string) Tools::getValue('email_to'));
        if (!Validate::isEmail($email)) {
            $this->errors[] = $this->l('Invalid e-mail address.');
            $this->display = 'edit';

            return false;
        }

        if (!$budget->share_active) {
            $budget->share_active = true;
            $budget->update();
        }

        $sent = $this->module->sendShareEmail(
            $budget,
            $email,
            trim((string) Tools::getValue('email_name')),
            (string) Tools::getValue('email_message'),
            (bool) Tools::getValue('email_attach_pdf')
        );

        if (!$sent) {
            $this->errors[] = $this->l('The e-mail could not be sent. Check your e-mail settings.');
            $this->display = 'edit';

            return false;
        }

        Tools::redirectAdmin($this->getEditUrl((int) $budget->id) . '&ctbMailSent=1');
    }

    protected function processDuplicate()
    {
        $budget = new CartToBudgetCart((int) Tools::getValue($this->identifier));
        if (!Validate::isLoadedObject($budget)) {
            $this->errors[] = $this->l('Budget not found.');

            return false;
        }

        $copy = $budget->duplicate($budget->name . ' (' . $this->l('copy') . ')');
        if (!$copy) {
            $this->errors[] = $this->l('Error while duplicating the budget.');

            return false;
        }
        $copy->source = CartToBudgetCart::SOURCE_ADMIN;
        $copy->id_employee = (int) $this->context->employee->id;
        $copy->update();

        Tools::redirectAdmin($this->getEditUrl((int) $copy->id) . '&conf=19');
    }

    protected function afterLineChange(CartToBudgetCart $budget)
    {
        $budget->updateTotals();
        Tools::redirectAdmin($this->getEditUrl((int) $budget->id) . '&conf=4');
    }

    public function initContent()
    {
        if (Tools::getValue('ctbMailSent')) {
            $this->confirmations[] = $this->l('The budget has been sent.');
        }

        parent::initContent();
    }

    /* ------------------------------------------------------------------
     * AJAX
     * ------------------------------------------------------------------ */

    public function ajaxProcessSearchProducts()
    {
        $query = trim((string) Tools::getValue('q'));
        $id_customer = (int) Tools::getValue('id_customer');
        $results = [];

        if (Tools::strlen($query) >= 2) {
            $id_lang = (int) $this->context->language->id;
            $products = Product::searchByName($id_lang, $query, null, 20);
            foreach ((array) $products as $row) {
                $id_product = (int) $row['id_product'];
                $product = new Product($id_product, false, $id_lang);
                $combinations = $product->getAttributesResume($id_lang);

                if ($combinations) {
                    foreach ($combinations as $combination) {
                        $results[] = $this->formatSearchResult($row, $id_customer, (int) $combination['id_product_attribute'], $combination['attribute_designation'], $combination['reference']);
                    }
                } else {
                    $results[] = $this->formatSearchResult($row, $id_customer);
                }
            }
        }

        die(json_encode(['results' => array_slice($results, 0, 50)]));
    }

    protected function formatSearchResult(array $row, $id_customer, $id_product_attribute = 0, $attributes = '', $reference = '')
    {
        $price = Product::getPriceStatic(
            (int) $row['id_product'],
            false,
            $id_product_attribute ?: null,
            6,
            null,
            false,
            true,
            1,
            false,
            $id_customer ?: null
        );

        return [
            'id_product' => (int) $row['id_product'],
            'id_product_attribute' => (int) $id_product_attribute,
            'name' => $row['name'],
            'attributes' => (string) $attributes,
            'reference' => $reference ?: (string) $row['reference'],
            'active' => (bool) $row['active'],
            'stock' => (int) StockAvailable::getQuantityAvailableByProduct((int) $row['id_product'], (int) $id_product_attribute),
            'price' => Tools::ps_round($price, 2),
            'price_formatted' => CartToBudgetPresenter::formatPrice($price, $this->context->currency ?: new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'))),
        ];
    }

    public function ajaxProcessSearchCustomers()
    {
        $query = trim((string) Tools::getValue('q'));
        $results = [];

        if (Tools::strlen($query) >= 2) {
            foreach ((array) Customer::searchByName($query, 20) as $customer) {
                $results[] = [
                    'id' => (int) $customer['id_customer'],
                    'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                    'email' => $customer['email'],
                    'company' => isset($customer['company']) ? $customer['company'] : '',
                ];
            }
        }

        die(json_encode(['results' => $results]));
    }
}
