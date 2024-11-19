<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-very-simple-license-manager
 * @since      1.0.1
 *
 * @package    Smarty_Very_Simple_License_Manager
 * @subpackage Smarty_Very_Simple_License_Manager/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
?>

<div class="wrap">
    <h1><?php esc_html_e('License Manager | Settings', 'smarty-very-simple-license-manager'); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('smarty_vslm_settings'); ?>
        <?php do_settings_sections('smarty_vslm_settings'); ?>
        <!-- Warning message -->
        <div style="background-color: #fff3cd; border: 1px solid #e5d4a2; border-left: 4px solid #e5d4a2; border-radius: 3px; padding: 10px; margin-top: 20px;">
            <p><?php esc_html(_e('The Consumer Key and Consumer Secret keys are used to authenticate API requests for the License Manager.</p><p>These keys should be generated once and not changed thereafter.</p><p>Altering them could disrupt existing API integrations that rely on these keys for secure access.', 'smarty-very-simple-license-manager')); ?></p>
        </div>
        <?php submit_button(); ?>
    </form>
</div>