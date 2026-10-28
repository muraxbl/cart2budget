<?php
/**
 * CartToBudget 2.0.1: the save-cart box also uses the standard displayShoppingCart hook,
 * available in the Classic and Hummingbird themes.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_1($module)
{
    return $module->registerHook('displayShoppingCart');
}
