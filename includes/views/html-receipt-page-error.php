if ( ! defined( 'ABSPATH' ) ) {
exit;
}

?>

<ul class="woocommerce-error">
	<?php foreach ( $response['error'] as $message ) : ?>
		<?php $sanitized_message = sanitize_text_field( $message ); ?>
		<li><?php echo esc_html( $sanitized_message ); ?></li>
	<?php endforeach; ?>
</ul>

<?php $cancel_order_url = esc_url( $order->get_cancel_order_url() ); ?>
<a class="button cancel"
   href="<?php echo esc_html__($cancel_order_url); ?>"><?php esc_html_e( 'Click to try again', 'woo-safe2pay' ); ?></a>
