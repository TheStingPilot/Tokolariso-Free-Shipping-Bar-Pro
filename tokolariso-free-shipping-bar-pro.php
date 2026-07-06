<?php
/**
 * Plugin Name: Toko Lariso Free Shipping Bar PRO
 * Plugin URI: https://www.tokolariso.nl/
 * Description: Free shipping bar + smart upsells + WooCommerce Blocks support.
 * Version: 1.2.0.20
 * Author: Toko Lariso
 * Text Domain: tokolariso
 */

if (!defined('ABSPATH')) {
    exit;
} // END if

define(
    'TOKOLARISO_FSB_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'TOKOLARISO_FSB_URL',
    plugin_dir_url(__FILE__)
);

add_action(
    'plugins_loaded',
    'tokolariso_fsb_init'
);

function tokolariso_fsb_init(){

    if (
        !class_exists('WooCommerce')
    ) {
        return;
    } // END if

    require_once TOKOLARISO_FSB_PATH . 'includes/admin/menu.php';
    require_once TOKOLARISO_FSB_PATH . 'includes/admin/settings.php';

    /*
     * Huidige werkende systeem laden
     */

    require_once TOKOLARISO_FSB_PATH . 'includes/free-shipping-bar.php';

} // END function tokolariso_fsb_init()
