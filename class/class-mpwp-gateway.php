<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * MugglePayForWP Gateway Class.
 */
class WC_Gateway_MPWP extends WC_Payment_Gateway
{
    /** @var Multi Method */
    public $current_method = '';

    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        include_once MPWP_PLUGIN_DIR . '/class/class-mugglepay-request.php';
        // Create muggle request
        $this->mugglepay_request  = new MugglePay_Request($this);

        $this->id           = 'mpwp';
        $this->icon         = '';
        $this->has_fields   = false;
        $this->order_button_text = __('Proceed to MugglePay', 'mpwp');
        $this->method_title      = __('MugglePay', 'mpwp');

        $this->gateway_methods = array(
            'muggle_pay_methods' => array(
                'title' => __('MugglePay', 'mpwp'),
                'currency'   => '',
                'order_button_text' => __('Proceed to MugglePay', 'mpwp')
            ),
            'card_methods'    => array(
                'title' => __('Card', 'mpwp'),
                'currency'   => 'CARD',
                'order_button_text' => __('Proceed to Card', 'mpwp')
            ),
            'alipay_methods'    => array(
                'title' => __('Alipay', 'mpwp'),
                'currency'   => 'ALIPAY',
                'order_button_text' => __('Proceed to Alipay', 'mpwp')
            ),
            'alipay_global_methods' => array(
                'title' => __('Alipay Global', 'mpwp'),
                'currency'   => 'ALIGLOBAL',
                'order_button_text' => __('Proceed to Alipay Global', 'mpwp')
            ),
            'wechat_methods'    => array(
                'title' => __('Wechat', 'mpwp'),
                'currency'   => 'WECHAT',
                'order_button_text' => __('Proceed to Wechat', 'mpwp')
            ),
            'btc_methods'       => array(
                'title' => __('BTC', 'mpwp'),
                'currency'   => 'BTC',
                'order_button_text' => __('Proceed to BTC', 'mpwp')
            ),
            'ltc_methods'       => array(
                'title' => __('LTC', 'mpwp'),
                'currency'   => 'LTC',
                'order_button_text' => __('Proceed to LTC', 'mpwp')
            ),
            'eth_methods'       => array(
                'title' => __('ETH', 'mpwp'),
                'currency'   => 'ETH',
                'order_button_text' => __('Proceed to ETH', 'mpwp')
            ),
            'eos_methods'       => array(
                'title' => __('EOS', 'mpwp'),
                'currency'   => 'EOS',
                'order_button_text' => __('Proceed to EOS', 'mpwp')
            ),
            'bch_methods'       => array(
                'title' => __('BCH', 'mpwp'),
                'currency'   => 'BCH',
                'order_button_text' => __('Proceed to BCH', 'mpwp')
            ),
            'lbtc_methods'      => array(
                'title' => __('LBTC (for Lightening BTC)', 'mpwp'),
                'currency'   => 'LBTC',
                'order_button_text' => __('Proceed to LBTC', 'mpwp')
            ),
            'cusd_methods'      => array(
                'title' => __('CUSD (for Celo Dollars)', 'mpwp'),
                'currency'   => 'CUSD',
                'order_button_text' => __('Proceed to CUSD', 'mpwp')
            )
        );

