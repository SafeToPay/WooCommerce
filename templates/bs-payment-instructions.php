<?php if (!defined('ABSPATH')) exit; ?>

<div class="woocommerce-message">
    <span>
        <a class="button" href="<?php echo esc_url($link); ?>" target="_blank">
            <?php esc_html_e('Visualizar boleto', 'woo-safe2pay'); ?>
            <br/>
        </a>

        <?php esc_html_e('Clique no botão para visualizar o seu boleto bancário. 
                Após a confirmação do pagamento, seu pedido será processado.', 'woo-safe2pay'); ?>
    </span>
</div>

