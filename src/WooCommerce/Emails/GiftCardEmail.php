<?php

namespace GiftCards\WooCommerce\Emails;

if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que WooCommerce est chargé
if (!class_exists('WC_Email')) {
    return;
}

use GiftCards\WooCommerce\PDF\Gift_Card_PDF_Generator;

/**
 * WC_Gift_Card_Email Class
 *
 * Handles the email sent to the recipient when a gift card is issued.
 *
 * @package    Gift_Cards_For_WooCommerce
 * @subpackage Emails
 * @since      1.0.0
 */
class GiftCardEmail extends \WC_Email {
    /**
     * Gift card object
     *
     * @var object
     */
    protected $gift_card;

    /**
     * Constructor to set up email settings and template paths.
     * 
     * @since  1.0.0
     */
    public function __construct() {
        $this->id             = 'wc_gift_card_email';
        $this->title          = __( 'Gift Card Email', 'gift-cards-for-woocommerce' );
        $this->description    = __( 'This email is sent to the recipient when a gift card is issued.', 'gift-cards-for-woocommerce' );
        $this->heading        = __( 'You have received a gift card!', 'gift-cards-for-woocommerce' );
        $this->subject        = __( 'You have received a gift card from {sender_name}', 'gift-cards-for-woocommerce' );

        // Définir le chemin de base correct pour les templates
        $this->template_html  = 'emails/gift-card-email.php';
        $this->template_plain = 'emails/plain/gift-card-email.php';
        
        // Corriger le chemin du template_base pour pointer vers la racine du plugin
        $this->template_base  = trailingslashit(plugin_dir_path(dirname(dirname(dirname(__FILE__))))) . 'templates/';

        error_log('Plugin base path: ' . plugin_dir_path(dirname(dirname(dirname(__FILE__)))));
        error_log('Template base path: ' . $this->template_base);

        // Initialize gift_card property
        $this->gift_card = null;

        // Triggers for this email
        add_action( 'wc_gift_card_email_notification', [ $this, 'trigger' ], 10, 1 );

        // Call parent constructor
        parent::__construct();

        // Initialize recipient and enable email by default
        $this->recipient = '';
        $this->enabled   = 'yes';
    }


    /**
     * Triggers the email notification when a gift card is issued.
     *
     * @param object $gift_card The gift card data object.
     * 
     * @since  1.0.0
     * @return void
     */
    public function trigger($gift_card) {
    error_log('Début de trigger() - GiftCardEmail');

    if (!$gift_card) {
        error_log('Gift card object est null');
        return;
    }

    error_log('Gift card object OK - Email: ' . $gift_card->recipient_email);

    $this->gift_card = $gift_card;
    $this->recipient = $gift_card->recipient_email;

    $this->placeholders['{sender_name}'] = $gift_card->sender_name;
    $this->placeholders['{gift_card_amount}'] = wc_price($gift_card->balance);

    if (!$this->is_enabled()) {
        error_log('Email est désactivé');
        return;
    }

    if (!$this->get_recipient()) {
        error_log('Pas de destinataire');
        return;
    }

    error_log('Vérification des paramètres OK');

    // Changer la valeur par défaut à 'no'
    $attach_pdf = get_option('gift_card_attach_pdf', 'no');
    error_log('Option PDF attachment: ' . $attach_pdf);

    try {
        // Préparer le contenu de l'email
        $subject = $this->get_subject();
        $content = $this->get_content();
        $headers = $this->get_headers();
        $attachments = [];

        if ($attach_pdf === 'yes') {
            error_log('Tentative de génération du PDF');
            
            if (!class_exists('GiftCards\WooCommerce\PDF\Gift_Card_PDF_Generator')) {
                throw new \Exception('PDF Generator class not found');
            }

            $pdf_generator = new \GiftCards\WooCommerce\PDF\Gift_Card_PDF_Generator($gift_card);
            $pdf_content = $pdf_generator->generate();
            
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['path'] . '/gift-cards';
            if (!file_exists($pdf_dir)) {
                wp_mkdir_p($pdf_dir);
            }
            
            $pdf_path = $pdf_dir . '/gift-card-' . $gift_card->code . '.pdf';
            file_put_contents($pdf_path, $pdf_content);
            $attachments[] = $pdf_path;
            
            error_log('PDF généré avec succès: ' . $pdf_path);
        }

        // Envoyer l'email
        error_log('Tentative d\'envoi de l\'email à ' . $this->get_recipient());
        
        $sent = wp_mail(
            $this->get_recipient(),
            $subject,
            $content,
            $headers,
            $attachments
        );

        error_log('Résultat de l\'envoi: ' . ($sent ? 'succès' : 'échec'));

        // Nettoyer le PDF si nécessaire
        if ($attach_pdf === 'yes' && !empty($pdf_path) && file_exists($pdf_path)) {
            unlink($pdf_path);
            error_log('PDF temporaire supprimé');
        }

    } catch (Exception $e) {
        error_log('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        
        // Tentative d'envoi sans PDF en cas d'erreur
        $sent = wp_mail(
            $this->get_recipient(),
            $subject,
            $content,
            $headers,
            []
        );
    }

    error_log('Fin de trigger() - GiftCardEmail');
    return $sent;
}

    /**
     * Gets the HTML content for the email.
     *
     * @since  1.0.0
     * @return string The email content in HTML format.
     */
    public function get_content_html() {
        $template_path = $this->template_base . $this->template_html;
        error_log('Template path: ' . $template_path);
        
        if (!file_exists($template_path)) {
            error_log('Template file does not exist: ' . $template_path);
            return 'Template file not found.';
        }
        
        return wc_get_template_html(
            $this->template_html,
            [
                'gift_card'          => $this->gift_card,
                'email_heading'      => $this->get_heading(),
                'custom_email_image' => get_option( 'gift_card_custom_email_image', '' ),
                'custom_email_text'  => get_option( 'gift_card_custom_email_text', '' ),
                'email'              => $this,
            ],
            '',
            $this->template_base
        );
    }

    /**
     * Gets the plain text content for the email.
     *
     * @since  1.0.0
     * @return string The email content in plain text format.
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'gift_card'          => $this->gift_card,
                'email_heading'      => $this->get_heading(),
                'custom_email_image' => get_option( 'gift_card_custom_email_image', '' ),
                'custom_email_text'  => get_option( 'gift_card_custom_email_text', '' ),
                'email'              => $this,
            ],
            '',
            $this->template_base
        );
    }
}
