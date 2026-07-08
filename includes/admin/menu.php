<?php

if (!defined('ABSPATH')) {
    exit;
} // END if

add_action(
    'admin_menu',
    'free_shipment_progressbar_admin_menu'
);

if ( ! function_exists( 'free_shipment_progressbar_admin_menu' ) ) {
function free_shipment_progressbar_admin_menu(){

    add_submenu_page(
        'woocommerce',
        'Free Shipping and Progressbar PRO',
        'Free Shipping and Progressbar PRO',
        'manage_woocommerce',
        'free-shipment-progressbar-fsb',
        'free_shipment_progressbar_settings_page'
    );

} // END function free_shipment_progressbar_admin_menu()

} // END if
