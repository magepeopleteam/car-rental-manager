<?php
/**
 * Plugin Single Template
 */

defined( 'ABSPATH' ) || exit;

/**
 * --------------------------
 * HEADER AREA
 * --------------------------
 */
if ( wp_is_block_theme() ) {
    if ( function_exists( 'block_header_area' ) ) {
        ob_start();
        block_header_area();
        $mpcrbm_header_html = trim( ob_get_clean() );

        if ( $mpcrbm_header_html ) {
            wp_head();
            wp_body_open();
            echo '<div class="wp-site-blocks">';
            echo '<header class="wp-block-template-part site-header">';
            echo wp_kses_post( $mpcrbm_header_html );
            echo '</header>';
            echo '</div>';
        } else {
            get_header();
        }
    } else {
        get_header();
    }
} else {
    get_header();
}

/**
 * --------------------------
 * MAIN CONTENT
 * --------------------------
 */
	the_post();
	do_action( 'mpcrbm_single_page_before_wrapper' );
	if ( post_password_required() ) {
		echo wp_kses_post(get_the_password_form()); // WPCS: XSS ok.
	} else {
		do_action( 'mpcrbm_woocommerce_before_single_product' );
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$post_id                   = get_the_id();
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$template_name = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_theme_file', 'default.php' );
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$price_based    = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_price_based' );
		include_once( MPCRBM_Function::details_template_path() );
	}
	do_action( 'mpcrbm_single_page_after_wrapper' );

// ==============================
// FOOTER
// ==============================
if ( function_exists( 'block_footer_area' ) && wp_is_block_theme() ) {
    echo '<footer class="wp-block-template-part mep-site-footer">';
        block_footer_area();
    echo '</footer>';
    wp_footer();
} else {
    get_footer();
}