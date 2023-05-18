<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="woocommerce-message"
     style="display:block !important; color:#000 !important; background-color:#fff !important;">
    <span>
        <a class="button" href="<?php echo esc_url( $link ); ?>" target="_blank">
            <?php esc_html_e( 'Visualizar boleto', 'woo-safe2pay' ); ?>
            <br/>
        </a>

        <?php echo esc_html__( 'Clique no botão para visualizar o seu boleto bancário. Após a confirmação do pagamento, seu pedido será processado.', 'woo-safe2pay' ); ?>
    </span>
</div>
