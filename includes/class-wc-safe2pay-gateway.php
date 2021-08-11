<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Safe2Pay_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'safe2pay';
        $this->icon = apply_filters('woocommerce_safe2pay_icon', plugins_url('assets/images/safe2pay.png', plugin_dir_path(__FILE__)));
        $this->method_title = __('Safe2Pay', 'woo-safe2pay');
        $this->method_description = __('Aceite pagamentos por boleto, criptomoedas e cartões de crédito e débito pelo Safe2Pay.', 'woo-safe2pay');
        $this->order_button_text = __('Finalizar', 'woo-safe2pay');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->secretkey = $this->get_option('secretkey');
        $this->token = $this->get_option('token');
        $this->sandbox_secretkey = $this->get_option('sandbox_secretkey');
        $this->sandbox_token = $this->get_option('sandbox_token');
        $this->method = $this->get_option('method', 'direct');
        $this->tc_credit = $this->get_option('tc_credit', 'no');
        $this->tc_installments = $this->get_option('tc_installments');
        $this->tc_minimum_installment_amount = $this->get_option('tc_minimum_installment_amount');
        $this->tc_debit = $this->get_option('tc_debit', 'no');
	    $this->tc_pix = $this->get_option('tc_pix', 'no');
	    $this->tc_ticket = $this->get_option('tc_ticket', 'no');
        $this->tc_cryptocurrency = $this->get_option('tc_cryptocurrency', 'no');
        $this->invoice_prefix = $this->get_option('invoice_prefix', 'WC-');
        $this->sandbox = $this->get_option('sandbox', 'no');
        $this->debug = $this->get_option('debug');
        $this->instruction = $this->get_option('instruction');
        $this->message1 = $this->get_option('message1');
        $this->message2 = $this->get_option('message2');
        $this->message3 = $this->get_option('message3');
        $this->duedate = $this->get_option('duedate');
        $this->cancelAfterDue = $this->get_option('cancelAfterDue');
        $this->penaltyRate = $this->get_option('penaltyRate');
        $this->interestRate = $this->get_option('interestRate');
        $this->isEnablePartialPayment = $this->get_option('isEnablePartialPayment');
        $this->discount_bank_slip = $this->get_option('discount_bank_slip');
        $this->discount_amount_bank_slip = $this->get_option('discount_amount_bank_slip');
        $this->dokan_enable_split = $this->get_option('dokan_enable_split');
        $this->dokan_shipping_commission = $this->get_option('dokan_shipping_commission');
        $this->dokan_receiver_pay_tax = $this->get_option('dokan_receiver_pay_tax');

        if ('yes' === $this->debug) {
            if (function_exists('wc_get_logger')) {
                $this->log = wc_get_logger();
            } else {
                $this->log = new WC_Logger();
            }
        }

        $this->api = new WC_Safe2Pay_API($this);

        add_action('valid_safe2pay_ipn_request', array($this, 'update_order_status'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 10, 3);
        add_action('wp_enqueue_scripts', array($this, 'checkout_scripts'));
    }

    public function using_supported_currency()
    {
        return 'BRL' === get_woocommerce_currency();
    }

    public function GetAPIKEY()
    {
        return 'yes' === $this->sandbox ? $this->sandbox_token : $this->token;
    }

    public function IsNullOrEmptyString($str)
    {
        return (!isset($str) || trim($str) === '');
    }

    public function GetBankSlipConfig()
    {
        $dueDateConfig = new DateTime();
        $dueDateConfig = $dueDateConfig->add(new DateInterval('P' . ($this->duedate >= 1 ? $this->duedate : 3) . 'D'))->format('Y-m-d');

        $Instruction = isset($this->instruction) ? $this->instruction : null;
        $IsEnablePartialPayment = $this->isEnablePartialPayment == "yes" ? true : false;
        $CancelAfterDue = $this->cancelAfterDue == "yes" ? true : false;
        $DueDate = $dueDateConfig;
        $PenaltyRate = floatval(($this->penaltyRate > 0 ? $this->penaltyRate : 0));
        $InterestRate = floatval(($this->interestRate > 0 ? $this->interestRate : 0));

        $Messages = array();

        if (!$this->IsNullOrEmptyString($this->message1)) {
            array_push($Messages, $this->message1);
        }

        if (!$this->IsNullOrEmptyString($this->message2)) {
            array_push($Messages, $this->message2);
        }

        if (!$this->IsNullOrEmptyString($this->message3)) {
            array_push($Messages, $this->message3);
        }

        $BankSlipSetting = array(
            "DueDate" => $DueDate,
            "Instruction" => $Instruction,
            "Message" => $Messages,
            "PenaltyRate" => $PenaltyRate,
            "InterestRate" => $InterestRate,
            "CancelAfterDue" => $CancelAfterDue,
            "IsEnablePartialPayment" => $IsEnablePartialPayment
        );

        if ($this->discount_bank_slip == "yes" && isset($this->discount_amount_bank_slip)) {
            $order_total = $this->get_order_total();

            $discount_percentage = $this->discount_amount_bank_slip;
            $discount_amount = ($order_total / 100) * $discount_percentage;

            $BankSlipSetting['DiscountDue'] = $dueDateConfig;
            $BankSlipSetting['DiscountType'] = '1';
            $BankSlipSetting['DiscountAmount'] = $discount_amount;
        }

        return $BankSlipSetting;
    }

    public function IsAvailable()
    {
        return 'yes' === $this->get_option('enabled') && $this->using_supported_currency();
    }

    public function checkout_scripts()
    {
        if ($this->IsAvailable()) {
            if (!get_query_var('order-received')) {
                wp_enqueue_style('safe2pay-checkout', plugins_url('assets/css/frontend/transparent-checkout.css', plugin_dir_path(__FILE__)), array(), WC_SAFE2PAY_VERSION);
                wp_enqueue_script('safe2pay-checkout', plugins_url('assets/js/frontend/transparent-checkout.js', plugin_dir_path(__FILE__)), array('jquery', 'safe2pay-library'), WC_SAFE2PAY_VERSION, true);

                wp_enqueue_script('safe2pay-library', $this->api->get_direct_payment_url(), array(), WC_SAFE2PAY_VERSION, true);

                wp_localize_script(
                    'safe2pay-checkout',
                    'wc_safe2pay_params',
                    array(
                        'interest_free' => __('interest free', 'woo-safe2pay'),
                        'invalid_card' => __('Número de cartão inválido.', 'woo-safe2pay'),
                        'invalid_expiry' => __('Data de expiração inválida, use o formato MM / AAAA.', 'woo-safe2pay'),
                        'expired_date' => __('Por favor, preencha a data no formato MM / AAAA.', 'woo-safe2pay'),
                        'general_error' => __('Não foi possível processar sua compra com os dados fornecidos, entre em contato para maiores informações.', 'woo-safe2pay'),
                        'empty_installments' => __('Selecione a quantidade de parcelas.', 'woo-safe2pay'),
                    )
                );
            }
        }
    }

    protected function get_log_view()
    {
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.2', '>=')) {
            return '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.log')) . '">' . __('System Status &gt; Logs', 'woo-safe2pay') . '</a>';
        }

        return '<code>woocommerce/logs/' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.txt</code>';
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Ativar/Desativar', 'woo-safe2pay'),
                'type' => 'checkbox',
                'label' => __('Ativar Safe2Pay', 'woo-safe2pay'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Título', 'woo-safe2pay'),
                'type' => 'text',
                'description' => __('Título do método de pagamento', 'woo-safe2pay'),
                'desc_tip' => true,
                'default' => __('Safe2Pay', 'woo-safe2pay'),
            ),
            'description' => array(
                'title' => __('Descrição do método de pagamento', 'woo-safe2pay'),
                'type' => 'textarea',
                'description' => __('Descrição do método de pagamento durante o checkout.', 'woo-safe2pay'),
                'default' => __('Pagar via Safe2Pay', 'woo-safe2pay'),
            ),
            'integration' => array(
                'title' => __('Integração', 'woo-safe2pay'),
                'type' => 'title',
                'description' => '',
            ),
            'method' => array(
                'title' => __('Método de integração', 'woo-safe2pay'),
                'type' => 'select',
                'description' => __('Choose how the customer will interact with the Safe2Pay. Redirect (Client goes to Safe2Pay page) or Lightbox (Inside your store)', 'woo-safe2pay'),
                'desc_tip' => true,
                'default' => 'direct',
                'class' => 'wc-enhanced-select',
                'options' => array(
                    'transparent' => __('Transparent Checkout', 'woo-safe2pay'),
                ),
            ),
            'sandbox' => array(
                'title' => __('Safe2Pay Sandbox', 'woo-safe2pay'),
                'type' => 'checkbox',
                'label' => __('Ativar/Desativar Safe2Pay Sandbox', 'woo-safe2pay'),
                'desc_tip' => true,
                'default' => 'no',
                'description' => __('Safe2Pay Sandbox pode ser utilizado para testes de pagamento.', 'woo-safe2pay'),
            ),
            'token' => array(
                'title' => __('Safe2Pay Token', 'woo-safe2pay'),
                'type' => 'text',
                /* translators: %s: link to Safe2Pay settings */
                'description' => sprintf(__('Insira seu Token aqui. Isso é necessário para processar os pagamentos.', 'woo-safe2pay'), '<a href="https://admin.safe2pay.com.br/integracao">' . __('here', 'woo-safe2pay') . '</a>'),
                'default' => '',
            ),
            'secretkey' => array(
                'title' => __('Safe2Pay SecretKey', 'woo-safe2pay'),
                'type' => 'text',
                /* translators: %s: link to Safe2Pay settings */
                'description' => sprintf(__('Insira sua Secret Key aqui. Isso é necessário para receber notificações de mudanças de status do pagamento.', 'woo-safe2pay'), '<a href="https://admin.safe2pay.com.br/integracao">' . __('here', 'woo-safe2pay') . '</a>'),
                'default' => '',
            ),
            'sandbox_token' => array(
                'title' => __('Safe2Pay Sandbox Token', 'woo-safe2pay'),
                'type' => 'text',
                /* translators: %s: link to Safe2Pay settings */
                'description' => sprintf(__('Insira seu Token de Sandbox aqui. Isso é necessário para processar os pagamentos em ambiente de teste.', 'woo-safe2pay'), '<a href=https://admin.safe2pay.com.br/integracao">' . __('here', 'woo-safe2pay') . '</a>'),
                'default' => '',
            ),
            'sandbox_secretkey' => array(
                'title' => __('Safe2Pay Sandbox SecretKey', 'woo-safe2pay'),
                'type' => 'text',
                /* translators: %s: link to Safe2Pay settings */
                'description' => sprintf(__('Insira sua Secret Key de Sandbox aqui. Isso é necessário para receber notificações de mudança de status do pagamento em ambiente de teste.', 'woo-safe2pay'), '<a href="https://admin.safe2pay.com.br/integracao">' . __('here', 'woo-safe2pay') . '</a>'),
                'default' => '',
            ),
            'transparent_checkout' => array(
                'title' => __('Opções de pagamento', 'woo-safe2pay'),
                'type' => 'title',
                'description' => '',
            ),
            'tc_pix' => array(
	            'title' => __('Pix', 'woo-safe2pay'),
	            'type' => 'checkbox',
	            'label' => __('Pix', 'woo-safe2pay'),
	            'default' => 'yes',
            ),
            'tc_ticket' => array(
                'title' => __('Boleto Bancário', 'woo-safe2pay'),
                'type' => 'checkbox',
                'label' => __('Boleto Bancário', 'woo-safe2pay'),
                'default' => 'yes',
            ),
            'duedate' => array(
                'title' => __('Data de vencimento', 'woo-safe2pay'),
                'type' => 'number',
                'description' => __('Número de dias para vencimento do boleto Bancário.', 'woo-safe2pay'),
                'default' => 3,
            ),
            'discount_bank_slip' => array(
                'title' => __('Conceder desconto no boleto', 'woo-safe2pay'),
                'type' => 'checkbox',
                'description' => __('Oferecer desconto no pagamento por boleto.', 'woo-safe2pay'),
                'default' => 'no',
            ),
            'discount_amount_bank_slip' => array(
                'title' => __('Percentual de desconto', 'woo-safe2pay'),
                'type' => 'decimal',
                'description' => __('Percentual de desconto se escolhido o pagamento por boleto.', 'woo-safe2pay'),
                'default' => 10.00,
            ),
            'instruction' => array(
                'title' => __('Instrução', 'woo-safe2pay'),
                'type' => 'text',
                'description' => __('Instrução do boleto bancário.', 'woo-safe2pay'),
                'default' => '',
            ),
            'message1' => array(
                'title' => __('Mensagem 1', 'woo-safe2pay'),
                'type' => 'text',
                'description' => __('Mensagem 1 impressa no boleto bancário.', 'woo-safe2pay'),
                'default' => '',
            ),
            'message2' => array(
                'title' => __('Mensagem 2', 'woo-safe2pay'),
                'type' => 'text',
                'description' => __('Mensagem 3 impressa no boleto bancário.', 'woo-safe2pay'),
                'default' => '',
            ),
            'message3' => array(
                'title' => __('Mensagem 3', 'woo-safe2pay'),
                'type' => 'text',
                'description' => __('Mensagem 3 impressa no boleto bancário.', 'woo-safe2pay'),
                'default' => '',
            ),
            'cancelAfterDue' => array(
                'title' => __('Cancelar após o vencimento', 'woo-safe2pay'),
                'type' => 'checkbox',
                'description' => __('Cancelar boleto bancário após o vencimento.', 'woo-safe2pay'),
                'default' => false,
            ),
            'isEnablePartialPayment' => array(
                'title' => __('Pagamento parcial', 'woo-safe2pay'),
                'type' => 'checkbox',
                'description' => __('Aceitar pagamento parcial do boleto bancário', 'woo-safe2pay'),
                'default' => false,
            ),
            'interestRate' => array(
                'title' => __('Taxa de juros', 'woo-safe2pay'),
                'type' => 'number',
                'description' => __('Juros aplicado após o vencimento do boleto bancário.', 'woo-safe2pay'),
                'default' => '',
            ),
            'penaltyRate' => array(
                'title' => __('Taxa de multa', 'woo-safe2pay'),
                'type' => 'number',
                'description' => __('Multa aplicada após o vencimento do boleto bancário.', 'woo-safe2pay'),
                'default' => '',
            ),
            'tc_credit' => array(
                'title' => __('Cartão de Crédito', 'woo-safe2pay'),
                'type' => 'checkbox',
                'label' => __('Cartão de crédito', 'woo-safe2pay'),
                'default' => 'no',
            ),
            'tc_installments' => array(
                'title' => __('Número máximo de parcelas', 'woo-safe2pay'),
                'type' => 'select',
                'label' => __('Número máximo de parcelas.', 'woo-safe2pay'),
                'default' => 1,
                'options' => array(
                    1 => 'Sem parcelamento',
                    2 => '2x',
                    3 => '3x',
                    4 => '4x',
                    5 => '5x',
                    6 => '6x',
                    7 => '7x',
                    8 => '8x',
                    9 => '9x',
                    10 => '10x',
                    11 => '11x',
                    12 => '12x',
                )
            ),
            'tc_minimum_installment_amount' => array(
                'title' => __('Valor mínimo da parcela', 'woo-safe2pay'),
                'type' => 'text',
                'description' => __('Valor mínimo da parcela.', 'woo-safe2pay'),
                'default' => 5.00,
                'custom_attributes' => array(
                    'min' => 5.00
                )
            ),
            'tc_debit' => array(
                'title' => __('Cartão de Débito', 'woo-safe2pay'),
                'type' => 'checkbox',
                'label' => __('Cartão de débito', 'woo-safe2pay'),
                'default' => 'no',
            ),
            'tc_cryptocurrency' => array(
                'title' => __('Criptomoedas', 'woo-safe2pay'),
                'type' => 'checkbox',
                'label' => __('Criptomoeda', 'woo-safe2pay'),
                'default' => 'no',
            ),
            'behavior' => array(
                'title' => __('Integration Behavior', 'woo-safe2pay'),
                'type' => 'title',
                'description' => '',
            ),
            'invoice_prefix' => array(
                'title' => __('Invoice Prefix', 'woo-safe2pay'),
                'type' => 'text',
                'description' => __('Please enter a prefix for your invoice numbers. If you use your Safe2Pay account for multiple stores ensure this prefix is unqiue as Safe2Pay will not allow orders with the same invoice number.', 'woo-safe2pay'),
                'desc_tip' => true,
                'default' => 'WC-',
            ),
            'testing' => array(
                'title' => __('Gateway Testing', 'woo-safe2pay'),
                'type' => 'title',
                'description' => '',
            ),
            'debug' => array(
                'title' => __('Debug Log', 'woo-safe2pay'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'woo-safe2pay'),
                'default' => 'no',
                /* translators: %s: log page link */
                'description' => sprintf(__('Log Safe2Pay events, such as API requests, inside %s', 'woo-safe2pay'), $this->get_log_view()),
            ),
        );

        if (is_plugin_active('dokan-lite/dokan.php') || is_plugin_active('dokan-pro/dokan-pro.php')) {
            $this->form_fields['dokan_integration_options'] =
                array(
                    'title' => __('Dokan', 'woo-safe2pay'),
                    'type' => 'title',
                    'description' => ''
                );

            $this->form_fields['dokan_enable_split'] =
                array(
                    'title' => __('Habilitar Split', 'woo-safe2pay'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar Split para subcontas', 'woo-safe2pay'),
                    'default' => 'no',
                    'description' => __('A venda será criada na conta do Marketplace e será enviada para os vendedores que possuam subcontas no Safe2Pay.', 'woo-safe2pay')
                );

            $this->form_fields['dokan_shipping_commission'] =
                array(
                    'title' => __('Comissão para frete', 'woo-safe2pay'),
                    'type' => 'checkbox',
                    'label' => __('Usar o frete no cálculo de comissões', 'woo-safe2pay'),
                    'default' => 'no',
                    'description' => __('Usar o frete no cálculo de comissões.', 'woo-safe2pay')
                );

            $this->form_fields['dokan_receiver_pay_tax'] =
                array(
                    'title' => __('Recebedor paga taxa', 'woo-safe2pay'),
                    'type' => 'checkbox',
                    'label' => __('Recebedor paga taxa da transação', 'woo-safe2pay'),
                    'default' => 'no',
                    'description' => __('Recebedor paga taxa da transação.', 'woo-safe2pay')
                );
        }
    }

    public function admin_options()
    {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_script('safe2pay-admin', plugins_url('assets/js/admin/admin' . $suffix . '.js', plugin_dir_path(__FILE__)), array('jquery'), WC_SAFE2PAY_VERSION, true);

        include dirname(__FILE__) . '/admin/views/html-admin-page.php';
    }

    protected function send_email($subject, $title, $message)
    {
        $mailer = WC()->mailer();
        $mailer->send(get_option('admin_email'), $subject, $mailer->wrap_message($title, $message));
    }

    public function payment_fields()
    {
        wp_enqueue_script('wc-credit-card-form');
        wp_enqueue_script('wc-debit-card-form');

        $description = $this->get_description();

        if ($description) {
            echo wpautop(wptexturize($description));
        }

        $cart_total = $this->get_order_total();

        $discount_percentage = $this->discount_amount_bank_slip;
        $discount_amount = ($cart_total / 100) * $discount_percentage;

        wc_get_template(
            'checkout-form.php',
            array(
                'cart_total' => $cart_total,
                'tc_credit' => $this->tc_credit,
                'tc_installments' => $this->tc_installments,
                'tc_minimum_installment_amount' => $this->tc_minimum_installment_amount,
                'tc_pix' => $this->tc_pix,
                'tc_ticket' => $this->tc_ticket,
                '$discount_percentage' => $discount_percentage,
                'discount_bank_slip' => $this->discount_bank_slip,
                'discount_amount' => $discount_amount,
                'tc_debit' => $this->tc_debit,
                'tc_cryptocurrency' => $this->tc_cryptocurrency,

                'flag' => plugins_url('assets/images/brazilian-flag.png', plugin_dir_path(__FILE__)),
            ),
            'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path()
        );
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if ('transparent' === $this->method) {

            $response = $this->api->PaymentController($order, $_POST);

            if ($response['data']) {
                $this->update_order_status($response['data'], $order_id);
            }

            if ($response['url']) {
                WC()->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $response['url'],
                );
            } else {
                wc_add_notice($response['error']);

                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }
        } else {
            $use_shipping = isset($_POST['ship_to_different_address']);

            return array(
                'result' => 'success',
                'redirect' => add_query_arg(array('use_shipping' => $use_shipping), $order->get_checkout_payment_url(true)),
            );
        }
    }

    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);
        $request_data = $_POST;

        if (isset($_GET['use_shipping']) && true === (bool)$_GET['use_shipping']) {
            $request_data['ship_to_different_address'] = true;
        }

        $response = $this->api->CheckoutController($order, $request_data);

        if ($response['url']) {
            wc_enqueue_js(
                '
				$( "#browser-has-javascript" ).show();
				$( "#browser-no-has-javascript, #cancel-payment, #submit-payment" ).hide();
				var isOpenLightbox = Safe2PayLightbox({
						code: "' . esc_js($response['token']) . '"
					}, {
						success: function ( transactionCode ) {
							window.location.href = "' . str_replace('&amp;', '&', esc_js($this->get_return_url($order))) . '";
						},
						abort: function () {
							window.location.href = "' . str_replace('&amp;', '&', esc_js($order->get_cancel_order_url())) . '";
						}
				});
				if ( ! isOpenLightbox ) {
					window.location.href = "' . esc_js($response['url']) . '";
				}
			'
            );

            wc_get_template(
                'lightbox-checkout.php',
                array(
                    'cancel_order_url' => $order->get_cancel_order_url(),
                    'payment_url' => $response['url'],
                    'lightbox_script_url' => '',
                ),
                'woocommerce/safe2pay/',
                WC_Safe2Pay::get_templates_path()
            );
        } else {
            include dirname(__FILE__) . '/views/html-receipt-page-error.php';
        }
    }

    protected function SavePaymentData($order, $posted)
    {
        $data = $order->get_data();

        $meta_data = array();
        $payment_data = array(
            'paymenttype' => '',
            'description' => '',
            'method' => '',
            'installments' => '',
            'link' => '',
            'walletaddress' => '',
            'amount' => '',
            'symbol' => ''
        );

        if ($posted->IdTransaction != null) {
            $meta_data[__('Código da transação', 'woo-safe2pay')] = sanitize_text_field((string)$posted->IdTransaction);
        }

        if ($posted->Message != null) {
            $meta_data[__('Status', 'woo-safe2pay')] = sanitize_text_field((string)$posted->Message);
        }

        if ($posted->Description != null) {
            $payment_data['description'] = sanitize_text_field((string)$posted->Description);
            $meta_data[__('Description', 'woo-safe2pay')] = $payment_data['description'];
        }

        if ($data['billing']['email'] != null) {
            $meta_data[__('Email', 'woo-safe2pay')] = sanitize_text_field($data['billing']['email']);
        }

        if ($data['billing']['first_name'] != null) {
            $meta_data[__('Nome', 'woo-safe2pay')] = sanitize_text_field($data['billing']['first_name'] . ' ' . $data['billing']['last_name']);
        }

        $method = sanitize_text_field($_POST['safe2pay_payment_method']);

        switch ($method) {
            case "bank-slip":
                $payment_data['paymenttype'] = '1';

                $meta_data[__('', 'woo-safe2pay')] = sanitize_text_field((string)$posted->BankSlipUrl);

                if ($posted->BankSlipUrl != null) {
                    $payment_data['link'] = sanitize_text_field((string)$posted->BankSlipUrl);
                    $meta_data[__('URL Boleto', 'woo-safe2pay')] = $payment_data['link'];

                    $meta_data[__('Número Boleto', 'woo-safe2pay')] = sanitize_text_field((string)$posted->BankSlipNumber);
                    $meta_data[__('Código de Barras', 'woo-safe2pay')] = sanitize_text_field((string)$posted->Barcode);
                    $meta_data[__('Data de Vencimento', 'woo-safe2pay')] = sanitize_text_field((string)$posted->DueDate);
                    $meta_data[__('Linha Digitável', 'woo-safe2pay')] = sanitize_text_field((string)$posted->DigitableLine);
                }
                break;
            case "credit-card":
                $payment_data['paymenttype'] = '2';

                $payment_data['installments'] = sanitize_text_field($_POST['safe2pay_card_installments']);
                $meta_data[__('Parcelas', 'woo-safe2pay')] = $payment_data['installments'];
                break;
            case "crypto-currency":
                $payment_data['paymenttype'] = '3';

                $payment_data['link'] = sanitize_text_field((string)$posted->QrCode);
                $meta_data[__('Payment URL', 'woo-safe2pay')] = $payment_data['link'];

                $payment_data['walletaddress'] = sanitize_text_field((string)$posted->WalletAddress);
                $meta_data[__('Wallet Address', 'woo-safe2pay')] = $payment_data['walletaddress'];

                $payment_data['symbol'] = sanitize_text_field((string)$posted->Symbol);
                $meta_data[__('Symbol', 'woo-safe2pay')] = $payment_data['symbol'];

                if ($posted->AmountBTC != null) {
                    $payment_data['amount'] = sanitize_text_field((string)$posted->AmountBTC);
                    $meta_data[__('Amount', 'woo-safe2pay')] = $payment_data['amount'];
                }

                if ($posted->AmountLTC != null) {
                    $payment_data['amount'] = sanitize_text_field((string)$posted->AmountLTC);
                    $meta_data[__('Amount', 'woo-safe2pay')] = $payment_data['amount'];
                }

                if ($posted->AmountBCH != null) {
                    $payment_data['amount'] = sanitize_text_field((string)$posted->AmountBCH);
                    $meta_data[__('Amount', 'woo-safe2pay')] = $payment_data['amount'];
                }
                break;
	        case "debit-card":
		        $payment_data['paymenttype'] = '4';

		        if ($posted->AuthenticationUrl != null) {
			        $payment_data['link'] = sanitize_text_field((string)$posted->AuthenticationUrl);
			        $meta_data[__('URL de Autenticação', 'woo-safe2pay')] = $payment_data['link'];
		        }
		        break;
	        case "pix":
		        $payment_data['paymenttype'] = '6';

		        if ($posted->Key != null) {
			        $payment_data['key'] = sanitize_text_field((string)$posted->Key);
			        $meta_data[__('Copia e Cola', 'woo-safe2pay')] = $payment_data['key'];
		        }

		        if ($posted->QrCode != null) {
			        $payment_data['link'] = sanitize_text_field((string)$posted->QrCode);
			        $meta_data[__('Qr-Code', 'woo-safe2pay')] = $payment_data['link'];
		        }
		        break;
        }

        $meta_data['_wc_safe2pay_payment_data'] = $payment_data;

        if (method_exists($order, 'update_meta_data')) {
            foreach ($meta_data as $key => $value) {
                $order->update_meta_data($key, $value);
            }

            $order->save();
        } else {
            foreach ($meta_data as $key => $value) {
                update_post_meta($order->id, $key, $value);
            }
        }
    }

    public function update_order_status($posted, $order_id)
    {
        if (isset($posted->IdTransaction)) {
            $id = (int)str_replace($this->invoice_prefix, '', $posted->IdTransaction);

            $order = wc_get_order($order_id);

            if (!$order) {
                return;
            }

            if ($id > 0) {
                if ('yes' === $this->debug) {
                    $this->log->add($this->id, 'Safe2Pay payment status for order ' . $order->get_order_number() . ' is: ' . intval($posted->Status));
                }

                $this->SavePaymentData($order, $posted);

                switch ($posted->Status) {
                    case '1':
                        $order->update_status('on-hold', __('Safe2Pay: Pendente.', 'woo-safe2pay'));
                        break;
                    case '2':
                        $order->update_status('on-hold', __('Safe2Pay: Processamento.', 'woo-safe2pay'));
                        $order->add_order_note(__('Safe2Pay: Pagamento em processamento..', 'woo-safe2pay'));
                        break;
                    case '3':
                        if (method_exists($order, 'get_status') && 'cancelled' === $order->get_status()) {
                            $order->update_status('processing', __('Safe2Pay: Pagamento autorizado.', 'woo-safe2pay'));
                            wc_reduce_stock_levels($order_id);
                        } else {
                            $order->add_order_note(__('Safe2Pay: Autorizado.', 'woo-safe2pay'));
                            $order->payment_complete(sanitize_text_field((string)$posted->IdTransaction));
                        }
                        break;
                    case '5':
                        $order->update_status('processing', __('Safe2Pay: Em disputa.', 'woo-safe2pay'));
                        $this->send_email(
                        /* translators: %s: order number */
                            sprintf(__('Payment for order %s came into dispute', 'woo-safe2pay'), $order->get_order_number()),
                            __('Payment in dispute', 'woo-safe2pay'),
                            /* translators: %s: order number */
                            sprintf(__('Order %s has been marked as on-hold, because the payment came into dispute in Safe2Pay.', 'woo-safe2pay'), $order->get_order_number())
                        );

                        break;
                    case '6':
                        $order->update_status('refunded', __('Safe2Pay: Devolvido.', 'woo-safe2pay'));
                        $this->send_email(
                        /* translators: %s: order number */
                            sprintf(__('Payment for order %s refunded', 'woo-safe2pay'), $order->get_order_number()),
                            __('Payment refunded', 'woo-safe2pay'),
                            /* translators: %s: order number */
                            sprintf(__('Order %s has been marked as refunded by Safe2Pay.', 'woo-safe2pay'), $order->get_order_number())
                        );

                        if (function_exists('wc_increase_stock_levels')) {
                            wc_increase_stock_levels($order_id);
                        }

                        break;
                    case '8':
                        $order->update_status('failed', __('Safe2Pay: Em cancelamento.', 'woo-safe2pay'));
                        $order->add_order_note(__('Safe2Pay: Recusado.', 'woo-safe2pay'));

                        break;
	                case '7':
                    case '12':
                        $order->update_status('cancelled', __('Safe2Pay: Em cancelamento.', 'woo-safe2pay'));

                        if (function_exists('wc_increase_stock_levels')) {
                            wc_increase_stock_levels($order_id);
                        }

                        break;
                    default:
                        break;
                }
            } else {
                if ('yes' === $this->debug) {
                    $this->log->add($this->id, 'Error: Order Key does not match with Safe2Pay reference.');
                }
            }
        }
    }

    public function thankyou_page($order_id)
    {
        //$order = wc_get_order($order_id);
        $order            = new WC_Order( $order_id );

        $data = is_callable([$order, 'get_meta'])
            ? $order->get_meta('_wc_safe2pay_payment_data')
            : get_post_meta($order_id, '_wc_safe2pay_payment_data', true);

        if (isset($data['paymenttype'])) {
            switch ($data['paymenttype']) {
                case '1':
                    wc_get_template('bs-payment-instructions.php',
                        [
                            'paymenttype' => $data['paymenttype'],
                            'link' => $data['link']
                        ], 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path());
                    break;
                case '2':
                    wc_get_template('cc-payment-instructions.php',
                        [
                            'paymenttype' => $data['paymenttype'],
                            'description' => $data['description'],
                        ], 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path());
                    break;
                case '3':
                    wc_get_template('crypto-payment-instructions.php',
                        [
                            'paymenttype' => $data['paymenttype'],
                            'link' => $data['link'],
                            'walletaddress' => $data['walletaddress'],
                            'amount' => $data['amount'],
                            'symbol' => $data['symbol']
                        ], 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path());
                    break;
                case '4':
                    wc_get_template('dc-payment-instructions.php',
                        [
                            'paymenttype' => $data['paymenttype'],
                            'link' => $data['link']
                        ], 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path());
                    break;
	            case '6':
		            wc_get_template('pix-payment-instructions.php',
			            [
				            'paymenttype' => $data['paymenttype'],
				            'link' => $data['link'],
				            'key' => $data['key']
			            ], 'woocommerce/safe2pay/', WC_Safe2Pay::get_templates_path());
		            break;
            }
        }
    }

    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if (method_exists($order, 'get_meta')) {
            if ($sent_to_admin || 'on-hold' !== $order->get_status() || $this->id !== $order->get_payment_method()) {
                return;
            }

            $data = $order->get_meta('_wc_safe2pay_payment_data');
        } else {
            if ($sent_to_admin || 'on-hold' !== $order->status || $this->id !== $order->payment_method) {
                return;
            }

            $data = get_post_meta($order->get_id(), '_wc_safe2pay_payment_data', true);
        }

        if ($plain_text) {
            wc_get_template(
                'emails/plain-instructions.php',
                array(
                    'paymenttype' => $data['paymenttype'],
                    'installments' => $data['installments'],
                    'method' => $data['method'],
                    'link' => $data['link'],
                    'walletaddress' => $data['walletaddress'],
                    'amount' => $data['amount'],
                    'symbol' => $data['symbol']
                ),
                'woocommerce/safe2pay/',
                WC_Safe2Pay::get_templates_path()
            );
        } else {
            wc_get_template(
                'emails/html-instructions.php',
                array(
                    'paymenttype' => $data['paymenttype'],
                    'installments' => $data['installments'],
                    'method' => $data['method'],
                    'link' => $data['link'],
                    'walletaddress' => $data['walletaddress'],
                    'amount' => $data['amount'],
                    'symbol' => $data['symbol']
                ),
                'woocommerce/safe2pay/',
                WC_Safe2Pay::get_templates_path()
            );
        }
    }
}
