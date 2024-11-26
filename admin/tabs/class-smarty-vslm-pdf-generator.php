<?php
/**
 * The PDF Generator-specific functionality of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.2
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/admin/tabs
 * @author     Smarty Studio | Martin Nestorov
 */

use Dompdf\Dompdf;
use Dompdf\Options;

class Smarty_Vslm_Pdf_Generator {

    /**
     * Register settings, sections, and fields for PDF title.
     * 
     * @since    1.0.2
     */
    public function vslm_pdf_settings_init() {
        // Register settings for PDF title
        register_setting('smarty_vslm_options_pdf_generator', 'smarty_vslm_pdf_title');

        // Add PDF Settings section
        add_settings_section(
            'smarty_vslm_section_pdf_generator',
            __('PDF Settings', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_section_pdf_cb'),
            'smarty_vslm_options_pdf_generator'
        );

        // Add PDF title field
        add_settings_field(
            'smarty_vslm_pdf_title',
            __('PDF Title', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_pdf_title_cb'),
            'smarty_vslm_options_pdf_generator',
            'smarty_vslm_section_pdf_generator'
        );
    }

    /**
     * Callback for the PDF Settings section.
     * 
     * @since    1.0.2
     */
    public function vslm_section_pdf_cb() {
        echo '<p>' . __('Configure the default settings for generated PDF files.', 'smarty-very-simple-license-manager') . '</p>';
    }

    /**
     * Callback to display and handle the PDF title field.
     * 
     * @since    1.0.2
     */
    public function vslm_pdf_title_cb() {
        $pdf_title = get_option('smarty_vslm_pdf_title', 'License Details');
        echo '<input type="text" id="smarty_vslm_pdf_title" name="smarty_vslm_pdf_title" value="' . esc_attr($pdf_title) . '" />';
        echo '<p class="description">' . __('Set the title to be displayed on the generated PDFs.', 'smarty-very-simple-license-manager') . '</p>';
    }

    /**
     * Generate and stream a PDF with license details.
     *
     * @since    1.0.2
     * @param int $license_id The ID of the license post.
     */
    public static function vslm_generate_license_pdf() {
        error_log('Incoming GET data: ' . print_r($_GET, true));

        // Validate and fetch the license ID
        if (!isset($_GET['license_id']) || empty($_GET['license_id'])) {
            error_log('License ID is empty or null.');
            wp_die(__('License ID is required.', 'smarty-very-simple-license-manager'));
        }

        $license_id = intval($_GET['license_id']);
        error_log('License ID received: ' . $license_id);

        // Check if the license exists and is of correct post type
        if (get_post_type($license_id) !== 'vslm-licenses') {
            error_log('Invalid License ID: ' . $license_id);
            wp_die(__('Invalid License ID.', 'smarty-very-simple-license-manager'));
        }

        // Proceed with actual PDF generation
        self::vslm_license_pdf($license_id);
    }

    /**
     * Generate and stream a PDF with license details.
     *
     * @since    1.0.2
     * @param int $license_id The ID of the license post.
     */
    public static function vslm_license_pdf($license_id) {
        if (!$license_id) {
            _vslm_write_logs("License ID is empty or null.");
            wp_die(__('Invalid license ID: ID is empty.', 'smarty-very-simple-license-manager'));
        }

        $post_type = get_post_type($license_id);

        if (!$license_id || get_post_type($license_id) !== 'vslm-licenses') {
            _vslm_write_logs("Invalid License ID: {$license_id}");
            wp_die(__('Invalid license ID.', 'smarty-very-simple-license-manager'));
        }
        _vslm_write_logs("Generating PDF for License ID: {$license_id}");

        // Debug the data retrieval
        $debug_log = [];
    
        // Fetch license details
        $product_terms = get_the_terms($license_id, 'product');
        $product_name = !empty($product_terms) && !is_wp_error($product_terms) ? $product_terms[0]->name : 'N/A';
        $license_key = get_post_meta($license_id, '_license_key', true);
        $multi_domain = get_post_meta($license_id, '_multi_domain', true) === '1' ? 'Yes' : 'No';
        $client_name = get_post_meta($license_id, '_client_name', true);
        $client_email = get_post_meta($license_id, '_client_email', true);
        $purchase_date = get_post_meta($license_id, '_purchase_date', true);
        $expiration_date = get_post_meta($license_id, '_expiration_date', true);
    
        // Log data for debugging
        $debug_log['product_terms'] = $product_terms;
        $debug_log['product_name'] = $product_name;
        $debug_log['license_key'] = $license_key;
        $debug_log['multi_domain'] = $multi_domain;
        $debug_log['client_name'] = $client_name;
        $debug_log['client_email'] = $client_email;
        $debug_log['purchase_date'] = $purchase_date;
        $debug_log['expiration_date'] = $expiration_date;
    
        _vslm_write_logs(print_r($debug_log, true));
    
        // Get custom PDF title from settings
        $pdf_title = get_option('smarty_vslm_pdf_title', 'License Details');
        $site_name = get_bloginfo('name');
    
        // Prepare HTML for PDF
        $html = '
            <style>
                body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; }
                .header { text-align: center; margin-bottom: 20px; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f4f4f4; }
            </style>
            <div class="header">
                <h2>' . esc_html($site_name) . '</h2>
                <h4>' . esc_html($pdf_title) . '</h4>
            </div>
            <table class="table">
                <tr><th>' . __('Product Name', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($product_name) . '</td></tr>
                <tr><th>' . __('License Key', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($license_key) . '</td></tr>
                <tr><th>' . __('Multi-Domain Usage', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($multi_domain) . '</td></tr>
                <tr><th>' . __('Client Name', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($client_name) . '</td></tr>
                <tr><th>' . __('Client Email', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($client_email) . '</td></tr>
                <tr><th>' . __('Purchase Date', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($purchase_date) . '</td></tr>
                <tr><th>' . __('Expiration Date', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($expiration_date) . '</td></tr>
            </table>';
    
        // Initialize Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        // Output the PDF
        $dompdf->stream('license-details-' . $license_id . '.pdf', array('Attachment' => true));
        exit;
    }    
}
