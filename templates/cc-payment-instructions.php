<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="woocommerce-message"
     style="display:block !important; color:#000 !important; background-color:#fff !important;">
    <span>
        <?php
        $message = esc_html__( $description, 'woo-safe2pay' );

        echo $message;
        ?>
    </span>
</div>