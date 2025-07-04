<?php
	/*
* @Author 		MagePeople Team
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRBM_Guideline')) {
		class MPCRBM_Guideline {
			public function __construct() {
				add_action('admin_menu', array($this, 'guideline_menu'));
			}
			public function guideline_menu() {
				$cpt = MPCRBM_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Guideline', 'car-rental-manager'), '<span>' . esc_html__('Guideline', 'car-rental-manager') . '</span>', 'manage_options', 'mpcrbm_guideline_page', array($this, 'guideline_page'));
			}
			public function guideline_page() {
				$label = MPCRBM_Function::get_name();
				?>
				<div class="wrap"></div>
				<div class="mpcrbm">
					<div class="_dShadow_6_adminLayout">
						<h2 class="textCenter"><?php echo esc_html($label) . '  ' . esc_html__('Shortcode', 'car-rental-manager'); ?></h2>
						<div class="divider"></div>
						<table class="table table-striped table-bordered" style="background:#EEF5E4;border-radius:10px;">
							<tbody>
							<tr>
								<td>Shortcode:</td>
								<td colspan="2"><code>[mpcrbm_booking form='inline' progressbar='yes']</code></td>
							</tr>
							
							<tr>
								<td><code>form</code></td>
								<td><strong>inline</strong> or <strong>horizontal</strong> default <strong>horizontal</strong> and inline means minimal single line form</td>
							</tr>
							<tr>
								<td><code>progressbar</code></td>
								<td><strong>yes</strong> or <strong>no</strong> default <strong>yes</strong> . if no then progressbar will be hidden</td>
							</tr>
							
							</tbody>
						</table>
					</div>
				</div>
				<?php
			}
		}
		new MPCRBM_Guideline();
	}