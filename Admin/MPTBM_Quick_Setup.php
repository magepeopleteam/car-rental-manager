<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.
if (!class_exists('MPTBM_Quick_Setup')) {
	class MPTBM_Quick_Setup
	{
		public function __construct()
		{
			// Enqueue admin script with inline JavaScript
			add_action('admin_enqueue_scripts', function () {
				wp_enqueue_script('mptbm-admin-dloader-script', plugin_dir_url(__FILE__) . '../assets/admin/mptbm_admin_dLoader.js', array('jquery'), time(), true);
			});
			add_action('admin_menu', array($this, 'quick_setup_menu'));
		}
		public function quick_setup_menu()
		{

			$status = MPCRM_Global_Function::check_woocommerce();
			if ($status == 1) {
				add_submenu_page('edit.php?post_type=mptbm_rent', esc_html__('Quick Setup', 'car-rental-manager'), '<span style="color:#10dd10">' . esc_html__('Quick Setup', 'car-rental-manager') . '</span>', 'manage_options', 'mptbm_quick_setup', array($this, 'quick_setup'));
				add_submenu_page('mptbm_rent', esc_html__('Quick Setup', 'car-rental-manager'), '<span style="color:#10dd10">' . esc_html__('Quick Setup', 'car-rental-manager') . '</span>', 'manage_options', 'mptbm_quick_setup', array($this, 'quick_setup'));
			} else {
				add_menu_page(esc_html__('Car', 'car-rental-manager'), esc_html__('Car', 'car-rental-manager'), 'manage_options', 'mptbm_rent', array($this, 'quick_setup'), 'dashicons-admin-site-alt2', 6);
				add_submenu_page('mptbm_rent', esc_html__('Quick Setup', 'car-rental-manager'), '<span style="color:#10dd17">' . esc_html__('Quick Setup', 'car-rental-manager') . '</span>', 'manage_options', 'mptbm_quick_setup', array($this, 'quick_setup'));
			}
		}
		public function quick_setup()
		{

			// Check WooCommerce status
			$status = MPCRM_Global_Function::check_woocommerce();

			// Generate a nonce field
			$nonce = wp_create_nonce('mptbm_quick_setup_nonce');

			// Check WooCommerce status
			$status = MPCRM_Global_Function::check_woocommerce();

			// Generate a nonce
			$nonce = wp_create_nonce('mptbm_quick_setup_nonce');

?>
			<form method="post" action="">
				<?php wp_nonce_field('mptbm_quick_setup_nonce', 'mptbm_quick_setup_nonce'); ?>
			</form>
			<?php

			// Handle form submission
			if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quick_setup'])) {
				if (!isset($_POST['mptbm_quick_setup_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mptbm_quick_setup_nonce'])), 'mptbm_quick_setup_nonce')) {
					return;
				}
			}

			// Only log specific fields that are relevant for debugging
			if (isset($_POST['submit_quick_setup'])) {
				$relevant_fields = array(
					'mptbm_quick_setup_nonce' => isset($_POST['mptbm_quick_setup_nonce']) ? 'set' : 'not set',
					'active_woo_btn' => isset($_POST['active_woo_btn']) ? 'set' : 'not set',
					'install_and_active_woo_btn' => isset($_POST['install_and_active_woo_btn']) ? 'set' : 'not set',
					'finish_quick_setup' => isset($_POST['finish_quick_setup']) ? 'set' : 'not set'
				);
			}

			$status = MPCRM_Global_Function::check_woocommerce();

			if (isset($_POST['active_woo_btn'])) {

				// Properly unslash and sanitize input
				$active_woo_btn = isset($_POST['active_woo_btn']) ? sanitize_text_field(wp_unslash($_POST['active_woo_btn'])) : '';




				// Activate WooCommerce Plugin
				activate_plugin('woocommerce/woocommerce.php');


				MPTBM_Plugin::on_activation_page_create();



				// Add inline JavaScript using standard concatenation
				$inline_script = '(function($) {';
				$inline_script .= '"use strict";';
				$inline_script .= '$(document).ready(function() {';
				$inline_script .= 'let mptbm_admin_location = window.location.href;';
				$inline_script .= 'mptbm_admin_location = mptbm_admin_location.replace("admin.php?post_type=mptbm_rent&page=mptbm_quick_setup", "edit.php?post_type=mptbm_rent&page=mptbm_quick_setup");';
				$inline_script .= 'mptbm_admin_location = mptbm_admin_location.replace("admin.php?page=mptbm_rent", "edit.php?post_type=mptbm_rent&page=mptbm_quick_setup");';
				$inline_script .= 'mptbm_admin_location = mptbm_admin_location.replace("admin.php?page=mptbm_quick_setup", "edit.php?post_type=mptbm_rent&page=mptbm_quick_setup");';
				$inline_script .= 'window.location.href = mptbm_admin_location;';
				$inline_script .= '});';
				$inline_script .= '})(jQuery);';

				wp_add_inline_script('mptbm-admin-dloader-script', $inline_script);
			}

			if (isset($_POST['install_and_active_woo_btn'])) {
			?>
				<div style="display:none">
					<?php
					if (is_admin()) { 
						if (!function_exists('plugins_api')) {
							require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
						}
						if (!function_exists('WP_Filesystem')) {
							require_once ABSPATH . 'wp-admin/includes/file.php';
						}
						if (!function_exists('wp_mkdir_p')) {
							require_once ABSPATH . 'wp-admin/includes/misc.php';
						}
						if (!class_exists('WP_Upgrader')) {
							require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
						}
					}
					$plugin = 'woocommerce';
					$api = plugins_api('plugin_information', array(
						'slug' => $plugin,
						'fields' => array(
							'short_description' => false,
							'sections' => false,
							'requires' => false,
							'rating' => false,
							'ratings' => false,
							'downloaded' => false,
							'last_updated' => false,
							'added' => false,
							'tags' => false,
							'compatibility' => false,
							'homepage' => false,
							'donate_link' => false,
						),
					));
					$title = 'title';
					$url = 'url';
					$nonce = 'nonce';
					$woocommerce_plugin = new Plugin_Upgrader(new Plugin_Installer_Skin(compact('title', 'url', 'nonce', 'plugin', 'api')));
					$woocommerce_plugin->install($api->download_link);
					activate_plugin('woocommerce/woocommerce.php');
					MPTBM_Plugin::on_activation_page_create();
					?>
				</div>

			<?php
				// Inline JavaScript (using concatenation instead of heredoc)
				$quick_setup_script = '(function($) {';
				$quick_setup_script .= '"use strict";';
				$quick_setup_script .= '$(document).ready(function() {';
				$quick_setup_script .= 'let mptbm_admin_location = window.location.href;';
				$quick_setup_script .= 'mptbm_admin_location = mptbm_admin_location.replace("admin.php?post_type=mptbm_rent&page=mptbm_quick_setup", "edit.php?post_type=mptbm_rent&page=mptbm_quick_setup");';
				$quick_setup_script .= 'mptbm_admin_location = mptbm_admin_location.replace("admin.php?page=mptbm_rent", "edit.php?post_type=mptbm_rent&page=mptbm_quick_setup");';
				$quick_setup_script .= 'mptbm_admin_location = mptbm_admin_location.replace("admin.php?page=mptbm_quick_setup", "edit.php?post_type=mptbm_rent&page=mptbm_quick_setup");';
				$quick_setup_script .= 'window.location.href = mptbm_admin_location;';
				$quick_setup_script .= '});';
				$quick_setup_script .= '})(jQuery);';

				wp_add_inline_script('mptbm-admin-dloader-script', $quick_setup_script);
			}
			if (isset($_POST['finish_quick_setup'])) {
				$label = isset($_POST['mptbm_label']) ? sanitize_text_field(wp_unslash($_POST['mptbm_label'])) : 'Car';
				$slug = isset($_POST['mptbm_slug']) ? sanitize_text_field(wp_unslash($_POST['mptbm_slug'])) : 'Car';
				$general_settings_data = get_option('mptbm_general_settings');
				$update_general_settings_arr = [
					'mptbm_rent_label' => $label,
					'mptbm_rent_slug' => $slug
				];
				$new_general_settings_data = is_array($general_settings_data) ? array_replace($general_settings_data, $update_general_settings_arr) : $update_general_settings_arr;
				update_option('mptbm_general_settings', $new_general_settings_data);
				update_option('mptbm_basic_gen_settings', $new_general_settings_data);
				update_option('mptbm_quick_setup_done', 'yes');
				wp_redirect(admin_url('edit.php?post_type=mptbm_rent'));
			}

			wp_nonce_field('mptbm_transportation_quick_setup_nonce', 'mptbm_transportation_quick_setup_nonce');
			?>
			<div class="mpStyle">
				<div class="_dShadow_6_adminLayout">
					<form method="post" action="">
						<div class="mpTabsNext">
							<div class="tabListsNext _max_700_mAuto">
								<div data-tabs-target-next="#mptbm_qs_welcome" class="tabItemNext" data-open-text="1" data-close-text=" " data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
									<h4 class="circleIcon" data-class>
										<span class="mp_zero" data-icon></span>
										<span class="mp_zero" data-text>1</span>
									</h4>
									<h6 class="circleTitle" data-class><?php esc_html_e('Welcome', 'car-rental-manager'); ?></h6>
								</div>
								<div data-tabs-target-next="#mptbm_qs_general" class="tabItemNext" data-open-text="2" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
									<h4 class="circleIcon" data-class>
										<span class="mp_zero" data-icon></span>
										<span class="mp_zero" data-text>2</span>
									</h4>
									<h6 class="circleTitle" data-class><?php esc_html_e('General', 'car-rental-manager'); ?></h6>
								</div>
								<div data-tabs-target-next="#mptbm_qs_done" class="tabItemNext" data-open-text="3" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
									<h4 class="circleIcon" data-class>
										<span class="mp_zero" data-icon></span>
										<span class="mp_zero" data-text>3</span>
									</h4>
									<h6 class="circleTitle" data-class><?php esc_html_e('Done', 'car-rental-manager'); ?></h6>
								</div>
							</div>
							<div class="tabsContentNext _infoLayout_mT">
								<?php
								$this->setup_welcome_content();
								$this->setup_general_content();
								$this->setup_content_done();
								?>
							</div>
							<?php if ($status == 1) { ?>
								<div class="justifyBetween">
									<button type="button" class="_mpBtn_dBR nextTab_prev">
										<span>&longleftarrow;<?php esc_html_e('Previous', 'car-rental-manager'); ?></span>
									</button>
									<div></div>
									<button type="button" class="_themeButton_dBR nextTab_next">
										<span><?php esc_html_e('Next', 'car-rental-manager'); ?>&longrightarrow;</span>
									</button>
								</div>
							<?php } ?>
						</div>
					</form>
				</div>
			</div>
		<?php
		}
		public function setup_welcome_content()
		{
			$status = MPCRM_Global_Function::check_woocommerce();
		?>
			<div data-tabs-next="#mptbm_qs_welcome">
				<h2><?php esc_html_e('Car Booking Manager For Woocommerce Plugin', 'car-rental-manager'); ?></h2>
				<p class="mTB_xs"><?php esc_html_e('Thanks for choosing Car Booking Manager Plugin for WooCommerce for your site, Please go step by step and choose some options to get started.', 'car-rental-manager'); ?></p>
				<div class="_dLayout_mT_alignCenter justifyBetween">
					<h5>
						<?php if ($status == 1) {
							esc_html_e('Woocommerce already installed and activated', 'car-rental-manager');
						} elseif ($status == 0) {
							esc_html_e('Woocommerce need to install and active', 'car-rental-manager');
						} else {
							esc_html_e('Woocommerce already install , please activate it', 'car-rental-manager');
						} ?>
					</h5>
					<?php if ($status == 1) { ?>
						<h5>
							<span class="fas fa-check-circle textSuccess"></span>
						</h5>
					<?php } elseif ($status == 0) { ?>
						<button class="_warningButton_dBR" type="submit" name="install_and_active_woo_btn"><?php esc_html_e('Install & Active Now', 'car-rental-manager'); ?></button>
					<?php } else { ?>
						<button class="_themeButton_dBR" type="submit" name="active_woo_btn"><?php esc_html_e('Active Now', 'car-rental-manager'); ?></button>
					<?php } ?>
				</div>
			</div>
		<?php
		}
		public function setup_general_content()
		{
			$label = MPCRM_Global_Function::mpcrm_get_settings('mptbm_general_settings', 'label', 'Car');
			$slug = MPCRM_Global_Function::mpcrm_get_settings('mptbm_general_settings', 'slug', 'Car');
		?>
			<div data-tabs-next="#mptbm_qs_general">
				<div class="section">
					<h2><?php esc_html_e('General settings', 'car-rental-manager'); ?></h2>
					<p class="mTB_xs"><?php esc_html_e('Choose some general option.', 'car-rental-manager'); ?></p>
					<div class="_dLayout_mT">
						<label class="_fullWidth">
							<span class="min_200"><?php esc_html_e('Car Label:', 'car-rental-manager'); ?></span>
							<input type="text" class="formControl" name="mptbm_label" value='<?php echo esc_attr($label); ?>' />
						</label>
						<i class="info_text">
							<span class="fas fa-info-circle"></span>
							<?php esc_html_e('It will change the Car post type label on the entire plugin.', 'car-rental-manager'); ?>
						</i>
						<div class="divider"></div>
						<label class="_fullWidth">
							<span class="min_200"><?php esc_html_e('Car Slug:', 'car-rental-manager'); ?></span>
							<input type="text" class="formControl" name="mptbm_slug" value='<?php echo esc_attr($slug); ?>' />
						</label>
						<i class="info_text">
							<span class="fas fa-info-circle"></span>
							<?php esc_html_e('It will change the Car slug on the entire plugin. Remember after changing this slug you need to flush permalinks. Just go to Settings->Permalinks hit the Save Settings button', 'car-rental-manager'); ?>
						</i>
					</div>
				</div>
			</div>
		<?php
		}
		public function setup_content_done()
		{
		?>
			<div data-tabs-next="#mptbm_qs_done">
				<h2><?php esc_html_e('Finalize Setup', 'car-rental-manager'); ?></h2>
				<p class="mTB_xs"><?php esc_html_e('You are about to Finish & Save Car Booking Manager For Woocommerce Plugin setup process', 'car-rental-manager'); ?></p>
				<div class="mT allCenter">
					<button type="submit" name="finish_quick_setup" class="_themeButton_dBR"><?php esc_html_e('Finish & Save', 'car-rental-manager'); ?></button>
				</div>
			</div>
<?php
		}
	}
	new MPTBM_Quick_Setup();
}
