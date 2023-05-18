<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$order_total     = esc_attr( number_format( $cart_total, 2, '.', '' ) );
$installments    = isset( $tc_installments ) ? min( $tc_installments, 12 ) : 12;
$min_installment = isset( $tc_minimum_installment_amount ) ? $tc_minimum_installment_amount : 5;
$interest_rates  = array_fill( 0, $installments, 0 );

if ( ! empty( $tc_interest_rate ) ) {
	$interest_rates = explode( ';', $tc_interest_rate );
}
?>

<fieldset id="safe2pay-payment-form">

    <ul id="safe2pay-payment-methods" style="margin-bottom: 5%;" class="payment-methods">

        <div class="payment-method-section">
			<?php if ( $tc_pix == 'yes' ) : ?>
                <li><label>
                        <input id="safe2pay-payment-method-pix" type="radio" name="safe2pay_payment_method" required
                               value="pix" <?php checked( true, ( 'no' == $tc_cryptocurrency && 'no' == $tc_credit && 'no' == $tc_ticket ), true ); ?> />
						<?php _e( 'Pix', 'woo-safe2pay' ); ?>

                    </label></li>
			<?php endif; ?>
        </div>

        <div class="payment-method-section">
			<?php if ( $tc_ticket == 'yes' ) : ?>
                <li><label>
                        <input id="safe2pay-payment-method-bank-slip" type="radio" name="safe2pay_payment_method" required
                               value="bank-slip" <?php checked( true, ( 'no' == $tc_pix && 'no' == $tc_cryptocurrency && 'no' == $tc_credit ), true ); ?> />
						<?php _e( 'Boleto', 'woo-safe2pay' ); ?>

                    </label></li>
			<?php endif; ?>
        </div>

        <div class="payment-method-section">
			<?php if ( $tc_credit == 'yes' ) : ?>
                <li><label>
                        <input id="safe2pay-payment-method-credit-card" type="radio" name="safe2pay_payment_method" required
                               value="credit-card" <?php checked( true, ( 'no' == $tc_pix && 'no' == $tc_cryptocurrency && 'yes' == $tc_ticket ), true ); ?> />

						<?php _e( 'Cartão de Crédito', 'woo-safe2pay' ); ?>
                    </label></li>
			<?php endif; ?>
        </div>

        <div class="payment-method-section">
			<?php if ( $tc_cryptocurrency == 'yes' ) : ?>
                <li><label>
                        <input id="safe2pay-payment-method-crypto-currency" type="radio" name="safe2pay_payment_method"
                               required
                               value="crypto-currency" <?php checked( true, ( 'no' == $tc_pix && 'no' == $tc_credit && 'no' == $tc_ticket ), true ); ?> />
						<?php _e( 'Criptomoedas', 'woo-safe2pay' ); ?>
                    </label></li>
			<?php endif; ?>
        </div>
    </ul>

    <div class="clear"></div>

	<?php if ( 'yes' == $tc_pix ) : ?>
        <div id="safe2pay-pix-form" class="safe2pay-method-form">
            <p>
                <i id="safe2pay-icon-pix"></i>
				<?php _e( 'Realize o pagamento através de Pix.', 'woo-safe2pay' ); ?>
            </p>

            <div class="clear"></div>

        </div>
	<?php endif; ?>

	<?php if ( 'yes' == $tc_ticket ) : ?>
        <div id="safe2pay-bank-slip-form" class="safe2pay-method-form">
            <p>
                <i id="safe2pay-icon-ticket"></i>
				<?php _e( 'Realize o pagamento através de boleto bancário.', 'woo-safe2pay' ); ?>
            </p>

			<?php if ( $discount_bank_slip == 'yes' && $discount_amount > 0 ) : ?>

				<?php echo sprintf( __( 'Desconto de %s para pagamento por boleto! Valor total: %s.', 'woo-safe2pay' ),
					sanitize_text_field( wc_price( $discount_amount ) ),
					sanitize_text_field( wc_price( $order_total - $discount_amount ) ) ); ?>

			<?php endif; ?>

            <div class="clear"></div>

        </div>
	<?php endif; ?>

    <div class="clear"></div>

	<?php if ( 'yes' == $tc_credit ) : ?>
        <div id="safe2pay-credit-card-form" class="safe2pay-method-form">

            <p>
                <i id="safe2pay-icon-credit-card"></i>
				<?php _e( 'Realize o pagamento através do seu cartão de crédito.', 'woo-safe2pay' ); ?>
            </p>

            <div class="clear"></div>

            <p id="safe2pay-card-holder-name-field" class="form-row form-row-first">
                <label for="safe2pay-card-holder-name"><?php _e( 'Nome impresso no cartão ', 'woo-safe2pay' ); ?><span
                            class="required">*</span></label>
                <input id="safe2pay-card-holder-name" name="safe2pay_card_holder_name" class="input-text" type="text"
                       autocomplete="off" style="font-size: 1.5em; padding: 8px;"/>
            </p>

            <p id="safe2pay-card-number-field" class="form-row form-row-last">
                <label for="safe2pay-card-number"><?php _e( 'Número do cartão', 'woo-safe2pay' ); ?> <span
                            class="required">*</span></label>
                <input onkeypress="return IsNumber(event)" id="safe2pay-card-number"
                       name="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off"
                       placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"
                       style="font-size: 1.5em; padding: 8px; width: 100%;"/>
            </p>

            <div class="clear"></div>

            <p id="safe2pay-card-expiry-field" class="form-row form-row-first">
                <label for="safe2pay-card-expiry"><?php _e( 'Validade (MM/YYYY)', 'woo-safe2pay' ); ?> <span
                            class="required">*</span></label>
                <input maxlength="7" onkeypress="return ExpiryMask(event,this)" id="safe2pay-card-expiry"
                       name="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off"
                       placeholder="<?php _e( 'MM / YYYY', 'woo-safe2pay' ); ?>"
                       style="font-size: 1.5em; padding: 8px; width: 100%;"/>
            </p>

            <p id="safe2pay-card-cvc-field" class="form-row form-row-last">
                <label for="safe2pay-card-cvc"><?php _e( 'CVV', 'woo-safe2pay' ); ?> <span
                            class="required">*</span></label>
                <input maxlength="4" onkeypress="return IsNumber(event)" id="safe2pay-card-cvc"
                       name="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off"
                       placeholder="<?php _e( 'CVV', 'woo-safe2pay' ); ?>"
                       style="font-size: 1.5em; padding: 8px;width: 100%;"/>
            </p>

            <div class="clear"></div>

			<?php if ( $tc_installments > 1 ) : ?>

                <p id="safe2pay-card-installments-field" class="form-row form-row-wide">
                    <label for="safe2pay-card-installments"><?php _e( 'Parcelar em ', 'woo-safe2pay' ); ?><span
                                class="required">*</span></label>
                    <select id="safe2pay-card-installments" name="safe2pay_card_installments"
                            style="font-size: 1.5em; padding: 4px; width: 100%;">
						<?php for ( $i = 1; $i <= $installments; $i ++ ) :
							$installment_total = $order_total / $i;
							$interest_rate = isset( $interest_rates[ $i - 1 ] ) ? $interest_rates[ $i - 1 ] : 0;
							$installment_with_interest = $installment_total * ( 1 + ( $interest_rate / 100 ) );
							$interest_label = ( $interest_rate > 0 ) ? 'com juros' : 'sem juros';

							if ( 1 != $i && $installment_with_interest < $min_installment ) {
								continue;
							}

							$total_with_tax = $installment_with_interest * $i;
							$total_label    = $i > 1 ? " (R$ " . wc_price( $total_with_tax ) . ")" : "";
							?>

                            <option value="<?php echo $i; ?>"><?php printf( '%dx de %s %s', $i,
									wc_price( $installment_with_interest ),
									$interest_label . $total_label
								); ?></option>
						<?php endfor; ?>
                    </select>

                    <input type="hidden" name="tc_interest_rate"
                           value="<?php echo isset( $tc_interest_rate ) ? esc_attr( $tc_interest_rate ) : ''; ?>"/>

                </p>

			<?php endif; ?>

            <div class="clear"></div>

        </div>
	<?php endif; ?>

    <div class="clear"></div>

	<?php if ( 'yes' == $tc_cryptocurrency ) : ?>
        <div id="safe2pay-crypto-currency-form" class="safe2pay-method-form">
            <p>
                <i id="safe2pay-icon-cryptocurrency"></i>
				<?php _e( 'Realize o pagamento através de criptomoedas.', 'woo-safe2pay' ); ?>
            </p>

            <div class="clear"></div>

            <p id="safe2pay-currency-type-field" class="form-row form-row-first">
                <label for="safe2pay-currency-type"><?php _e( 'Informe o tipo de moeda.', 'woo-safe2pay' ); ?></label>
                <select id="safe2pay-currency-type" name="safe2pay_currency-type"
                        style="font-size: 1.5em; padding: 8px; width: 100%; height: 60px;">
                    <option value="0">Selecione</option>
                    <option value="BTC">Bitcoin (BTC)</option>
                </select>
            </p>

            <div class="clear"></div>

        </div>
	<?php endif; ?>

    <div class="clear"></div>

    <div class="clear"></div>

	<?php if ( ! is_plugin_active( 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php' ) )  : ?>
		<?php if ( ! is_plugin_active( 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php' ) )  : ?>

            <p id="safe2pay-identity-field" class="form-row form-row-first" style="margin-bottom: 4%;">
                <label for="safe2pay-card-cvc">CPF/CNPJ do titular <span class="required">*</span></label>
                <input onkeypress="MaskcpfCnpj(this)" id="safe2pay-customer-identity" name="customer_identity"
                       type="tel"
                       autocomplete="off" maxlength="18"
                       style="font-size: 1.5em; padding: 8px;width: 100%; heigth: 100%;"/>
            </p>

		<?php endif; ?>
	<?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var paymentForm = document.getElementById('safe2pay-payment-form');
            var paymentMethods = paymentForm.querySelectorAll('input[type="radio"]');
            var submitButton = paymentForm.querySelector('button[type="submit"]');

            // Auto select the first method if only one is available
            if (paymentMethods.length == 1) {
                paymentMethods[0].checked = true;
            }

            submitButton.addEventListener('click', function(e) {
                var selectedMethod = false;
                for (var i = 0; i < paymentMethods.length; i++) {
                    if (paymentMethods[i].checked) {
                        selectedMethod = true;
                        break;
                    }
                }

                if (!selectedMethod) {
                    e.preventDefault();
                    alert('Please select a payment method.');
                }
            });
        });

    </script>

</fieldset>