<?php
/*
Plugin Name:  MugglePay For WooCommerce
Plugin URI:   https://github.com/hoythan/MugglePayForWooCommerce
Description:  MugglePay is a one-stop payment solution for merchants with an online payment need.
Version:      1.0.0
Author:       MugglePay
Author URI:   https://merchants.mugglepay.com/user/register?ref=MP9237F1193789
Text Domain:  mpwp
Domain Path:  /i18n/languages/
License:      GPLv3+
License URI:  https://www.gnu.org/licenses/gpl-3.0.html

Copyright (C) 2018 DooFox, Inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

define('MPWP_PLUGIN_URL', plugins_url('', __FILE__));
define('MPWP_PLUGIN_DIR', plugin_dir_path(__FILE__));

function mpwp_init()
{
    // If WooCommerce is available, initialise WC parts.
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        require_once 'class/class-mpwp-gateway.php';
        require_once 'class/gateways/class-mpwp-alipay.php';

        // add_action( 'init', 'cb_wc_register_blockchain_status' );
        add_filter('woocommerce_payment_gateways', 'mpwp_add_gateway_class');
        // add_filter('wc_order_statuses', 'mpwp_wc_add_status');
        add_action( 'mpwp_check_orders', 'mpwp_wc_check_orders' );
        add_action('woocommerce_admin_order_data_after_order_details', 'mpwp_order_meta_general');
        add_action('woocommerce_order_details_after_order_table', 'mpwp_order_meta_general');
        // add_filter( 'woocommerce_email_order_meta_fields', 'cb_custom_woocommerce_email_order_meta_fields', 10, 3 );
        // add_filter( 'woocommerce_email_actions', 'cb_register_email_action' );
        add_action( 'admin_print_footer_scripts', 'mpwp_admin_load_script' );
        add_action( 'woocommerce_settings_start', 'mpwp_admin_load_style' );
        // add payment gateway filter 
        add_filter( 'woocommerce_available_payment_gateways', 'mpwp_filter_woocommerce_available_payment_gateways', 10, 1 );
    }
}
add_action('plugins_loaded', 'mpwp_init');

// Regiester Gateway To WooCommerce
function mpwp_add_gateway_class($methods)
{
    $methods[] = 'WC_Gateway_MPWP';
    return $methods;
}

/**
 * Check All MugglePay Order Status
 */
function mpwp_wc_check_orders() {
	$gateway = WC()->payment_gateways()->payment_gateways()['mpwp'];
	return $gateway->check_orders();
}


/**
 * Setup cron job.
 */
function mpwp_activation() {
	if ( ! wp_next_scheduled( 'mpwp_check_orders' ) ) {
		wp_schedule_event( time(), 'hourly', 'mpwp_check_orders' );
	}
}
function mpwp_deactivation() {
	wp_clear_scheduled_hook( 'mpwp_check_orders' );
}
register_activation_hook( __FILE__, 'mpwp_activation' );
register_deactivation_hook( __FILE__, 'mpwp_deactivation' );


/**
 * Add order MugglePay meta after General and before Billing
 *
 * @see: https://rudrastyh.com/woocommerce/customize-order-details.html
 *
 * @param WC_Order $order WC order instance
 */
function mpwp_order_meta_general($order)
{
    if ($order->get_payment_method() == 'mpwp') {
        ?>

        <br class="clear"/>
        <h3><?php _e('MugglePay Payment Voucher', 'mpwp'); ?></h3>
        <div class="">
            <p><?php echo sprintf(__('Transaction ID: %s', 'mpwp'), $order->get_transaction_id()); ?></p>
        </div>

        <?php
    }
}


/**
 * i18n init
 */
function plugin_languages_init()
{
    load_plugin_textdomain('mpwp', false, basename(dirname(__FILE__)) . '/i18n/languages/');
}
add_action('plugins_loaded', 'plugin_languages_init');


/**
 * Init Wooocommerce multi payment gateway
 */
function mpwp_filter_woocommerce_available_payment_gateways( $available_gateways ) { 
    if( $available_gateways['mpwp'] ) {
        $mpwp = $available_gateways['mpwp'];
        foreach( $available_gateways['mpwp']->gateway_methods as $key => $method ) {
            if( $mpwp->get_option($key) === 'yes') {
                $available_gateways[$key] = clone $mpwp;

                $available_gateways[$key]->id = $key;
                $available_gateways[$key]->current_method = $method['currency'];
                $available_gateways[$key]->title = $method['title'];
                $available_gateways[$key]->order_button_text = $method['order_button_text'];
            }
        }

        // unset self
        unset($available_gateways['mpwp']);
    }
    return $available_gateways; 
}; 
         

/**
 * Init admin setting hook
 */
function mpwp_admin_load_style() 
{
    ?>
    <style>
        #woocommerce_mpwp_payment_gateway + .form-table {
            display: none;
        }
        .mpwp-custom-payment_gateway .titledesc{
            display: none;
        }
        .mpwp-custom-payment_gateway tr[valign="top"] {
            display: inline-block;
        }
    </style>
    <?php
}
function mpwp_admin_load_script() 
{
    ?>
    <script>
        var $ = jQuery;
        if( $('#woocommerce_mpwp_payment_gateway').length ) {
            $('#woocommerce_mpwp_payment_gateway + .form-table').addClass('mpwp-custom-payment_gateway');
            $('#woocommerce_mpwp_payment_gateway + .form-table').show();
        }
    </script>
    <?php
}