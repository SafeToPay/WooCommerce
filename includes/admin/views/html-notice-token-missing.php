<?php
/**
 * Admin View: Notice - Token missing
 *
 * @package WooCommerce_Safe2Pay/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e( 'Safe2Pay Disabled', 'woo-safe2pay' ); ?></strong>: <?php _e( 'You should inform your token.', 'woo-safe2pay' ); ?>
	</p>
</div>
