<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="woocommerce-message"
     style="display:block !important; color:#000 !important; background-color:#fff !important;">

    <span>
        <?php
        $message = esc_html__( 'Seu pedido foi recebido. Utilize os dados abaixo para efetuar o pagamento:', 'woo-safe2pay' );
        $message .= '<div style="margin-left: auto;margin-right: auto;width: 10em;">';
        $message .= '<img src="' . esc_url( $link ) . '" alt="QR CODE">';
        $message .= '<br/>';
        $message .= '</div>';
        $message .= esc_html__( 'Copia e Cola: ', 'woo-safe2pay' ) . ' ' . $key;
        $message .= '<br/>';
        $message .= '<br/>';
        $message .= esc_html__( 'Após a confirmação do pagamento, seu pedido será liberado.', 'woo-safe2pay' );

        echo $message;
        ?>
    </span>

</div>