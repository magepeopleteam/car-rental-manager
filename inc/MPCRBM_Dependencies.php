<?php
	/*
	 * @Author 		MagePeople Team
	 * Copyright: 	mage-people.com
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Dependencies' ) ) {
		class MPCRBM_Dependencies {
			public function __construct() {
				// add_action( 'init', array( $this, 'language_load' ) );
				$this->load_file();
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 80 );
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ), 80 );
				add_action( 'current_screen', function( $screen ) {
					if ( $screen && $screen->id === 'mpcrbm_rent_page_mpcrbm_car_rental' ) {
						// try to remove generic actions (risky: may remove others)
						remove_all_actions( 'admin_notices' ); // <-- এইটা সাবধান দিয়ে ব্যবহার করো
						remove_all_actions( 'all_admin_notices' ); // optional
					}
				});
			}

			// public function language_load(): void {
			// 	$plugin_dir = basename( dirname( __DIR__ ) ) . "/languages/";
			// 	load_plugin_textdomain( 'car-rental-manager', false, $plugin_dir );
			// }

			private function load_file(): void {
				require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Function.php';
				require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Query.php';
				require_once MPCRBM_PLUGIN_DIR . '/inc/MPCRBM_Layout.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Admin.php';
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Frontend.php';
				require_once MPCRBM_PLUGIN_DIR . '/frontend/MPCRBM_Manage_Review.php';
				require_once MPCRBM_PLUGIN_DIR . '/admin/MPCRBM_Branch_Manager.php';
			}

			public function global_enqueue() {
				do_action( 'mpcrbm_common_script' );
				wp_enqueue_style('mage-icons', MPCRBM_PLUGIN_URL . '/assets/mage-icon/css/mage-icon.css', array(), time());
                $this->mpcrbm_enque_flatpickr();
			}

			public function admin_enqueue() {
				$this->global_enqueue();
				// custom
				wp_enqueue_style( 'mpcrbm_admin', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_admin.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_price_set', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_price_set.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_order_list', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_order_list.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_manage_taxonomy', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_manage_taxonomy.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_branch_manager', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm-branch-manager.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_admin', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_admin.js', array( 'jquery' ), time(), true );
				wp_enqueue_script( 'mpcrbm_order_lists', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_order_lists.js', array( 'jquery' ), time(), true );
				wp_enqueue_script( 'mpcrbm_manage_taxonomy', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm_manage_taxonomy.js', array( 'jquery' ), time(), true );
				wp_enqueue_script( 'mpcrbm_branch_manager', MPCRBM_PLUGIN_URL . '/assets/admin/mpcrbm-branch-manager.js', array( 'jquery' ), time(), true );
				$nonce = wp_create_nonce( 'mpcrbm_extra_service' );
				wp_localize_script( 'mpcrbm_admin', 'mpcrbm_admin_nonce', array(
					'nonce' => $nonce,
                    'site_url' => get_site_url(),
				) );
				wp_localize_script( 'mpcrbm_branch_manager', 'mpcrbmBranchAdmin', array(
					'loadingText'         => __( 'Loading…', 'car-rental-manager' ),
					'carsText'            => __( 'cars', 'car-rental-manager' ),
					'transferText'        => __( 'Transfer', 'car-rental-manager' ),
					'transferringText'    => __( 'Transferring…', 'car-rental-manager' ),
					'selectBranchText'    => __( 'Please select a target branch.', 'car-rental-manager' ),
					'confirmTransferText' => __( 'Transfer this car to the selected branch?', 'car-rental-manager' ),
					'isPro'               => is_plugin_active( MPCRBM_PRO_PLUGIN_NAME ),
				) );
				// Trigger the action hook to add additional scripts if needed
				do_action( 'mpcrbm_admin_script' );
			}

			public function frontend_enqueue() {
				$this->global_enqueue();
				wp_enqueue_style( 'mpcrbm_frontend', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_frontend.css', array(), time() );
				wp_enqueue_style( 'car_list_shortcode', MPCRBM_PLUGIN_URL . '/assets/frontend/car_list_shortcode.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_search_shortcode', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_search_shortcode.css', array(), time() );
				wp_enqueue_style( 'mpcrbm_car_details', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_car_details.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_frontend', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_frontend.js', array( 'jquery' ), time(), true );
				wp_enqueue_script( 'car_list_shortcode', MPCRBM_PLUGIN_URL . '/assets/frontend/car_list_shortcode.js', array( 'jquery' ), time(), true );
				wp_enqueue_style( 'mpcrbm_registration', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_registration.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_registration', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_registration.js', array( 'jquery' ), time(), true );
				// Localize scripts
				wp_enqueue_style( 'mpcrbm_manage_review', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_manage_review.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_manage_review', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm_manage_review.js', array( 'jquery' ), time(), true );
				wp_enqueue_style( 'mpcrbm_branch', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm-branch.css', array(), time() );
				wp_enqueue_script( 'mpcrbm_branch', MPCRBM_PLUGIN_URL . '/assets/frontend/mpcrbm-branch.js', array( 'jquery' ), time(), true );
				wp_localize_script( 'mpcrbm_branch', 'mpcrbmBranchL10n', $this->get_branch_l10n() );
				// Localize scripts
				wp_localize_script( 'mpcrbm_registration', 'mpcrbm_ajax', array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'mpcrbm_transportation_type_nonce' ),
                    'site_url' => get_site_url(),
				) );
				wp_localize_script( 'mpcrbm_registration', 'mpcrbmL10n', array(
					'nameLabel'  => __( 'Name : ', 'car-rental-manager' ),
					'qtyLabel'   => __( 'Quantity : ', 'car-rental-manager' ),
					'priceLabel' => __( 'Price : ', 'car-rental-manager' )
				) );

				do_action( 'mpcrbm_frontend_script' );
			}

			/** Build branch localization data for the frontend JS. */
			private function get_branch_l10n(): array {
				$car_one_way_fees = [];
				$cars = get_posts( [
					'post_type'      => MPCRBM_Function::get_cpt(),
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				] );
				foreach ( $cars as $car_id ) {
					$enabled = get_post_meta( $car_id, 'mpcrbm_car_one_way_enabled', true );
					if ( $enabled ) {
						$fee      = floatval( get_post_meta( $car_id, 'mpcrbm_car_one_way_fee', true ) );
						$fee_type = get_post_meta( $car_id, 'mpcrbm_car_one_way_fee_type', true );
						$fee_type = ( $fee_type === 'percentage' ) ? 'percentage' : 'fixed';
						$car_one_way_fees[ (string) $car_id ] = [
							'enabled'  => true,
							'fee'      => $fee,
							'fee_type' => $fee_type,
						];
					}
				}
				return [
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'car_one_way_fees' => $car_one_way_fees,
					'currency'         => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
					'strings'          => [
						'loading'        => __( 'Loading branch info…', 'car-rental-manager' ),
						'viewHours'      => __( 'View opening hours', 'car-rental-manager' ),
						'hideHours'      => __( 'Hide opening hours', 'car-rental-manager' ),
						'closed'         => __( 'Closed', 'car-rental-manager' ),
						'day'            => __( 'Day', 'car-rental-manager' ),
						'hours'          => __( 'Hours', 'car-rental-manager' ),
						'oneWayFeeLabel' => __( 'One-way return fee', 'car-rental-manager' ),
						'oneWayFeeDesc'  => __( 'Applied because the return location is a different branch.', 'car-rental-manager' ),
					],
				];
			}

            public function mpcrbm_enque_flatpickr() {

                wp_enqueue_style( 'mpcrbm_flatpickr.min', MPCRBM_PLUGIN_URL . 'mp_global/assets/flatpickr/mpcrbm_flatpickr.min.css', array(), time() );
                wp_enqueue_script( 'flatpickr.min', MPCRBM_PLUGIN_URL . 'mp_global/assets/flatpickr/flatpickr.min.js', array( 'jquery' ), time(), true );

                /*wp_enqueue_style(
                    'mpcrbm-flatpickr-css',
                    'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
                    array(),
                    '4.6.13'
                );

                // Flatpickr JS
                wp_enqueue_script(
                    'mpcrbm-flatpickr-js',
                    'https://cdn.jsdelivr.net/npm/flatpickr',
                    array('jquery'),
                    '4.6.13',
                    true
                );*/

            }

		}
		new MPCRBM_Dependencies();
	}
