<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.

	if (!class_exists('MPTBM_Operation_Area_Settings')) {
		class MPTBM_Operation_Area_Settings {
			public function __construct() {
				add_action('add_mptbm_settings_tab_content', [$this, 'operation_area_settings']);
				add_action('save_post', array($this, 'save_operation_area_settings'), 99, 1);
			}
			
			public function operation_area_settings($post_id) {
                ?>

				<div class="tabsItem" data-tabs="#mptbm_setting_operation_area">
                <h2><?php esc_html_e('Operation Area', 'wpcarrently'); ?></h2>
                <p><?php esc_html_e('You can choose multiple regions as your operational area', 'wpcarrently'); ?></p>
                </div>
                
                <?php
			}
			
			public function save_operation_area_settings($post_id) {
				
			}
			
		}
		new MPTBM_Operation_Area_Settings();
	}