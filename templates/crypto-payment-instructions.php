<?php if (!defined('ABSPATH')) exit; ?>

<div class="woocommerce-message">

    <span>
        <?php echo __('Seu pedido foi recebido. Veja abaixo os dados para realizar o pagamento:', 'woo-safe2pay'); ?>
        <br/>
        <br/>
            <?php echo __('Valor: ', 'woo-safe2pay'); ?> <?php echo $amount; ?>
        <br/>
            <?php echo __('Moeda: ', 'woo-safe2pay'); ?> <?php echo $symbol; ?>
        <br/>
            <?php echo __('Carteira: ', 'woo-safe2pay'); ?> <?php echo $walletaddress; ?>
        <br/>
        <br/>
            <?php echo __('Após a confirmação do pagamento, seu pedido será liberado.', 'woo-safe2pay'); ?>
        <br/>
    </span>

</div>