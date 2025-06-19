<?php
	/*
	* @Author 		engr.sumonazma@gmail.com
	* Copyright: 	mage-people.com
	*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Woocommerce' ) ) {
		class MPCRBM_Woocommerce {
			private $custom_order_data = array(); // Property to store the data
			private $ordered_item_name;

			public function __construct() {
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'product_custom_field_to_custom_order_notes' ), 100, 2 );
				add_filter( 'woocommerce_add_cart_item_data', array( $this, 'cart_item_data' ), 90, 3 );
				add_action( 'woocommerce_before_calculate_totals', array( $this, 'before_calculate_totals' ), 90 );
				add_filter( 'woocommerce_cart_item_thumbnail', array( $this, 'cart_item_thumbnail' ), 90, 3 );
				add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 90, 2 );
				//************//
				add_action( 'woocommerce_after_checkout_validation', array( $this, 'after_checkout_validation' ) );
				add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'checkout_create_order_line_item' ), 90, 4 );
				// add_action('woocommerce_checkout_order_processed', array($this, 'checkout_order_processed'));
				add_action( 'woocommerce_before_thankyou', array( $this, 'checkout_order_processed' ) );
				add_filter( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ) );
				/*****************************/
				add_action( 'wp_ajax_mpcrbm_add_to_cart', [ $this, 'mpcrbm_add_to_cart' ] );
				add_action( 'wp_ajax_nopriv_mpcrbm_add_to_cart', [ $this, 'mpcrbm_add_to_cart' ] );
			}

			public function product_custom_field_to_custom_order_notes( $order_id, $data ) {
				foreach ( $data as $key => $value ) {
					if ( strpos( $key, 'order' ) === 0 ) {
						$this->custom_order_data[ $key ] = $value;
					}
				}
			}

			public function cart_item_data( $cart_item_data, $product_id ) {
				if ( ! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ) {
					return;
				}
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
					return;
				}
				$linked_id = MPCRBM_Global_Function::get_post_info( $product_id, 'link_mpcrbm_id', $product_id );
				$post_id = is_string( get_post_status( $linked_id ) ) ? $linked_id : $product_id;
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					$start_place      = isset( $_POST['mpcrbm_start_place'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_start_place'] ) ) : '';
					$end_place        = isset( $_POST['mpcrbm_end_place'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_end_place'] ) ) : '';
					$return           = isset( $_POST['mpcrbm_taxi_return'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_taxi_return'] ) ) : 1;
					$start_time       = isset( $_POST['mpcrbm_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_date'] ) ) : '';
					$return_date      = isset( $_POST['mpcrbm_return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_return_date'] ) ) : '';
					$return_time      = isset( $_POST['mpcrbm_return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_return_time'] ) ) : '';
					$return_date_time = $return_date ? gmdate( "Y-m-d", strtotime( $return_date ) ) : "";
					if ( $return_date && $return_time !== "" ) {
						if ( $return_time !== "" ) {
							if ( $return_time !== "0" ) {
								// Convert start time to hours and minutes
								list( $hours, $decimal_part ) = explode( '.', $return_time );
								$interval_time = MPCRBM_Function::get_general_settings( 'pickup_interval_time' );
								if ( $interval_time == "5" || $interval_time == "15" ) {
									$minutes = isset( $decimal_part ) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
								} else {
									$minutes = isset( $decimal_part ) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
								}
							} else {
								$hours   = 0;
								$minutes = 0;
							}
						} else {
							$hours   = 0;
							$minutes = 0;
						}
						$return_time_formatted = sprintf( '%02d:%02d', $hours, $minutes );
						$return_date_time      .= " " . $return_time_formatted;
						$cart_item_data['return_date_time'] = $return_date_time;
					}
					$total_price = $this->mpcrbm_get_cart_total_price( $post_id );
					$price     = MPCRBM_Function::get_price( $post_id, $start_place, $end_place, $start_time, $return_date_time );
					$wc_price  = MPCRBM_Global_Function::wc_price( $post_id, $price );
					$raw_price = MPCRBM_Global_Function::price_convert_raw( $wc_price );
					$cart_item_data['mpcrbm_date'] = isset( $_POST['mpcrbm_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_date'] ) ) : '';
					$cart_item_data['mpcrbm_taxi_return']         = $return;
					$cart_item_data['mpcrbm_waiting_time']        = $waiting_time;
					$cart_item_data['mpcrbm_start_place']         = wp_strip_all_tags( $start_place );
					$cart_item_data['mpcrbm_end_place']           = wp_strip_all_tags( $end_place );
					$cart_item_data['mpcrbm_distance']            = $distance;
					$cart_item_data['mpcrbm_distance_text']       = isset( $_COOKIE['mpcrbm_distance_text'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mpcrbm_distance_text'] ) ) : '';
					$cart_item_data['mpcrbm_duration']            = $duration;
					$cart_item_data['mpcrbm_fixed_hours']         = $fixed_hour;
					$cart_item_data['mpcrbm_duration_text']       = isset( $_COOKIE['mpcrbm_duration_text'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mpcrbm_duration_text'] ) ) : '';
					$cart_item_data['mpcrbm_base_price']          = $raw_price;
					$cart_item_data['mpcrbm_extra_service_info'] = self::cart_extra_service_info( $post_id );
					$cart_item_data['mpcrbm_tp']                  = $total_price;
					$cart_item_data['line_total']                = $total_price;
					$cart_item_data['line_subtotal']             = $total_price;
					$return_target_date                         = isset( $_POST['mpcrbm_return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_return_date'] ) ) : '';
					$return_target_time                         = isset( $_POST['mpcrbm_return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_return_time'] ) ) : '';
					$cart_item_data['mpcrbm_return_target_date'] = $return_target_date;
					$cart_item_data['mpcrbm_return_target_time'] = $return_target_time;
					$cart_item_data = apply_filters( 'mpcrbm_add_cart_item', $cart_item_data, $post_id );
				}
				$cart_item_data['mpcrbm_id'] = $post_id;

				//  echo '<pre>';print_r($cart_item_data);echo '</pre>';
				return $cart_item_data;
			}

			public function before_calculate_totals( $cart_object ) {
				foreach ( $cart_object->cart_contents as $value ) {
					$post_id = array_key_exists( 'mpcrbm_id', $value ) ? $value['mpcrbm_id'] : 0;
					if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
						$total_price = $value['mpcrbm_tp'];
						if ( isset( $_SESSION[ 'geo_fence_post_' . $post_id ] ) ) {
							// Extract amount from session
							$session_key  = 'geo_fence_post_' . intval( $post_id ); // Ensure $post_id is an integer
							$session_data = isset( $_SESSION[ $session_key ] ) ? sanitize_text_field( $_SESSION[ $session_key ] ) : '';
							// Check if session data contains the amount
							if ( isset( $session_data[0] ) ) {
								// Add the amount to the price
								$total_price += (float) $session_data[0];
							}
						}
						$value['data']->set_price( $total_price );
						$value['data']->set_regular_price( $total_price );
						$value['data']->set_sale_price( $total_price );
						$value['data']->set_sold_individually( 'yes' );
						$value['data']->get_price();
					}
				}
			}

			public function cart_item_thumbnail( $thumbnail, $cart_item ) {
				$mpcrbm_id = array_key_exists( 'mpcrbm_id', $cart_item ) ? $cart_item['mpcrbm_id'] : 0;
				if ( get_post_type( $mpcrbm_id ) == MPCRBM_Function::get_cpt() ) {
					$thumbnail = '<div class="bg_image_area" data-href="' . get_the_permalink( $mpcrbm_id ) . '"><div data-bg-image="' . MPCRBM_Global_Function::get_image_url( $mpcrbm_id ) . '"></div></div>';
				}

				return $thumbnail;
			}

			public function get_item_data( $item_data, $cart_item ) {
				$post_id = array_key_exists( 'mpcrbm_id', $cart_item ) ? $cart_item['mpcrbm_id'] : 0;
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					ob_start();
					$this->show_cart_item( $cart_item, $post_id );
					do_action( 'mpcrbm_show_cart_item', $cart_item, $post_id );
					$item_data[] = array( 'key' => esc_html__( 'Booking Details ', 'car-rental-manager' ), 'value' => ob_get_clean() );
				}

				return $item_data;
			}

			//**************//
			public function after_checkout_validation() {
				global $woocommerce;
				$items = $woocommerce->cart->get_cart();
				foreach ( $items as $values ) {
					$post_id = array_key_exists( 'mpcrbm_id', $values ) ? $values['mpcrbm_id'] : 0;
					if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
						do_action( 'mpcrbm_validate_cart_item', $values, $post_id );
					}
				}
			}

			public function checkout_create_order_line_item( $item, $cart_item_key, $values ) {
				$this->ordered_item_name = $item->get_name();
				$post_id = array_key_exists( 'mpcrbm_id', $values ) ? $values['mpcrbm_id'] : 0;
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					$date           = $values['mpcrbm_date'] ?? '';
					$start_location = $values['mpcrbm_start_place'] ?? '';
					$end_location   = $values['mpcrbm_end_place'] ?? '';
					$distance       = $values['mpcrbm_distance'] ?? '';
					$distance_text  = $values['mpcrbm_distance_text'] ?? '';
					$duration       = $values['mpcrbm_duration'] ?? '';
					$duration_text  = $values['mpcrbm_duration_text'] ?? '';
					$base_price     = $values['mpcrbm_base_price'] ?? '';
					$return         = $values['mpcrbm_taxi_return'] ?? '';
					$waiting_time   = $values['mpcrbm_waiting_time'] ?? '';
					$fixed_time     = $values['mpcrbm_fixed_hours'] ?? 0;
					$extra_service  = $values['mpcrbm_extra_service_info'] ?? [];
					$price          = $values['mpcrbm_tp'] ?? '';
					$item->add_meta_data( esc_html__( 'Pickup Location ', 'car-rental-manager' ), $start_location );
					$item->add_meta_data( esc_html__( 'Return Location ', 'car-rental-manager' ), $end_location );
					$price_type = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_price_based' );
					if ( $price_type !== 'manual' ) {
						$item->add_meta_data( esc_html__( 'Approximate Distance ', 'car-rental-manager' ), $distance_text );
						$item->add_meta_data( esc_html__( 'Approximate Time ', 'car-rental-manager' ), $duration_text );
					}
					if ( $waiting_time && $waiting_time > 0 ) {
						$item->add_meta_data( esc_html__( 'Extra Waiting Hours', 'car-rental-manager' ), $waiting_time . ' ' . esc_html__( 'Hour ', 'car-rental-manager' ) );
					}
					if ( $fixed_time && $fixed_time > 0 ) {
						$item->add_meta_data( esc_html__( 'Service Times', 'car-rental-manager' ), $fixed_time . ' ' . esc_html__( 'Hour ', 'car-rental-manager' ) );
					}
					$item->add_meta_data( esc_html__( 'Date ', 'car-rental-manager' ), esc_html( MPCRBM_Global_Function::date_format( $date ) ) );
					$item->add_meta_data( esc_html__( 'Time ', 'car-rental-manager' ), esc_html( MPCRBM_Global_Function::date_format( $date, 'time' ) ) );
					$item->add_meta_data( esc_html__( 'Transfer Type', 'car-rental-manager' ), esc_html__( 'Return ', 'car-rental-manager' ) );
					$return_date = $values['mpcrbm_return_target_date'] ?? '';
					$return_time = $values['mpcrbm_return_target_time'] ?? '';
					if ( $return_time !== "" ) {
						if ( $return_time !== "0" ) {
							// Convert start time to hours and minutes
							list( $hours, $decimal_part ) = explode( '.', $return_time );
							$interval_time = MPCRBM_Function::get_general_settings( 'pickup_interval_time' );
							if ( $interval_time == "5" || $interval_time == "15" ) {
								$minutes = isset( $decimal_part ) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
							} else {
								$minutes = isset( $decimal_part ) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
							}
						} else {
							$hours   = 0;
							$minutes = 0;
						}
					} else {
						$hours   = 0;
						$minutes = 0;
					}
					// Format hours and minutes
					$return_time_formatted = sprintf( '%02d:%02d', $hours, $minutes );
					// Combine date and time if both are available
					$return_date_time = $return_date ? gmdate( "Y-m-d", strtotime( $return_date ) ) : "";
					if ( $return_date_time && $return_time !== "" ) {
						$return_date_time .= " " . $return_time_formatted;
					}
					$item->add_meta_data( esc_html__( 'Return Date', 'car-rental-manager' ), esc_html( MPCRBM_Global_Function::date_format( $return_date_time ) ) );
					$item->add_meta_data( esc_html__( 'Return Time', 'car-rental-manager' ), esc_html( MPCRBM_Global_Function::date_format( $return_date_time, 'time' ) ) );
					$item->add_meta_data( '_mpcrbm_return_date', $return_date );
					$item->add_meta_data( '_mpcrbm_return_time', $return_time );
					$item->add_meta_data( '_return_date_time', $return_date_time );
					$item->add_meta_data( esc_html__( 'Price ', 'car-rental-manager' ), wp_kses_post( wc_price( $base_price ) ) );
					if ( sizeof( $extra_service ) > 0 ) {
						$item->add_meta_data( esc_html__( 'Optional Service ', 'car-rental-manager' ), '' );
						foreach ( $extra_service as $service ) {
							$item->add_meta_data( esc_html__( 'Services Name ', 'car-rental-manager' ), $service['service_name'] );
							$item->add_meta_data( esc_html__( 'Services Quantity ', 'car-rental-manager' ), $service['service_quantity'] );
							$item->add_meta_data( esc_html__( 'Price ', 'car-rental-manager' ), esc_html( ' ( ' ) . wp_kses_post( wc_price( $service['service_price'] ) ) . esc_html( ' X ' ) . esc_html( $service['service_quantity'] ) . esc_html( ') = ' ) . wp_kses_post( wc_price( $service['service_price'] * $service['service_quantity'] ) ) );
						}
					}
					if ( class_exists( 'MPCRBM_Plugin_Ecab_Calendar_Addon' ) ) {
						// Prepare date and time for Google Calendar format
						$formatted_date = MPCRBM_Global_Function::date_format( $date );
						$formatted_time = MPCRBM_Global_Function::date_format( $date, 'time' );
						// Combine the provided formatted date and time
						$date_time_string = $formatted_date . ' ' . $formatted_time; // Combine date and time as a single string
						// Get the WordPress time zone
						$timezone = new DateTimeZone( wp_timezone_string() );
						// Create DateTime object with the combined date and time, and apply WordPress time zone
						$start_date_time = new DateTime( $date_time_string, $timezone );
						// Convert to UTC (Google Calendar requires UTC time format)
						$start_date_time->setTimezone( new DateTimeZone( 'UTC' ) );
						// Format date and time for Google Calendar
						$formatted_date_time = $start_date_time->format( 'Ymd\THis\Z' ); // Start time in Google Calendar format
						// For the event end time (assuming 1 hour duration)
						$end_date_time = clone $start_date_time;
						$end_date_time->modify( '+2  hour' ); // Set the end time to 1 hour later
						$formatted_end_time = $end_date_time->format( 'Ymd\THis\Z' ); // End time in Google Calendar format
						$driver_id          = get_post_meta( $post_id, 'mpcrbm_selected_driver', true );
						if ( $driver_id ) {
							$driver_info  = get_userdata( $driver_id );
							$driver_name  = $driver_info->display_name;
							$driver_email = $driver_info->user_email;
						} else {
							$driver_name  = '';
							$driver_email = '';
						}
						// Build the details string conditionally
						$details = "Transport service from " . $start_location . " to " . $end_location;
						if ( $driver_email ) {
							$details .= ". Driver email: " . $driver_email;
						}
						if ( $driver_name ) {
							$details .= ". Driver name: " . $driver_name;
						}
						// Create Google Calendar link
						$google_calendar_link = "https://www.google.com/calendar/render?action=TEMPLATE&text="
						                        . urlencode( $this->ordered_item_name ) // Event title
						                        . "&dates=" . $formatted_date_time . "/" . $formatted_end_time // Start and end times
						                        . "&details=" . urlencode( $details )
						                        . "&location=" . urlencode( $start_location )
						                        . "&sf=true&output=xml";
						// Add Google Calendar link as meta data
						$item->add_meta_data(
							esc_html__( 'Add this event to your Google Calendar', 'car-rental-manager' ),
							'<a href="' . esc_url( $google_calendar_link ) . '" target="_blank">' . esc_html__( 'Add this event to your Google Calendar', 'car-rental-manager' ) . '</a>'
						);
					}
					$item->add_meta_data( '_mpcrbm_id', $post_id );
					$item->add_meta_data( '_mpcrbm_date', $date );
					$item->add_meta_data( '_mpcrbm_start_place', $start_location );
					$item->add_meta_data( '_mpcrbm_end_place', $end_location );
					$item->add_meta_data( '_mpcrbm_taxi_return', $return );
					$item->add_meta_data( '_mpcrbm_waiting_time', $waiting_time );
					$item->add_meta_data( '_mpcrbm_fixed_hours', $fixed_time );
					$item->add_meta_data( '_mpcrbm_distance', $distance );
					$item->add_meta_data( '_mpcrbm_distance_text', $distance_text );
					$item->add_meta_data( '_mpcrbm_duration', $duration );
					$item->add_meta_data( '_mpcrbm_duration_text', $duration_text );
					$item->add_meta_data( '_mpcrbm_base_price', $base_price );
					$item->add_meta_data( '_mpcrbm_tp', $price );
					$item->add_meta_data( '_mpcrbm_service_info', $extra_service );
					do_action( 'mpcrbm_checkout_create_order_line_item', $item, $values );
				}
			}

			public function checkout_order_processed( $order_id ) {
				if ( $order_id ) {
					$order = wc_get_order( $order_id );
					// Get all meta data
					$meta_data = $order->get_meta_data();
					// Initialize an associative array to store meta keys and values
					$meta_array = [];
					foreach ( $meta_data as $meta ) {
						// Get the meta key and value
						$meta_key   = $meta->get_data()['key'];
						$meta_value = $meta->get_data()['value'];
						// Store the key-value pair in the associative array
						$meta_array[ $meta_key ] = $meta_value;
					}
					// Unset any meta keys you don't want to include
					unset( $meta_array['_billing_address_index'] );
					unset( $meta_array['_shipping_address_index'] );
					unset( $meta_array['is_vat_exempt'] );
					// Add the filtered custom order data to the meta array
					if ( ! empty( $this->custom_order_data ) ) {
						foreach ( $this->custom_order_data as $key => $value ) {
							$meta_array[ $key ] = $value;
						}
					}
					$order_status   = $order->get_status();
					$order_meta     = get_post_meta( $order_id );
					$payment_method = $order_meta['_payment_method_title'][0] ?? '';
					$user_id        = $order_meta['_customer_user'][0] ?? '';
					if ( $order_status != 'failed' ) {
						foreach ( $order->get_items() as $item_id => $item ) {
							$post_id = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_id' );
							if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
								$date = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_date' );
								$date = $date ? MPCRBM_Global_Function::data_sanitize( $date ) : '';
								$return_date_time = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_return_date_time' );
								$return_date_time = $return_date_time ? MPCRBM_Global_Function::data_sanitize( $return_date_time ) : '';
								$start_place  = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_start_place' );
								$start_place  = $start_place ? MPCRBM_Global_Function::data_sanitize( $start_place ) : '';
								$end_place    = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_end_place' );
								$end_place    = $end_place ? MPCRBM_Global_Function::data_sanitize( $end_place ) : '';
								$waiting_time = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_waiting_time' );
								$waiting_time = $waiting_time ? MPCRBM_Global_Function::data_sanitize( $waiting_time ) : '';
								$return       = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_taxi_return' );
								$return       = $return ? MPCRBM_Global_Function::data_sanitize( $return ) : '';
								$return_target_date = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_return_date' );
								$return_target_time = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_return_time' );
								$fixed_time   = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_fixed_hours' );
								$fixed_time   = $fixed_time ? MPCRBM_Global_Function::data_sanitize( $fixed_time ) : '';
								$distance     = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_distance' );
								$distance     = $distance ? MPCRBM_Global_Function::data_sanitize( $distance ) : '';
								$duration     = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_duration' );
								$duration     = $duration ? MPCRBM_Global_Function::data_sanitize( $duration ) : '';
								$base_price   = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_base_price' );
								$base_price   = $base_price ? MPCRBM_Global_Function::data_sanitize( $base_price ) : '';
								$service      = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_service_info' );
								$service_info = $service ? MPCRBM_Global_Function::data_sanitize( $service ) : [];
								$price        = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_tp' );
								$price        = $price ? MPCRBM_Global_Function::data_sanitize( $price ) : [];
								// Add meta array data to the $data array
								$data = array_merge( $meta_array, [
									'mpcrbm_id'                          => $post_id,
									'mpcrbm_date'                        => $date,
									'return_date_time'                  => $return_date_time,
									'mpcrbm_return_target_date'          => $return_target_date,
									'mpcrbm_return_target_time'          => $return_target_time,
									'mpcrbm_start_place'                 => $start_place,
									'mpcrbm_end_place'                   => $end_place,
									'mpcrbm_waiting_time'                => $waiting_time,
									'mpcrbm_taxi_return'                 => $return,
									'mpcrbm_fixed_hours'                 => $fixed_time,
									'mpcrbm_distance'                    => $distance,
									'mpcrbm_duration'                    => $duration,
									'mpcrbm_base_price'                  => $base_price,
									'mpcrbm_order_id'                    => $order_id,
									'mpcrbm_order_status'                => $order_status,
									'mpcrbm_payment_method'              => $order->get_payment_method_title(),
									'mpcrbm_user_id'                     => $user_id,
									'mpcrbm_tp'                          => $price,
									'mpcrbm_service_info'                => $service_info,
									'mpcrbm_billing_name'                => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
									'mpcrbm_billing_email'               => $order->get_billing_email(),
									'mpcrbm_billing_phone'               => $order->get_billing_phone(),
									'mpcrbm_target_pickup_interval_time' => MPCRBM_Function::get_general_settings( 'pickup_interval_time', '30' )
								] );
								$booking_data = apply_filters( 'add_mpcrbm_booking_data', $data, $post_id );
								self::mpcrbm_cpt_data( 'mpcrbm_booking', $booking_data['mpcrbm_billing_name'], $booking_data );
								if ( sizeof( $service_info ) > 0 ) {
									foreach ( $service_info as $service ) {
										$ex_data = [
											'mpcrbm_id'               => $post_id,
											'mpcrbm_date'             => $date,
											'mpcrbm_order_id'         => $order_id,
											'mpcrbm_order_status'     => $order_status,
											'mpcrbm_service_name'     => $service['service_name'],
											'mpcrbm_service_quantity' => $service['service_quantity'],
											'mpcrbm_service_price'    => $service['service_price'],
											'mpcrbm_payment_method'   => $payment_method,
											'mpcrbm_user_id'          => $user_id
										];
										self::mpcrbm_cpt_data( 'mpcrbm_service_booking', '#' . $order_id . $ex_data['mpcrbm_service_name'], $ex_data );
									}
								}
							}
						}
					}
					$data['mpcrbm_item_name'] = $this->ordered_item_name;
					$driver_id = get_post_meta( $post_id, 'mpcrbm_selected_driver', true );
					if ( $driver_id ) {
						$driver_info                     = get_userdata( $driver_id );
						$data['mpcrbm_item_driver_name']  = $driver_info->display_name;
						$data['mpcrbm_item_driver_email'] = $driver_info->user_email;
						$data['mpcrbm_item_driver_phone'] = get_user_meta( $driver_id, 'user_phone', true );
					}
					do_action( 'mpcrbm_checkout_order_processed', $data );
				}
			}

			public function order_status_changed( $order_id ) {
				$order        = wc_get_order( $order_id );
				$order_status = $order->get_status();
				foreach ( $order->get_items() as $item_id => $item_values ) {
					$post_id = MPCRBM_Global_Function::get_order_item_meta( $item_id, '_mpcrbm_id' );
					if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
						if ( $order->has_status( 'processing' ) || $order->has_status( 'pending' ) || $order->has_status( 'on-hold' ) || $order->has_status( 'completed' ) || $order->has_status( 'cancelled' ) || $order->has_status( 'refunded' ) || $order->has_status( 'failed' ) || $order->has_status( 'requested' ) ) {
							$this->wc_order_status_change( $order_status, $post_id, $order_id );
						}
					}
				}
			}

			//**************************//
			public function show_cart_item( $cart_item, $post_id ) {
				$date = array_key_exists( 'mpcrbm_date', $cart_item ) ? $cart_item['mpcrbm_date'] : '';
				$start_location = array_key_exists( 'mpcrbm_start_place', $cart_item ) ? $cart_item['mpcrbm_start_place'] : '';
				$end_location   = array_key_exists( 'mpcrbm_end_place', $cart_item ) ? $cart_item['mpcrbm_end_place'] : '';
				$base_price     = array_key_exists( 'mpcrbm_base_price', $cart_item ) ? $cart_item['mpcrbm_base_price'] : '';
				$return         = array_key_exists( 'mpcrbm_taxi_return', $cart_item ) ? $cart_item['mpcrbm_taxi_return'] : '';
				$waiting_time   = array_key_exists( 'mpcrbm_waiting_time', $cart_item ) ? $cart_item['mpcrbm_waiting_time'] : '';
				$fixed_time     = array_key_exists( 'mpcrbm_fixed_hours', $cart_item ) ? $cart_item['mpcrbm_fixed_hours'] : '';
				$extra_service  = array_key_exists( 'mpcrbm_extra_service_info', $cart_item ) ? $cart_item['mpcrbm_extra_service_info'] : [];
				?>
                <div class="mpcrbm">
					<?php do_action( 'mpcrbm_before_cart_item_display', $cart_item, $post_id ); ?>
                    <div class="dLayout_xs">
                        <ul class="cart_list">
                            <li>
                                <span class="fas fa-map-marker-alt"></span>
                                <h6 class="_mR_xs"><?php esc_html_e( 'Pickup Location', 'car-rental-manager' ); ?> :</h6>
                                <span><?php echo esc_html( $start_location ); ?></span>
                            </li>
                            <li>
                                <span class="fas fa-map-marker-alt"></span>
                                <h6 class="_mR_xs"><?php esc_html_e( 'Return Location', 'car-rental-manager' ); ?> :</h6>
                                <span><?php echo esc_html( $end_location ); ?></span>
                            </li>
							<?php
								$price_type = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_price_based' );
								if ( $price_type !== 'manual' ) {
									?>
                                    <li>
                                        <span class="fas fa-route"></span>
                                        <h6 class="_mR_xs"><?php esc_html_e( 'Approximate Distance', 'car-rental-manager' ); ?> :</h6>
                                        <span><?php echo esc_html( $cart_item['mpcrbm_distance_text'] ); ?></span>
                                    </li>
                                    <li>
                                        <span class="far fa-clock"></span>
                                        <h6 class="_mR_xs"><?php esc_html_e( 'Approximate Time', 'car-rental-manager' ); ?> :</h6>
                                        <span><?php echo esc_html( $cart_item['mpcrbm_duration_text'] ); ?></span>
                                    </li>
								<?php } ?>
                            <li>
                                <span class="far fa-calendar-alt"></span>
                                <h6 class="_mR_xs"><?php esc_html_e( 'Date', 'car-rental-manager' ); ?> :</h6>
                                <span><?php echo esc_html( MPCRBM_Global_Function::date_format( $date ) ); ?></span>
                            </li>
                            <li>
                                <span class="far fa-clock"></span>
                                <h6 class="_mR_xs"><?php esc_html_e( 'Time : ', 'car-rental-manager' ); ?></h6>
                                <span><?php echo esc_html( MPCRBM_Global_Function::date_format( $date, 'time' ) ); ?></span>
                            </li>
							<?php
								$return_date = array_key_exists( 'mpcrbm_return_target_date', $cart_item ) ? $cart_item['mpcrbm_return_target_date'] : '';
								$return_time = array_key_exists( 'mpcrbm_return_target_time', $cart_item ) ? $cart_item['mpcrbm_return_target_time'] : '';
								if ( $return_time !== "" ) {
									if ( $return_time !== "0" ) {
										// Convert start time to hours and minutes
										if ( MPCRBM_Global_Function::hasDecimal( $return_time ) ) {
											list( $hours, $decimal_part ) = explode( '.', $return_time );
										} else {
											$hours        = $return_time;
											$decimal_part = 0;
										}
										$interval_time = MPCRBM_Function::get_general_settings( 'pickup_interval_time' );
										if ( $interval_time == "5" || $interval_time == "15" ) {
											$minutes = isset( $decimal_part ) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
										} else {
											$minutes = isset( $decimal_part ) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
										}
									} else {
										$hours   = 0;
										$minutes = 0;
									}
								} else {
									$hours   = 0;
									$minutes = 0;
								}
								// Format hours and minutes
								$return_time_formatted = sprintf( '%02d:%02d', $hours, $minutes );
								// Combine date and time if both are available
								$return_date_time = $return_date ? gmdate( "Y-m-d", strtotime( $return_date ) ) : "";
								if ( $return_date_time && $return_time !== "" ) {
									$return_date_time .= " " . $return_time_formatted;
								}
							?>
                            <li>
                                <span class="far fa-calendar-alt"></span>
                                <h6 class="_mR_xs"><?php esc_html_e( 'Return Date', 'car-rental-manager' ); ?> :</h6>
                                <span><?php echo esc_html( MPCRBM_Global_Function::date_format( $return_date_time ) ); ?></span>
                            </li>
                            <li>
                                <span class="far fa-clock"></span>
                                <h6 class="_mR_xs"><?php esc_html_e( 'Return Time', 'car-rental-manager' ); ?> :</h6>
                                <span><?php echo esc_html( MPCRBM_Global_Function::date_format( $return_date_time, 'time' ) ); ?></span>
                            </li>
							<?php if ( $waiting_time && $waiting_time > 0 ) { ?>
                                <li>
                                    <h6 class="_mR_xs"><?php esc_html_e( 'Extra Waiting Hours', 'car-rental-manager' ); ?> :</h6>
                                    <span><?php echo esc_html( $waiting_time ); ?><?php esc_html_e( 'Hours', 'car-rental-manager' ); ?></span>
                                </li>
							<?php } ?>
							<?php if ( $fixed_time && $fixed_time > 0 ) { ?>
                                <li>
                                    <h6 class="_mR_xs"><?php esc_html_e( 'Service Times', 'car-rental-manager' ); ?> :</h6>
                                    <span><?php echo esc_html( $fixed_time ); ?><?php esc_html_e( 'Hours', 'car-rental-manager' ); ?></span>
                                </li>
							<?php } ?>
                            <li>
                                <span class="fa fa-tag"></span>
                                <h6 class="_mR_xs"><?php esc_html_e( 'Base Price : ', 'car-rental-manager' ); ?></h6>
                                <span><?php echo wp_kses_post( wc_price( $base_price ) ); ?></span>
                            </li>
							<?php do_action( 'mpcrbm_cart_item_display', $cart_item, $post_id ); ?>
                        </ul>
                    </div>
					<?php if ( sizeof( $extra_service ) > 0 ) { ?>
                        <h5 class="_mB_xs"><?php esc_html_e( 'Extra Services', 'car-rental-manager' ); ?></h5>
						<?php foreach ( $extra_service as $service ) { ?>
                            <div class="dLayout_xs">
                                <ul class="cart_list">
                                    <li>
                                        <h6 class="_mR_xs"><?php esc_html_e( 'Name : ', 'car-rental-manager' ); ?></h6>
                                        <span><?php echo esc_html( $service['service_name'] ); ?></span>
                                    </li>
                                    <li>
                                        <h6 class="_mR_xs"><?php esc_html_e( 'Quantity : ', 'car-rental-manager' ); ?></h6>
                                        <span><?php echo esc_html( $service['service_quantity'] ); ?></span>
                                    </li>
                                    <li>
                                        <h6 class="_mR_xs"><?php esc_html_e( 'Price : ', 'car-rental-manager' ); ?></h6>
                                        <span><?php echo esc_html( ' ( ' ) . wp_kses_post( wc_price( $service['service_price'] ) ) . esc_html( ' X ' ) . esc_html( $service['service_quantity'] ) . esc_html( ' ) =' ) . wp_kses_post( wc_price( $service['service_price'] * $service['service_quantity'] ) ); ?></span>
                                    </li>
                                </ul>
                            </div>
						<?php } ?>
					<?php } ?>
					<?php do_action( 'mpcrbm_after_cart_item_display', $cart_item, $post_id ); ?>
                </div>
				<?php
			}

			public function wc_order_status_change( $order_status, $post_id, $order_id ) {
				$args = array(
					'post_type'      => 'mpcrbm_booking',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							array(
								'key'     => 'mpcrbm_id',
								'value'   => $post_id,
								'compare' => '='
							),
							array(
								'key'     => 'mpcrbm_order_id',
								'value'   => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query( $args );
				foreach ( $loop->posts as $user ) {
					$user_id = $user->ID;
					update_post_meta( $user_id, 'mpcrbm_order_status', $order_status );
				}
				$args = array(
					'post_type'      => 'mpcrbm_service_booking',
					'posts_per_page' => - 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							array(
								'key'     => 'mpcrbm_id',
								'value'   => $post_id,
								'compare' => '='
							),
							array(
								'key'     => 'mpcrbm_order_id',
								'value'   => $order_id,
								'compare' => '='
							)
						)
					)
				);
				$loop = new WP_Query( $args );
				foreach ( $loop->posts as $user ) {
					$user_id = $user->ID;
					update_post_meta( $user_id, 'mpcrbm_order_status', $order_status );
				}
			}

			//**********************//
			public static function cart_extra_service_info( $post_id ): array {
				if ( ! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ) {
					return false;
				}
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
					return false;
				}
				$start_date       = isset( $_POST['mpcrbm_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_date'] ) ) : '';
				$service_name     = isset( $_POST['mpcrbm_extra_service'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_extra_service'] ) ) : [];
				$service_quantity = isset( $_POST['mpcrbm_extra_service_qty'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_extra_service_qty'] ) ) : [];
				$extra_service    = array();
				if ( sizeof( $service_name ) > 0 ) {
					for ( $i = 0; $i < count( $service_name ); $i ++ ) {
						if ( $service_name[ $i ] && $service_quantity[ $i ] > 0 ) {
							$price                                   = MPCRBM_Function::get_extra_service_price_by_name( $post_id, $service_name[ $i ] );
							$wc_price                                = MPCRBM_Global_Function::wc_price( $post_id, $price );
							$raw_price                               = MPCRBM_Global_Function::price_convert_raw( $wc_price );
							$extra_service[ $i ]['service_name']     = $service_name[ $i ];
							$extra_service[ $i ]['service_quantity'] = $service_quantity[ $i ];
							$extra_service[ $i ]['service_price']    = $raw_price;
							$extra_service[ $i ]['mpcrbm_date']       = $start_date ?? '';
						}
					}
				}

				return $extra_service;
			}

			public function mpcrbm_get_cart_total_price( $post_id ) {
				//Validate nonce before processing
				if ( ! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ) {
					return;
				}
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
					return;
				}
				$start_place = isset( $_POST['mpcrbm_start_place'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_start_place'] ) ) : '';
				$end_place       = isset( $_POST['mpcrbm_end_place'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_end_place'] ) ) : '';
				$start_date_time = isset( $_POST['mpcrbm_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_start_date'] ) ) : '';
				$start_time      = isset( $_POST['mpcrbm_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_date'] ) ) : '';
				$return_date     = isset( $_POST['mpcrbm_return_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_return_date'] ) ) : '';
				$return_time     = isset( $_POST['mpcrbm_return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_return_time'] ) ) : '';
				$return_date_time = $return_date ? gmdate( "Y-m-d", strtotime( $return_date ) ) : "";
				if ( $return_date && $return_time !== "" ) {
					if ( $return_time !== "" ) {
						if ( $return_time !== "0" ) {
							// Convert start time to hours and minutes
							list( $hours, $decimal_part ) = explode( '.', $return_time );
							$interval_time = MPCRBM_Function::get_general_settings( 'pickup_interval_time' );
							if ( $interval_time == "5" || $interval_time == "15" ) {
								$minutes = isset( $decimal_part ) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
							} else {
								$minutes = isset( $decimal_part ) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
							}
						} else {
							$hours   = 0;
							$minutes = 0;
						}
					} else {
						$hours   = 0;
						$minutes = 0;
					}
					$return_time_formatted = sprintf( '%02d:%02d', $hours, $minutes );
					$return_date_time      .= " " . $return_time_formatted;
				}
				$price            = MPCRBM_Function::get_price( $post_id, $start_place, $end_place, $start_time, $return_date_time );
				$wc_price         = MPCRBM_Global_Function::wc_price( $post_id, $price );
				$raw_price        = MPCRBM_Global_Function::price_convert_raw( $wc_price );
				$service_name     = isset( $_POST['mpcrbm_extra_service'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_extra_service'] ) ) : [];
				$service_quantity = isset( $_POST['mpcrbm_extra_service_qty'] ) ? array_map( 'absint', $_POST['mpcrbm_extra_service_qty'] ) : [];
				if ( sizeof( $service_name ) > 0 ) {
					for ( $i = 0; $i < count( $service_name ); $i ++ ) {
						if ( $service_name[ $i ] ) {
							if ( array_key_exists( $i, $service_quantity ) && isset( $service_quantity[ $i ] ) ) {
								$raw_price = $raw_price + MPCRBM_Function::get_extra_service_price_by_name( $post_id, $service_name[ $i ] ) * $service_quantity[ $i ];
							} else {
								$raw_price = $raw_price + MPCRBM_Function::get_extra_service_price_by_name( $post_id, $service_name[ $i ] );
							}
						}
					}
				}
				$wc_price = MPCRBM_Global_Function::wc_price( $post_id, $raw_price );

				return MPCRBM_Global_Function::price_convert_raw( $wc_price );
			}

			public static function mpcrbm_cpt_data( $cpt_name, $title, $meta_data = array(), $status = 'publish', $cat = array() ) {
				$new_post = array(
					'post_title'    => $title,
					'post_content'  => '',
					'post_category' => $cat,
					'tags_input'    => array(),
					'post_status'   => $status,
					'post_type'     => $cpt_name
				);
				$post_id = wp_insert_post( $new_post );
				if ( sizeof( $meta_data ) > 0 ) {
					foreach ( $meta_data as $key => $value ) {
						update_post_meta( $post_id, $key, $value );
					}
				}
				if ( $cpt_name == 'mpcrbm_booking' ) {
					$mpcrbm_pin = $meta_data['mpcrbm_user_id'] . $meta_data['mpcrbm_order_id'] . $meta_data['mpcrbm_id'] . $post_id;
					update_post_meta( $post_id, 'mpcrbm_pin', $mpcrbm_pin );
				}
			}

			/****************************/
			public function mpcrbm_add_to_cart() {
				if ( ! isset( $_POST['mpcrbm_transportation_type_nonce'] ) ) {
					return;
				}
				// Sanitize and verify the nonce
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
					return;
				}
				$link_id           = isset( $_POST['link_id'] ) ? absint( $_POST['link_id'] ) : 0;
				$product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', $link_id );
				$quantity          = 1;
				$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
				$product_status    = get_post_status( $product_id );
				WC()->cart->empty_cart();
				ob_start();
				if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity ) && 'publish' === $product_status ) {
					echo esc_url( wc_get_checkout_url() );
				}
				echo wp_kses_post( ob_get_clean() );
				die();
			}
		}
		new MPCRBM_Woocommerce();
	}


