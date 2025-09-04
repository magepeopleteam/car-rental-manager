<?php
	if ( ! defined( 'ABSPATH' ) ) {
		die; // Exit if accessed directly
	}
	/*
	 * @Author 		MagePeople Team
	 * Copyright: 	mage-people.com
	 */
	if ( ! class_exists( 'MPCRBM_Transport_Search' ) ) {
		class MPCRBM_Transport_Search {
			public function __construct() {
				add_action( 'mpcrbm_transport_search', [ $this, 'transport_search' ], 10, 1 );
				//add_action('mpcrbm_transport_search_form', [$this, 'transport_search_form'], 10, 2);
				/*******************/
				add_action( 'wp_ajax_mpcrbm_get_map_search_result', [ $this, 'mpcrbm_get_map_search_result' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_get_map_search_result', [ $this, 'mpcrbm_get_map_search_result' ] );
				add_action( 'wp_ajax_mpcrbm_get_map_search_result_redirect', [ $this, 'mpcrbm_get_map_search_result_redirect' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_get_map_search_result_redirect', [ $this, 'mpcrbm_get_map_search_result_redirect' ] );
				/*********************/
				add_action( 'wp_ajax_mpcrbm_get_end_place', [ $this, 'mpcrbm_get_end_place' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_get_end_place', [ $this, 'mpcrbm_get_end_place' ] );
				/**************************/
				add_action( 'wp_ajax_mpcrbm_get_extra_service', [ $this, 'mpcrbm_get_extra_service' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_get_extra_service', [ $this, 'mpcrbm_get_extra_service' ] );
				/*******************************/
				add_action( 'wp_ajax_mpcrbm_get_extra_service_summary', [ $this, 'mpcrbm_get_extra_service_summary' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_get_extra_service_summary', [ $this, 'mpcrbm_get_extra_service_summary' ] );
				
				// Multi-location support
				add_action( 'wp_ajax_mpcrbm_get_dropoff_locations', [ $this, 'mpcrbm_get_dropoff_locations' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_get_dropoff_locations', [ $this, 'mpcrbm_get_dropoff_locations' ] );
			}

			public function transport_search( $params ) {
				$display_map = MPCRBM_Global_Function::get_settings( 'mpcrbm_map_api_settings', 'display_map', 'enable' );
				$price_based = $params['price_based'] ?: 'dynamic';
				$price_based = $display_map == 'disable' ? 'manual' : $price_based;
				$progressbar = $params['progressbar'] ?: 'yes';
				$form_style  = $params['form'] ?: 'horizontal';
				$map         = $params['map'] ?: 'yes';
				$map         = $display_map == 'disable' ? 'no' : $map;

                $is_title    = $params['title'] ?: 'no';;

				ob_start();
				do_shortcode( '[shop_messages]' );
				echo wp_kses_post( ob_get_clean() );
				//echo '<pre>';print_r($params);echo '</pre>';
				include( MPCRBM_Function::template_path( 'registration/registration_layout.php' ) );
			}

			public function mpcrbm_get_map_search_result() {
                $is_redirect = 'no';
				include( MPCRBM_Function::template_path( 'registration/choose_vehicles.php' ) );
				die(); // Ensure further execution stops after outputting the JavaScript
			}

			public function mpcrbm_get_map_search_result_redirect_old() {
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
                $is_redirect = 'yes';
				include( MPCRBM_Function::template_path( 'registration/choose_vehicles.php' ) );
				// }
				$content = ob_get_clean(); // Get the buffered content and clean the buffer
				// Store the content in a session variable
				session_start();
				$_SESSION['custom_content'] = $content;
				session_write_close(); // Close the session to release the lock
				// Sanitize and validate redirect URL
				$redirect_url = isset( $_POST['mpcrbm_enable_view_search_result_page'] )
					?  sanitize_text_field( wp_unslash( $_POST['mpcrbm_enable_view_search_result_page'] ) )
					: '';
				if ( $redirect_url == '' ) {
					$redirect_url = 'transport-result';
				}
				echo wp_json_encode( $redirect_url );
				die(); // Ensure further execution stops after outputting the JavaScript
			}

            public static function is_valid_page_slug( $slug ) {
                if ( empty( $slug ) ) {
                    return false;
                }
                $page = get_page_by_path( $slug );
                return ( $page !== null );
            }

            public function mpcrbm_get_map_search_result_redirect() {
                ob_start(); // Output buffering শুরু

                // Security check - nonce verify
                if ( ! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ) {
                    wp_die( esc_html__( 'Security check failed', 'car-rental-manager' ) );
                }
                $nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) );
                $progress_bar = isset( $_POST['progress_bar'] ) ? sanitize_text_field( wp_unslash( $_POST['progress_bar'] ) ) : '';

                if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
                    wp_die( esc_html__( 'Security check failed', 'car-rental-manager' ) );
                }

                // Cookie থেকে distance/duration
                $distance = isset( $_COOKIE['mpcrbm_distance'] ) ? absint( $_COOKIE['mpcrbm_distance'] ) : '';
                $duration = isset( $_COOKIE['mpcrbm_duration'] ) ? absint( $_COOKIE['mpcrbm_duration'] ) : '';

                $is_redirect = 'yes';
                include( MPCRBM_Function::template_path( 'registration/choose_vehicles.php' ) );

                $content = ob_get_clean(); // Buffer content get & clean

                session_start();
                $_SESSION['custom_content'] = $content;
                $_SESSION['progress_bar'] = $progress_bar;
                session_write_close();

                // Plugin settings থেকে search result page slug আনো
                $search_page_slug = isset( $_POST['mpcrbm_enable_view_search_result_page'] )
                    ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_enable_view_search_result_page'] ) )
                    : '';

                if ( ! self::is_valid_page_slug( $search_page_slug ) ) {
                    $search_page_slug = 'transport-result';
                }
                $redirect_url = home_url( '/' . $search_page_slug . '/' );

                echo wp_json_encode( $redirect_url );
                wp_die();
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
				wp_safe_redirect( home_url( $redirect_url ) );
				exit();
			}

			/**
			 * Get available dropoff locations for multi-location support
			 */
			public function mpcrbm_get_dropoff_locations() {
				// Verify nonce
				if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mpcrbm_nonce' ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Security check failed', 'car-rental-manager' ) ) );
				}

				$pickup_location = isset( $_POST['pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_location'] ) ) : '';
				$vehicle_ids = isset( $_POST['vehicle_ids'] ) ? array_map( 'intval', $_POST['vehicle_ids'] ) : array();

				if ( empty( $pickup_location ) ) {
					wp_send_json_error( array( 'message' => esc_html__( 'Pickup location is required', 'car-rental-manager' ) ) );
				}

				$available_locations = array();

				// If specific vehicles are provided, check their multi-location settings
				if ( ! empty( $vehicle_ids ) ) {
					foreach ( $vehicle_ids as $vehicle_id ) {
						$dropoff_locations = MPCRBM_Function::get_vehicle_dropoff_locations( $vehicle_id, $pickup_location );
						$available_locations = array_merge( $available_locations, $dropoff_locations );
					}
				} else {
					// Get all vehicles and their dropoff locations
					$all_vehicles = MPCRBM_Query::query_transport_list( 'manual' );
					if ( $all_vehicles->found_posts > 0 ) {
						foreach ( $all_vehicles->posts as $vehicle ) {
							$dropoff_locations = MPCRBM_Function::get_vehicle_dropoff_locations( $vehicle->ID, $pickup_location );
							$available_locations = array_merge( $available_locations, $dropoff_locations );
						}
					}
				}

				// Remove duplicates and get location names
				$available_locations = array_unique( $available_locations );
				$location_data = array();

				foreach ( $available_locations as $location_slug ) {
					$location_name = MPCRBM_Function::get_taxonomy_name_by_slug( $location_slug, 'mpcrbm_locations' );
					if ( $location_name ) {
						$location_data[] = array(
							'slug' => $location_slug,
							'name' => $location_name
						);
					}
				}

				wp_send_json_success( array( 'locations' => $location_data ) );
			}
		}
		new MPCRBM_Transport_Search();
	}