        // supported features.
        $this->supports     = array(
            'products',
            'refunds'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title                = $this->get_option('title');
        $this->method_description   = $this->get_option('description');
        $this->debug                = 'yes' === $this->get_option('debug', 'no');

        self::$log_enabled = $this->debug;

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
        add_action('woocommerce_api_wc_gateway_mpwp', array( $this, 'check_response' ));
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'custom_query_var' ), 10, 2);
        // add_action('woocommerce_cancelled_order', array( $this, 'cancel_order' ), 10 ,1);
        // add_action( 'woocommerce_order_status_processing', array( $this, 'capture_payment' ) );
        // add_action( 'woocommerce_order_status_completed', array( $this, 'capture_payment' ) );
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level   Optional. Default 'info'.
     *     emergency|alert|critical|error|warning|notice|info|debug
     * @param boolean $is_end insert log end flag
     */
    public static function log($message, $level = 'info', $is_end = true)
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, array( 'source' => 'mpwp' ));
            if ($is_end) {
                self::$log->log($level, '=========================================== ↑↑↑ END ↑↑↑ ===========================================', array( 'source' => 'mpwp' ));
            }
        }
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'       => array(
                'title'         => __('Enable/Disable', 'mpwp'),
                'type'          => 'checkbox',
                'label'         => __('Enable MugglePay', 'mpwp'),
                'default'       => 'yes'
            ),
            'title'                 => array(
                'title'       => __('Title', 'mpwp'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mpwp'),
                'default'     => __('MugglePay', 'mpwp'),
                'desc_tip'    => true,
            ),
            'description'           => array(
                'title'       => __('Description', 'mpwp'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __('This controls the description which the user sees during checkout.', 'mpwp'),
                'default'     => __('MugglePay is a one-stop payment solution for merchants with an online payment need.', 'mpwp'),
            ),
            'check_orders'      => array(
                'title'       => __('Check Orders', 'mpwp'),
                'type'        => 'title',
                'description' => __('The plugin automatically checks the order payment status by default and updates the order status every 5 minutes.', 'mpwp'),
            ),
            // <br>You can click the button to check and update the payment status of all outstanding orders.
            // 'check_orders_btn'      => array(
            //     'title'       => '<div style="margin-top: -20px;"><button class="button change-theme" type="button">更新订单状态</button></div>',
            //     'type'        => 'title'
            // ),
            'setting'              => array(
                'title'       => __('Setting', 'mpwp'),
                'type'        => 'title',
                'description' => '',
            ),
            'api_key'               => array(
                'title'       => __('API Auth Token (API key) ', 'mpwp'),
                'type'        => 'text',
                'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                /* translators: %s: URL */
                'description' => sprintf(__('Register your MugglePay merchant accounts with your invitation code and get your API key at <a href="%s" target="_blank">Merchants Portal</a>. You will find your API Auth Token (API key) for authentication. <a href="%s" target="_blank">MORE</a>', 'mpwp'), 'https://merchants.mugglepay.com/user/register?ref=MP9237F1193789', 'https://mugglepay.docs.stoplight.io/api-overview/authentication'),
            ),
            'debug'          => array(
                'title'       => __('Debug log', 'mpwp'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'mpwp'),
                'default'     => 'no',
                // translators: Description for 'Debug log' section of settings page.
                'description' => sprintf(__('Log MPWP API events inside %s', 'mpwp'), '<code>' . WC_Log_Handler_File::get_log_file_path('mpwp') . '</code>'),
            ),
            'payment_gateway'              => array(
                'title'       => __('Payment Gateway', 'mpwp'),
                'type'        => 'title',
                'description' => '',
            )
        );

        foreach ($this->gateway_methods as $key => $value) {
            $this->form_fields[$key] = array(
                'title'     => '',
                'type'      => 'checkbox',
                'label'     => $value['title']
            );
        }
    }
    
    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        global $woocommerce;

        $order  = wc_get_order($order_id);

        $result = $this->get_payment_url($order, $this->current_method);

        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            return;
        }

        return array(
            'result'   => 'success',
            'redirect' => $result,
        );
    }

    /**
     * Process a refund if supported.
     *
     * @param  int    $order_id Order ID.
     * @param  float  $amount Refund amount.
     * @param  string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (! $order || ! $order->get_transaction_id()) {
            return new WP_Error('error', __('Refund failed.', 'mpwp'));
        }

        $result = $this->refund_transaction($order, $amount, $reason);

        if (is_wp_error($result)) {
            return new WP_Error('error', $result->get_error_message());
        }

        return true;
    }
    
    /**
     * Payment Callback (Webhook)
     * Send Post Request Url Like /?wc-api=WC_Gateway_MPWP
     */
    public function check_response()
    {
        try {
            $posted = wp_unslash(json_decode(file_get_contents('php://input'), true));

            if (! empty($posted) && ! empty($posted['merchant_order_id']) && $posted['token']) { // WPCS: CSRF ok.

                $order_id = wc_get_order_id_by_order_key($posted['merchant_order_id']);
                $order = wc_get_order($order_id);

                if (! $order) {
                    self::log('Failed to Checking IPN response order callback for: ' . $order_id, 'error');
                    throw new Exception('Checking IPN response is valid');
                }

                if (! $this->check_order_token($order, $posted['token'])) {
                    self::log('Checking IPN response is valid: ', 'error', false);
                    self::log(print_r($posted, true), 'error', false);
                    self::log(print_r($order, true), 'error');
                    throw new Exception('Checking IPN response is valid');
                }

                if ($order->has_status(wc_get_is_paid_statuses())) {
                    self::log('Aborting, Order #' . $order_id. ' is already complete.', 'error');
                } else {
                    $this->order_complete($order, $posted);
                }

                wp_send_json(array(
                    'status' => 200
                ), 200);
                exit;
            }
            self::log('Failed to check response order callback : ', 'error', false);
            self::log(print_r($posted, true), 'error', false);
            throw new Exception('MugglePay IPN Request Failure');
        } catch (Exception $e) {
            add_option('test message', $e->getMessage());
            wp_send_json(array(
                'message' => $e->getMessage(),
                'status' => 500
            ), 500);
            exit;
        }
    }

    /**
     * Complete order payment
     */
    public function order_complete($order, $voucher)
    {

        // Payment is complete
        $order->payment_complete();
        // Set transaction id.
        $order->set_transaction_id($voucher['order_id']);
        // Save payment voucher data
        $order->update_meta_data('_mpwp_payment_voucher', $voucher);
        // Change active status
        $order->update_meta_data('_mpwp_payment_active', false);
        // Save metadata
        $order->save();

        return true;
    }

    /**
     * Check payment statuses on orders and update order statuses.
     */
    public function check_orders()
    {
        // Check the status of non-archived MugglePay orders.
        $orders = wc_get_orders(array( 'mpwp_payment_active' => true, 'status'   => array( 'wc-pending' ) ));
        foreach ($orders as $order) {
            $transaction_id = $order->get_meta('_mpwp_prev_payment_transaction_id');

            usleep(1000000 * 3);  // Ensure we don't hit the rate limit. Delay 5 seconds.

            $mugglepay_order = $this->mugglepay_request->get_order($transaction_id);

            self::log('Auto Checking Order #' . $order->get_id(), 'info', false);
            self::log(print_r($mugglepay_order, true), 'info');

            if (is_wp_error($mugglepay_order)) {
                continue;
            }

            if ($mugglepay_order['invoice']['status'] !== 'PAID') {
                continue;
            }

            $this->order_complete($order, $mugglepay_order['invoice']);

            self::log('Auto Complete Order #' . $order->get_id(), 'info');
        }
    }


    /**
     * Get the MugglePay request URL for an order.
     *
     * @param  WC_Order $order Order object.
     * @param string $pay_currency Only use this field if you have the payment gateway enabled, and it will select the payment gateway. e.g. ALIPAY, ALIGLOBAL, WECHAT, BTC, LTC, ETH, EOS, BCH, LBTC (for Lightening BTC), CUSD (for Celo Dollars)
     * @return string
     */
    public function get_payment_url($order, $pay_currency)
    {
        // Create description for charge based on order's products. Ex: 1 x Product1, 2 x Product2
        try {
            $order_items = array_map(function ($item) {
                return $item['name'] . ' x ' . $item['quantity'];
            }, $order->get_items());

            $description = mb_substr(implode(', ', $order_items), 0, 200);
        } catch (Exception $e) {
            $description = null;
        }

        $mugglepay_args = array(
            'merchant_order_id'	=> $order->get_order_key(),
            'price_amount'		=> $order->get_total(),
            'price_currency'	=> $order->get_currency(),
            'pay_currency'		=> $pay_currency,
            'title'				=> sprintf(__('Payment order #%s', 'mpwp'), $order->get_id()),
            'description'		=> $description,
            'callback_url'		=> WC()->api_request_url('WC_Gateway_MPWP'),
            'cancel_url'		=> esc_url_raw($order->get_cancel_order_url_raw()),
            'success_url'		=> esc_url_raw($this->get_return_url($order)),
            'mobile'			=> wp_is_mobile(),
            // 'fast'				=> '',
            'token'				=> $this->create_order_token($order)
        );
        self::log(print_r($mugglepay_args, true), 'info');

        // Send Request
        $raw_response = $this->mugglepay_request->send_request(
            '/orders',
            $mugglepay_args,
            array(
                'token'	=> $this->get_option('api_key')
            )
        );

        self::log('Create Payment Url: ', 'info', false);
        self::log(print_r($raw_response, true), 'info');

        if (
            (($raw_response['status'] === 200 || $raw_response['status'] === 201) && $raw_response['payment_url']) ||
            (($raw_response['status'] === 400 && $raw_response['error_code'] === 'ORDER_MERCHANTID_EXIST') && $raw_response['payment_url'])
        ) {
            // Insert mugglepay order active flag
            $order->update_meta_data('_mpwp_payment_active', true);
            // Save payment order id
            $order->update_meta_data('_mpwp_prev_payment_transaction_id', $raw_response['order']['order_id']);
            // Save metadata
            $order->save();

            return $raw_response['payment_url'];
        } elseif (!empty($raw_response['error_code'])) {
            return new WP_Error('error', $this->get_error_str($raw_response['error_code']), $raw_response);
        }

        return new WP_Error('error', $raw_response['error'], $raw_response);
    }

    /**
     * Refund an order via MugglePay.
     *
     * @param  WC_Order $order Order object.
     * @param  float    $amount Refund amount.
     * @param  string   $reason Refund reason.
     * @return object Either an object of name value pairs for a success, or a WP_ERROR object.
     */
    public function refund_transaction($order, $amount = null, $reason = '')
    {
        // Send Request
        $raw_response = $this->mugglepay_request->send_request(
            '/orders/' . $order->get_transaction_id() . '/refund',
            array(),
            array(
                'token'	=> $this->get_option('api_key')
            )
        );

        add_option('$raw_response', $raw_response);
        
        if (is_wp_error($raw_response)) {
            return $raw_response;
        } elseif (empty($raw_response['status'] || $raw_response['status'] !== 200)) {
            return new WP_Error('error', __('Empty Response', 'mpwp'));
        }

        return (object) $raw_response;
    }

    /**
     * Get Order token to validate Payment
     *
     * @param  WC_Order $order Order object.
     * @return string
     */
    public function create_order_token($order)
    {
        return wp_hash_password($order->get_order_key());
    }

    /**
     * Check Order token to validate Payment
     */
    public function check_order_token($order, $token)
    {
        return wp_check_password($order->get_order_key(), $token);
    }

    /**
     * HTTP Response and Error Codes
     * Most common API errors are as follows, including message, reason and status code.
     */
    public function get_error_str($code)
    {
        switch ($code) {
            case 'AUTHENTICATION_FAILED':
                return __('Authentication Token is not set or expired.', 'mpwp');
            case 'INVOICE_NOT_EXIST':
                return __('Invoice does not exist.', 'mpwp');
            case 'INVOICE_VERIFIED_ALREADY':
                return __('It has been verified already.', 'mpwp');
            case 'INVOICE_CANCELED_FAIILED':
                return __('Invoice does not exist, or it cannot be canceled.', 'mpwp');
            case 'ORDER_NO_PERMISSION':
                return __('Order does not exist or permission denied.', 'mpwp');
            case 'ORDER_CANCELED_FAIILED':
                return __('Order does not exist, or it cannot be canceled.', 'mpwp');
            case 'ORDER_REFUND_FAILED':
                return __('Order does not exist, or it`s status is not refundable.', 'mpwp');
            case 'ORDER_VERIFIED_ALREADY':
                return __('Payment has been verified with payment already.', 'mpwp');
            case 'ORDER_VERIFIED_PRICE_NOT_MATCH':
                return __('Payment money does not match the order money, please double check the price.', 'mpwp');
            case 'ORDER_VERIFIED_MERCHANT_NOT_MATCH':
                return __('Payment money does not the order of current merchant , please double check the order.', 'mpwp');
            case 'ORDER_NOT_VALID':
                return __('Order id is not valid.', 'mpwp');
            case 'ORDER_PAID_FAILED':
                return __('Order not exist or is not paid yet.', 'mpwp');
            case 'ORDER_MERCHANTID_EXIST':
                return __('Order with same merchant_order_id exisits.', 'mpwp');
            case 'ORDER_NOT_NEW':
                return __('The current order is not new, and payment method cannot be switched.', 'mpwp');
            case 'PAYMENT_NOT_AVAILABLE':
                return __('The payment method is not working, please retry later.', 'mpwp');
            case 'MERCHANT_CALLBACK_STATUS_WRONG':
                return __('The current payment status not ready to send callback.', 'mpwp');
            case 'PARAMETERS_MISSING':
                return __('Missing parameters.', 'mpwp');
            case 'PAY_PRICE_ERROR':
                switch ($this->current_method) {
                    case 'WECHAT':
                    case 'ALIPAY':
                    case 'ALIGLOBAL':
                        return __('The payment is temporarily unavailable, please use another payment method', 'mpwp');
                }
                return __('Price amount or currency is not set correctly.', 'mpwp');
            case 'CREDENTIALS_NOT_MATCH':
                return __('The email or password does not match.', 'mpwp');
            case 'USER_NOT_EXIST':
                return __('The user does not exist or no permission.', 'mpwp');
            case 'USER_FAILED':
                return __('The user operatioin failed.', 'mpwp');
            case 'INVITATION_FAILED':
                return __('The invitation code is not filled correctly.', 'mpwp');
            case 'ERROR':
                return __('Error.', 'mpwp');
            case '(Unauthorized)':
                return __('API credentials are not valid', 'mpwp');
            case '(Not Found)':
                return __('Page, action not found', 'mpwp');
            case '(Too Many Requests)':
                return __('API request limit is exceeded', 'mpwp');
            case '(InternalServerError)':
                return __('Server error in MugglePay', 'mpwp');
        }
        return __('Server error in MugglePay', 'mpwp');
    }
    
    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon()
    {
        // We need a base country for the link to work, bail if in the unlikely event no country is set.
        $base_country = WC()->countries->get_base_country();

        $icon_html = '';
        $icon      = (array) $this->get_icon_image($this->current_method, $base_country);

        if (empty($icon[0])) {
            return '';
        }

        foreach ($icon as $i) {
            $icon_html .= '<img src="' . esc_attr($i) . '" alt="' . esc_attr__('MugglePay acceptance mark', 'mpwp') . '" />';
        }

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }
    
    /**
     * Get MugglePay images for a country.
     *
     * @param string $method switch mulit language.
     * @param string $country Country code.
     * @return array of image URLs
     */
    protected function get_icon_image($method, $country)
    {
        switch ($method) {
            case '':
                $icon = '//cdn.mugglepay.com/pay/home/mugglepay-logo-c.png';
            break;
            case 'ALIPAY':
            case 'ALIGLOBAL':
                $icon = '//cdn.mugglepay.com/pay/media/icons16/alipay.ico';
            break;
            case 'WECHAT':
                $icon = '//cdn.mugglepay.com/pay/media/icons16/wechatpay.png';
            break;
            case 'BTC':
                $icon = '//cdn.mugglepay.com/pay/media/icons16/btc.png';
            break;
            case 'ETH':
                $icon = '//cdn.mugglepay.com/pay/media/icons16/eth.png';
            break;
            default:
                return '';
        }
        return apply_filters('woocommerce_mpwp_icon', $icon);
    }

    /**
     * Handle a custom 'mpwp_prev_payment_transaction_id' query var to get orders
     * payed through MugglePay with the 'mpwp_prev_payment_transaction_id' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function custom_query_var($query, $query_vars)
    {
        if (array_key_exists('mpwp_payment_active', $query_vars)) {
            // Only check the order with MugglePay payment voucher
            $query['meta_query'][] = array(
                'key'     => '_mpwp_payment_active',
                'compare' => $query_vars['mpwp_payment_active'] ? 'EXISTS' : 'NOT EXISTS',
            );
        }

        if (array_key_exists('mpwp_prev_payment_transaction_id', $query_vars)) {
            $query['meta_query'][] = array(
                'key'       => '_mpwp_prev_payment_transaction_id',
                'value'     => esc_attr($query_vars['mpwp_prev_payment_transaction_id'])
            );
        }

        return $query;
    }
}
