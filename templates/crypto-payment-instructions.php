<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="woocommerce-message"
     style="display:block !important; color:#000 !important; background-color:#fff !important;">
    <span>
        <?php
        $payment_message              = esc_html__( 'Seu pedido foi recebido. Veja abaixo os dados para realizar o pagamento:', 'woo-safe2pay' );
        $qr_code_image                = '<div style="margin-left: auto;margin-right: auto;width: 10em;"><img src="' . esc_url( $link ) . '" alt="QR CODE"><br/></div>';
        $amount_message               = esc_html__( 'Valor: ', 'woo-safe2pay' ) . esc_html( $amount );
        $symbol_message               = esc_html__( 'Moeda: ', 'woo-safe2pay' ) . esc_html( $symbol );
        $wallet_message               = esc_html__( 'Carteira: ', 'woo-safe2pay' ) . esc_html( $walletaddress );
        $payment_confirmation_message = esc_html__( 'Após a confirmação do pagamento, seu pedido será liberado.', 'woo-safe2pay' );

        $message = $payment_message . '<br/>' . $qr_code_image . '<br/>' . $amount_message . '<br/>' . $symbol_message . '<br/>' . $wallet_message . '<br/><br/>' . $payment_confirmation_message;

        echo $message;
        ?>
    </span>
</div>
