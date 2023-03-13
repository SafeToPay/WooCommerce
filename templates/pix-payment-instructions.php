<?php if (!defined('ABSPATH')) exit; ?>

<div class="woocommerce-message" style="display:block !important; color:#000 !important; background-color:#fff !important;">

    <span>
        <?php echo esc_html__('Seu pedido foi recebido. Utilize os dados abaixo para efetuar o pagamento:', 'woo-safe2pay'); ?>
        <div style="margin-left: auto;margin-right: auto;width: 10em;">
			<img src="<?php echo esc_url( $link ); ?>" alt="QR CODE">
			<br/>
		</div>
            <?php echo esc_html__('Copia e Cola: ', 'woo-safe2pay'); ?> <?php echo $key; ?>
        <br/>
        <br/>
            <?php echo esc_html__('Após a confirmação do pagamento, seu pedido será liberado.', 'woo-safe2pay'); ?>
        <br/>
    </span>

</div>