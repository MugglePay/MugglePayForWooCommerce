<?php
/*
Plugin Name:  MugglePay For WooCommerce
Plugin URI:   https://github.com/MugglePay/MugglePayForWooCommerce
Description:  MugglePay is a one-stop payment solution for merchants with an online payment need.
Version:      1.0.0
Author:       MugglePay
Author URI:   https://merchants.mugglepay.com/user/register?ref=MP9237F1193789
Text Domain:  mpwp
Domain Path:  /i18n/languages/
License:MIT License

MIT License

Copyright (c) 2018 DooFox. Inc,

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
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