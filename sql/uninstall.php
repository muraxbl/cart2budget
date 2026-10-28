<?php
/**
 * CartToBudget - database uninstallation
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'carttobudget_cart_line`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'carttobudget_cart`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'carttobudget_customer`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

return true;
