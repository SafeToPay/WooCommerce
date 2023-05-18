<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<h2><?php esc_html_e( 'Payment', 'woo-safe2pay' ); ?></h2>

<?php if ( $paymenttype == '1' ) : ?>

    <div class="order_details">
		<?php
		$message = '<a class="button" href="' . esc_url( $link ) . '" target="_blank">' . esc_html__( 'Visualizar Boleto Bancário', 'woo-safe2pay' ) . '<br/></a>';
		$message .= esc_html__( 'Clique no botão para visualizar o seu boleto bancário. Após a confirmação do pagamento, seu pedido será processado.', 'woo-safe2pay' );
		echo '<span>' . $message . '</span>';
		?>
    </div>

<?php elseif ( $paymenttype == '2' ) : ?>

    <div class="order_details">
        <span>
            <?php esc_html_e( 'Pagamento autorizado', 'woo-safe2pay' ); ?>
            <?php esc_html_e( 'Tudo certo! Seu pedido será processado.', 'woo-safe2pay' ); ?>
        </span>
    </div>

<?php elseif ( $paymenttype == '3' ) : ?>

    <div class="order_details">
        <div style="margin-left: auto;margin-right: auto;width: 10em;">
            <img src="<?php echo esc_url( $link ); ?>" alt="QR CODE">
            <br/>
        </div>

        <div style="text-align: center; font-size: 12px;">
            <span style=""><?php esc_html_e( 'Moeda: ', 'woo-safe2pay' ); ?><?php echo esc_html( $symbol ); ?></span>
            <br/>
            <span style=""><?php esc_html_e( 'Valor: ', 'woo-safe2pay' ); ?><?php echo esc_html( $amount ); ?></span>
            <br/>
            <span style=""><?php esc_html_e( 'Carteira: ', 'woo-safe2pay' ); ?><?php echo esc_html( $walletaddress ); ?></span>
            <br>
        </div>

        <br/>

        <div style="text-align: center;">
			<?php esc_html_e( 'Após a confirmação do pagamento, Seu pedido será processado.', 'woo-safe2pay' ); ?>
        </div>
        <br/>
    </div>

<?php elseif ( $paymenttype == '6' ) : ?>

    <div class="order_details">
		<?php
		$message = esc_html__( 'Seu pedido foi recebido. Utilize os dados abaixo para efetuar o pagamento:', 'woo-safe2pay' );
		$message .= '<div style="margin-left: auto;margin-right: auto;width: 10em;">';
		$message .= '<img src="' . esc_url( $link ) . '" alt="QR CODE">';
		$message .= '<br/>';
		$message .= '</div>';
		$message .= esc_html__( 'Copia e Cola: ', 'woo-safe2pay' ) . ' ' . esc_html( $key );
		$message .= '<br/>';
		$message .= '<br/>';
		$message .= esc_html__( 'Após a confirmação do pagamento, seu pedido será liberado.', 'woo-safe2pay' );
		echo '<span>' . $message . '</span>';
		?>
    </div>

<?php endif; ?>
