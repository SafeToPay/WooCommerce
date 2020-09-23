<?php

if (!defined('ABSPATH')) {
    exit;
}

_e('Payment', 'woo-safe2pay');

echo "\n\n";

if ($paymenttype == '1') {

    _e('Please use the link below to view your Banking Ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woo-safe2pay');

    echo "\n";

    echo esc_url($link);

    echo "\n";

    _e('After we receive the ticket payment confirmation, your order will be processed.', 'woo-safe2pay');

} elseif ($paymenttype == '2') {

    _e('After we receive the ticket payment confirmation, your order will be processed', 'woo-safe2pay');

    echo "\n";

    _e('After we receive the confirmation from the bank, your order will be processed.', 'woo-safe2pay');

} elseif ($paymenttype == '3') {

    _e('Please use the link below to make the payment in your bankline:', 'woo-safe2pay');

    echo "\n";

    echo esc_url($link);

    echo "\n";

    _e('After we receive the confirmation from the bank, your order will be processed.', 'woo-safe2pay');

} elseif ($paymenttype == '4') {

    _e('Please use the link below to make the payment in your bankline:', 'woo-safe2pay');

    echo "\n";

    echo esc_url($link);

    echo "\n";

    _e('After we receive the confirmation from the bank, your order will be processed.', 'woo-safe2pay');

} else {

    echo "\n";

    _e('As soon as the credit card operator confirm the payment, your order will be processed.', 'woo-safe2pay');

}

echo "\n\n****************************************************\n\n";
