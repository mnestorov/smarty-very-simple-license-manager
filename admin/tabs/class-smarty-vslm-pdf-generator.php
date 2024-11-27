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
        register_setting('smarty_vslm_options_pdf_generator', 'smarty_vslm_pdf_text');
        register_setting('smarty_vslm_options_pdf_generator', 'smarty_vslm_pdf_copyright');
        register_setting('smarty_vslm_options_pdf_generator', 'smarty_vslm_pdf_contact_email');
        register_setting('smarty_vslm_options_pdf_generator', 'smarty_vslm_pdf_contact_phone');


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

        // Add Text field
        add_settings_field(
            'smarty_vslm_pdf_text',
            __('Text', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_pdf_text_cb'),
            'smarty_vslm_options_pdf_generator',
            'smarty_vslm_section_pdf_generator'
        );

        // Add Copyright Text field
        add_settings_field(
            'smarty_vslm_pdf_copyright',
            __('Copyright', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_pdf_copyright_cb'),
            'smarty_vslm_options_pdf_generator',
            'smarty_vslm_section_pdf_generator'
        );

        // Add Contact Email field
        add_settings_field(
            'smarty_vslm_pdf_contact_email',
            __('Support Email', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_pdf_contact_email_cb'),
            'smarty_vslm_options_pdf_generator',
            'smarty_vslm_section_pdf_generator'
        );

        // Add Contact Phone field
        add_settings_field(
            'smarty_vslm_pdf_contact_phone',
            __('Support Phone', 'smarty-very-simple-license-manager'),
            array($this, 'vslm_pdf_contact_phone_cb'),
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
     * Callback for the Text field.
     * 
     * @since    1.0.2
     */
    public function vslm_pdf_text_cb() {
        $pdf_text = get_option('smarty_vslm_pdf_text', '');
        echo '<textarea id="smarty_vslm_pdf_text" name="smarty_vslm_pdf_text" rows="4" cols="50">' . esc_textarea($pdf_text) . '</textarea>';
        echo '<p class="description">' . __('Enter the text to display in the PDF.', 'smarty-very-simple-license-manager') . '</p>';
    }

    /**
     * Callback for the Copyright field.
     * 
     * @since    1.0.2
     */
    public function vslm_pdf_copyright_cb() {
        $copyright_text = get_option('smarty_vslm_pdf_copyright', '');
        echo '<textarea id="smarty_vslm_pdf_copyright" name="smarty_vslm_pdf_copyright" rows="4" cols="50">' . esc_textarea($copyright_text) . '</textarea>';
        echo '<p class="description">' . __('Enter the copyright text to display in the PDF.', 'smarty-very-simple-license-manager') . '</p>';
    }

    /**
     * Callback for the Contact Email field.
     * 
     * @since    1.0.2
     */
    public function vslm_pdf_contact_email_cb() {
        $contact_email = get_option('smarty_vslm_pdf_contact_email', '');
        echo '<input type="email" id="smarty_vslm_pdf_contact_email" name="smarty_vslm_pdf_contact_email" value="' . esc_attr($contact_email) . '" />';
        echo '<p class="description">' . __('Enter the support email to display in the PDF.', 'smarty-very-simple-license-manager') . '</p>';
    }

    /**
     * Callback for the Contact Phone field.
     * 
     * @since    1.0.2
     */
    public function vslm_pdf_contact_phone_cb() {
        $contact_phone = get_option('smarty_vslm_pdf_contact_phone', '');
        echo '<input type="text" id="smarty_vslm_pdf_contact_phone" name="smarty_vslm_pdf_contact_phone" value="' . esc_attr($contact_phone) . '" />';
        echo '<p class="description">' . __('Enter the support phone number to display in the PDF.', 'smarty-very-simple-license-manager') . '</p>';
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

        // Log the PDF generation activity using a new instance of Smarty_Vslm_Activity_Logging
        if (class_exists('Smarty_Vslm_Activity_Logging')) {
            $activity_logger = new Smarty_Vslm_Activity_Logging();
            $activity_logger->vslm_add_activity_log('PDF generated for License #' . $license_id);
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
            //_vslm_write_logs("License ID is empty or null.");
            wp_die(__('Invalid license ID: The ID is empty.', 'smarty-very-simple-license-manager'));
        }

        $post_type = get_post_type($license_id);

        if (!$license_id || get_post_type($license_id) !== 'vslm-licenses') {
            //_vslm_write_logs("Invalid License ID: {$license_id}");
            wp_die(__('Invalid license ID.', 'smarty-very-simple-license-manager'));
        }
        //_vslm_write_logs("Generating PDF for License ID: {$license_id}");

        // Debug the data retrieval
        $debug_log = [];
    
        // Fetch license details
        $product_terms = get_the_terms($license_id, 'product');
        $product_name = !empty($product_terms) && !is_wp_error($product_terms) ? $product_terms[0]->name : 'N/A';
        $license_key = get_post_meta($license_id, '_license_key', true);
        $multi_domain = get_post_meta($license_id, '_multi_domain', true) === '1' ? 'Yes' : 'No';
        $client_name = get_post_meta($license_id, '_client_name', true);
        $client_email = get_post_meta($license_id, '_client_email', true);
        $company_name = get_post_meta($license_id, '_company_name', true);
        $company_address = get_post_meta($license_id, '_company_address', true);
        $vat = get_post_meta($license_id, '_vat', true);
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
    
        //_vslm_write_logs(print_r($debug_log, true));
    
        // Get custom PDF title from settings
        $pdf_title = get_option('smarty_vslm_pdf_title', 'License Details');
        $pdf_text = get_option('smarty_vslm_pdf_text', '');
        $copyright_text = get_option('smarty_vslm_pdf_copyright', '');
        $contact_email = get_option('smarty_vslm_pdf_contact_email', '');
        $contact_phone = get_option('smarty_vslm_pdf_contact_phone', '');

        // Load external CSS
        $css_file_path = plugin_dir_path(__FILE__) . '../css/smarty-vslm-pdf.css';
        $css = file_exists($css_file_path) ? file_get_contents($css_file_path) : '';
    
        // Prepare HTML for PDF
        $html = '
            <style>' . $css . '</style>
            <div class="header">
                <div> #10000' . esc_html($license_id) . '</div>
                <h2>' . esc_html($pdf_title) . '</h2>
            </div>
            <div class="license-info">
                <table class="table">
                    <thead>
                        <tr><th colspan="2">' .  __('License Details', 'smarty-very-simple-license-manager') . '</th></tr>
                    </thead>
                    <tbody>
                        <tr><th>' . __('Product Name', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($product_name) . '</td></tr>
                        <tr><th>' . __('License Key', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($license_key) . '</td></tr>
                        <tr><th>' . __('Multi-Domain Usage', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($multi_domain) . '</td></tr>
                        <tr><th>' . __('Purchase Date', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($purchase_date) . '</td></tr>
                        <tr><th>' . __('Expiration Date', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($expiration_date) . '</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="client-info">
                <table class="table">
                    <thead>
                        <tr><th colspan="2">' .  __('Client Details', 'smarty-very-simple-license-manager') . '</th></tr>
                    </thead>
                    <tbody>
                        <tr><th>' . __('Client Name', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($client_name) . '</td></tr>
                        <tr><th>' . __('Client Email', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($client_email) . '</td></tr>
                        <tr><th>' . __('Company Name', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($company_name) . '</td></tr>
                        <tr><th>' . __('Company Address', 'smarty-very-simple-license-manager') . '</th><td>' . nl2br(esc_html($company_address)) . '</td></tr>
                        <tr><th>' . __('VAT Number', 'smarty-very-simple-license-manager') . '</th><td>' . esc_html($vat) . '</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pdf-text">
                <p>' . nl2br(esc_html($pdf_text)) . '</p>
            </div>
            <div class="contact-info">
                <hr>
                <table class="contact-table">
                    <tr>
                        <td class="left"><strong>' . __('Phone:', 'smarty-very-simple-license-manager') . '</strong> ' . esc_html($contact_phone) . '</td>
                        <td class="right"><strong>' . __('Email:', 'smarty-very-simple-license-manager') . '</strong> ' . esc_html($contact_email) . '</td>
                    </tr>
                </table>
                <hr>
            </div>
            <div class="pdf-copyright">
                <p>' . nl2br(esc_html($copyright_text)) . '</p>
            </div>';
    
        // Initialize Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        // Output the PDF
        $dompdf->stream('smartystudio-license-details-10000' . $license_id . '.pdf', array('Attachment' => true));
        exit;
    }    
}
