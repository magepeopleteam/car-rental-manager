<?php
	/**
	 * Admin "Add Booking" screen — lets an administrator create a booking on a
	 * customer's behalf (typically while talking to them on the phone).
	 *
	 * Flow
	 * ----
	 *  1. Customer details.
	 *  2. Pickup / return date & time (defaults: next time slot today, return +1 day).
	 *  3. Vehicles — loaded over AJAX for exactly that period. Changing ANY date or time
	 *     empties the list, drops the selected vehicle and reloads, so a vehicle chosen for
	 *     one period can never be booked for another.
	 *  4. Vehicle details (quantity, locations, extra services), price, payment, save.
	 *
	 * Design notes
	 * ------------
	 * - Price is NEVER computed here. It comes from the same server-side functions the
	 *   customer checkout uses (MPCRBM_Woocommerce::mpcrbm_get_cart_total_price() for the
	 *   fare, MPCRBM_Offline_Checkout::build_draft() for the stored booking meta), so
	 *   early-bird, location fees, extra services and the security deposit follow exactly
	 *   the same rules. The admin may then OVERRIDE the rental total (lower or higher);
	 *   an override is recorded on the booking so it stays auditable.
	 * - Availability is checked over the WHOLE pickup→return period against the car's
	 *   stock (peak number of vehicles out at once), not just the pickup day.
	 * - The booking is written as an `mpcrbm_booking` post with the same meta schema both
	 *   checkouts produce (mpcrbm_order_id = 0, mpcrbm_booking_source = 'admin'), so every
	 *   existing consumer (Booking List, PDF, mail, availability) sees it like any other
	 *   booking. No WooCommerce order is created.
	 * - Fires `mpcrbm_add_booking_data` and `mpcrbm_native_booking_created`, so add-ons
	 *   (including Pro's confirmation mail) react as they do for a customer booking.
	 *
	 * Requires the `manage_options` capability and a nonce on every request.
	 */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	if ( ! class_exists( 'MPCRBM_Admin_Booking' ) ) {
		class MPCRBM_Admin_Booking {

			const SLUG  = 'mpcrbm_add_booking';
			const NONCE = 'mpcrbm_admin_booking';

			public function __construct() {
				add_action( 'admin_menu', array( $this, 'add_menu' ), 25 );
				add_action( 'wp_ajax_mpcrbm_admin_booking_cars', array( $this, 'ajax_cars' ) );
				add_action( 'wp_ajax_mpcrbm_admin_booking_quote', array( $this, 'ajax_quote' ) );
				add_action( 'wp_ajax_mpcrbm_admin_booking_create', array( $this, 'ajax_create' ) );
				add_filter( 'mpcrbm_shell_menu_items', array( $this, 'add_shell_menu_item' ) );
				add_filter( 'mpcrbm_shell_screen_ids', array( $this, 'add_shell_screen_id' ) );
			}

			/* --------------------------------------------------------------
			 * Menu
			 * ------------------------------------------------------------ */

			private function cpt(): string {
				return class_exists( 'MPCRBM_Function' ) ? MPCRBM_Function::get_cpt() : 'mpcrbm_rent';
			}

			private function page_url(): string {
				return admin_url( 'edit.php?post_type=' . $this->cpt() . '&page=' . self::SLUG );
			}

			/** Where the booking list lives: Pro's full list when Pro is active, else the free one. */
			private function list_url(): string {
				$pro  = class_exists( 'MPCRBM_Dependencies_Pro' ) || class_exists( 'MPCRBM_Plugin_Pro' );
				$slug = $pro ? 'mpcrbm_order_list' : 'mpcrbm_bookings';

				return admin_url( 'edit.php?post_type=' . $this->cpt() . '&page=' . $slug );
			}

			public function add_menu() {
				add_submenu_page(
					'edit.php?post_type=' . $this->cpt(),
					__( 'Add Booking', 'car-rental-manager' ),
					__( 'Add Booking', 'car-rental-manager' ),
					'manage_options',
					self::SLUG,
					array( $this, 'render_page' )
				);
			}

			public function add_shell_menu_item( $items ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return $items;
				}
				$items[] = array(
					'slug'  => self::SLUG,
					'label' => __( 'Add Booking', 'car-rental-manager' ),
					'icon'  => 'fas fa-phone-volume',
					'link'  => $this->page_url(),
				);

				return $items;
			}

			public function add_shell_screen_id( $ids ) {
				$ids[] = $this->cpt() . '_page_' . self::SLUG;

				return $ids;
			}

			/* --------------------------------------------------------------
			 * Data helpers
			 * ------------------------------------------------------------ */

			private function interval(): int {
				$interval = (int) MPCRBM_Function::get_general_settings( 'pickup_interval_time', '30' );

				return ( $interval < 5 || $interval > 60 ) ? 30 : $interval;
			}

			/** Pickup-time options, stepped by the pickup interval the site already uses. */
			private function time_options(): array {
				$interval = $this->interval();
				$out      = array();
				for ( $m = 0; $m < 1440; $m += $interval ) {
					$out[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
				}

				return $out;
			}

			/** Next free time slot from "now" (site time) plus the matching return one day later. */
			private function default_period(): array {
				$interval = $this->interval();
				$now      = current_datetime();
				$minutes  = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
				$slot     = (int) ( ceil( $minutes / $interval ) * $interval );
				$date     = $now;
				if ( $slot >= 1440 ) {
					$slot = 0;
					$date = $now->modify( '+1 day' );
				}
				$time = sprintf( '%02d:%02d', intdiv( $slot, 60 ), $slot % 60 );

				return array(
					'pickup_date' => $date->format( 'Y-m-d' ),
					'pickup_time' => $time,
					'return_date' => $date->modify( '+1 day' )->format( 'Y-m-d' ),
					'return_time' => $time,
				);
			}

			/** "10:30" -> the decimal form the booking form posts ("10.3"); see MPCRBM_Function::decimal_time_to_hi(). */
			private function hm_to_decimal( string $hm ): string {
				$parts = array_pad( explode( ':', $hm ), 2, '0' );
				$h     = (int) $parts[0];
				$m     = (int) $parts[1];
				if ( 0 === $m ) {
					return (string) $h;
				}
				$interval = (string) MPCRBM_Function::get_general_settings( 'pickup_interval_time', '30' );
				if ( in_array( $interval, array( '5', '15' ), true ) ) {
					return $h . '.' . $m;
				}

				return $h . '.' . (int) round( $m / 10 );
			}

			/** Extra services a car offers: [ ['name'=>, 'price'=>, 'type'=>], ... ]. */
			private function car_services( int $car_id ): array {
				if ( 'on' !== MPCRBM_Global_Function::get_post_info( $car_id, 'display_mpcrbm_extra_services', 'on' ) ) {
					return array();
				}
				$service_id = MPCRBM_Global_Function::get_post_info( $car_id, 'mpcrbm_extra_services_id', $car_id );
				$infos      = MPCRBM_Global_Function::get_post_info( $service_id, 'mpcrbm_extra_service_infos', array() );
				$out        = array();
				if ( is_array( $infos ) ) {
					foreach ( $infos as $info ) {
						$name = isset( $info['service_name'] ) ? (string) $info['service_name'] : '';
						if ( '' === $name ) {
							continue;
						}
						$out[] = array(
							'name'  => $name,
							'price' => isset( $info['service_price'] ) ? (float) $info['service_price'] : 0.0,
							'type'  => isset( $info['service_price_type'] ) ? (string) $info['service_price_type'] : 'flat',
						);
					}
				}

				return $out;
			}

			/** Pickup/return locations for one car: [ 'pickups' => [...], 'dropoffs' => [ pickup => [...] ] ]. */
			private function car_locations( int $car_id ): array {
				$pickups  = array_values( array_filter( (array) MPCRBM_Function::get_vehicle_pickup_locations( $car_id ), 'strlen' ) );
				$dropoffs = array();
				foreach ( $pickups as $p ) {
					$dropoffs[ $p ] = array_values( array_filter( (array) MPCRBM_Function::get_vehicle_dropoff_locations( $car_id, $p ), 'strlen' ) );
				}

				return array(
					'pickups'  => $pickups,
					'dropoffs' => $dropoffs,
				);
			}

			private function money( $amount ): string {
				return html_entity_decode( wp_strip_all_tags( MPCRBM_Global_Function::format_price( (float) $amount ) ), ENT_QUOTES, 'UTF-8' );
			}

			/* --------------------------------------------------------------
			 * Period + availability
			 * ------------------------------------------------------------ */

			/**
			 * Validate the posted pickup/return fields.
			 *
			 * @return array|WP_Error { start: DateTime, end: DateTime, pd, pt, rd, rt }
			 */
			private function parse_period( array $in ) {
				$pd = isset( $in['pickup_date'] ) ? sanitize_text_field( $in['pickup_date'] ) : '';
				$pt = isset( $in['pickup_time'] ) ? sanitize_text_field( $in['pickup_time'] ) : '';
				$rd = isset( $in['return_date'] ) ? sanitize_text_field( $in['return_date'] ) : '';
				$rt = isset( $in['return_time'] ) ? sanitize_text_field( $in['return_time'] ) : '';

				$tz    = wp_timezone();
				$start = DateTime::createFromFormat( 'Y-m-d H:i', $pd . ' ' . $pt, $tz );
				$end   = DateTime::createFromFormat( 'Y-m-d H:i', $rd . ' ' . $rt, $tz );
				if ( ! $start || $start->format( 'Y-m-d H:i' ) !== $pd . ' ' . $pt || ! $end || $end->format( 'Y-m-d H:i' ) !== $rd . ' ' . $rt ) {
					return new WP_Error( 'date', __( 'Please enter valid pickup and return dates and times.', 'car-rental-manager' ) );
				}
				if ( $end <= $start ) {
					return new WP_Error( 'date', __( 'The return must be after the pickup.', 'car-rental-manager' ) );
				}

				return compact( 'start', 'end', 'pd', 'pt', 'rd', 'rt' );
			}

			/**
			 * How many units of a car are free for the WHOLE period start→end.
			 *
			 * Counts every booking that overlaps the period (cancelled / refunded / failed
			 * ones don't count) and takes the PEAK number of vehicles out at the same time,
			 * so two short bookings that never overlap each other don't over-block.
			 *
			 * @return int[] { available, stock }
			 */
			private function availability( int $car_id, DateTime $start, DateTime $end ): array {
				$stock = (int) MPCRBM_Global_Function::get_post_info( $car_id, 'mpcrbm_car_stock', 1 );
				if ( $stock <= 0 ) {
					return array( 'available' => 0, 'stock' => 0 );
				}

				$ids = get_posts( array(
					'post_type'      => 'mpcrbm_booking',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'     => array(
						'relation' => 'AND',
						array( 'key' => 'mpcrbm_id', 'value' => $car_id, 'compare' => '=', 'type' => 'NUMERIC' ),
						array( 'key' => 'mpcrbm_date', 'value' => $end->format( 'Y-m-d H:i:s' ), 'compare' => '<', 'type' => 'DATETIME' ),
						array( 'key' => 'return_date_time', 'value' => $start->format( 'Y-m-d H:i:s' ), 'compare' => '>', 'type' => 'DATETIME' ),
						array( 'key' => 'mpcrbm_order_status', 'value' => array( 'cancelled', 'refunded', 'failed' ), 'compare' => 'NOT IN' ),
					),
				) );

				$tz     = wp_timezone();
				$events = array();
				foreach ( $ids as $bid ) {
					$bs = date_create( (string) get_post_meta( $bid, 'mpcrbm_date', true ), $tz );
					$be = date_create( (string) get_post_meta( $bid, 'return_date_time', true ), $tz );
					if ( ! $bs || ! $be ) {
						continue;
					}
					$qty = max( 1, (int) get_post_meta( $bid, 'mpcrbm_car_quantity', true ) );
					// Clip to the requested window.
					$s = max( $bs->getTimestamp(), $start->getTimestamp() );
					$e = min( $be->getTimestamp(), $end->getTimestamp() );
					if ( $e <= $s ) {
						continue;
					}
					$events[] = array( $s, $qty );
					$events[] = array( $e, -$qty );
				}
				// Ends sort before starts at the same instant (back-to-back bookings don't clash).
				usort( $events, static function ( $a, $b ) {
					return $a[0] === $b[0] ? $a[1] <=> $b[1] : $a[0] <=> $b[0];
				} );
				$cur  = 0;
				$peak = 0;
				foreach ( $events as $ev ) {
					$cur += $ev[1];
					$peak = max( $peak, $cur );
				}

				return array(
					'available' => max( 0, $stock - $peak ),
					'stock'     => $stock,
				);
			}

			/* --------------------------------------------------------------
			 * Shared builder: input -> priced booking draft
			 * ------------------------------------------------------------ */

			/**
			 * Validate the posted form and price it through the checkout's own functions.
			 *
			 * @return array|WP_Error { car_id, draft, system_rental, deposit, overridden, qty, start, end }
			 */
			private function prepare( array $in ) {
				$car_id = isset( $in['car_id'] ) ? absint( $in['car_id'] ) : 0;
				if ( ! $car_id || get_post_type( $car_id ) !== $this->cpt() || 'publish' !== get_post_status( $car_id ) ) {
					return new WP_Error( 'car', __( 'Please choose a vehicle.', 'car-rental-manager' ) );
				}

				$period = $this->parse_period( $in );
				if ( is_wp_error( $period ) ) {
					return $period;
				}
				$pd = $period['pd'];
				$pt = $period['pt'];
				$rd = $period['rd'];
				$rt = $period['rt'];

				$qty         = isset( $in['quantity'] ) ? max( 1, absint( $in['quantity'] ) ) : 1;
				$start_place = isset( $in['pickup_location'] ) ? sanitize_text_field( $in['pickup_location'] ) : '';
				$end_place   = isset( $in['dropoff_location'] ) ? sanitize_text_field( $in['dropoff_location'] ) : '';
				if ( '' === $end_place ) {
					$end_place = $start_place;
				}

				// Only services this car really offers (a name that matches nothing would
				// otherwise slip in as a free line).
				$allowed = wp_list_pluck( $this->car_services( $car_id ), 'name' );
				$names   = array();
				$qtys    = array();
				foreach ( (array) ( $in['svc'] ?? array() ) as $row ) {
					if ( ! is_array( $row ) || empty( $row['on'] ) ) {
						continue;
					}
					$name = sanitize_text_field( $row['name'] ?? '' );
					if ( '' === $name || ! in_array( $name, $allowed, true ) ) {
						continue;
					}
					$names[] = $name;
					$qtys[]  = max( 1, absint( $row['qty'] ?? 1 ) );
				}

				// The fare functions read $_POST (and verify the checkout's nonce), because
				// that is how the customer flow feeds them. Hand them exactly that shape,
				// slashed the way real request data is, and always restore the real $_POST.
				$fake = array(
					'mpcrbm_transportation_type_nonce' => wp_create_nonce( 'mpcrbm_transportation_type_nonce' ),
					'mpcrbm_start_place'               => $start_place,
					'mpcrbm_end_place'                 => $end_place,
					'mpcrbm_date'                      => $pd . ' ' . $pt,
					'mpcrbm_return_date'               => $rd,
					'mpcrbm_return_time'               => $this->hm_to_decimal( $rt ),
					'mpcrbm_car_quantity'              => $qty,
					'mpcrbm_extra_service'             => $names,
					'mpcrbm_extra_service_qty'         => $qtys,
				);

				$real_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$_POST     = wp_slash( $fake );
				try {
					$breakdown = array();
					$total     = (float) MPCRBM_Woocommerce::mpcrbm_get_cart_total_price( $car_id, $breakdown );
					$draft     = MPCRBM_Offline_Checkout::build_draft( $car_id, $total, $_POST, $breakdown );
				} finally {
					$_POST = $real_post;
				}

				$deposit       = (float) ( $draft['mpcrbm_security_deposit_amount'] ?? 0 );
				$system_rental = max( 0.0, (float) $draft['mpcrbm_tp'] - $deposit );

				// Optional admin override of the RENTAL total (vehicle + fees + services,
				// excluding the deposit, which always follows the car's own rule).
				$overridden = false;
				$custom_raw = isset( $in['custom_total'] ) ? trim( (string) $in['custom_total'] ) : '';
				if ( '' !== $custom_raw ) {
					if ( ! is_numeric( $custom_raw ) || (float) $custom_raw < 0 ) {
						return new WP_Error( 'price', __( 'The custom price must be a number, zero or more.', 'car-rental-manager' ) );
					}
					$custom                           = round( (float) $custom_raw, 2 );
					$diff                             = $custom - $system_rental;
					$draft['mpcrbm_base_price']       = max( 0, round( (float) $draft['mpcrbm_base_price'] + $diff / $qty, 2 ) );
					$draft['mpcrbm_tp']               = round( $custom + $deposit, 2 );
					$draft['mpcrbm_price_overridden'] = 1;
					$draft['mpcrbm_system_total']     = round( $system_rental + $deposit, 2 );
					$overridden                       = true;
				} elseif ( $total <= 0 ) {
					return new WP_Error( 'price', __( 'No price could be calculated for these dates. Check the vehicle\'s pricing, or enter a custom price.', 'car-rental-manager' ) );
				}

				return array(
					'car_id'        => $car_id,
					'draft'         => $draft,
					'system_rental' => $system_rental,
					'deposit'       => $deposit,
					'overridden'    => $overridden,
					'qty'           => $qty,
					'start'         => $period['start'],
					'end'           => $period['end'],
				);
			}

			/** Fail early, with a consistent message, on a bad nonce or missing permission. */
			private function guard() {
				check_ajax_referer( self::NONCE, 'nonce' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error( array( 'message' => __( 'You do not have permission to create bookings.', 'car-rental-manager' ) ), 403 );
				}
			}

			/* --------------------------------------------------------------
			 * AJAX: vehicles for a period
			 * ------------------------------------------------------------ */

			public function ajax_cars() {
				$this->guard();
				$in     = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
				$period = $this->parse_period( $in );
				if ( is_wp_error( $period ) ) {
					wp_send_json_error( array( 'message' => $period->get_error_message() ) );
				}
				$include_booked = ! empty( $in['include_booked'] );

				$cars = get_posts( array(
					'post_type'      => $this->cpt(),
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				) );

				$days    = (int) MPCRBM_Function::get_days_from_start_end_date( $period['pd'] . ' ' . $period['pt'], $period['rd'] . ' ' . $period['rt'] );
				$out     = array();
				$hidden  = 0;
				foreach ( $cars as $car ) {
					$av     = $this->availability( $car->ID, $period['start'], $period['end'] );
					$booked = $av['available'] < 1;
					if ( $booked && ! $include_booked ) {
						$hidden++;
						continue;
					}

					// Indicative fare for one vehicle from its first pickup point, so the admin
					// can compare cars at a glance. The real price is recalculated with the
					// chosen locations / services once a car is selected.
					$loc   = $this->car_locations( $car->ID );
					$first = $loc['pickups'][0] ?? '';
					$price = '';
					$res   = $this->prepare( array(
						'car_id'          => $car->ID,
						'pickup_date'     => $period['pd'],
						'pickup_time'     => $period['pt'],
						'return_date'     => $period['rd'],
						'return_time'     => $period['rt'],
						'quantity'        => 1,
						'pickup_location' => $first,
					) );
					if ( ! is_wp_error( $res ) ) {
						$price = $this->money( $res['system_rental'] );
					}

					$out[] = array(
						'id'        => $car->ID,
						'name'      => get_the_title( $car ),
						'thumb'     => (string) get_the_post_thumbnail_url( $car->ID, 'medium' ),
						'available' => $av['available'],
						'stock'     => $av['stock'],
						'booked'    => $booked,
						'price'     => $price,
					);
				}

				wp_send_json_success( array(
					'cars'   => $out,
					'hidden' => $hidden,
					'days'   => $days,
				) );
			}

			/* --------------------------------------------------------------
			 * AJAX: details for the chosen car (locations / services)
			 * ------------------------------------------------------------ */

			private function car_details( int $car_id ): array {
				return $this->car_locations( $car_id ) + array( 'services' => $this->car_services( $car_id ) );
			}

			/* --------------------------------------------------------------
			 * AJAX: price preview
			 * ------------------------------------------------------------ */

			public function ajax_quote() {
				$this->guard();
				$in  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().
				$res = $this->prepare( $in );
				if ( is_wp_error( $res ) ) {
					wp_send_json_error( array( 'message' => $res->get_error_message() ) );
				}

				$av    = $this->availability( $res['car_id'], $res['start'], $res['end'] );
				$draft = $res['draft'];
				wp_send_json_success( array(
					'days'              => (int) ( $draft['mpcrbm_rental_days'] ?? 1 ),
					'system_rental_fmt' => $this->money( $res['system_rental'] ),
					'rental_fmt'        => $this->money( max( 0, (float) $draft['mpcrbm_tp'] - $res['deposit'] ) ),
					'deposit'           => $res['deposit'],
					'deposit_fmt'       => $this->money( $res['deposit'] ),
					'total_fmt'         => $this->money( $draft['mpcrbm_tp'] ),
					'overridden'        => $res['overridden'],
					'available'         => $av['available'],
					'stock'             => $av['stock'],
					'qty'               => $res['qty'],
				) );
			}

			/* --------------------------------------------------------------
			 * AJAX: create the booking
			 * ------------------------------------------------------------ */

			public function ajax_create() {
				$this->guard();
				$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in guard().

				$name  = isset( $in['customer_name'] ) ? sanitize_text_field( $in['customer_name'] ) : '';
				$email = isset( $in['customer_email'] ) ? sanitize_email( $in['customer_email'] ) : '';
				$phone = isset( $in['customer_phone'] ) ? sanitize_text_field( $in['customer_phone'] ) : '';
				$note  = isset( $in['note'] ) ? sanitize_textarea_field( $in['note'] ) : '';
				if ( '' === $name || '' === $phone ) {
					wp_send_json_error( array( 'message' => __( 'Please enter the customer\'s name and phone number.', 'car-rental-manager' ) ) );
				}
				if ( '' !== $email && ! is_email( $email ) ) {
					wp_send_json_error( array( 'message' => __( 'The email address is not valid.', 'car-rental-manager' ) ) );
				}

				$res = $this->prepare( $in );
				if ( is_wp_error( $res ) ) {
					wp_send_json_error( array( 'message' => $res->get_error_message() ) );
				}
				$car_id = $res['car_id'];

				// Same stock rule the customer checkout aims for — but over the whole period
				// and against the requested quantity. An admin can knowingly override it
				// (a vehicle about to come back from another rental, say).
				$av = $this->availability( $car_id, $res['start'], $res['end'] );
				if ( $res['qty'] > $av['available'] && empty( $in['force'] ) ) {
					wp_send_json_error( array(
						'message' => sprintf(
							/* translators: 1: requested quantity, 2: available quantity. */
							__( 'Only %2$d of this vehicle is free for the whole period, but %1$d were requested. Choose a smaller quantity, or pick the vehicle from the "Include fully booked" list to book it anyway.', 'car-rental-manager' ),
							$res['qty'],
							$av['available']
						),
					) );
				}

				$statuses = array( 'pending', 'processing', 'completed', 'on-hold' );
				$status   = isset( $in['status'] ) && in_array( $in['status'], $statuses, true ) ? $in['status'] : 'pending';
				$methods  = array(
					'cash'  => __( 'Cash', 'car-rental-manager' ),
					'bank'  => __( 'Bank transfer', 'car-rental-manager' ),
					'card'  => __( 'Card (taken by phone)', 'car-rental-manager' ),
					'other' => __( 'Other', 'car-rental-manager' ),
				);
				$method_key = isset( $in['payment_method'] ) && isset( $methods[ $in['payment_method'] ] ) ? $in['payment_method'] : 'cash';

				// Link to an existing WordPress account when the email matches one, so the
				// customer sees the booking in their own account.
				$user    = '' !== $email ? get_user_by( 'email', $email ) : false;
				$user_id = $user ? (int) $user->ID : 0;

				$meta = array_merge( $res['draft'], array(
					'mpcrbm_order_id'        => 0,
					'mpcrbm_order_status'    => $status,
					'mpcrbm_payment_method'  => $methods[ $method_key ],
					'mpcrbm_payment_gateway' => 'admin',
					'mpcrbm_booking_source'  => 'admin',
					'mpcrbm_created_by'      => get_current_user_id(),
					'mpcrbm_user_id'         => $user_id,
					'mpcrbm_billing_name'    => $name,
					'mpcrbm_billing_email'   => $email,
					'mpcrbm_billing_phone'   => $phone,
					'mpcrbm_customer_note'   => $note,
					'mpcrbm_booking_date'    => current_time( 'mysql' ),
				) );
				$meta = apply_filters( 'mpcrbm_add_booking_data', $meta, $car_id );

				$booking_id = MPCRBM_Woocommerce::mpcrbm_cpt_data( 'mpcrbm_booking', $meta['mpcrbm_billing_name'], $meta );
				if ( ! $booking_id || is_wp_error( $booking_id ) ) {
					wp_send_json_error( array( 'message' => __( 'The booking could not be saved. Please try again.', 'car-rental-manager' ) ) );
				}
				$booking_id = absint( $booking_id );

				// Extra-service rows, mirroring both checkouts so service reports match.
				if ( ! empty( $meta['mpcrbm_service_info'] ) && is_array( $meta['mpcrbm_service_info'] ) ) {
					foreach ( $meta['mpcrbm_service_info'] as $service ) {
						MPCRBM_Woocommerce::mpcrbm_cpt_data( 'mpcrbm_service_booking', '#' . $booking_id . $service['service_name'], array(
							'mpcrbm_id'               => $car_id,
							'mpcrbm_date'             => $meta['mpcrbm_date'],
							'mpcrbm_order_id'         => 0,
							'mpcrbm_booking_id'       => $booking_id,
							'mpcrbm_order_status'     => $status,
							'mpcrbm_service_name'     => $service['service_name'],
							'mpcrbm_service_quantity' => $service['service_quantity'],
							'mpcrbm_service_price'    => $service['service_price'],
							'mpcrbm_payment_method'   => $methods[ $method_key ],
							'mpcrbm_user_id'          => $user_id,
						) );
					}
				}

				// Pro's confirmation mail listens on the action below and de-dupes on this
				// flag. When the admin does not want the customer emailed, set it first.
				$send_mail = ! empty( $in['send_email'] ) && '' !== $email;
				if ( ! $send_mail ) {
					update_post_meta( $booking_id, '_mpcrbm_booking_mail_sent', 1 );
				}

				/** Fires once a booking is fully recorded (customer or admin created). */
				do_action( 'mpcrbm_native_booking_created', $booking_id, $meta );

				if ( $send_mail && ! class_exists( 'MPCRBM_Native_Booking_Mail' ) ) {
					$this->send_customer_email( $booking_id, $meta );
				}

				wp_send_json_success( array(
					'booking_id' => $booking_id,
					'message'    => sprintf(
						/* translators: %d: booking id. */
						__( 'Booking #%d created.', 'car-rental-manager' ),
						$booking_id
					),
					'list_url'   => $this->list_url(),
				) );
			}

			/** Minimal customer email, used only when Pro's richer mail is not present. */
			private function send_customer_email( int $booking_id, array $meta ) {
				$to = $meta['mpcrbm_billing_email'];
				if ( ! is_email( $to ) ) {
					return;
				}
				$lines   = array();
				$lines[] = '<p>' . sprintf( /* translators: %s: customer name. */ esc_html__( 'Hi %s,', 'car-rental-manager' ), esc_html( $meta['mpcrbm_billing_name'] ) ) . '</p>';
				$lines[] = '<p>' . esc_html__( 'We have created a booking for you. Here are the details:', 'car-rental-manager' ) . '</p>';
				$lines[] = '<ul>';
				$lines[] = '<li><strong>' . esc_html__( 'Reference', 'car-rental-manager' ) . ':</strong> #' . esc_html( (string) $booking_id ) . '</li>';
				$lines[] = '<li><strong>' . esc_html__( 'Vehicle', 'car-rental-manager' ) . ':</strong> ' . esc_html( get_the_title( $meta['mpcrbm_id'] ) ) . '</li>';
				$lines[] = '<li><strong>' . esc_html__( 'Pickup', 'car-rental-manager' ) . ':</strong> ' . esc_html( $meta['mpcrbm_date'] ) . '</li>';
				if ( ! empty( $meta['return_date_time'] ) ) {
					$lines[] = '<li><strong>' . esc_html__( 'Return', 'car-rental-manager' ) . ':</strong> ' . esc_html( $meta['return_date_time'] ) . '</li>';
				}
				$lines[] = '<li><strong>' . esc_html__( 'Total', 'car-rental-manager' ) . ':</strong> ' . esc_html( $this->money( $meta['mpcrbm_tp'] ) ) . '</li>';
				$lines[] = '</ul>';

				$subject = sprintf( /* translators: %s: booking reference. */ __( 'Your booking #%s', 'car-rental-manager' ), $booking_id );
				$body    = apply_filters( 'mpcrbm_native_booking_email_body', implode( "\n", $lines ), $booking_id, $meta );
				wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
			}

			/* --------------------------------------------------------------
			 * Page
			 * ------------------------------------------------------------ */

			public function render_page() {
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_style( 'mp_jquery_ui', MPCRBM_PLUGIN_URL . 'mp_global/assets/jquery-ui.min.css', array(), '1.13.2' );
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				$times    = $this->time_options();
				$defaults = $this->default_period();

				// Locations + services for every car, so choosing a vehicle is instant.
				$details = array();
				foreach ( get_posts( array(
					'post_type'      => $this->cpt(),
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				) ) as $cid ) {
					$details[ $cid ] = $this->car_details( (int) $cid );
				}

				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Add Booking', 'car-rental-manager' ) );
				$this->render_styles();
				?>
				<div class="mpcrbm-ab" id="mpcrbm-ab">
					<div class="ab-head">
						<span class="ab-eyebrow"><?php esc_html_e( 'Booking management', 'car-rental-manager' ); ?></span>
						<h2><?php esc_html_e( 'Add Booking', 'car-rental-manager' ); ?></h2>
						<p><?php esc_html_e( 'Create a booking for a customer, for example during a phone call. Vehicles shown are the ones free for the whole period you choose.', 'car-rental-manager' ); ?></p>
					</div>

					<div id="ab-msg" role="status" aria-live="polite"></div>

					<form id="ab-form" autocomplete="off" novalidate>
						<input type="hidden" name="car_id" id="ab_car_id" value="">
						<input type="hidden" name="force" id="ab_force" value="">
						<div class="ab-layout">
							<div class="ab-main">

								<!-- 1 · Customer -->
								<section class="ab-card" data-step="1">
									<header class="ab-card-head"><span class="ab-num">1</span><div><h3><?php esc_html_e( 'Customer', 'car-rental-manager' ); ?></h3><p><?php esc_html_e( 'Who is the booking for?', 'car-rental-manager' ); ?></p></div></header>
									<div class="ab-grid ab-grid-3">
										<div class="ab-field"><label for="ab_name"><?php esc_html_e( 'Full name', 'car-rental-manager' ); ?> <b>*</b></label><input type="text" id="ab_name" name="customer_name" placeholder="<?php esc_attr_e( 'e.g. Rahim Uddin', 'car-rental-manager' ); ?>"></div>
										<div class="ab-field"><label for="ab_phone"><?php esc_html_e( 'Phone', 'car-rental-manager' ); ?> <b>*</b></label><input type="text" id="ab_phone" name="customer_phone" placeholder="<?php esc_attr_e( 'e.g. +880 1…', 'car-rental-manager' ); ?>"></div>
										<div class="ab-field"><label for="ab_email"><?php esc_html_e( 'Email', 'car-rental-manager' ); ?> <span class="ab-opt"><?php esc_html_e( 'optional', 'car-rental-manager' ); ?></span></label><input type="email" id="ab_email" name="customer_email" placeholder="name@example.com"></div>
									</div>
								</section>

								<!-- 2 · Period -->
								<section class="ab-card" data-step="2">
									<header class="ab-card-head"><span class="ab-num">2</span><div><h3><?php esc_html_e( 'Pickup & return', 'car-rental-manager' ); ?></h3><p><?php esc_html_e( 'Changing any date or time reloads the available vehicles.', 'car-rental-manager' ); ?></p></div><span class="ab-chip" id="ab_duration"></span></header>
									<div class="ab-period">
										<div class="ab-period-col">
											<span class="ab-period-title"><i class="ab-dot ab-dot-go"></i><?php esc_html_e( 'Pickup', 'car-rental-manager' ); ?></span>
											<div class="ab-grid ab-grid-2">
												<div class="ab-field"><label for="ab_pd"><?php esc_html_e( 'Date', 'car-rental-manager' ); ?></label><input type="text" class="ab-date" autocomplete="off" readonly id="ab_pd" name="pickup_date" value="<?php echo esc_attr( $defaults['pickup_date'] ); ?>"></div>
												<div class="ab-field"><label for="ab_pt"><?php esc_html_e( 'Time', 'car-rental-manager' ); ?></label><select id="ab_pt" name="pickup_time"><?php foreach ( $times as $t ) : ?><option value="<?php echo esc_attr( $t ); ?>"<?php selected( $t, $defaults['pickup_time'] ); ?>><?php echo esc_html( $t ); ?></option><?php endforeach; ?></select></div>
											</div>
										</div>
										<div class="ab-period-arrow" aria-hidden="true">→</div>
										<div class="ab-period-col">
											<span class="ab-period-title"><i class="ab-dot ab-dot-stop"></i><?php esc_html_e( 'Return', 'car-rental-manager' ); ?></span>
											<div class="ab-grid ab-grid-2">
												<div class="ab-field"><label for="ab_rd"><?php esc_html_e( 'Date', 'car-rental-manager' ); ?></label><input type="text" class="ab-date" autocomplete="off" readonly id="ab_rd" name="return_date" value="<?php echo esc_attr( $defaults['return_date'] ); ?>"></div>
												<div class="ab-field"><label for="ab_rt"><?php esc_html_e( 'Time', 'car-rental-manager' ); ?></label><select id="ab_rt" name="return_time"><?php foreach ( $times as $t ) : ?><option value="<?php echo esc_attr( $t ); ?>"<?php selected( $t, $defaults['return_time'] ); ?>><?php echo esc_html( $t ); ?></option><?php endforeach; ?></select></div>
											</div>
										</div>
									</div>
									<div class="ab-inline-error" id="ab_period_error" hidden></div>
								</section>

								<!-- 3 · Vehicle -->
								<section class="ab-card" data-step="3">
									<header class="ab-card-head"><span class="ab-num">3</span><div><h3><?php esc_html_e( 'Choose a vehicle', 'car-rental-manager' ); ?></h3><p id="ab_cars_sub"><?php esc_html_e( 'Free for the whole period.', 'car-rental-manager' ); ?></p></div>
										<label class="ab-switch"><input type="checkbox" id="ab_include_booked"><span><?php esc_html_e( 'Include fully booked', 'car-rental-manager' ); ?></span></label>
									</header>
									<div id="ab_cars" class="ab-cars" aria-live="polite"></div>
								</section>

								<!-- 4 · Vehicle details (unlocked after a vehicle is chosen) -->
								<section class="ab-card is-locked" id="ab_step4" data-step="4">
									<header class="ab-card-head"><span class="ab-num">4</span><div><h3><?php esc_html_e( 'Trip details', 'car-rental-manager' ); ?></h3><p><?php esc_html_e( 'Quantity, locations and extras for the chosen vehicle.', 'car-rental-manager' ); ?></p></div></header>
									<div class="ab-lock-note"><?php esc_html_e( 'Choose a vehicle first.', 'car-rental-manager' ); ?></div>
									<div class="ab-step-body">
										<div class="ab-grid ab-grid-3">
											<div class="ab-field"><label for="ab_qty"><?php esc_html_e( 'Quantity', 'car-rental-manager' ); ?></label><input type="number" id="ab_qty" name="quantity" min="1" value="1"><div class="ab-hint" id="ab_qty_hint"></div></div>
											<div class="ab-field" id="ab_pickup_wrap"><label for="ab_pickup"><?php esc_html_e( 'Pickup location', 'car-rental-manager' ); ?></label><select id="ab_pickup" name="pickup_location"></select></div>
											<div class="ab-field" id="ab_dropoff_wrap"><label for="ab_dropoff"><?php esc_html_e( 'Return location', 'car-rental-manager' ); ?></label><select id="ab_dropoff" name="dropoff_location"></select></div>
										</div>
										<div id="ab_services_wrap" hidden>
											<span class="ab-sub"><?php esc_html_e( 'Extra services', 'car-rental-manager' ); ?></span>
											<div id="ab_services" class="ab-services"></div>
										</div>
									</div>
								</section>

								<!-- 5 · Payment -->
								<section class="ab-card is-locked" id="ab_step5" data-step="5">
									<header class="ab-card-head"><span class="ab-num">5</span><div><h3><?php esc_html_e( 'Payment & notes', 'car-rental-manager' ); ?></h3><p><?php esc_html_e( 'How is the customer paying, and anything to remember.', 'car-rental-manager' ); ?></p></div></header>
									<div class="ab-lock-note"><?php esc_html_e( 'Choose a vehicle first.', 'car-rental-manager' ); ?></div>
									<div class="ab-step-body">
										<div class="ab-grid ab-grid-2">
											<div class="ab-field"><label for="ab_status"><?php esc_html_e( 'Booking status', 'car-rental-manager' ); ?></label>
												<select id="ab_status" name="status">
													<option value="pending"><?php esc_html_e( 'Pending payment', 'car-rental-manager' ); ?></option>
													<option value="processing"><?php esc_html_e( 'Confirmed (paid)', 'car-rental-manager' ); ?></option>
													<option value="on-hold"><?php esc_html_e( 'On hold', 'car-rental-manager' ); ?></option>
													<option value="completed"><?php esc_html_e( 'Completed', 'car-rental-manager' ); ?></option>
												</select></div>
											<div class="ab-field"><label for="ab_method"><?php esc_html_e( 'Payment method', 'car-rental-manager' ); ?></label>
												<select id="ab_method" name="payment_method">
													<option value="cash"><?php esc_html_e( 'Cash', 'car-rental-manager' ); ?></option>
													<option value="bank"><?php esc_html_e( 'Bank transfer', 'car-rental-manager' ); ?></option>
													<option value="card"><?php esc_html_e( 'Card (taken by phone)', 'car-rental-manager' ); ?></option>
													<option value="other"><?php esc_html_e( 'Other', 'car-rental-manager' ); ?></option>
												</select></div>
										</div>
										<div class="ab-field" style="margin-top:14px"><label for="ab_note"><?php esc_html_e( 'Note', 'car-rental-manager' ); ?></label><textarea id="ab_note" name="note" rows="3" placeholder="<?php esc_attr_e( 'Anything the team should know about this booking…', 'car-rental-manager' ); ?>"></textarea></div>
										<label class="ab-check"><input type="checkbox" name="send_email" value="1" checked> <span><?php esc_html_e( 'Email the booking details to the customer (needs an email address)', 'car-rental-manager' ); ?></span></label>
									</div>
								</section>
							</div>

							<!-- Summary -->
							<aside class="ab-side">
								<div class="ab-summary">
									<h3><?php esc_html_e( 'Booking summary', 'car-rental-manager' ); ?></h3>
									<dl class="ab-sum-list">
										<div><dt><?php esc_html_e( 'Customer', 'car-rental-manager' ); ?></dt><dd id="sum_customer">—</dd></div>
										<div><dt><?php esc_html_e( 'Pickup', 'car-rental-manager' ); ?></dt><dd id="sum_pickup">—</dd></div>
										<div><dt><?php esc_html_e( 'Return', 'car-rental-manager' ); ?></dt><dd id="sum_return">—</dd></div>
										<div><dt><?php esc_html_e( 'Vehicle', 'car-rental-manager' ); ?></dt><dd id="sum_car">—</dd></div>
									</dl>
									<div class="ab-price" id="ab_price"><span class="ab-price-empty"><?php esc_html_e( 'Choose a vehicle to see the price.', 'car-rental-manager' ); ?></span></div>
									<div class="ab-field ab-custom">
										<label for="ab_custom"><?php esc_html_e( 'Custom rental total', 'car-rental-manager' ); ?> <span class="ab-opt"><?php esc_html_e( 'optional', 'car-rental-manager' ); ?></span></label>
										<input type="number" step="0.01" min="0" id="ab_custom" name="custom_total" placeholder="<?php esc_attr_e( 'Use system price', 'car-rental-manager' ); ?>" disabled>
										<div class="ab-hint"><?php esc_html_e( 'Overrides vehicle + fees + extras, up or down. The security deposit is added on top.', 'car-rental-manager' ); ?></div>
									</div>
									<button type="submit" class="ab-btn ab-btn-primary" id="ab_submit" disabled><?php esc_html_e( 'Create booking', 'car-rental-manager' ); ?></button>
									<a class="ab-btn ab-btn-ghost" href="<?php echo esc_url( $this->list_url() ); ?>"><?php esc_html_e( 'Back to bookings', 'car-rental-manager' ); ?></a>
								</div>
							</aside>
						</div>
					</form>
				</div>
				<?php
				$this->render_script( $details, $defaults );
				MPCRBM_Admin_Shell::render_shell_close();
			}

			private function render_styles() {
				?>
				<style>
				.mpcrbm-ab{--ab-bg:#f5f7fb;--ab-card:#fff;--ab-line:#e5e9f2;--ab-ink:#0f172a;--ab-mute:#64748b;--ab-accent:#2563eb;--ab-accent-soft:#eaf1ff;--ab-ok:#15803d;--ab-ok-soft:#e8f7ee;--ab-warn:#b45309;--ab-warn-soft:#fff4e5;--ab-bad:#b42318;--ab-bad-soft:#fdecec;--ab-r:14px;color:var(--ab-ink);max-width:1240px}
				.mpcrbm-ab *{box-sizing:border-box}
				.mpcrbm-ab .ab-head{margin:0 0 20px}
				.mpcrbm-ab .ab-eyebrow{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ab-accent);background:var(--ab-accent-soft);padding:3px 10px;border-radius:999px}
				.mpcrbm-ab .ab-head h2{margin:8px 0 4px;font-size:26px;line-height:1.2;padding:0}
				.mpcrbm-ab .ab-head p{margin:0;color:var(--ab-mute);font-size:14px}
				.mpcrbm-ab #ab-msg:empty{display:none}
				.mpcrbm-ab .ab-alert{border-radius:12px;padding:12px 16px;margin:0 0 16px;font-size:14px;display:flex;gap:10px;align-items:flex-start}
				.mpcrbm-ab .ab-alert.is-ok{background:var(--ab-ok-soft);color:var(--ab-ok)}
				.mpcrbm-ab .ab-alert.is-bad{background:var(--ab-bad-soft);color:var(--ab-bad)}
				.mpcrbm-ab .ab-alert a{font-weight:700;color:inherit;text-decoration:underline}
				.mpcrbm-ab .ab-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:22px;align-items:start}
				.mpcrbm-ab .ab-main{display:flex;flex-direction:column;gap:16px;min-width:0}
				.mpcrbm-ab .ab-card{background:var(--ab-card);border:1px solid var(--ab-line);border-radius:var(--ab-r);padding:20px 22px;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:opacity .2s}
				.mpcrbm-ab .ab-card-head{display:flex;align-items:center;gap:14px;margin:0 0 16px}
				.mpcrbm-ab .ab-card-head>div{flex:1;min-width:0}
				.mpcrbm-ab .ab-card-head h3{margin:0;font-size:16px;padding:0}
				.mpcrbm-ab .ab-card-head p{margin:2px 0 0;color:var(--ab-mute);font-size:13px}
				.mpcrbm-ab .ab-num{width:30px;height:30px;border-radius:50%;background:var(--ab-accent);color:#fff;font-weight:700;font-size:14px;display:inline-flex;align-items:center;justify-content:center;flex:none}
				.mpcrbm-ab .ab-card.is-locked .ab-num{background:#cbd5e1}
				.mpcrbm-ab .ab-card.is-locked .ab-step-body{display:none}
				.mpcrbm-ab .ab-card:not(.is-locked) .ab-lock-note{display:none}
				.mpcrbm-ab .ab-lock-note{background:var(--ab-bg);border:1px dashed var(--ab-line);border-radius:10px;color:var(--ab-mute);font-size:13px;padding:14px;text-align:center}
				.mpcrbm-ab .ab-grid{display:grid;gap:14px}
				.mpcrbm-ab .ab-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
				.mpcrbm-ab .ab-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
				.mpcrbm-ab .ab-field label{display:block;font-size:12px;font-weight:600;color:#334155;margin:0 0 5px}
				.mpcrbm-ab .ab-field label b{color:var(--ab-bad)}
				.mpcrbm-ab .ab-opt{font-weight:500;color:var(--ab-mute);font-size:11px;margin-left:4px}
.mpcrbm-ab .ab-date{cursor:pointer;padding-right:36px!important;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%236b7280' stroke-width='2' viewBox='0 0 24 24'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cpath d='M16 2v4M8 2v4M3 10h18'/%3E%3C/svg%3E") no-repeat right 12px center!important}
				#ui-datepicker-div{z-index:100000!important;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,.18);padding:8px;font-size:13px}
				.mpcrbm-ab .ab-field input[type=text],.mpcrbm-ab .ab-field input[type=email],.mpcrbm-ab .ab-field input[type=date],.mpcrbm-ab .ab-field input[type=number],.mpcrbm-ab .ab-field select,.mpcrbm-ab .ab-field textarea{width:100%;max-width:100%;min-height:42px;padding:9px 12px;border:1px solid #d5dbe7;border-radius:10px;background:#fff;font-size:14px;color:var(--ab-ink);box-shadow:none;transition:border-color .15s,box-shadow .15s}
				.mpcrbm-ab .ab-field textarea{min-height:84px;resize:vertical}
				.mpcrbm-ab .ab-field input:focus,.mpcrbm-ab .ab-field select:focus,.mpcrbm-ab .ab-field textarea:focus{outline:0;border-color:var(--ab-accent);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
				.mpcrbm-ab .ab-field input:disabled{background:#f1f5f9;cursor:not-allowed}
				.mpcrbm-ab .ab-field input.has-error{border-color:var(--ab-bad);box-shadow:0 0 0 3px rgba(180,35,24,.12)}
				.mpcrbm-ab .ab-hint{font-size:12px;color:var(--ab-mute);margin-top:5px}
				.mpcrbm-ab .ab-sub{display:block;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--ab-mute);margin:18px 0 8px}
				.mpcrbm-ab .ab-chip{background:var(--ab-accent-soft);color:var(--ab-accent);font-weight:700;font-size:12px;padding:5px 12px;border-radius:999px;white-space:nowrap}
				.mpcrbm-ab .ab-chip:empty{display:none}
				.mpcrbm-ab .ab-period{display:grid;grid-template-columns:minmax(0,1fr) 30px minmax(0,1fr);gap:10px;align-items:end}
				.mpcrbm-ab .ab-period-title{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--ab-mute);margin-bottom:8px}
				.mpcrbm-ab .ab-dot{width:9px;height:9px;border-radius:50%;display:inline-block}
				.mpcrbm-ab .ab-dot-go{background:#16a34a}.mpcrbm-ab .ab-dot-stop{background:#dc2626}
				.mpcrbm-ab .ab-period-arrow{color:#94a3b8;font-size:20px;text-align:center;padding-bottom:10px}
				.mpcrbm-ab .ab-inline-error{margin-top:12px;background:var(--ab-bad-soft);color:var(--ab-bad);border-radius:10px;padding:10px 14px;font-size:13px;font-weight:600}
				.mpcrbm-ab .ab-switch{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--ab-mute);cursor:pointer;white-space:nowrap}
				.mpcrbm-ab .ab-cars{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px;min-height:60px}
				.mpcrbm-ab .ab-car{position:relative;border:2px solid var(--ab-line);border-radius:12px;overflow:hidden;background:#fff;cursor:pointer;text-align:left;padding:0;display:flex;flex-direction:column;transition:border-color .15s,box-shadow .15s,transform .15s}
				.mpcrbm-ab .ab-car:hover{border-color:#bcd0f7;box-shadow:0 6px 18px rgba(37,99,235,.10);transform:translateY(-1px)}
				.mpcrbm-ab .ab-car:focus-visible{outline:0;border-color:var(--ab-accent);box-shadow:0 0 0 3px rgba(37,99,235,.2)}
				.mpcrbm-ab .ab-car.is-selected{border-color:var(--ab-accent);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
				.mpcrbm-ab .ab-car.is-selected::after{content:"✓";position:absolute;top:10px;right:10px;width:26px;height:26px;border-radius:50%;background:var(--ab-accent);color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:14px}
				.mpcrbm-ab .ab-car-img{aspect-ratio:16/10;background:linear-gradient(135deg,#eef2f9,#dfe7f5);display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:34px;overflow:hidden}
				.mpcrbm-ab .ab-car-img img{width:100%;height:100%;object-fit:cover;display:block}
				.mpcrbm-ab .ab-car-body{padding:12px 14px 14px;display:flex;flex-direction:column;gap:6px}
				.mpcrbm-ab .ab-car-name{font-weight:700;font-size:14px;line-height:1.3}
				.mpcrbm-ab .ab-car-row{display:flex;align-items:center;justify-content:space-between;gap:8px}
				.mpcrbm-ab .ab-car-price{font-weight:800;font-size:15px;color:var(--ab-accent)}
				.mpcrbm-ab .ab-badge{font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;white-space:nowrap}
				.mpcrbm-ab .ab-badge.is-ok{background:var(--ab-ok-soft);color:var(--ab-ok)}
				.mpcrbm-ab .ab-badge.is-low{background:var(--ab-warn-soft);color:var(--ab-warn)}
				.mpcrbm-ab .ab-badge.is-out{background:var(--ab-bad-soft);color:var(--ab-bad)}
				.mpcrbm-ab .ab-car.is-booked .ab-car-img{filter:grayscale(.8);opacity:.7}
				.mpcrbm-ab .ab-cars-state{grid-column:1/-1;text-align:center;color:var(--ab-mute);font-size:14px;padding:26px 10px;background:var(--ab-bg);border-radius:12px;border:1px dashed var(--ab-line)}
				.mpcrbm-ab .ab-skel{height:206px;border-radius:12px;background:linear-gradient(90deg,#eef1f7 25%,#f7f9fc 37%,#eef1f7 63%);background-size:400% 100%;animation:abshimmer 1.3s ease infinite}
				@keyframes abshimmer{0%{background-position:100% 50%}100%{background-position:0 50%}}
				.mpcrbm-ab .ab-services{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px}
				.mpcrbm-ab .ab-svc{display:flex;align-items:center;gap:10px;border:1px solid var(--ab-line);border-radius:10px;padding:10px 12px;background:#fff}
				.mpcrbm-ab .ab-svc.is-on{border-color:var(--ab-accent);background:var(--ab-accent-soft)}
				.mpcrbm-ab .ab-svc .nm{flex:1;font-size:13px;font-weight:600;min-width:0}
				.mpcrbm-ab .ab-svc .nm small{display:block;color:var(--ab-mute);font-weight:500;font-size:12px}
				.mpcrbm-ab .ab-svc input[type=number]{width:62px;min-height:34px;padding:4px 8px;border:1px solid #d5dbe7;border-radius:8px}
				.mpcrbm-ab .ab-check{display:flex;gap:10px;align-items:flex-start;margin-top:16px;font-size:13px;color:#334155;cursor:pointer}
				.mpcrbm-ab .ab-side{position:sticky;top:42px}
				.mpcrbm-ab .ab-summary{background:var(--ab-card);border:1px solid var(--ab-line);border-radius:var(--ab-r);padding:20px;box-shadow:0 8px 24px rgba(15,23,42,.06)}
				.mpcrbm-ab .ab-summary h3{margin:0 0 12px;font-size:16px;padding:0}
				.mpcrbm-ab .ab-sum-list{margin:0 0 14px}
				.mpcrbm-ab .ab-sum-list>div{display:flex;justify-content:space-between;gap:14px;padding:8px 0;border-bottom:1px solid #f0f2f7;font-size:13px}
				.mpcrbm-ab .ab-sum-list dt{color:var(--ab-mute);margin:0}
				.mpcrbm-ab .ab-sum-list dd{margin:0;font-weight:600;text-align:right;word-break:break-word}
				.mpcrbm-ab .ab-price{background:var(--ab-bg);border-radius:12px;padding:14px;margin-bottom:14px;font-size:13px}
				.mpcrbm-ab .ab-price-empty{color:var(--ab-mute)}
				.mpcrbm-ab .ab-price-row{display:flex;justify-content:space-between;padding:3px 0;color:#334155}
				.mpcrbm-ab .ab-price-total{display:flex;justify-content:space-between;align-items:baseline;border-top:1px solid #dfe5f0;margin-top:8px;padding-top:10px;font-weight:700}
				.mpcrbm-ab .ab-price-total strong{font-size:22px;color:var(--ab-ink)}
				.mpcrbm-ab .ab-tag{display:inline-block;margin-top:8px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px}
				.mpcrbm-ab .ab-tag.is-ok{background:var(--ab-ok-soft);color:var(--ab-ok)}
				.mpcrbm-ab .ab-tag.is-warn{background:var(--ab-warn-soft);color:var(--ab-warn)}
				.mpcrbm-ab .ab-tag.is-bad{background:var(--ab-bad-soft);color:var(--ab-bad)}
				.mpcrbm-ab .ab-custom{margin-bottom:14px}
				.mpcrbm-ab .ab-btn{display:flex;align-items:center;justify-content:center;width:100%;min-height:46px;border-radius:12px;font-size:15px;font-weight:700;border:0;cursor:pointer;text-decoration:none;transition:background .15s,transform .05s,opacity .15s}
				.mpcrbm-ab .ab-btn:active{transform:translateY(1px)}
				.mpcrbm-ab .ab-btn-primary{background:var(--ab-accent);color:#fff}
				.mpcrbm-ab .ab-btn-primary:hover:not(:disabled){background:#1d4ed8;color:#fff}
				.mpcrbm-ab .ab-btn-primary:disabled{background:#c3cde0;cursor:not-allowed}
				.mpcrbm-ab .ab-btn-ghost{background:transparent;color:var(--ab-mute);margin-top:8px;min-height:38px;font-weight:600;font-size:13px}
				.mpcrbm-ab .ab-btn-ghost:hover{color:var(--ab-ink)}
				@media (max-width:1100px){.mpcrbm-ab .ab-layout{grid-template-columns:1fr}.mpcrbm-ab .ab-side{position:static}}
				@media (max-width:700px){.mpcrbm-ab .ab-grid-3,.mpcrbm-ab .ab-grid-2{grid-template-columns:1fr}.mpcrbm-ab .ab-period{grid-template-columns:1fr}.mpcrbm-ab .ab-period-arrow{display:none}.mpcrbm-ab .ab-card{padding:16px}.mpcrbm-ab .ab-card-head{flex-wrap:wrap}}
				@media (prefers-reduced-motion:reduce){.mpcrbm-ab *{transition:none!important;animation:none!important}}
				</style>
				<?php
			}

			private function render_script( array $details, array $defaults ) {
				$i18n = array(
					'days'        => __( 'day(s)', 'car-rental-manager' ),
					'loading'     => __( 'Finding available vehicles…', 'car-rental-manager' ),
					'none'        => __( 'No vehicle is free for this whole period. Try different dates or times — or tick "Include fully booked".', 'car-rental-manager' ),
					'hiddenN'     => __( '%d vehicle(s) hidden because they are fully booked in this period.', 'car-rental-manager' ),
					'sub'         => __( 'Free for the whole period.', 'car-rental-manager' ),
					'subAll'      => __( 'Showing every vehicle; fully booked ones are marked.', 'car-rental-manager' ),
					'left'        => __( '%d free', 'car-rental-manager' ),
					'booked'      => __( 'Fully booked', 'car-rental-manager' ),
					'perday'      => __( 'per day', 'car-rental-manager' ),
					'qty'         => __( 'Qty', 'car-rental-manager' ),
					'rental'      => __( 'Rental', 'car-rental-manager' ),
					'deposit'     => __( 'Security deposit', 'car-rental-manager' ),
					'total'       => __( 'Total', 'car-rental-manager' ),
					'system'      => __( 'System price', 'car-rental-manager' ),
					'over'        => __( 'Custom price applied', 'car-rental-manager' ),
					'availOk'     => __( '%1$d of %2$d free for the whole period', 'car-rental-manager' ),
					'availBad'    => __( 'Booking over the free quantity — will be created as an override', 'car-rental-manager' ),
					'maxHint'     => __( 'Up to %d available', 'car-rental-manager' ),
					'pricePrompt' => __( 'Choose a vehicle to see the price.', 'car-rental-manager' ),
					'creating'    => __( 'Creating…', 'car-rental-manager' ),
					'create'      => __( 'Create booking', 'car-rental-manager' ),
					'viewlist'    => __( 'View bookings', 'car-rental-manager' ),
					'another'     => __( 'Add another booking', 'car-rental-manager' ),
					'needName'    => __( 'Please enter the customer\'s name and phone number.', 'car-rental-manager' ),
					'needCar'     => __( 'Please choose a vehicle.', 'car-rental-manager' ),
					'error'       => __( 'Request failed. Please try again.', 'car-rental-manager' ),
					'badPeriod'   => __( 'The return must be after the pickup.', 'car-rental-manager' ),
					'badDate'     => __( 'Please enter valid pickup and return dates and times.', 'car-rental-manager' ),
					'dash'        => '—',
				);
				?>
				<script>
				jQuery(function ($) {
					var DETAILS  = <?php echo wp_json_encode( $details ); ?>;
					var DEFAULTS = <?php echo wp_json_encode( $defaults ); ?>;
					var NONCE    = <?php echo wp_json_encode( wp_create_nonce( self::NONCE ) ); ?>;
					var T        = <?php echo wp_json_encode( $i18n ); ?>;

					var $form = $('#ab-form'), $msg = $('#ab-msg'), $cars = $('#ab_cars');
					var carsReq = 0, quoteReq = 0, carsTimer = null, quoteTimer = null, busy = false;
					var current = null;           // { id, name, available, stock, booked }
					var durationMs = 24 * 3600 * 1000;

					function esc(s){ return $('<div>').text(s == null ? '' : String(s)).html(); }
					function fmt(tpl){ var a = Array.prototype.slice.call(arguments, 1), i = 0; return tpl.replace(/%(\d+\$)?d/g, function(){ return a[i++]; }); }
					function pad(n){ return (n < 10 ? '0' : '') + n; }
					function toDate(d, t){ var p = (d || '').split('-'), h = (t || '00:00').split(':'); if (p.length !== 3) { return null; } var x = new Date(+p[0], +p[1]-1, +p[2], +h[0], +h[1]); return isNaN(x) ? null : x; }
					function dateStr(x){ return x.getFullYear() + '-' + pad(x.getMonth()+1) + '-' + pad(x.getDate()); }
					function timeStr(x){ return pad(x.getHours()) + ':' + pad(x.getMinutes()); }
					function human(d, t){ var x = toDate(d, t); return x ? x.toLocaleDateString(undefined, {weekday:'short', day:'numeric', month:'short', year:'numeric'}) + ' · ' + t : T.dash; }
					function alertBox(type, html){ $msg.html('<div class="ab-alert is-' + type + '">' + html + '</div>'); $('html,body').animate({scrollTop: $('#mpcrbm-ab').offset().top - 50}, 200); }

					function period(){ return { pd: $('#ab_pd').val(), pt: $('#ab_pt').val(), rd: $('#ab_rd').val(), rt: $('#ab_rt').val() }; }
					function periodError(){
						var p = period(), s = toDate(p.pd, p.pt), e = toDate(p.rd, p.rt);
						if (!s || !e) { return T.badDate; }
						if (e <= s) { return T.badPeriod; }
						return '';
					}

					/* ---- summary panel ---- */
					function updateSummary(){
						var p = period();
						$('#sum_customer').text(($('#ab_name').val() || '').trim() || T.dash);
						$('#sum_pickup').text(human(p.pd, p.pt));
						$('#sum_return').text(human(p.rd, p.rt));
						$('#sum_car').text(current ? current.name : T.dash);
						var s = toDate(p.pd, p.pt), e = toDate(p.rd, p.rt);
						if (s && e && e > s) { $('#ab_duration').text(Math.max(1, Math.ceil((e - s) / 86400000)) + ' ' + T.days); }
						else { $('#ab_duration').text(''); }
					}
					T.days = T.days || 'day(s)';

					/* ---- step locking ---- */
					function lockDetails(lock){
						$('#ab_step4, #ab_step5').toggleClass('is-locked', lock);
						$('#ab_custom').prop('disabled', lock);
						$('#ab_submit').prop('disabled', lock);
						if (lock) {
							$('#ab_price').html('<span class="ab-price-empty">' + esc(T.pricePrompt) + '</span>');
							$('#ab_custom').val('');
						}
					}
					function clearSelection(){
						current = null;
						$('#ab_car_id').val(''); $('#ab_force').val('');
						$('#ab_qty').val(1);
						lockDetails(true);
						updateSummary();
					}

					/* ---- vehicles for the period ---- */
					function showPeriodError(msg){
						var $e = $('#ab_period_error');
						if (msg) { $e.text(msg).prop('hidden', false); } else { $e.prop('hidden', true).text(''); }
						$('#ab_pd,#ab_pt,#ab_rd,#ab_rt').toggleClass('has-error', !!msg);
					}
					function showSkeleton(){
						$cars.empty();
						for (var i = 0; i < 4; i++) { $cars.append('<div class="ab-skel"></div>'); }
						$('#ab_cars_sub').text(T.loading);
					}
					function scheduleCars(){
						// Any date/time change: drop the chosen vehicle AND empty the list at once
						// (a car shown for the old period must never be clickable for the new
						// one), and invalidate any request still in flight for the old period.
						clearSelection();
						clearTimeout(carsTimer);
						carsReq++;
						var err = periodError();
						showPeriodError(err);
						if (err) { $cars.html('<div class="ab-cars-state">' + esc(err) + '</div>'); $('#ab_cars_sub').text(T.sub); return; }
						showSkeleton();
						carsTimer = setTimeout(loadCars, 300);
					}
					function loadCars(){
						var my = ++carsReq, p = period();
						$.post(ajaxurl, {
							action: 'mpcrbm_admin_booking_cars', nonce: NONCE,
							pickup_date: p.pd, pickup_time: p.pt, return_date: p.rd, return_time: p.rt,
							include_booked: $('#ab_include_booked').is(':checked') ? 1 : ''
						}).done(function(r){
							if (my !== carsReq) { return; }
							$('#ab_cars_sub').text($('#ab_include_booked').is(':checked') ? T.subAll : T.sub);
							if (!r || !r.success) {
								$cars.html('<div class="ab-cars-state">' + esc((r && r.data && r.data.message) || T.error) + '</div>');
								return;
							}
							renderCars(r.data);
						}).fail(function(){
							if (my !== carsReq) { return; }
							$('#ab_cars_sub').text(T.sub);
							$cars.html('<div class="ab-cars-state">' + esc(T.error) + '</div>');
						});
					}
					function renderCars(d){
						$cars.empty();
						if (!d.cars.length) {
							$cars.append('<div class="ab-cars-state">' + esc(T.none) + '</div>');
						}
						$.each(d.cars, function(_, c){
							var badge = c.booked ? '<span class="ab-badge is-out">' + esc(T.booked) + '</span>'
								: '<span class="ab-badge ' + (c.available <= 1 ? 'is-low' : 'is-ok') + '">' + esc(fmt(T.left, c.available)) + '</span>';
							var img = c.thumb ? '<img loading="lazy" src="' + esc(c.thumb) + '" alt="">' : '🚗';
							var $b = $('<button type="button" class="ab-car' + (c.booked ? ' is-booked' : '') + '" data-id="' + c.id + '">' +
								'<span class="ab-car-img">' + img + '</span>' +
								'<span class="ab-car-body"><span class="ab-car-name">' + esc(c.name) + '</span>' +
								'<span class="ab-car-row"><span class="ab-car-price">' + esc(c.price || T.dash) + '</span>' + badge + '</span></span>' +
								'</button>');
							$b.data('car', c);
							$cars.append($b);
						});
						if (d.hidden > 0) {
							$cars.append('<div class="ab-cars-state">' + esc(fmt(T.hiddenN, d.hidden)) + '</div>');
						}
					}

					/* ---- choose a vehicle ---- */
					$cars.on('click', '.ab-car', function(){
						var c = $(this).data('car');
						$('.ab-car').removeClass('is-selected'); $(this).addClass('is-selected');
						current = c;
						$('#ab_car_id').val(c.id);
						$('#ab_force').val(c.booked ? 1 : '');
						$('#ab_qty').val(1).attr('max', c.booked ? '' : c.available);
						$('#ab_qty_hint').text(c.booked ? '' : fmt(T.maxHint, c.available));
						fillLocations(c.id); fillServices(c.id);
						lockDetails(false);
						$('#ab_custom').val('');
						updateSummary();
						requote();
					});

					function fillLocations(id){
						var d = DETAILS[id] || {pickups:[], dropoffs:{}};
						var $p = $('#ab_pickup').empty();
						$.each(d.pickups, function(_, l){ $p.append($('<option>').val(l).text(l)); });
						$('#ab_pickup_wrap').toggle(d.pickups.length > 0);
						fillDropoffs(id);
					}
					function fillDropoffs(id){
						var d = DETAILS[id] || {pickups:[], dropoffs:{}}, pick = $('#ab_pickup').val();
						var list = (d.dropoffs && d.dropoffs[pick]) ? d.dropoffs[pick] : [];
						var $d = $('#ab_dropoff').empty();
						if (pick && list.indexOf(pick) === -1) { $d.append($('<option>').val(pick).text(pick)); }
						$.each(list, function(_, l){ $d.append($('<option>').val(l).text(l)); });
						$('#ab_dropoff_wrap').toggle($d.children().length > 0);
					}
					function fillServices(id){
						var d = DETAILS[id] || {services:[]}, $w = $('#ab_services').empty();
						$.each(d.services, function(i, s){
							var type = (s.type === 'day') ? ' / ' + T.perday : '';
							$w.append('<div class="ab-svc">' +
								'<input type="hidden" name="svc[' + i + '][name]" value="' + esc(s.name) + '">' +
								'<input type="checkbox" class="svc-on" name="svc[' + i + '][on]" value="1" aria-label="' + esc(s.name) + '">' +
								'<span class="nm">' + esc(s.name) + '<small>' + esc(String(s.price)) + type + '</small></span>' +
								'<input type="number" min="1" value="1" name="svc[' + i + '][qty]" aria-label="' + esc(T.qty) + '">' +
								'</div>');
						});
						$('#ab_services_wrap').prop('hidden', !d.services.length);
					}

					/* ---- price ---- */
					function requote(){ clearTimeout(quoteTimer); quoteTimer = setTimeout(doQuote, 300); }
					function doQuote(){
						if (!current) { return; }
						var my = ++quoteReq;
						$.post(ajaxurl, $form.serialize() + '&action=mpcrbm_admin_booking_quote&nonce=' + encodeURIComponent(NONCE))
							.done(function(r){
								if (my !== quoteReq) { return; }
								if (!r || !r.success) {
									$('#ab_price').html('<span class="ab-tag is-bad">' + esc((r && r.data && r.data.message) || T.error) + '</span>');
									return;
								}
								var d = r.data, h = '';
								h += '<div class="ab-price-row"><span>' + esc(T.rental) + ' (' + d.days + ' ' + esc(T.days) + ')</span><span>' + esc(d.rental_fmt) + '</span></div>';
								if (d.overridden) { h += '<div class="ab-price-row" style="color:#64748b"><span>' + esc(T.system) + '</span><span><s>' + esc(d.system_rental_fmt) + '</s></span></div>'; }
								if (d.deposit > 0) { h += '<div class="ab-price-row"><span>' + esc(T.deposit) + '</span><span>' + esc(d.deposit_fmt) + '</span></div>'; }
								h += '<div class="ab-price-total"><span>' + esc(T.total) + '</span><strong>' + esc(d.total_fmt) + '</strong></div>';
								if (d.overridden) { h += '<span class="ab-tag is-warn">' + esc(T.over) + '</span>'; }
								h += '<br>' + (d.qty > d.available
									? '<span class="ab-tag is-bad">' + esc(T.availBad) + '</span>'
									: '<span class="ab-tag is-ok">' + esc(fmt(T.availOk, d.available, d.stock)) + '</span>');
								$('#ab_price').html(h);
							})
							.fail(function(){ if (my === quoteReq) { $('#ab_price').html('<span class="ab-tag is-bad">' + esc(T.error) + '</span>'); } });
					}

					/* ---- events ---- */
					// Pickup change keeps the trip length: the return moves with it.
					$('#ab_pd, #ab_pt').on('change', function(){
						var s = toDate($('#ab_pd').val(), $('#ab_pt').val());
						if (s && durationMs > 0) {
							var e = new Date(s.getTime() + durationMs);
							$('#ab_rd').val(dateStr(e));
							var t = timeStr(e);
							if ($('#ab_rt option[value="' + t + '"]').length) { $('#ab_rt').val(t); }
						}
						updateSummary(); scheduleCars();
					});
					// A direct return edit becomes the new trip length.
					$('#ab_rd, #ab_rt').on('change', function(){
						var s = toDate($('#ab_pd').val(), $('#ab_pt').val()), e = toDate($('#ab_rd').val(), $('#ab_rt').val());
						if (s && e && e > s) { durationMs = e - s; }
						updateSummary(); scheduleCars();
					});
					$('#ab_include_booked').on('change', function(){ scheduleCars(); });

					$('#ab_pickup').on('change', function(){ if (current) { fillDropoffs(current.id); requote(); } });
					$('#ab_dropoff, #ab_qty, #ab_custom').on('input change', requote);
					$form.on('change input', '.svc-on, .ab-svc input[type=number]', function(){
						$(this).closest('.ab-svc').toggleClass('is-on', $(this).closest('.ab-svc').find('.svc-on').is(':checked'));
						requote();
					});
					$('#ab_name').on('input', updateSummary);

					$form.on('submit', function(e){
						e.preventDefault();
						if (busy) { return; }
						$msg.empty();
						if (!$('#ab_name').val().trim() || !$('#ab_phone').val().trim()) {
							$('#ab_name, #ab_phone').each(function(){ $(this).toggleClass('has-error', !$(this).val().trim()); });
							alertBox('bad', esc(T.needName)); return;
						}
						$('#ab_name, #ab_phone').removeClass('has-error');
						if (!current) { alertBox('bad', esc(T.needCar)); return; }
						busy = true;
						var $btn = $('#ab_submit').prop('disabled', true).text(T.creating);
						$.post(ajaxurl, $form.serialize() + '&action=mpcrbm_admin_booking_create&nonce=' + encodeURIComponent(NONCE))
							.done(function(r){
								if (r && r.success) {
									alertBox('ok', '<span>' + esc(r.data.message) + ' <a href="' + esc(r.data.list_url) + '">' + esc(T.viewlist) + '</a> · <a href="#" id="ab_again">' + esc(T.another) + '</a></span>');
									resetForm();
								} else {
									alertBox('bad', esc((r && r.data && r.data.message) || T.error));
								}
							})
							.fail(function(){ alertBox('bad', esc(T.error)); })
							.always(function(){ busy = false; $btn.text(T.create).prop('disabled', !current); });
					});
					$msg.on('click', '#ab_again', function(e){ e.preventDefault(); $msg.empty(); $('#ab_name').focus(); });

					function resetForm(){
						$('#ab_name, #ab_phone, #ab_email, #ab_note').val('');
						$('#ab_status').val('pending'); $('#ab_method').val('cash');
						$('#ab_pd').val(DEFAULTS.pickup_date); $('#ab_pt').val(DEFAULTS.pickup_time);
						$('#ab_rd').val(DEFAULTS.return_date); $('#ab_rt').val(DEFAULTS.return_time);
						durationMs = 24 * 3600 * 1000;
						updateSummary(); scheduleCars();
					}

					/* ---- go ---- */
					updateSummary();
					scheduleCars();
				});
				</script>
				<?php
			}
		}
		new MPCRBM_Admin_Booking();
	}
