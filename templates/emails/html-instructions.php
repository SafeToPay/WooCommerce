<?php if (!defined('ABSPATH')) exit; ?>

    <h2><?php _e('Payment', 'woo-safe2pay'); ?></h2>


<?php if ($paymenttype == '1') : ?>

    <div class="order_details">
    <span>
		<a class="button" href="<?php echo esc_url($link); ?>" target="_blank">
			<?php _e('Visualizar Boleto Bancário', 'woo-safe2pay'); ?>
			<br/>
		</a>

		<?php _e('Clique no botão para visualizar o seu boleto bancário. 
					Após a confirmação do pagamento, seu pedido será processado.', 'woo-safe2pay'); ?>
	</span>
    </div>

<?php elseif ($paymenttype == '2') : ?>

    <div class="order_details">
		<span>
			<?php _e('Pagamento autorizado', 'woo-safe2pay'); ?></a>
			<?php _e('Tudo certo! Seu pedido será processado.', 'woo-safe2pay'); ?>
        </span>
    </div>

<?php elseif ($paymenttype == '3') : ?>

    <div class="order_details">
        <div style="margin-left: auto;margin-right: auto;width: 10em;">
            <img src="<?php echo esc_url($link); ?>" alt="QR CODE">
            <br/>
        </div>

        <div style="text-align: center; font-size: 12px;">
            <span style="">Moeda: <?php echo $symbol; ?></span>
            <br/>
            <span style="">Valor: <?php echo $amount; ?></span>
            <br/>
            <span style="">Carteira: <?php echo $walletaddress; ?></span>
            <br>
        </div>

        <br/>

        <div style="text-align: center;">
            <?php _e('Após a confirmação do pagamento, Seu pedido será processado.', 'woo-safe2pay'); ?>
        </div>
        <br/>
    </div>

<?php elseif ($paymenttype == '4') : ?>

    <div class="order_details">

		<span>
			<a class="button" href="<?php echo esc_url($link); ?>" target="_blank">
				<?php _e('Finalizar compra!', 'woo-safe2pay'); ?>
				<br/>
			</a>

			<?php _e('Clique no botão para acessar o internet banking e finalizar a compra. 
					Após a confirmação do pagamento, seu pedido será processado.', 'woo-safe2pay'); ?>
		</span>

    </div>

<?php elseif ($paymenttype == '6') : ?>

    <div class="order_details">

        <span>
            <?php echo __('Seu pedido foi recebido. Utilize os dados abaixo para efetuar o pagamento:', 'woo-safe2pay'); ?>
            <div style="margin-left: auto;margin-right: auto;width: 10em;">
                <img src="<?php echo esc_url( $link ); ?>" alt="QR CODE">
                <br/>
            </div>
                <?php echo __('Copia e Cola: ', 'woo-safe2pay'); ?> <?php echo $key; ?>
            <br/>
            <br/>
                <?php echo __('Após a confirmação do pagamento, seu pedido será liberado.', 'woo-safe2pay'); ?>
            <br/>
        </span>

    </div>

<?php endif; ?>