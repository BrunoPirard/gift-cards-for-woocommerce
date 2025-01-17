<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//echo $email_heading . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

printf( __( 'Hello! You\'ve received a gift card worth %s from %s.', 'gift-cards-for-woocommerce' ), strip_tags( wc_price( $gift_card->balance ) ), $gift_card->sender_name );
echo "\n\n";

if ( ! empty( $gift_card->message ) ) {
    printf( __( 'Message: %s', 'gift-cards-for-woocommerce' ), $gift_card->message );
    echo "\n\n";
}

if ( ! empty( $custom_email_text ) ) {
    echo wp_strip_all_tags( $custom_email_text );
    echo "\n\n";
}

printf( __( 'Redeem your gift card with code: %s', 'gift-cards-for-woocommerce' ), $gift_card->code );
echo "\n\n";

// Ajouter la date d'expiration
$validity_days = get_option('gift_card_validity_days', 365);
$expiry_date = date_i18n(
    get_option('date_format'), 
    strtotime("+{$validity_days} days")
);

printf( __( 'This gift card will expire on: %s', 'gift-cards-for-woocommerce' ), $expiry_date );
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

