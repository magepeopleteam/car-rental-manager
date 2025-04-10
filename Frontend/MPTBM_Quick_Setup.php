<?php
if (!defined('ABSPATH')) {
    die; // Exit if accessed directly
}

if (isset($_POST['mptbm_quick_setup_nonce'])) {
    $nonce = sanitize_text_field(wp_unslash($_POST['mptbm_quick_setup_nonce']));
    if (!wp_verify_nonce($nonce, 'mptbm_quick_setup_nonce')) {
        wp_die(esc_html__('Security check failed', 'car-rental-manager'));
    }
}

// Sanitize and validate all inputs
$mptbm_quick_setup = isset($_POST['mptbm_quick_setup']) 
    ? array_map('sanitize_text_field', wp_unslash($_POST['mptbm_quick_setup'])) 
    : array();

$mptbm_quick_setup_extra_service = isset($_POST['mptbm_quick_setup_extra_service']) 
    ? array_map('sanitize_text_field', wp_unslash($_POST['mptbm_quick_setup_extra_service'])) 
    : array();

$mptbm_quick_setup_price = isset($_POST['mptbm_quick_setup_price']) 
    ? array_map('absint', wp_unslash($_POST['mptbm_quick_setup_price'])) 
    : array(); 