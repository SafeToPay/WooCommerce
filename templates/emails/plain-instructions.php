<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$return_string = esc_html__( 'Payment', 'woo-safe2pay' ) . "\n\n";

if ( $paymenttype == '1' ) {

	$return_string .= esc_html__( 'Please use the link below to view your Banking Ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woo-safe2pay' ) . "\n";
	$return_string .= esc_url( $link ) . "\n";
	$return_string .= esc_html__( 'After we receive the ticket payment confirmation, your order will be processed.', 'woo-safe2pay' );

} elseif ( $paymenttype == '2' ) {

	$return_string .= esc_html__( 'After we receive the ticket payment confirmation, your order will be processed', 'woo-safe2pay' ) . "\n";
	$return_string .= esc_html__( 'After we receive the confirmation from the bank, your order will be processed.', 'woo-safe2pay' );

} elseif ( $paymenttype == '3' ) {

	$return_string .= esc_html__( 'Please use the link below to make the payment in your bankline:', 'woo-safe2pay' ) . "\n";
	$return_string .= esc_url( $link ) . "\n";
	$return_string .= esc_html__( 'After we receive the confirmation from the bank, your order will be processed.', 'woo-safe2pay' );

} else {

	$return_string .= esc_html__( 'As soon as the credit card operator confirm the payment, your order will be processed.', 'woo-safe2pay' );

}

$return_string .= "\n\n****************************************************\n\n";

return $return_string;
