<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Safe2Pay_API
{
    protected $gateway;

    public function __construct($gateway = null)
    {
        $this->gateway = $gateway;
    }

    protected function HttpClient($url, $method = 'POST', $data = array(), $headers = array())
    {
        $params = array(
            'method' => $method,
            'timeout' => 30,
        );

        if ('POST' == $method && !empty($data)) {
            $params['body'] = $data;
        }

        if (!empty($headers)) {
            $params['headers'] = $headers;
        }

        return wp_safe_remote_post($url, $params);
    }

    protected function GetPaymentURI()
    {
        return 'https://payment.safe2pay.com.br/v2/Payment';
    }

    public function get_callback_uri($orderId)
    {
        return get_home_url() . "/" . 'wp-json/safe2pay/v2/callback/' . $orderId;
    }

    public function GetPaymentMethod($method)
    {
        $methods = [
            'bank-slip' => 'bankslip',
            'credit-card' => 'creditcard',
            'crypto-currency' => 'cryptocurrency',
            'pix' => 'pix',
        ];

        return isset($methods[$method]) ? $methods[$method] : '';
    }

    protected function GetAvailablePaymentMethods()
    {
        $methods = array();

        $token = strtoupper($this->gateway->settings['sandbox']) !== "NO" ? $this->gateway->sandbox_token : $this->gateway->token;
        $response = $this->HttpClient('https://api.safe2pay.com.br/v2/MerchantPaymentMethod/List', 'GET', null, array('Content-Type' => 'application/json', 'X-API-KEY' => $token));

        $response = json_decode($response['body']);

        if ($response->HasError == false) {

            foreach ($response->ResponseDetail as $key => $value) {

                if ($value->PaymentMethod->Code === '1') {
                    $methods[] = 'bank-slip';
                }

                if ($value->PaymentMethod->Code === '2') {
                    $methods[] = 'credit-card';
                }

                if ($value->PaymentMethod->Code === '3') {
                    $methods[] = 'crypto-currency';
                }

	            if ($value->PaymentMethod->Code === '6') {
		            $methods[] = 'pix';
	            }
            }
        }

        return $methods;
    }

    public function set_split_data($order)
    {
        $split_data = [];
        $splits = [];
        $is_pay_tax = false;

        if ($this->gateway->settings['dokan_enable_split'] == 'yes') {
            $items = $order->get_items();
            $sellers_shipping_cost = [];

            if ($this->gateway->settings['dokan_receiver_pay_tax'] == 'yes') {
                $is_pay_tax = true;
            }

            foreach ($order->get_items('shipping') as $shipping) {
                $shipping_cost_amount = $shipping->get_total();
                $seller_id = $shipping->get_meta('seller_id');

                if (!$seller_id)
                    continue;

                $sellers_shipping_cost[$seller_id] = $shipping_cost_amount;
            }

            $items_per_seller = [];

            foreach ($items as $key => $item) {
                $item_id = $item->get_product_id();
                $seller_id = get_post_field('post_author', $item_id);
                $price_item = $item->get_total();

                if (!isset($items_per_seller[$seller_id])) {
                    $items_per_seller[$seller_id] = 0;
                }

                $items_per_seller[$seller_id] += $price_item;
            }

            foreach ($items_per_seller as $seller_id => $total) {

                if ($this->gateway->settings['dokan_shipping_commission'] == 'yes') {
                    $shipping_cost_amount = isset($sellers_shipping_cost[$seller_id]) ? $sellers_shipping_cost[$seller_id] : 0;
                    $total += $shipping_cost_amount;
                }

                $admin_commission_percentage = dokan_get_option('admin_percentage', 'dokan_selling', '10');
                $admin_commission_total = ($total / 100) * $admin_commission_percentage;

                $seller_commission_total = $total - $admin_commission_total;
                if (!isset($split_data[$seller_id])) $split_data[$seller_id] = 0;
                $split_data[$seller_id] += $seller_commission_total;
            }

            if ($this->gateway->settings['dokan_shipping_commission'] != 'yes') {
                foreach ($sellers_shipping_cost as $seller_id => $total) {
                    if (!isset($split_data[$seller_id]))
                        continue;
                    $split_data[$seller_id] += $total;
                }
            }

            if ($split_data) {
                foreach ($split_data as $item_seller_id => $item_value) {
                    $seller_data = get_userdata($item_seller_id);

                    $seller_cpf = get_user_meta($item_seller_id, 'billing_cpf', true);
                    $seller_cnpj = get_user_meta($item_seller_id, 'billing_cnpj', true);
                    $identity = $seller_cnpj ? $seller_cnpj : $seller_cpf;

                    $split = [
                        'Name' => $seller_data->first_name . ' ' . isset($seller_data->last_name),
                        'Identity' => preg_replace('([^0-9])', '', $identity),
                        'Amount' => $item_value,
                        'IsPayTax' => $is_pay_tax,
                        'CodeReceiverType' => '1',
                        'CodeTaxType' => '2'
                    ];

                    $splits[] = $split;
                }
            }
        }

        return $splits;
    }


    protected function get_payload($order, $IsSandbox)
    {
        $method = sanitize_text_field(isset($_POST['safe2pay_payment_method'])) ? $this->GetPaymentMethod(sanitize_text_field($_POST['safe2pay_payment_method'])) : '';
        $woo = new WooCommerce();
	    $PaymentObject = null;

        $Products = array(
            (object)array(
                'Code' => 1,
                'Description' => "Pedido #" . $order->get_id(),
                'Quantity' => 1,
                'UnitPrice' => $order->get_total()
            )
        );

        switch ($method) {
            case 'bankslip':

                $paymentMethod = "1";
                $PaymentObject = $this->gateway->GetBankSlipConfig();

                break;
            case 'creditcard':
                $paymentMethod = "2";

	            $PaymentObject = array(
                    'Holder' => sanitize_text_field($_POST['safe2pay-card-holder-name']),
                    'CardNumber' => sanitize_text_field($_POST['safe2pay-card-number']),
                    'ExpirationDate' => sanitize_text_field($_POST['safe2pay-card-expiry-field']),
                    'SecurityCode' => sanitize_text_field($_POST['safe2pay-card-cvc'])
                );

	            $installment = sanitize_text_field(isset($_POST['safe2pay_card_installments'])) ? sanitize_text_field(absint($_POST['safe2pay_card_installments'])) : 1;

	            if (!empty(sanitize_text_field($_POST['safe2pay_card_installments']))) {
		            $PaymentObject['InstallmentQuantity'] = $installment;
	            }

	            $interest_rates = sanitize_text_field(isset($_POST['tc_interest_rate'])) ? explode(';', sanitize_text_field($_POST['tc_interest_rate'])) : array_fill(0, $installment, 0);
	            $interest_rate = isset($interest_rates[$installment - 1]) ? floatval($interest_rates[$installment - 1]) : 0;

	            if ($interest_rate > 0) {
		            $PaymentObject['IsApplyInterest'] = true;
		            $PaymentObject['InterestRate'] = floatval($interest_rate);
	            }

	            break;
            case 'cryptocurrency':
                $paymentMethod = "3";
                break;
	        case 'pix':
		        $paymentMethod = "6";
		        break;
            default:
                return [
                    'url' => '',
                    'data' => '',
                    'error' => 'Método de pagamento não selecionado',
                ];
        };

        $identity = null;

        if (!empty(sanitize_text_field($_POST['billing_cpf']))) {
            $identity = sanitize_text_field($_POST['billing_cpf']);
        } else if (!empty(sanitize_text_field($_POST['billing_cnpj']))) {
            $identity = sanitize_text_field($_POST['billing_cnpj']);
        } else if (!empty(sanitize_text_field($_POST['customer_identity']))) {
            $identity = sanitize_text_field($_POST['customer_identity']);
        }

        $payload = [
            'IsSandbox' => $IsSandbox,
            'Application' => 'WooCommerce v' . $woo->version,
            'PaymentMethod' => $paymentMethod,
            'PaymentObject' => $PaymentObject,
            'Reference' => $order->get_id(),
            'IpAddress' => WC_Geolocation::get_ip_address(),
            'Products' => $Products,
            'Customer' => [
                "Name" => sanitize_text_field($_POST['billing_first_name'] . ' ' . $_POST['billing_last_name']),
                "Identity" => $identity,
                "Phone" => sanitize_text_field($_POST['billing_phone']),
                "Email" => sanitize_text_field($_POST['billing_email']),
                "Address" => [
                    "Street" => sanitize_text_field($_POST['billing_address_1']),
                    "Number" => sanitize_text_field(isset($_POST['billing_number']) ? $_POST['billing_number'] : 'S/N'),
                    "District" => sanitize_text_field(isset($_POST['billing_neighborhood']) ? $_POST['billing_neighborhood'] : 'Não informado'),
                    "ZipCode" => sanitize_text_field($_POST['billing_postcode']),
                    "CityName" => sanitize_text_field($_POST['billing_city']),
                    "StateInitials" => sanitize_text_field($_POST['billing_state']),
                    "CountryName" => sanitize_text_field(isset($_POST['billing_country']) ? $_POST['billing_country'] : 'Brasil')
                ]
            ],
            'CallbackUrl' => $this->get_callback_uri($order->get_id())
        ];

        if (is_plugin_active('dokan-lite/dokan.php') || is_plugin_active('dokan-pro/dokan-pro.php')) {
            if ($this->gateway->settings['dokan_enable_split'] == 'yes') {
                $splits = $this->set_split_data($order);
                $payload['Splits'] = $splits;
            }
        }

        return json_encode($payload);
    }

    public function CheckoutController($order)
    {
        try {
            $IsSandbox = strtoupper($this->gateway->settings['sandbox']) !== "NO";

            $payload = $this->get_payload($order, $IsSandbox);

            if ('yes' == $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'Requesting token for order ' . $order->get_order_number());
            }

            $token = strtoupper($this->gateway->settings['sandbox']) !== "NO" ? $this->gateway->sandbox_token : $this->gateway->token;

            $response = $this->HttpClient($this->GetPaymentURI(), 'POST', $payload, array('Content-Type' => 'application/json', 'X-API-KEY' => $token));

            if ($response['response']['code'] === 200) {

                $response = json_decode($response["body"]);

                if ($response->HasError == false) {
                    return array(
                        'url' => $this->gateway->GetPaymentURI(),
                        'data' => $response,
                        'error' => '',
                    );
                }
            } else if ($response['response']['code'] === 401) {

                if ('yes' == $this->gateway->debug) {
                    $this->gateway->log->add($this->gateway->id, 'Invalid token and/or email settings!');
                }

                return array(
                    'url' => '',
                    'data' => '',
                    'error' => array(__('Too bad! The email or token from the Safe2Pay are invalids my little friend!', 'woo-safe2pay')),
                );
            } else {
                wc_add_notice(__('Erro ', 'woo-safe2pay') . $response->ErrorCode . ' - ' . $response->Error, 'error');
            }
        } catch (Exception $e) {

            return array(
                'url' => '',
                'token' => '',
                'error' => array('<strong>' . __('Safe2Pay', 'woo-safe2pay') . '</strong>: ' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woo-safe2pay')),
            );
        }
    }

    public function PaymentController($order)
    {
        $payment_method =  sanitize_text_field($_POST['safe2pay_payment_method']) ;

        $IsSandbox = strtoupper($this->gateway->settings['sandbox']) !== "NO";

        $payload = $this->get_payload($order, $IsSandbox);

        if ('yes' == $this->gateway->debug) {
            $this->gateway->log->add($this->gateway->id, 'Requesting direct payment for order ' . $order->get_order_number());
        }

        $token = strtoupper($this->gateway->settings['sandbox']) !== "NO" ? $this->gateway->sandbox_token : $this->gateway->token;

        $response = $this->HttpClient($this->GetPaymentURI(), 'POST', $payload, array('Content-Type' => 'application/json', 'X-API-KEY' => $token));

        if (is_wp_error($response)) {
            if ('yes' == $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'WP_Error in requesting the direct payment:');
            }
        } else if (401 === $response['response']['code']) {
            if ('yes' == $this->gateway->debug) {
                $this->gateway->log->add($this->gateway->id, 'The user does not have permissions to use the Safe2Pay Transparent Checkout!');
            }

            return array(
                'url' => '',
                'data' => '',
                'error' => array(__('You are not allowed to use the Safe2Pay Transparent Checkout. Looks like you neglected to installation guide of this plugin. This is not pretty, do you know?', 'woo-safe2pay')),
            );
        } else {
            try {
                $response = json_decode($response["body"]);

	            if ( $response->HasError == true ) {
		            if ( $this->gateway->debug == 'yes' ) {
			            $this->gateway->log->add( $this->gateway->id, print_r($response, true ) );
		            }

		            wc_add_notice( __( 'Erro: ', 'woo-safe2pay' ) . $response->ErrorCode . ' - ' . $response->Error, 'error' );
	            } else if (  $response->ResponseDetail->Status == 6 || $response->ResponseDetail->Status == 8  ) {
		            if ( $this->gateway->debug == 'yes' ) {
			            $this->gateway->log->add( $this->gateway->id, 'Erro no pedido ' . $order->get_order_number() . ' com a seguinte responsta: ' . print_r($response, true ) );
		            }

		            wc_add_notice( __( 'Erro: ', 'woo-safe2pay' ) . $response->ResponseDetail->Message, 'error' );
	            } else {
		            if ( $this->gateway->debug == 'yes' ) {
			            $this->gateway->log->add( $this->gateway->id, 'Transação ' . $response->ResponseDetail->IdTransaction . ' gerada para o pedido ' . $order->get_order_number() . ' com o seguinte conteúdo: ' . print_r($response, true ) );
		            }

		            return array(
			            'url'   => $this->gateway->get_return_url( $order ),
			            'data'  => $response->ResponseDetail,
			            'error' => '',
		            );
	            }
            } catch (Exception $e) {
                $data = '';

                if ('yes' == $this->gateway->debug) {
                    $this->gateway->log->add($this->gateway->id, 'Error while parsing the Safe2Pay response: ' . print_r($e->getMessage(), true));
                }
            }
        }
    }

    public function get_direct_payment_url()
    {
        return 'https://payment.safe2pay.com.br/v2/Payment';
    }
}
