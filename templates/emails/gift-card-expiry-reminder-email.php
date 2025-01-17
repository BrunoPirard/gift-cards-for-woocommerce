<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*do_action( 'woocommerce_email_header', $email_heading, $email );

?>

<p><?php printf( __( 'Hello! Your gift card with code <strong>%s</strong> is about to expire on %s.', 'gift-cards-for-woocommerce' ), esc_html( $gift_card->code ), date_i18n( wc_date_format(), strtotime( $gift_card->expiration_date ) ) ); ?></p>

<p><?php printf( __( 'Current Balance: %s', 'gift-cards-for-woocommerce' ), wc_price( $gift_card->balance ) ); ?></p>

<p><?php _e( 'Please use your gift card before it expires.', 'gift-cards-for-woocommerce' ); ?></p>

<?php

do_action( 'woocommerce_email_footer', $email );*/

do_action( 'woocommerce_email_header', $email_heading, $email );

// Calculate the number of days remaining
$days_remaining = ceil((strtotime($gift_card->expiration_date) - current_time('timestamp')) / DAY_IN_SECONDS);
?>

<p><?php printf( 
    __( 'Hello! Your gift card with code <strong>%s</strong> will expire in %d days on <strong>%s</strong>.', 'gift-cards-for-woocommerce' ),
    esc_html( $gift_card->code ),
    $days_remaining,
    date_i18n( get_option('date_format'), strtotime( $gift_card->expiration_date ) )
); ?></p>

<p><?php printf( __( 'Current Balance: <strong>%s</strong>', 'gift-cards-for-woocommerce' ), wc_price( $gift_card->balance ) ); ?></p>

<?php if ($gift_card->balance > 0) : ?>
    <p><?php _e( 'Don\'t let this balance go to waste! Use your gift card before it expires.', 'gift-cards-for-woocommerce' ); ?></p>
    
    <p style="text-align: center; margin: 30px 0;">
        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" style="background-color: #7f54b3; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 3px;">
            <?php _e( 'Shop Now', 'gift-cards-for-woocommerce' ); ?>
        </a>
    </p>
<?php endif; ?>

<p><small><?php _e( 'This is an automated reminder. Please do not reply to this email.', 'gift-cards-for-woocommerce' ); ?></small></p>

<?php
do_action( 'woocommerce_email_footer', $email );
