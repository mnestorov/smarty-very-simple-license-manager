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
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <h2 class="nav-tab-wrapper">
		<?php foreach ($tabs as $tab_key => $tab_caption) : ?>
			<?php $active = $current_tab == $tab_key ? 'nav-tab-active' : ''; ?>
			<a class="nav-tab <?php echo $active; ?>" href="?page=smarty-vslm-settings&tab=<?php echo $tab_key; ?>">
				<?php echo $tab_caption; ?>
			</a>
		<?php endforeach; ?>
	</h2>
    <form method="post" action="options.php">
        <?php if ($current_tab == 'general') : ?>
			<?php settings_fields('smarty_vslm_options_general'); ?>
			<?php do_settings_sections('smarty_vslm_options_general'); ?>
            <!-- Warning message -->
            <div class="smarty-vslm-warning-msg">
                <h3 class="warning"><span class="dashicons dashicons-warning"></span>&nbsp;<?php esc_html(_e('WARNING', 'smarty-very-simple-license-manager')); ?></h3>
                <p><?php esc_html(_e('The Consumer Key and Consumer Secret keys are used to authenticate API requests for the License Manager.</p><p>These keys should be generated once and not changed thereafter.</p><p>Altering them could disrupt existing API integrations that rely on these keys for secure access.', 'smarty-very-simple-license-manager')); ?></p>
            </div>
		<?php elseif ($current_tab == 'pdf-settings') : ?>	
			<?php settings_fields('smarty_vslm_options_pdf_generator'); ?>
			<?php do_settings_sections('smarty_vslm_options_pdf_generator'); ?>
		<?php elseif ($current_tab == 'activity-logging') : ?>
			<?php settings_fields('smarty_vslm_options_activity_logging'); ?>
			<?php do_settings_sections('smarty_vslm_options_activity_logging'); ?>
		<?php endif; ?>
        <?php submit_button(); ?>
    </form>
</div>