<?php
/**
 * CartToBudget - saved cart / budget entity
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CartToBudgetCart extends ObjectModel
{
    const SOURCE_CUSTOMER = 'customer';
    const SOURCE_ADMIN = 'admin';

    /** @var int */
    public $id_shop;
    /** @var int */
    public $id_lang;
    /** @var int */
    public $id_currency;
    /** @var int */
    public $id_customer = 0;
    /** @var int */
    public $id_employee = 0;
    /** @var string */
    public $source = self::SOURCE_CUSTOMER;
    /** @var string */
    public $name;
    /** @var string */
    public $recipient_name;
    /** @var string */
    public $recipient_company;
    /** @var string */
    public $recipient_email;
    /** @var string */
    public $recipient_vat;
    /** @var string */
    public $recipient_address;
    /** @var string */
    public $notes;
    /** @var string */
    public $template;
    /** @var string */
    public $token;
    /** @var bool */
    public $share_active = true;
    /** @var string|null */
    public $valid_until;
    /** @var float */
    public $total_tax_excl = 0;
    /** @var float */
    public $total_tax_incl = 0;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    public static $definition = [
        'table' => 'carttobudget_cart',
        'primary' => 'id_carttobudget_cart',
        'fields' => [
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'source' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 16],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'recipient_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'recipient_company' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255],
            'recipient_email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255],
            'recipient_vat' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'recipient_address' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'notes' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'template' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'size' => 64],
            'token' => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'size' => 32],
            'share_active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'valid_until' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'total_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'total_tax_incl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function add($auto_date = true, $null_values = false)
    {
        $context = Context::getContext();

        if (empty($this->token)) {
            $this->token = self::generateToken();
        }
        if (!$this->id_shop) {
            $this->id_shop = (int) $context->shop->id;
        }
        if (!$this->id_lang) {
            $this->id_lang = (int) $context->language->id;
        }
        if (!$this->id_currency) {
            $this->id_currency = isset($context->currency->id) && $context->currency->id
                ? (int) $context->currency->id
                : (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }
        if (empty($this->valid_until)) {
            $this->valid_until = null;
        }

        return parent::add($auto_date, $null_values);
    }

    public function update($null_values = false)
    {
        if (empty($this->valid_until) || $this->valid_until === '0000-00-00') {
            $this->valid_until = null;
        }

        return parent::update($null_values);
    }

    public function delete()
    {
        Db::getInstance()->delete('carttobudget_cart_line', 'id_carttobudget_cart = ' . (int) $this->id);

        return parent::delete();
    }

    /**
     * Unguessable token used for the public share link.
     */
    public static function generateToken()
    {
        do {
            if (function_exists('random_bytes')) {
                $token = bin2hex(random_bytes(16));
            } else {
                $token = md5(uniqid((string) mt_rand(), true));
            }
        } while (self::getIdByToken($token));

        return $token;
    }

    public static function getIdByToken($token)
    {
        if (!is_string($token) || !Validate::isMd5($token)) {
            return 0;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT `id_carttobudget_cart` FROM `' . _DB_PREFIX_ . 'carttobudget_cart`
            WHERE `token` = \'' . pSQL($token) . '\''
        );
    }

    /**
     * @return CartToBudgetCart|null
     */
    public static function getByToken($token)
    {
        $id = self::getIdByToken($token);
        if (!$id) {
            return null;
        }
        $cart = new self($id);

        return Validate::isLoadedObject($cart) ? $cart : null;
    }

    public function regenerateToken()
    {
        $this->token = self::generateToken();

        return $this->update();
    }

    /**
     * Human readable budget number, e.g. PRE-000042.
     */
    public function getReference()
    {
        $prefix = (string) Configuration::get('CTB_REFERENCE_PREFIX');

        return $prefix . str_pad((string) (int) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function isExpired()
    {
        if ($this->source === self::SOURCE_CUSTOMER) {
            $days = (int) Configuration::get('CTB_EXPIRATION_DAYS');

            return $days > 0 && strtotime($this->date_add) + $days * 86400 < time();
        }

        return !empty($this->valid_until)
            && $this->valid_until !== '0000-00-00'
            && strtotime($this->valid_until . ' 23:59:59') < time();
    }

    public function getPublicUrl($params = [])
    {
        return Context::getContext()->link->getModuleLink(
            'carttobudget',
            'budget',
            array_merge(['token' => $this->token], $params),
            true,
            (int) $this->id_lang ?: null,
            (int) $this->id_shop ?: null
        );
    }

    public function getCustomer()
    {
        if (!$this->id_customer) {
            return null;
        }
        $customer = new Customer((int) $this->id_customer);

        return Validate::isLoadedObject($customer) ? $customer : null;
    }

    /**
     * Raw stored lines.
     */
    public function getLines()
    {
        if (!$this->id) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'carttobudget_cart_line`
            WHERE `id_carttobudget_cart` = ' . (int) $this->id . '
            ORDER BY `position` ASC, `id_carttobudget_cart_line` ASC'
        );

        return $rows ?: [];
    }

    public function countLines()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'carttobudget_cart_line`
            WHERE `id_carttobudget_cart` = ' . (int) $this->id
        );
    }

    protected function getNextPosition()
    {
        return 1 + (int) Db::getInstance()->getValue(
            'SELECT MAX(`position`) FROM `' . _DB_PREFIX_ . 'carttobudget_cart_line`
            WHERE `id_carttobudget_cart` = ' . (int) $this->id
        );
    }

    /**
     * Current catalog price (tax excluded, catalog reductions applied) and tax rate
     * for this cart's customer.
     *
     * @return array [price_tax_excl, tax_rate]
     */
    public function getCatalogPrice($id_product, $id_product_attribute, $quantity = 1)
    {
        $id_customer = (int) $this->id_customer ?: null;
        $id_address = $id_customer ? (int) Address::getFirstCustomerAddressId($id_customer) : null;
        $specific_price_output = null;

        $price = Product::getPriceStatic(
            (int) $id_product,
            false,
            (int) $id_product_attribute ?: null,
            6,
            null,
            false,
            true,
            max(1, (int) $quantity),
            false,
            $id_customer,
            null,
            $id_address ?: null,
            $specific_price_output
        );

        $tax_rate = 0;
        if (Configuration::get('PS_TAX')) {
            $tax_rate = (float) Tax::getProductTaxRate((int) $id_product, $id_address ?: null);
        }

        return [(float) $price, (float) $tax_rate];
    }

    /**
     * Adds a catalog product. Same product/combination lines are merged unless a
     * custom price is given.
     */
    public function addProduct($id_product, $id_product_attribute, $quantity, $price_tax_excl = null, $tax_rate = null, $reduction_percent = 0)
    {
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;
        $quantity = max(1, (int) $quantity);

        if (!$this->id || !$id_product) {
            return false;
        }

        if ($price_tax_excl === null || $tax_rate === null) {
            list($catalog_price, $catalog_rate) = $this->getCatalogPrice($id_product, $id_product_attribute, $quantity);
            if ($price_tax_excl === null) {
                $price_tax_excl = $catalog_price;
            }
            if ($tax_rate === null) {
                $tax_rate = $catalog_rate;
            }
        }

        $existing = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'carttobudget_cart_line`
            WHERE `id_carttobudget_cart` = ' . (int) $this->id . '
            AND `id_product` = ' . $id_product . '
            AND `id_product_attribute` = ' . $id_product_attribute . '
            AND ABS(`price_tax_excl` - ' . (float) $price_tax_excl . ') < 0.000001
            AND ABS(`reduction_percent` - ' . (float) $reduction_percent . ') < 0.001'
        );

        if ($existing) {
            return Db::getInstance()->update(
                'carttobudget_cart_line',
                ['quantity' => (int) $existing['quantity'] + $quantity],
                'id_carttobudget_cart_line = ' . (int) $existing['id_carttobudget_cart_line']
            );
        }

        return Db::getInstance()->insert('carttobudget_cart_line', [
            'id_carttobudget_cart' => (int) $this->id,
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'quantity' => $quantity,
            'price_tax_excl' => (float) $price_tax_excl,
            'reduction_percent' => self::clampPercent($reduction_percent),
            'tax_rate' => (float) $tax_rate,
            'position' => $this->getNextPosition(),
        ]);
    }

    /**
     * Adds a free-text line (services, shipping, installation...).
     */
    public function addCustomLine($name, $reference, $quantity, $price_tax_excl, $tax_rate, $reduction_percent = 0)
    {
        if (!$this->id || trim((string) $name) === '') {
            return false;
        }

        return Db::getInstance()->insert('carttobudget_cart_line', [
            'id_carttobudget_cart' => (int) $this->id,
            'id_product' => 0,
            'id_product_attribute' => 0,
            'custom_name' => pSQL(Tools::substr(trim($name), 0, 255)),
            'custom_reference' => pSQL(Tools::substr(trim((string) $reference), 0, 64)),
            'quantity' => max(1, (int) $quantity),
            'price_tax_excl' => (float) $price_tax_excl,
            'reduction_percent' => self::clampPercent($reduction_percent),
            'tax_rate' => (float) $tax_rate,
            'position' => $this->getNextPosition(),
        ]);
    }

    public function updateLine($id_line, array $data)
    {
        $allowed = [];
        if (isset($data['quantity'])) {
            $allowed['quantity'] = max(1, (int) $data['quantity']);
        }
        if (isset($data['price_tax_excl'])) {
            $allowed['price_tax_excl'] = (float) str_replace(',', '.', $data['price_tax_excl']);
        }
        if (isset($data['reduction_percent'])) {
            $allowed['reduction_percent'] = self::clampPercent($data['reduction_percent']);
        }
        if (isset($data['tax_rate'])) {
            $allowed['tax_rate'] = max(0, (float) str_replace(',', '.', $data['tax_rate']));
        }
        if (isset($data['custom_name'])) {
            $allowed['custom_name'] = pSQL(Tools::substr(trim($data['custom_name']), 0, 255));
        }
        if (isset($data['custom_reference'])) {
            $allowed['custom_reference'] = pSQL(Tools::substr(trim($data['custom_reference']), 0, 64));
        }
        if (isset($data['position'])) {
            $allowed['position'] = (int) $data['position'];
        }
        if (!$allowed) {
            return true;
        }

        return Db::getInstance()->update(
            'carttobudget_cart_line',
            $allowed,
            'id_carttobudget_cart_line = ' . (int) $id_line . ' AND id_carttobudget_cart = ' . (int) $this->id
        );
    }

    public function deleteLine($id_line)
    {
        return Db::getInstance()->delete(
            'carttobudget_cart_line',
            'id_carttobudget_cart_line = ' . (int) $id_line . ' AND id_carttobudget_cart = ' . (int) $this->id
        );
    }

    /**
     * Refreshes every catalog line with the current catalog price and tax rate.
     */
    public function refreshCatalogPrices($keep_reductions = true)
    {
        foreach ($this->getLines() as $line) {
            if (!(int) $line['id_product']) {
                continue;
            }
            list($price, $rate) = $this->getCatalogPrice($line['id_product'], $line['id_product_attribute'], $line['quantity']);
            $data = ['price_tax_excl' => $price, 'tax_rate' => $rate];
            if (!$keep_reductions) {
                $data['reduction_percent'] = 0;
            }
            $this->updateLine($line['id_carttobudget_cart_line'], $data);
        }

        return $this->updateTotals();
    }

    /**
     * Line amounts and totals with the VAT breakdown, unformatted.
     */
    public function computeSummary()
    {
        $lines = [];
        $taxes = [];
        $gross = 0;
        $total_tax_excl = 0;

        foreach ($this->getLines() as $line) {
            $qty = (int) $line['quantity'];
            $price = (float) $line['price_tax_excl'];
            $reduction = (float) $line['reduction_percent'];
            $rate = (float) $line['tax_rate'];

            $unit_net = Tools::ps_round($price * (1 - $reduction / 100), 6);
            $line_gross = Tools::ps_round($price * $qty, 2);
            $line_net = Tools::ps_round($unit_net * $qty, 2);

            $line['unit_price_tax_excl'] = $unit_net;
            $line['unit_price_tax_incl'] = Tools::ps_round($unit_net * (1 + $rate / 100), 6);
            $line['total_before_reduction'] = $line_gross;
            $line['total_tax_excl'] = $line_net;
            $line['total_tax_incl'] = Tools::ps_round($line_net * (1 + $rate / 100), 2);
            $line['reduction_amount'] = Tools::ps_round($line_gross - $line_net, 2);
            $lines[] = $line;

            $key = number_format($rate, 3, '.', '');
            if (!isset($taxes[$key])) {
                $taxes[$key] = ['rate' => $rate, 'base' => 0, 'amount' => 0];
            }
            $taxes[$key]['base'] += $line_net;

            $gross += $line_gross;
            $total_tax_excl += $line_net;
        }

        ksort($taxes, SORT_NUMERIC);
        $total_tax = 0;
        foreach ($taxes as $key => $tax) {
            $taxes[$key]['base'] = Tools::ps_round($tax['base'], 2);
            $taxes[$key]['amount'] = Tools::ps_round($tax['base'] * $tax['rate'] / 100, 2);
            $total_tax += $taxes[$key]['amount'];
        }

        $total_tax_excl = Tools::ps_round($total_tax_excl, 2);
        $total_tax = Tools::ps_round($total_tax, 2);

        return [
            'lines' => $lines,
            'taxes' => array_values($taxes),
            'total_before_reduction' => Tools::ps_round($gross, 2),
            'total_reduction' => Tools::ps_round($gross - $total_tax_excl, 2),
            'total_tax_excl' => $total_tax_excl,
            'total_tax' => $total_tax,
            'total_tax_incl' => Tools::ps_round($total_tax_excl + $total_tax, 2),
            'products_count' => array_sum(array_map(function ($l) {
                return (int) $l['quantity'];
            }, $lines)),
        ];
    }

    /**
     * Persists the cached totals shown in lists.
     */
    public function updateTotals()
    {
        $summary = $this->computeSummary();
        $this->total_tax_excl = $summary['total_tax_excl'];
        $this->total_tax_incl = $summary['total_tax_incl'];

        return $this->update();
    }

    /**
     * Clones the header and lines into a new private cart.
     *
     * @return CartToBudgetCart|false
     */
    public function duplicate($name = null)
    {
        $copy = new self();
        foreach (array_keys(self::$definition['fields']) as $field) {
            if (!in_array($field, ['token', 'date_add', 'date_upd'])) {
                $copy->{$field} = $this->{$field};
            }
        }
        $copy->name = $name ?: $this->name;
        $copy->token = null;

        if (!$copy->add()) {
            return false;
        }

        foreach ($this->getLines() as $line) {
            unset($line['id_carttobudget_cart_line']);
            $line['id_carttobudget_cart'] = (int) $copy->id;
            $line['custom_name'] = pSQL((string) $line['custom_name']);
            $line['custom_reference'] = pSQL((string) $line['custom_reference']);
            Db::getInstance()->insert('carttobudget_cart_line', $line);
        }
        $copy->updateTotals();

        return $copy;
    }

    protected static function clampPercent($value)
    {
        $value = (float) str_replace(',', '.', (string) $value);

        return max(0, min(100, $value));
    }
}
