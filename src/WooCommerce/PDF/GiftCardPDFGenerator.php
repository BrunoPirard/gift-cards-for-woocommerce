<?php

namespace GiftCards\WooCommerce\PDF;

if (!defined('ABSPATH')) {
    exit;
}

use TCPDF;

/*// Vérifier si TCPDF est déjà chargé
if (!class_exists('TCPDF')) {
    // Essayer d'abord l'autoloader de Composer
    $autoload_path = $plugin_root . '/vendor/autoload.php';
    
    if (file_exists($autoload_path)) {
        require_once $autoload_path;
    } else {
        error_log('Autoloader not found at: ' . $autoload_path);
        throw new \Exception('Composer autoloader not found. Please run composer install.');
    }
}

// Vérifier que TCPDF est bien chargé
if (!class_exists('TCPDF')) {
    error_log('TCPDF class not found after loading autoloader');
    throw new \Exception('TCPDF class not available');
}*/

class GiftCardPDFGenerator {
    private $gift_card;
    
    public function __construct($gift_card) {
        $this->debug_paths();
        $this->gift_card = $gift_card;
    }
    
    /**
     * Génère le PDF de la carte-cadeau.
     *
     * @return string Le PDF généré.
     *
     * @throws \Exception Si une erreur survient pendant la génération du PDF.
     */
    public function generate() {
    error_log('Début de la génération du PDF');
    try {
        if (!class_exists('TCPDF')) {
            error_log('La classe TCPDF n\'est pas disponible');
            throw new \Exception('TCPDF class not available');
        }

        error_log('Création d\'une nouvelle instance de TCPDF');
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Définir les informations du document
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Your Store Name');
            $pdf->SetTitle('Gift Card #' . $this->gift_card->code);
        
        // Supprimer les en-têtes et pieds de page par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Définir les marges
        $pdf->SetMargins(15, 15, 15);
        
        // Ajouter une nouvelle page
        $pdf->AddPage();
        
        // Ajouter le logo de votre boutique (optionnel)
        $logo = get_option('gift_card_custom_email_image');
        if ($logo) {
            $pdf->Image($logo, 15, 15, 50);
        }
        
        // Ajouter le contenu
        $html = $this->get_gift_card_html();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Générer le PDF
        return $pdf->Output('gift-card-' . $this->gift_card->code . '.pdf', 'S');
    } catch (\Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function get_gift_card_html() {
        ob_start();
        ?>
        <style>
            .gift-card {
                font-family: helvetica;
                color: #333;
                margin-top: 50px;
            }
            .gift-card-code {
                font-size: 24px;
                color: #000;
                margin: 20px 0;
            }
            .gift-card-amount {
                font-size: 36px;
                color: #4CAF50;
                margin: 20px 0;
            }
            .gift-card-message {
                font-style: italic;
                margin: 20px 0;
            }
        </style>
        
        <div class="gift-card">
            <h1><?php echo esc_html__('Gift Card', 'gift-cards-for-woocommerce'); ?></h1>
            
            <div class="gift-card-amount">
                <?php echo wc_price($this->gift_card->balance); ?>
            </div>
            
            <div class="gift-card-code">
                <?php echo esc_html($this->gift_card->code); ?>
            </div>
            
            <?php if (!empty($this->gift_card->message)) : ?>
                <div class="gift-card-message">
                    "<?php echo esc_html($this->gift_card->message); ?>"
                </div>
            <?php endif; ?>
            
            <div class="gift-card-details">
                <p><strong><?php echo esc_html__('From:', 'gift-cards-for-woocommerce'); ?></strong> <?php echo esc_html($this->gift_card->sender_name); ?></p>
                <p><strong><?php echo esc_html__('To:', 'gift-cards-for-woocommerce'); ?></strong> <?php echo esc_html($this->gift_card->recipient_email); ?></p>
                <?php if (!empty($this->gift_card->expiration_date)) : ?>
                    <p><strong><?php echo esc_html__('Expires:', 'gift-cards-for-woocommerce'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($this->gift_card->expiration_date)); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function debug_paths() {
        $paths = [
            'Current File' => __FILE__,
            'Plugin Root' => dirname(dirname(__DIR__)),
            'Autoloader Path' => dirname(dirname(__DIR__)) . '/vendor/autoload.php',
            'TCPDF Direct Path' => dirname(dirname(__DIR__)) . '/vendor/tecnickcom/tcpdf/tcpdf.php'
        ];
        
        foreach ($paths as $label => $path) {
            error_log(sprintf(
                '%s: %s (Exists: %s)',
                $label,
                $path,
                file_exists($path) ? 'Yes' : 'No'
            ));
        }
    }
}
