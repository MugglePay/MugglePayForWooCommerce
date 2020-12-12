<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Sends API requests to MugglePay.
 * @see https://mugglepay.docs.stoplight.io/
 */
class MugglePay_Request
{
    /**
     * Pointer to gateway making the request.
     *
     * @var WC_Gateway_MPWP
     */
    protected $gateway;

    /** @var string MugglePay API url. */
    public $api_url = 'https://api.mugglepay.com/v1';

    /**
     * Constructor.
     *
     * @param WC_Gateway_MPWP $gateway MugglePay gateway object.
     */
    public function __construct($gateway)
    {
        $this->gateway    = $gateway;
    }

    /**
     * Get the response from an API request.
     * @param  string $endpoint
     * @param  array  $params
     * @param  array  $header
     * @param  string $method
     * @return array
     */
    public function send_request($endpoint, $params = array(), $header = array(), $method = 'POST')
    {
        $args = array(
            'method'  => $method,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        );
        
        if (is_array($header) && count($header)) {
            $args['headers'] = array_merge($args['headers'], $header);
        }
        
        $url = $this->api_url . $endpoint;

        if (in_array($method, array( 'POST', 'PUT' ))) {
            $args['body'] = json_encode($params);
        } else {
            $url = add_query_arg($params, $url);
        }

        $response = wp_remote_request(esc_url_raw($url), $args);
        
        if (is_wp_error($response)) {
            return $response;
        } else {
            return json_decode($response['body'], true);
        }
    }
}
