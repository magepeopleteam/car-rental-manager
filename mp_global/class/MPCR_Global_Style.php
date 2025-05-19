<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists('MPCR_Global_Style') ) {
		class MPCR_Global_Style {
			public function __construct() {
				add_action( 'add_mp_global_enqueue', array( $this, 'mpcrm_inline_css_setings' ), 100 );
			}
			public function mpcrm_inline_css_setings() {
				$default_color   = MPCRM_Global_Function::get_style_settings( 'default_text_color', '#303030' );
				$theme_color     = MPCRM_Global_Function::get_style_settings( 'theme_color', '#F12971' );
				$alternate_color = MPCRM_Global_Function::get_style_settings( 'theme_alternate_color', '#fff' );
				$warning_color   = MPCRM_Global_Function::get_style_settings( 'warning_color', '#E67C30' );
				$default_fs      = MPCRM_Global_Function::get_style_settings( 'default_font_size', '14' ) . 'px';
				$fs_h1           = MPCRM_Global_Function::get_style_settings( 'font_size_h1', '35' ) . 'px';
				$fs_h2           = MPCRM_Global_Function::get_style_settings( 'font_size_h2', '30' ) . 'px';
				$fs_h3           = MPCRM_Global_Function::get_style_settings( 'font_size_h3', '25' ) . 'px';
				$fs_h4           = MPCRM_Global_Function::get_style_settings( 'font_size_h4', '22' ) . 'px';
				$fs_h5           = MPCRM_Global_Function::get_style_settings( 'font_size_h5', '18' ) . 'px';
				$fs_h6           = MPCRM_Global_Function::get_style_settings( 'font_size_h6', '16' ) . 'px';
				$fs_label        = MPCRM_Global_Function::get_style_settings( 'font_size_label', '16' ) . 'px';
				$button_fs       = MPCRM_Global_Function::get_style_settings( 'button_font_size', '16' ) . 'px';
				$button_color    = MPCRM_Global_Function::get_style_settings( 'button_color', $alternate_color );
				$button_bg       = MPCRM_Global_Function::get_style_settings( 'button_bg', '#ea8125' );
				$section_bg      = MPCRM_Global_Function::get_style_settings( 'section_bg', '#FAFCFE' );
				$theme_color_ee  = $theme_color.'ee';
				$theme_color_cc  = $theme_color.'cc';
				$theme_color_aa  = $theme_color.'aa';
				$theme_color_88  = $theme_color.'88';
				$theme_color_77  = $theme_color.'77';
				$inline_css	  = "
					:root {
					--dcontainer_width: 1320px;
					--sidebarleft: 280px;
					--sidebarright: 300px;
					--mainsection: calc(100% - 300px);
					--dmpl: 40px;
					--dmp: 20px;
					--dmp_negetive: -20px;
					--dmp_xs: 10px;
					--dmp_xs_negative: -10px;
					--dbrl: 10px;
					--dbr: 5px;
					--dshadow: 0 0 2px #665F5F7A;
				}
				/*****Font size********/
				:root {
					--fs: " . esc_attr($default_fs) . ";
					--fw: normal;
					--fs_small: 10px;
					--fs_label: " . esc_attr($fs_label) . ";
					--fs_h6: " . esc_attr($fs_h6) . ";
					--fs_h5: " . esc_attr($fs_h5) . ";
					--fs_h4: " . esc_attr($fs_h4) . ";
					--fs_h3: " . esc_attr($fs_h3) . ";
					--fs_h2: " . esc_attr($fs_h2) . ";
					--fs_h1: " . esc_attr($fs_h1) . ";
					--fw-thin: 300; /*font weight medium*/
					--fw-normal: 500; /*font weight medium*/
					--fw-medium: 600; /*font weight medium*/
					--fw-bold: bold; /*font weight bold*/
				}
				/*****Button********/
				:root {
					--button_bg: " . esc_attr($button_bg) . ";
					--color_button: " . esc_attr($button_color) . ";
					--button_fs: " . esc_attr($button_fs) . ";
					--button_height: 40px;
					--button_height_xs: 30px;
					--button_width: 120px;
					--button_shadows: 0 8px 12px rgb(51 65 80 / 6%), 0 14px 44px rgb(51 65 80 / 11%);
				}
				/*******Color***********/
				:root {
					--d_color: " . esc_attr($default_color) . ";
					--color_border: #DDD;
					--color_active: #0E6BB7;
					--color_section: " . esc_attr($section_bg) . ";
					--color_theme: " . esc_attr($theme_color) . ";
					--color_theme_ee: " . esc_attr($theme_color_ee) . ";
					--color_theme_cc: " . esc_attr($theme_color_cc) . ";
					--color_theme_aa: " . esc_attr($theme_color_aa) . ";
					--color_theme_88: " . esc_attr($theme_color_88) . ";
					--color_theme_77: " . esc_attr($theme_color_77) . ";
					--color_theme_alter: " . esc_attr($alternate_color) . ";
					--color_warning: " . esc_attr($warning_color) . ";
					--color_black: #000;
					--color_success: #00A656;
					--color_danger: #C00;
					--color_required: #C00;
					--color_white: #FFFFFF;
					--color_light: #F2F2F2;
					--color_light_1: #BBB;
					--color_light_2: #EAECEE;
					--color_light_3: #878787;
					--color_light_4: #f9f9f9;
					--color_info: #666;
					--color_yellow: #FEBB02;
					--color_blue: #815DF2;
					--color_navy_blue: #007CBA;
					--color_1: #0C5460;
					--color_2: #caf0ffcc;
					--color_3: #FAFCFE;
					--color_4: #6148BA;
					--color_5: #BCB;
				}
				@media only screen and (max-width: 1100px) {
					:root {
						--fs: 14px;
						--fs_small: 12px;
						--fs_label: 15px;
						--fs_h4: 20px;
						--fs_h3: 22px;
						--fs_h2: 25px;
						--fs_h1: 30px;
						--dmpl: 32px;
						--dmp: 16px;
						--dmp_negetive: -16px;
						--dmp_xs: 8px;
						--dmp_xs_negative: -8px;
					}
				}
				@media only screen and (max-width: 700px) {
					:root {
						--fs: 12px;
						--fs_small: 10px;
						--fs_label: 13px;
						--fs_h6: 15px;
						--fs_h5: 16px;
						--fs_h4: 18px;
						--fs_h3: 20px;
						--fs_h2: 22px;
						--fs_h1: 24px;
						--dmp: 10px;
						--dmp_xs: 5px;
						--dmp_xs_negative: -5px;
						--button_fs: 14px;
					}
				}
				";
				wp_add_inline_style('mp_plugin_global', $inline_css);
			}
		}
		new MPCR_Global_Style();
	}