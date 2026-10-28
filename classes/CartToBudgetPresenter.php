<?php
/**
 * CartToBudget - builds the array used by the front page, the back office and the PDF
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class CartToBudgetPresenter
{
    /** @var Context */
    protected $context;

    /** @var Currency */
    protected $currency;

    /** @var int */
    protected $id_lang;

    public function __construct(?Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
    }

    /**
     * @param CartToBudgetCart $cart
     * @param bool $for_pdf embed images as base64 data (TCPDF friendly)
     *
     * @return array
     */
    public function present(CartToBudgetCart $cart, $for_pdf = false)
    {
        $this->currency = new Currency((int) $cart->id_currency ?: (int) Configuration::get('PS_CURRENCY_DEFAULT'));
        // Product names follow the language of whoever is viewing the budget
        $this->id_lang = (int) $this->context->language->id;

        $summary = $cart->computeSummary();
        $show_images = !$for_pdf || (bool) Configuration::get('CTB_PDF_SHOW_IMAGES');

        $lines = [];
        foreach ($summary['lines'] as $line) {
            $lines[] = $this->presentLine($line, $show_images, $for_pdf);
        }

        $taxes = [];
        foreach ($summary['taxes'] as $tax) {
            $taxes[] = [
                'rate' => $tax['rate'],
                'rate_label' => self::formatRate($tax['rate']),
                'base' => $this->price($tax['base']),
                'amount' => $this->price($tax['amount']),
                'base_raw' => $tax['base'],
                'amount_raw' => $tax['amount'],
            ];
        }

        $customer = $cart->getCustomer();
        $recipient = [
            'name' => (string) ($cart->recipient_name ?: ($customer ? $customer->firstname . ' ' . $customer->lastname : '')),
            'company' => (string) ($cart->recipient_company ?: ($customer ? $customer->company : '')),
            'email' => (string) ($cart->recipient_email ?: ($customer ? $customer->email : '')),
            'vat' => (string) $cart->recipient_vat,
            'address' => (string) ($cart->recipient_address ?: $this->getCustomerAddress($customer)),
        ];

        $valid_until = (!empty($cart->valid_until) && $cart->valid_until !== '0000-00-00') ? $cart->valid_until : null;
        if (!$valid_until && $cart->source === CartToBudgetCart::SOURCE_CUSTOMER && (int) Configuration::get('CTB_EXPIRATION_DAYS') > 0) {
            $valid_until = date('Y-m-d', strtotime($cart->date_add) + (int) Configuration::get('CTB_EXPIRATION_DAYS') * 86400);
        }

        return [
            'id' => (int) $cart->id,
            'reference' => $cart->getReference(),
            'name' => $cart->name,
            'source' => $cart->source,
            'template' => $cart->template ?: Configuration::get('CTB_DEFAULT_TEMPLATE'),
            'date' => Tools::displayDate($cart->date_add),
            'date_raw' => $cart->date_add,
            'valid_until' => $valid_until ? Tools::displayDate($valid_until) : '',
            'valid_until_raw' => $valid_until,
            'expired' => $cart->isExpired(),
            'notes' => (string) $cart->notes,
            'recipient' => $recipient,
            'lines' => $lines,
            'taxes' => $taxes,
            'has_reductions' => $summary['total_reduction'] > 0,
            'totals' => [
                'products_count' => $summary['products_count'],
                'before_reduction' => $this->price($summary['total_before_reduction']),
                'reduction' => $this->price($summary['total_reduction']),
                'tax_excl' => $this->price($summary['total_tax_excl']),
                'tax' => $this->price($summary['total_tax']),
                'tax_incl' => $this->price($summary['total_tax_incl']),
                'tax_excl_raw' => $summary['total_tax_excl'],
                'tax_raw' => $summary['total_tax'],
                'tax_incl_raw' => $summary['total_tax_incl'],
            ],
            'currency' => [
                'iso_code' => $this->currency->iso_code,
                'sign' => $this->currency->sign,
            ],
            'shop' => $this->getShopData((int) $cart->id_shop, $for_pdf),
            'footer_text' => (string) Configuration::get('CTB_PDF_FOOTER', $this->id_lang, null, (int) $cart->id_shop),
            'public_url' => $cart->getPublicUrl(),
            'pdf_url' => $cart->getPublicUrl(['action' => 'pdf']),
        ];
    }

    protected function presentLine(array $line, $show_images, $for_pdf)
    {
        $id_product = (int) $line['id_product'];
        $id_product_attribute = (int) $line['id_product_attribute'];

        $data = [
            'id_line' => (int) $line['id_carttobudget_cart_line'],
            'id_product' => $id_product,
            'id_product_attribute' => $id_product_attribute,
            'is_custom' => !$id_product,
            'name' => (string) $line['custom_name'],
            'reference' => (string) $line['custom_reference'],
            'attributes' => '',
            'image_url' => '',
            'image_data' => '',
            'product_url' => '',
            'available' => true,
            'quantity' => (int) $line['quantity'],
            'tax_rate' => (float) $line['tax_rate'],
            'tax_rate_label' => self::formatRate($line['tax_rate']),
            'reduction_percent' => (float) $line['reduction_percent'],
            'reduction_label' => self::formatRate($line['reduction_percent']),
            'price_tax_excl_raw' => (float) $line['price_tax_excl'],
            'price_input' => rtrim(rtrim(sprintf('%.6F', (float) $line['price_tax_excl']), '0'), '.'),
            'price_tax_excl' => $this->price($line['price_tax_excl']),
            'unit_price_tax_excl' => $this->price($line['unit_price_tax_excl']),
            'unit_price_tax_incl' => $this->price($line['unit_price_tax_incl']),
            'total_tax_excl' => $this->price($line['total_tax_excl']),
            'total_tax_incl' => $this->price($line['total_tax_incl']),
            'reduction_amount' => $this->price($line['reduction_amount']),
            'unit_price_tax_excl_raw' => $line['unit_price_tax_excl'],
            'total_tax_excl_raw' => $line['total_tax_excl'],
            'total_tax_incl_raw' => $line['total_tax_incl'],
        ];

        if (!$id_product) {
            return $data;
        }

        $product = Db::getInstance()->getRow(
            'SELECT p.`reference`, p.`active`, pl.`name`, pl.`link_rewrite`, pa.`reference` AS combination_reference
            FROM `' . _DB_PREFIX_ . 'product` p
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.`id_product` = p.`id_product`
                AND pl.`id_lang` = ' . (int) $this->id_lang . ' AND pl.`id_shop` = ' . (int) $this->context->shop->id . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pa.`id_product_attribute` = ' . $id_product_attribute . ')
            WHERE p.`id_product` = ' . $id_product
        );

        if (!$product) {
            $data['available'] = false;
            $data['name'] = $data['name'] ?: '#' . $id_product;

            return $data;
        }

        $data['name'] = $product['name'];
        $data['reference'] = $product['combination_reference'] ?: $product['reference'];
        $data['available'] = (bool) $product['active'];
        $data['attributes'] = $this->getAttributesLabel($id_product_attribute);

        try {
            $data['product_url'] = $this->context->link->getProductLink(
                $id_product,
                $product['link_rewrite'],
                null,
                null,
                $this->id_lang,
                null,
                $id_product_attribute
            );
        } catch (Exception $e) {
            $data['product_url'] = '';
        }

        if ($show_images) {
            $id_image = $this->getImageId($id_product, $id_product_attribute);
            if ($id_image) {
                if ($for_pdf) {
                    $data['image_data'] = $this->getImageData($id_image);
                } else {
                    $data['image_url'] = $this->context->link->getImageLink(
                        $product['link_rewrite'],
                        $id_product . '-' . $id_image,
                        self::imageType('cart')
                    );
                }
            }
        }

        return $data;
    }

    protected function getAttributesLabel($id_product_attribute)
    {
        if (!$id_product_attribute) {
            return '';
        }

        $rows = Db::getInstance()->executeS(
            'SELECT agl.`public_name` AS group_name, al.`name`
            FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = ' . (int) $this->id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (agl.`id_attribute_group` = a.`id_attribute_group` AND agl.`id_lang` = ' . (int) $this->id_lang . ')
            WHERE pac.`id_product_attribute` = ' . (int) $id_product_attribute . '
            ORDER BY a.`id_attribute_group`'
        );

        $parts = [];
        foreach ((array) $rows as $row) {
            $parts[] = $row['group_name'] ? $row['group_name'] . ': ' . $row['name'] : $row['name'];
        }

        return implode(', ', $parts);
    }

    protected function getImageId($id_product, $id_product_attribute)
    {
        if ($id_product_attribute) {
            $id_image = (int) Db::getInstance()->getValue(
                'SELECT pai.`id_image` FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
                LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_image` = pai.`id_image`)
                WHERE pai.`id_product_attribute` = ' . (int) $id_product_attribute . '
                ORDER BY i.`position` ASC'
            );
            if ($id_image) {
                return $id_image;
            }
        }

        $cover = Image::getCover($id_product);

        return $cover ? (int) $cover['id_image'] : 0;
    }

    /**
     * Base64 payload for TCPDF (<img src="@...">), read from disk to avoid HTTP calls.
     */
    protected function getImageData($id_image)
    {
        $image = new Image((int) $id_image);
        $base = _PS_PROD_IMG_DIR_ . $image->getImgPath();
        foreach ([self::imageType('small'), self::imageType('cart'), ''] as $type) {
            $file = $base . ($type ? '-' . $type : '') . '.jpg';
            if (file_exists($file)) {
                return base64_encode((string) Tools::file_get_contents($file));
            }
        }

        return '';
    }

    protected function getShopData($id_shop, $for_pdf)
    {
        $id_shop = $id_shop ?: (int) $this->context->shop->id;
        $shop = new Shop($id_shop);

        $address = '';
        $address_obj = $shop->getAddress();
        if ($address_obj instanceof Address) {
            $address = AddressFormat::generateAddress($address_obj, [], "\n", ' ');
        }

        $logo = self::getLogoFile($id_shop);
        $logo_data = '';
        $logo_url = '';
        $logo_width = 0;
        $logo_height = 0;
        if ($logo) {
            list($logo_width, $logo_height) = @getimagesize(_PS_IMG_DIR_ . $logo);
            if ($for_pdf) {
                $logo_data = base64_encode((string) Tools::file_get_contents(_PS_IMG_DIR_ . $logo));
            } else {
                $logo_url = $this->context->link->getMediaLink(_PS_IMG_ . $logo);
            }
        }

        return [
            'name' => (string) Configuration::get('PS_SHOP_NAME', null, null, $id_shop),
            'email' => (string) Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop),
            'phone' => (string) Configuration::get('PS_SHOP_PHONE', null, null, $id_shop),
            'details' => (string) Configuration::get('PS_SHOP_DETAILS', null, null, $id_shop),
            'address' => $address,
            'url' => $this->context->link->getPageLink('index', true, null, null, false, $id_shop),
            'logo_url' => $logo_url,
            'logo_data' => $logo_data,
            'logo_width' => (int) $logo_width,
            'logo_height' => (int) $logo_height,
        ];
    }

    protected function getCustomerAddress($customer)
    {
        if (!$customer) {
            return '';
        }
        $id_address = (int) Address::getFirstCustomerAddressId((int) $customer->id);
        if (!$id_address) {
            return '';
        }
        $address = new Address($id_address);

        return AddressFormat::generateAddress($address, ['avoid' => ['firstname', 'lastname', 'company']], "\n", ' ');
    }

    public static function getLogoFile($id_shop)
    {
        foreach (['PS_LOGO_INVOICE', 'PS_LOGO'] as $key) {
            $logo = Configuration::get($key, null, null, (int) $id_shop);
            if ($logo && file_exists(_PS_IMG_DIR_ . $logo)) {
                return $logo;
            }
        }

        return null;
    }

    public static function imageType($name)
    {
        if (method_exists('ImageType', 'getFormattedName')) {
            return ImageType::getFormattedName($name);
        }

        return ImageType::getFormatedName($name);
    }

    public static function formatRate($rate)
    {
        $formatted = rtrim(rtrim(number_format((float) $rate, 3, ',', ''), '0'), ',');

        return $formatted . ' %';
    }

    protected function price($amount)
    {
        return self::formatPrice($amount, $this->currency, $this->context);
    }

    public static function formatPrice($amount, Currency $currency, ?Context $context = null)
    {
        $context = $context ?: Context::getContext();
        if (method_exists($context, 'getCurrentLocale')) {
            try {
                $locale = $context->getCurrentLocale();
                if ($locale) {
                    return $locale->formatPrice((float) $amount, $currency->iso_code);
                }
            } catch (Exception $e) {
                // fall back to the legacy formatter
            }
        }

        // Tools::displayPrice() was removed in PrestaShop 9
        if (method_exists('Tools', 'displayPrice')) {
            return Tools::displayPrice((float) $amount, $currency);
        }

        return number_format((float) $amount, 2, ',', '.') . ' ' . $currency->sign;
    }
}
