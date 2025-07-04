<?php

	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	get_header();
	the_post();
	do_action( 'mpcrbm_single_page_before_wrapper' );
	if ( post_password_required() ) {
		echo wp_kses_post(get_the_password_form()); // WPCS: XSS ok.
	} else {
		do_action( 'mpcrbm_woocommerce_before_single_product' );
		$post_id                   = get_the_id();
		$template_name = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_theme_file', 'default.php' );
		$price_based    = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_price_based' );
		include_once( MPCRBM_Function::details_template_path() );
	}
	do_action( 'mpcrbm_single_page_after_wrapper' );
	get_footer();