<?php
namespace GiftCards\WooCommerce\PDF;

if (!defined('ABSPATH')) {
    exit;
}

use TCPDF;

class GiftCardPDFGenerator {

    private $gift_card;
    
    public function __construct($gift_card) {
        //$this->debug_paths();
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
    //error_log('Début de la génération du PDF');
    try {
        if (!class_exists('TCPDF')) {
            //error_log('La classe TCPDF n\'est pas disponible');
            throw new \Exception('TCPDF class not available');
        }

        //error_log('Création d\'une nouvelle instance de TCPDF');
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
            //error_log('PDF Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Returns the HTML for the gift card PDF.
     *
     * @return string
     */
    private function get_gift_card_html() {
        // Get the site logo
        $logo_url = get_option('gift_card_pdf_logo', get_site_icon_url());
        
        ob_start();
        ?>
        <style>
            .gift-card-container {
                width: 100%;
                max-width: 800px;
                margin: 0 auto;
                padding: 40px;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border-radius: 15px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            }
            
            .gift-card-header {
                text-align: center;
                margin-bottom: 30px;
            }
            
            .gift-card-logo {
                max-width: 200px;
                margin-bottom: 20px;
            }
            
            .gift-card-title {
                font-size: 32px;
                color: #2c3e50;
                margin-bottom: 20px;
                text-transform: uppercase;
                letter-spacing: 2px;
            }
            
            .gift-card-amount {
                font-size: 48px;
                color: #2ecc71;
                font-weight: bold;
                text-align: center;
                margin: 30px 0;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            }
            
            .gift-card-code {
                background: #fff;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                font-family: monospace;
                font-size: 24px;
                letter-spacing: 3px;
                margin: 20px 0;
                border: 2px dashed #3498db;
            }
            
            .gift-card-message {
                background: rgba(255,255,255,0.9);
                padding: 20px;
                border-radius: 8px;
                font-style: italic;
                margin: 20px 0;
                color: #34495e;
            }
            
            .gift-card-details {
                margin-top: 30px;
                padding: 20px;
                background: rgba(255,255,255,0.8);
                border-radius: 8px;
            }
            
            .gift-card-details p {
                margin: 10px 0;
                color: #2c3e50;
            }
            
            .gift-card-footer {
                text-align: center;
                margin-top: 30px;
                font-size: 12px;
                color: #7f8c8d;
            }
        </style>
        
        <div class="gift-card-container">
            <div class="gift-card-header">
                <?php if ($logo_url) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="gift-card-logo">
                <?php endif; ?>
                <h1 class="gift-card-title"><?php echo esc_html__('Gift Card', 'gift-cards-for-woocommerce'); ?></h1>
            </div>
            
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
                <p><strong><?php echo esc_html__('Date:', 'gift-cards-for-woocommerce'); ?></strong> <?php echo date_i18n(get_option('date_format')); ?></p>
            </div>
            
            <div class="gift-card-footer">
                <?php echo esc_html(get_bloginfo('name')); ?> - <?php echo esc_html(get_bloginfo('url')); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /*private function debug_paths() {
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
    }*/
}
