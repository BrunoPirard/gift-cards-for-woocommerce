<?php
/**
 * Gift Cards Logger Class
 */
class Gift_Cards_Logger {

    /**
     * Table name for activity logs
     *
     * @var string
     */
    private $table_name;

     /**
     * Whether logging is enabled
     *
     * @var bool
     */
    private $enabled;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gift_card_activities';
        $this->enabled = get_option('gift_cards_enable_logging', true);
    }

    public function is_enabled() {
        return get_option('gift_cards_enable_logging', true) === 'yes';
    }


    /**
     * Logs an activity related to a gift card.
     *
     * @param string $action_type The type of action (e.g., 'created', 'used').
     * @param string $code        The gift card code.
     * @param float  $amount      The amount associated with the action.
     * @param int    $user_id     The user ID related to the action.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_activity( $action_type, $code, $amount = null, $user_id = null ) {
        if (!$this->enabled) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gift_card_activities';

        $wpdb->insert(
            $table_name,
            [
                'action_type' => $action_type,
                'code'        => $code,
                'amount'      => $amount,
                'user_id'     => $user_id,
                'action_date' => current_time('mysql'),
            ],
            [ '%s', '%s', '%f', '%d', '%s' ]
        );
    }

    /**
     * Logs the creation of a gift card.
     *
     * @param string $code        The code of the created gift card.
     * @param float  $balance     The initial balance of the gift card.
     * @param int    $user_id     The user ID associated with the card.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_creation( $code, $balance, $user_id = null ) {
        $this->log_activity( 'created', $code, $balance, $user_id );
    }

    /**
     * Logs the usage of a gift card.
     *
     * @param string $code        The code of the used gift card.
     * @param float  $amount_used The amount used.
     * @param int    $user_id     The user ID who used the gift card.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_usage( $code, $amount_used, $user_id = null ) {
        $this->log_activity( 'used', $code, $amount_used, $user_id );
    }

    /**
     * Logs a balance adjustment action for a gift card.
     *
     * @param string $code        The code of the adjusted gift card.
     * @param float  $new_balance The new balance after adjustment.
     * @param int    $user_id     The ID of the admin making the adjustment.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_balance_adjustment( $code, $new_balance, $user_id ) {
        $this->log_activity( 'balance_adjusted', $code, $new_balance, $user_id );
    }

    /**
     * Logs an update to the expiration date of a gift card.
     *
     * @param string $code            The code of the gift card with updated expiration.
     * @param string $expiration_date The new expiration date.
     * @param int    $user_id         The ID of the admin making the update.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_expiration_update( $code, $expiration_date, $user_id ) {
        $this->log_activity( 'expiration_updated', $code, null, $user_id );
    }

    /**
     * Logs the deletion of a gift card.
     *
     * @param string $code    The code of the deleted gift card.
     * @param int    $user_id The ID of the admin who deleted the card.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_deletion( $code, $user_id ) {
        $this->log_activity( 'deleted', $code, null, $user_id );
    }

    /**
     * Logs when an expiration reminder email is sent.
     *
     * @param string $code    The code of the gift card.
     * @param int    $user_id The ID of the recipient user.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_expiration_reminder_sent( $code, $user_id ) {
        $this->log_activity( 'expiration_reminder_sent', $code, null, $user_id );
    }

    /**
     * Logs a gift card import or export action.
     *
     * @param string $action_type Action type: 'import' or 'export'.
     * @param int    $user_id     The admin ID who initiated the action.
     * @param int    $count       The number of cards processed.
     * 
     * @since  1.0.0
     * @return void
     */
    private function log_import_export( $action_type, $user_id, $count ) {
        $this->log_activity( $action_type . '_csv', null, $count, $user_id );
    }

}
