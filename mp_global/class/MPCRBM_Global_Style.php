<?php
	/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Global_Style' ) ) {
		class MPCRBM_Global_Style {
			public function __construct() {
				add_action( 'mpcrbm_global_enqueue', array( $this, 'css_variable' ), 100 );
			}

			public function css_variable() {
				$default_color   = MPCRBM_Global_Function::get_style_settings( 'default_text_color', '#303030' );
				$theme_color     = MPCRBM_Global_Function::get_style_settings( 'theme_color', '#F12971' );
				$alternate_color = MPCRBM_Global_Function::get_style_settings( 'theme_alternate_color', '#fff' );
				$warning_color   = MPCRBM_Global_Function::get_style_settings( 'warning_color', '#E67C30' );
				$default_fs      = MPCRBM_Global_Function::get_style_settings( 'default_font_size', '14' ) . 'px';
				$fs_h1           = MPCRBM_Global_Function::get_style_settings( 'font_size_h1', '35' ) . 'px';
				$fs_h2           = MPCRBM_Global_Function::get_style_settings( 'font_size_h2', '30' ) . 'px';
				$fs_h3           = MPCRBM_Global_Function::get_style_settings( 'font_size_h3', '25' ) . 'px';
				$fs_h4           = MPCRBM_Global_Function::get_style_settings( 'font_size_h4', '22' ) . 'px';
				$fs_h5           = MPCRBM_Global_Function::get_style_settings( 'font_size_h5', '18' ) . 'px';
				$fs_h6           = MPCRBM_Global_Function::get_style_settings( 'font_size_h6', '16' ) . 'px';
				$fs_label        = MPCRBM_Global_Function::get_style_settings( 'font_size_label', '16' ) . 'px';
				$button_fs       = MPCRBM_Global_Function::get_style_settings( 'button_font_size', '16' ) . 'px';
				$button_color    = MPCRBM_Global_Function::get_style_settings( 'button_color', $alternate_color );
				$button_bg       = MPCRBM_Global_Function::get_style_settings( 'button_bg', '#ea8125' );
				$section_bg      = MPCRBM_Global_Function::get_style_settings( 'section_bg', '#FAFCFE' );
				$theme_color_ee  = $theme_color . 'ee';
				$theme_color_cc  = $theme_color . 'cc';
				$theme_color_aa  = $theme_color . 'aa';
				$theme_color_88  = $theme_color . '88';
				$theme_color_77  = $theme_color . '77';
				$inline_css      = "
				/*****Font size********/
				:root {
					--fs: " . esc_attr( $default_fs ) . ";
					--fs_label: " . esc_attr( $fs_label ) . ";
					--fs_h6: " . esc_attr( $fs_h6 ) . ";
					--fs_h5: " . esc_attr( $fs_h5 ) . ";
					--fs_h4: " . esc_attr( $fs_h4 ) . ";
					--fs_h3: " . esc_attr( $fs_h3 ) . ";
					--fs_h2: " . esc_attr( $fs_h2 ) . ";
					--fs_h1: " . esc_attr( $fs_h1 ) . ";
				}
				/*****Button********/
				:root {
					--button_bg: " . esc_attr( $button_bg ) . ";
					--color_button: " . esc_attr( $button_color ) . ";
					--button_fs: " . esc_attr( $button_fs ) . ";
				}
				/*******Color***********/
				:root {
					--d_color: " . esc_attr( $default_color ) . ";
					--color_section: " . esc_attr( $section_bg ) . ";
					--color_theme: " . esc_attr( $theme_color ) . ";
					--color_theme_ee: " . esc_attr( $theme_color_ee ) . ";
					--color_theme_cc: " . esc_attr( $theme_color_cc ) . ";
					--color_theme_aa: " . esc_attr( $theme_color_aa ) . ";
					--color_theme_88: " . esc_attr( $theme_color_88 ) . ";
					--color_theme_77: " . esc_attr( $theme_color_77 ) . ";
					--color_theme_alter: " . esc_attr( $alternate_color ) . ";
					--color_warning: " . esc_attr( $warning_color ) . ";
				}";
				wp_add_inline_style( 'mpcrbm_global', $inline_css );
			}
		}
		new MPCRBM_Global_Style();
	}