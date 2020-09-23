<?php
/**
 * Admin View: Notice - Currency not supported.
 *
 * @package WooCommerce_Safe2Pay/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e( 'Safe2Pay Disabled', 'woo-safe2pay' ); ?></strong>: <?php printf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', 'woo-safe2pay' ), get_woocommerce_currency() ); ?>
	</p>
</div>
