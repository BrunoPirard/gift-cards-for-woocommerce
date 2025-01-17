<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//echo $email_heading . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// Calculate the number of days remaining
$days_remaining = ceil((strtotime($gift_card->expiration_date) - current_time('timestamp')) / DAY_IN_SECONDS);

printf( 
    __( 'Hello! Your gift card with code %s will expire in %d days on %s.', 'gift-cards-for-woocommerce' ),
    $gift_card->code,
    $days_remaining,
    date_i18n( get_option('date_format'), strtotime( $gift_card->expiration_date ) )
);
echo "\n\n";

printf( __( 'Current Balance: %s', 'gift-cards-for-woocommerce' ), strip_tags( wc_price( $gift_card->balance ) ) );
echo "\n\n";

if ($gift_card->balance > 0) {
    _e( 'Don\'t let this balance go to waste! Use your gift card before it expires.', 'gift-cards-for-woocommerce' );
    echo "\n\n";
    
    _e( 'Visit our shop: ', 'gift-cards-for-woocommerce' );
    echo esc_url( wc_get_page_permalink( 'shop' ) );
    echo "\n\n";
}

_e( 'This is an automated reminder. Please do not reply to this email.', 'gift-cards-for-woocommerce' );
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
