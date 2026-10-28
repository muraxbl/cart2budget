<?php
/**
 * CartToBudget - database installation
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

// Saved carts / budgets
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'carttobudget_cart` (
    `id_carttobudget_cart` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_shop` int(10) unsigned NOT NULL DEFAULT 1,
    `id_lang` int(10) unsigned NOT NULL DEFAULT 0,
    `id_currency` int(10) unsigned NOT NULL DEFAULT 0,
    `id_customer` int(10) unsigned NOT NULL DEFAULT 0,
    `id_employee` int(10) unsigned NOT NULL DEFAULT 0,
    `source` varchar(16) NOT NULL DEFAULT \'customer\',
    `name` varchar(255) NOT NULL,
    `recipient_name` varchar(255) DEFAULT NULL,
    `recipient_company` varchar(255) DEFAULT NULL,
    `recipient_email` varchar(255) DEFAULT NULL,
    `recipient_vat` varchar(64) DEFAULT NULL,
    `recipient_address` text,
    `notes` text,
    `template` varchar(64) DEFAULT NULL,
    `token` varchar(32) NOT NULL,
    `share_active` tinyint(1) unsigned NOT NULL DEFAULT 1,
    `valid_until` date DEFAULT NULL,
    `total_tax_excl` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `total_tax_incl` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_carttobudget_cart`),
    UNIQUE KEY `token` (`token`),
    KEY `id_customer` (`id_customer`),
    KEY `source` (`source`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Lines of each saved cart / budget (id_product = 0 means free-text line)
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'carttobudget_cart_line` (
    `id_carttobudget_cart_line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_carttobudget_cart` int(10) unsigned NOT NULL,
    `id_product` int(10) unsigned NOT NULL DEFAULT 0,
    `id_product_attribute` int(10) unsigned NOT NULL DEFAULT 0,
    `custom_name` varchar(255) DEFAULT NULL,
    `custom_reference` varchar(64) DEFAULT NULL,
    `quantity` int(10) unsigned NOT NULL DEFAULT 1,
    `price_tax_excl` decimal(20,6) NOT NULL DEFAULT 0.000000,
    `reduction_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
    `tax_rate` decimal(10,3) NOT NULL DEFAULT 0.000,
    `position` int(10) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_carttobudget_cart_line`),
    KEY `id_carttobudget_cart` (`id_carttobudget_cart`),
    KEY `id_product` (`id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Per-customer access to the "save cart" feature (no row = shop default)
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'carttobudget_customer` (
    `id_customer` int(10) unsigned NOT NULL,
    `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;
