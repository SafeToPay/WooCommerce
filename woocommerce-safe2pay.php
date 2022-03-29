<?php
/**
 * Plugin Name:          Safe2Pay (MOD)
 * Plugin URI:           https://safe2pay.com.br/
 * Description:          Aceite pagamentos por boleto, criptomoedas, PIX, cartões de crédito e débito pelo Safe2Pay.
 * Author:               Safe2Pay
 * Version:              1.8.1
 * License:              GPLv3 or later
 * Text Domain:          woo-safe2pay
 * Domain Path:          /languages
 * WC requires at least: 4.0.0
 * WC tested up to:      5.9.2
 */

defined( 'ABSPATH' ) || exit;

define( 'WC_SAFE2PAY_VERSION', '1.7' );
define( 'WC_SAFE2PAY_PLUGIN_FILE', __FILE__ );

if ( ! class_exists( 'WC_Safe2Pay' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-wc-safe2pay.php';
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    
    add_action('rest_api_init', array( 'WC_Safe2Pay', 'init' ));
}
