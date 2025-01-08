<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPTBM_Guideline')) {
		class MPTBM_Guideline {
			public function __construct() {
				add_action('admin_menu', array($this, 'guideline_menu'));
			}
			public function guideline_menu() {
				$cpt = MPTBM_Function::get_cpt();
				add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Guideline', 'wpcarrently'), '<span>' . esc_html__('Guideline', 'wpcarrently') . '</span>', 'manage_options', 'mptbm_guideline_page', array($this, 'guideline_page'));
			}
			public function guideline_page() {
				$label = MPTBM_Function::get_name();
				?>
				<div class="wrap"></div>
				<div class="mpStyle">
					<div class="_dShadow_6_adminLayout">
						<h2 class="textCenter"><?php echo esc_html($label) . '  ' . esc_html__('Shortcode', 'wpcarrently'); ?></h2>
						<div class="divider"></div>
						<table class="table table-striped table-bordered" style="background:#EEF5E4;border-radius:10px;">
							<tbody>
							<tr>
								<td>Shortcode:</td>
								<td colspan="2"><code>[wpcb_booking form='horizontal' progressbar='yes']</code></td>
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
		new MPTBM_Guideline();
	}