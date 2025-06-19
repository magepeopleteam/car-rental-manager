<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die; // Exit if accessed directly
	}
	/*
	 * @Author 		engr.sumonazma@gmail.com
	 * Copyright: 	mage-people.com
	 */
	if ( ! class_exists( 'MPCRBM_Transport_Search' ) ) {
		class MPCRBM_Transport_Search {
			public function __construct() {
				add_action( 'mpcrbm_transport_search', [ $this, 'transport_search' ], 10, 1 );
				//add_action('mpcrbm_transport_search_form', [$this, 'transport_search_form'], 10, 2);
				/*******************/
				add_action( 'wp_ajax_get_mpcrbm_map_search_result', [ $this, 'mpcrbm_get_map_search_result' ] );
				add_action( 'wp_ajax_nopriv_get_mpcrbm_map_search_result', [ $this, 'mpcrbm_get_map_search_result' ] );
				add_action( 'wp_ajax_get_mpcrbm_map_search_result_redirect', [ $this, 'get_mpcrbm_map_search_result_redirect' ] );
				add_action( 'wp_ajax_nopriv_get_mpcrbm_map_search_result_redirect', [ $this, 'get_mpcrbm_map_search_result_redirect' ] );
				/*********************/
				add_action( 'wp_ajax_get_mpcrbm_end_place', [ $this, 'get_mpcrbm_end_place' ] );
				add_action( 'wp_ajax_nopriv_get_mpcrbm_end_place', [ $this, 'get_mpcrbm_end_place' ] );
				/**************************/
				add_action( 'wp_ajax_get_mpcrbm_extra_service', [ $this, 'mpcrbm_get_extra_service' ] );
				add_action( 'wp_ajax_nopriv_get_mpcrbm_extra_service', [ $this, 'mpcrbm_get_extra_service' ] );
				/*******************************/
				add_action( 'wp_ajax_get_mpcrbm_extra_service_summary', [ $this, 'mpcrbm_get_extra_service_summary' ] );
				add_action( 'wp_ajax_nopriv_get_mpcrbm_extra_service_summary', [ $this, 'mpcrbm_get_extra_service_summary' ] );
			}

			public function transport_search( $params ) {
				$display_map = MPCRBM_Global_Function::get_settings( 'mpcrbm_map_api_settings', 'display_map', 'enable' );
				$price_based = $params['price_based'] ?: 'dynamic';
				$price_based = $display_map == 'disable' ? 'manual' : $price_based;
				$progressbar = $params['progressbar'] ?: 'yes';
				$form_style  = $params['form'] ?: 'horizontal';
				$map         = $params['map'] ?: 'yes';
				$map         = $display_map == 'disable' ? 'no' : $map;
				ob_start();
				do_shortcode( '[shop_messages]' );
				echo wp_kses_post( ob_get_clean() );
				//echo '<pre>';print_r($params);echo '</pre>';
				include( MPCRBM_Function::template_path( 'registration/registration_layout.php' ) );
			}

			public function mpcrbm_get_map_search_result() {
				include( MPCRBM_Function::template_path( 'registration/choose_vehicles.php' ) );
				die(); // Ensure further execution stops after outputting the JavaScript
			}

			public function mpcrbm_get_map_search_result_redirect() {
				ob_start(); // Start output buffering
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ) {
					wp_die( esc_html__( 'Security check failed', 'car-rental-manager' ) );
				}
				// Unslash and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
					wp_die( esc_html__( 'Security check failed', 'car-rental-manager' ) );
				}
				$distance = isset( $_COOKIE['mpcrbm_distance'] ) ? absint( $_COOKIE['mpcrbm_distance'] ) : '';
				$duration = isset( $_COOKIE['mpcrbm_duration'] ) ? absint( $_COOKIE['mpcrbm_duration'] ) : '';
				// if ($distance && $duration) {
				include( MPCRBM_Function::template_path( 'registration/choose_vehicles.php' ) );
				// }
				$content = ob_get_clean(); // Get the buffered content and clean the buffer
				// Store the content in a session variable
				session_start();
				$_SESSION['custom_content'] = $content;
				session_write_close(); // Close the session to release the lock
				// Sanitize and validate redirect URL
				$redirect_url = isset( $_POST['mpcrbm_enable_view_search_result_page'] )
					? esc_url_raw( sanitize_text_field( wp_unslash( $_POST['mpcrbm_enable_view_search_result_page'] ) ) )
					: '';
				if ( $redirect_url == '' ) {
					$redirect_url = 'mpcrbm-search';
				}
				echo wp_json_encode( $redirect_url );
				die(); // Ensure further execution stops after outputting the JavaScript
			}

			public function mpcrbm_get_end_place() {
				include( MPCRBM_Function::template_path( 'registration/get_end_place.php' ) );
				die();
			}

			public function mpcrbm_get_extra_service() {
				include( MPCRBM_Function::template_path( 'registration/extra_service.php' ) );
				die();
			}

			public function mpcrbm_get_extra_service_summary() {
				include( MPCRBM_Function::template_path( 'registration/extra_service_summary.php' ) );
				die();
			}

			public function search_transport() {
				// Verify nonce
				if (
					! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ||
					! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) ), 'mpcrbm_transportation_type_nonce' )
				) {
					wp_send_json_error( array( 'message' => esc_html__( 'Security check failed', 'car-rental-manager' ) ) );
					wp_die();
				}
				// Store search parameters in session
				if ( isset( $_POST['mpcrbm_start_place'] ) ) {
					$_SESSION['mpcrbm_start_place'] = sanitize_text_field( wp_unslash( $_POST['mpcrbm_start_place'] ) );
				}
				if ( isset( $_POST['mpcrbm_end_place'] ) ) {
					$_SESSION['mpcrbm_end_place'] = sanitize_text_field( wp_unslash( $_POST['mpcrbm_end_place'] ) );
				}
				if ( isset( $_POST['mpcrbm_start_date'] ) ) {
					$_SESSION['mpcrbm_start_date'] = sanitize_text_field( wp_unslash( $_POST['mpcrbm_start_date'] ) );
				}
				// Redirect to search results
				$redirect_url = 'mpcrbm-search';
				wp_redirect( home_url( $redirect_url ) );
				exit;
			}
		}
		new MPCRBM_Transport_Search();
	}
