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

        //error_log('Plugin base path: ' . plugin_dir_path(dirname(dirname(dirname(__FILE__)))));
        //error_log('Template base path: ' . $this->template_base);

        // Initialize gift_card property
        $this->gift_card = null;

        // Triggers for this email
        add_action( 'wc_gift_card_email_notification', [ $this, 'trigger' ], 10, 1 );
        add_action('send_gift_card_email_with_pdf', array($this, 'send_email_with_pdf'), 10, 1);

        // Call parent constructor
        parent::__construct();

        // Initialize recipient and enable email by default
        $this->recipient = '';
        $this->enabled   = 'yes';
    }

    public function get_headers() {
        $site_name = get_bloginfo('name');
        $headers = parent::get_headers();
        $headers .= "\r\nFrom: {$site_name} <" . get_bloginfo('admin_email') . ">";
        return $headers;
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

    if (!$this->is_enabled() || !$this->get_recipient()) {
        error_log('Email est désactivé ou pas de destinataire');
        return;
    }

    // Préparer le contenu de l'email
    $subject = $this->get_subject();
    $content = $this->get_content();
    $headers = $this->get_headers();
    
    // Changer la valeur par défaut à 'no'
    $attach_pdf = get_option('gift_card_attach_pdf', 'no');
    error_log('Option PDF attachment: ' . $attach_pdf);

    if ($attach_pdf === 'yes') {
        error_log('Planification de l\'envoi différé avec PDF');
        try {
            // Envoi immédiat plutôt que différé
            $this->send_email_with_pdf([
                [
                    'recipient' => $this->get_recipient(),
                    'subject' => $subject,
                    'content' => $content,
                    'headers' => $headers,
                    'gift_card' => $gift_card
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'envoi avec PDF: ' . $e->getMessage());
            // Tentative d'envoi sans PDF en cas d'erreur
            wp_mail(
                $this->get_recipient(),
                $subject,
                $content,
                $headers,
                []
            );
        }
    } else {
        error_log('Envoi immédiat sans PDF');
        $sent = wp_mail(
            $this->get_recipient(),
            $subject,
            $content,
            $headers,
            []
        );
        error_log('Email sans PDF envoyé: ' . ($sent ? 'succès' : 'échec'));
    }

    error_log('Fin de trigger() - GiftCardEmail');
    return true;
}


    public function send_email_with_pdf($args) {
    error_log('Début de send_email_with_pdf');
    
    try {
        // Les arguments sont dans le premier élément du tableau
        $args = $args[0];
        
        // Vérifier que tous les arguments nécessaires sont présents
        if (!isset($args['recipient']) || !isset($args['subject']) || 
            !isset($args['content']) || !isset($args['headers']) || 
            !isset($args['gift_card'])) {
            throw new \Exception('Missing required arguments for email');
        }

        // Utiliser le bon namespace et le bon chemin selon PSR-4
        if (!class_exists('GiftCards\WooCommerce\PDF\GiftCardPDFGenerator')) {
            // Construire le chemin correct vers le fichier
            $pdf_generator_path = plugin_dir_path(dirname(dirname(dirname(__FILE__)))) 
                               . 'src/WooCommerce/PDF/GiftCardPDFGenerator.php';
            
            error_log('Tentative de chargement du fichier PDF Generator: ' . $pdf_generator_path);
            
            if (!file_exists($pdf_generator_path)) {
                throw new \Exception('PDF Generator file not found at: ' . $pdf_generator_path);
            }
            
            require_once $pdf_generator_path;
        }

        $pdf_generator = new \GiftCards\WooCommerce\PDF\GiftCardPDFGenerator($args['gift_card']);
        $pdf_content = $pdf_generator->generate();
        
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['path'] . '/gift-cards';
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $pdf_path = $pdf_dir . '/gift-card-' . $args['gift_card']->code . '.pdf';
        file_put_contents($pdf_path, $pdf_content);
        
        $attachments = array($pdf_path);
        
        error_log('Envoi de l\'email avec PDF à : ' . $args['recipient']);
        error_log('Sujet : ' . $args['subject']);
        error_log('Pièce jointe : ' . $pdf_path);
        
        $sent = wp_mail(
            $args['recipient'],
            $args['subject'],
            $args['content'],
            $args['headers'],
            $attachments
        );
        
        error_log('Email avec PDF envoyé: ' . ($sent ? 'succès' : 'échec'));
        
        // Nettoyer le fichier PDF
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
            error_log('PDF temporaire supprimé: ' . $pdf_path);
        }
        
    } catch (\Exception $e) {
        error_log('Erreur lors de l\'envoi de l\'email avec PDF: ' . $e->getMessage());
        
        // Envoyer sans PDF en cas d'erreur
        if (isset($args['recipient']) && isset($args['subject']) && 
            isset($args['content']) && isset($args['headers'])) {
            wp_mail(
                $args['recipient'],
                $args['subject'],
                $args['content'],
                $args['headers'],
                []
            );
            error_log('Email envoyé sans PDF suite à une erreur');
        } else {
            error_log('Impossible d\'envoyer l\'email même sans PDF - arguments manquants');
        }
    }
    
    error_log('Fin de send_email_with_pdf');
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
