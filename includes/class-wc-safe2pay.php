<?php

class WC_Safe2Pay
{
    public static function init()
    {
        add_action('rest_api_init', function () {
            register_rest_route('safe2pay/v2/', 'callback/(?P<order_id>\d+)', array(
                'methods' => 'POST',
                'callback' => function ($callback) {

                    $callback = $callback->get_params();
                    $gateway = new WC_Safe2Pay_Gateway();
                    $order_id = $callback['order_id'];

                    $UpdateOrder = array(
                        "IdTransaction" => $callback['IdTransaction'],
                        "Status" => $callback['TransactionStatus']['Id'],
                        "Message" => $callback['TransactionStatus']['Name'],
                        "Description" => $callback['TransactionStatus']['Name'],
                    );

                    $object = new stdClass();

                    foreach ($UpdateOrder as $key => $value) {
                        $object->$key = $value;
                    }

                    $gateway->update_order_status($object, $order_id);

                    $response = new WP_REST_Response(true);
                    $response->set_status(200);

                    return $response;
                },
                'permission_callback' => function ($callback) {

                    $callback = $callback->get_params();

                    if ($callback == null) {
                        return new WP_Error('The request has not been applied because it lacks valid authentication credentials for the target resource.', '', array('status' => 401));
                    } else {

                        $secretkey = $callback['SecretKey'];

                        if ($secretkey == null) {
                            return new WP_Error('The request has not been applied because it lacks valid authentication credentials for the target resource.', '', array('status' => 401));
                        }

                        $gateway = new WC_Safe2Pay_Gateway();

                        if ($secretkey == $gateway->secretkey || $secretkey == $gateway->sandbox_secretkey) {

                            return true;

                        } else {
                            return new WP_Error('The request has not been applied because it lacks valid authentication credentials for the target resource.', '', array('status' => 401));
                        }
                    }
                }
            ));
        });

        if (class_exists('WC_Payment_Gateway')) {
            self::includes();

            add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));
            add_filter('plugin_action_links_' . plugin_basename(WC_SAFE2PAY_PLUGIN_FILE), array(__CLASS__, 'plugin_action_links'));
        } else {
            add_action('admin_notices', array(__CLASS__, 'woocommerce_missing_notice'));
        }
    }

    public static function get_templates_path()
    {
        return plugin_dir_path(WC_SAFE2PAY_PLUGIN_FILE) . 'templates/';
    }

    public static function load_plugin_textdomain()
    {
        load_plugin_textdomain('woo-safe2pay', false, dirname(plugin_basename(WC_SAFE2PAY_PLUGIN_FILE)) . '/languages/');
    }

    public static function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=safe2pay')) . '">' . __('Settings', 'woo-safe2pay') . '</a>';

        return array_merge($plugin_links, $links);
    }

    private static function includes()
    {
        include_once dirname(__FILE__) . '/class-wc-safe2pay-api.php';
        include_once dirname(__FILE__) . '/class-wc-safe2pay-gateway.php';
    }

    public static function add_gateway($methods)
    {
        $methods[] = 'WC_Safe2Pay_Gateway';

        return $methods;
    }

    public static function ecfb_missing_notice()
    {
        $settings = get_option('woocommerce_safe2pay_settings', array('method' => ''));

        if ('transparent' === $settings['method'] && !class_exists('Extra_Checkout_Fields_For_Brazil')) {
            include dirname(__FILE__) . '/admin/views/html-notice-missing-ecfb.php';
        }
    }

    public static function woocommerce_missing_notice()
    {
        include dirname(__FILE__) . '/admin/views/html-notice-missing-woocommerce.php';
    }
}
