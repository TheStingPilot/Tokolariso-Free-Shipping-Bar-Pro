<?php

if (!defined('ABSPATH')) {
    exit;
} // END if

add_action(
    'admin_menu',
    'tokolariso_fsb_admin_menu'
);

if ( ! function_exists( 'tokolariso_fsb_admin_menu' ) ) {
function tokolariso_fsb_admin_menu(){

    add_submenu_page(
        'woocommerce',
        'Free Shipping Bar',
        'Free Shipping Bar',
        'manage_woocommerce',
        'tokolariso-fsb',
        'tokolariso_fsb_settings_page'
    );

} // END function tokolariso_fsb_admin_menu()

} // END if
