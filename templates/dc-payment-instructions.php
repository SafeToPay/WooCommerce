<?php if (!defined('ABSPATH')) exit; ?>

<div class="woocommerce-message">
    <span>
        <a class="button" href="<?php echo esc_url($link); ?>" target="_blank">
            <?php echo __('Finalizar compra', 'woo-safe2pay'); ?>
            <br/>
        </a>

        <?php echo __('Clique no botão para acessar o internet banking e finalizar a compra. 
                Após a confirmação do pagamento, seu pedido será processado.', 'woo-safe2pay'); ?>
    </span>
</div>