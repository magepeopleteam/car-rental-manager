<?php
	/*
	 * @Author 		MagePeople Team
	 * Copyright: 	mage-people.com
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly
	if ( ! class_exists( 'MPCRBM_Query' ) ) {
		class MPCRBM_Query {
			public function __construct() { }

			public static function query_post_id( $post_type ): array {
				return get_posts( array(
					'fields'         => 'ids',
					'posts_per_page' => - 1,
					'post_type'      => $post_type,
					'post_status'    => 'publish'
				) );
			}

			public static function query_transport_list( $price_based = '' ): WP_Query {
				$price_based_4 = $price_based == 'manual' ? array(
					'key'     => 'mpcrbm_price_based',
					'value'   => 'manual',
					'compare' => '=',
				) : '';
				// Main query args
				$args = array(
					'post_type'      => array( MPCRBM_Function::get_cpt() ),
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
					'meta_query'     => array(
						'relation' => 'OR',
						$price_based_4,
					)
				);
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$main_query = new WP_Query( $args );

				// Return a new WP_Query object with merged posts
				return new WP_Query( array(
					'post_type'      => array( MPCRBM_Function::get_cpt() ),
					'posts_per_page' => - 1,
					'post_status'    => 'publish',
					'post__in'       => wp_list_pluck( $main_query->posts, 'ID' ) // Include all post IDs from merged result
				) );
			}

			public static function query_all_service_sold( $post_id, $date, $service_name = '' ): WP_Query {
				$_seat_booked_status      = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', 'set_book_status', array( 'processing', 'completed' ) );
				$seat_booked_status       = ! empty( $_seat_booked_status ) ? $_seat_booked_status : [];
				$type_filter              = ! empty( $type ) ? array(
					'key'     => 'mpcrbm_service_name',
					'value'   => $service_name,
					'compare' => '='
				) : '';
				$date_filter              = ! empty( $date ) ? array(
					'key'     => 'mpcrbm_date',
					'value'   => $date,
					'compare' => 'LIKE'
				) : '';
				$pending_status_filter    = in_array( 'pending', $seat_booked_status ) ? array(
					'key'     => 'mpcrbm_order_status',
					'value'   => 'pending',
					'compare' => '='
				) : '';
				$on_hold_status_filter    = in_array( 'on-hold', $seat_booked_status ) ? array(
					'key'     => 'mpcrbm_order_status',
					'value'   => 'on-hold',
					'compare' => '='
				) : '';
				$processing_status_filter = array(
					'key'     => 'mpcrbm_order_status',
					'value'   => 'processing',
					'compare' => '='
				);
				$completed_status_filter  = array(
					'key'     => 'mpcrbm_order_status',
					'value'   => 'completed',
					'compare' => '='
				);
				$args                     = array(
					'post_type'      => 'mpcrbm_service_booking',
					'posts_per_page' => - 1,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'relation' => 'AND',
							array(
								'key'     => 'mpcrbm_id',
								'value'   => $date,
								'compare' => '='
							),
							$type_filter,
							$date_filter
						),
						array(
							'relation' => 'OR',
							$pending_status_filter,
							$on_hold_status_filter,
							$processing_status_filter,
							$completed_status_filter
						)
					)
				);
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				return new WP_Query( $args );
			}
		}
		new MPCRBM_Query();
	}
