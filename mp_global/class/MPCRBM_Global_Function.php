<?php
	/*
	* @Author 		MagePeople Team
	* Copyright: 	mage-people.com
	*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Global_Function' ) ) {
		class MPCRBM_Global_Function {
			public function __construct() {
				add_action( 'mpcrbm_load_date_picker_js', [ $this, 'date_picker_js' ], 10, 2 );
			}

			public static function query_post_type( $post_type, $show = - 1, $page = 1 ): WP_Query {
				$args = array(
					'post_type'      => $post_type,
					'posts_per_page' => $show,
					'paged'          => $page,
					'post_status'    => 'publish'
				);

				return new WP_Query( $args );
			}

			public static function get_all_post_id( $post_type, $show = - 1, $page = 1, $status = 'publish' ): array {
				$all_data = get_posts( array(
					'fields'         => 'ids',
					'post_type'      => $post_type,
					'posts_per_page' => $show,
					'paged'          => $page,
					'post_status'    => $status
				) );

				return array_unique( $all_data );
			}

            public static function get_mpcrbm_ids_by_datetime( $given_datetime ) {

                $args = array(
                    'post_type'      => 'mpcrbm_booking',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'mpcrbm_date',
                            'value'   => $given_datetime,
                            'compare' => '<=',
                            'type'    => 'DATETIME',
                        ),
                        array(
                            'key'     => 'return_date_time',
                            'value'   => $given_datetime,
                            'compare' => '>=',
                            'type'    => 'DATETIME',
                        ),
                        [
                            'key'     => 'mpcrbm_order_status',
                            'value'   => ['cancelled', 'refunded', 'failed'],
                            'compare' => 'NOT IN',
                        ],
                    ),
                );
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                $query = new WP_Query( $args );

                if ( empty( $query->posts ) ) {
                    return array();
                }

                $mpcrbm_ids = array();

                foreach ( $query->posts as $post_id ) {
                    $mpcrbm_id = get_post_meta( $post_id, 'mpcrbm_id', true );
                    if ( ! empty( $mpcrbm_id ) ) {
                        $mpcrbm_ids[] = $mpcrbm_id;
                    }
                }

                return $mpcrbm_ids;
            }

			public static function get_post_info( $post_id, $key, $default = '' ) {
				$data = get_post_meta( $post_id, $key, true ) ?: $default;

				// Security fix: Special handling for mpcrbm_day_price to prevent PHP Object Injection
				// Ensure it always returns a numeric value, never unserialized objects
				if ( $key === 'mpcrbm_day_price' ) {
					// If data is serialized, reject it and return default
					if ( is_serialized( $data ) ) {
						return is_numeric( $default ) ? floatval( $default ) : 0;
					}
					// Ensure the value is numeric
					if ( ! is_numeric( $data ) ) {
						return is_numeric( $default ) ? floatval( $default ) : 0;
					}
					return floatval( $data );
				}

				return self::data_sanitize( $data );
			}

			//***********************************//
			public static function get_taxonomy( $name ) {
				return get_terms( array( 'taxonomy' => $name, 'hide_empty' => false ) );
			}

			public static function get_all_term_data( $term_name, $value = 'name' ) {
				$all_data   = [];
				$taxonomies = self::get_taxonomy( $term_name );
				if ( $taxonomies && is_array( $taxonomies ) && sizeof( $taxonomies ) > 0 ) {
					foreach ( $taxonomies as $taxonomy ) {
						$all_data[] = $taxonomy->$value;
					}
				}

				return $all_data;
			}

			public static function get_submit_info( $key, $default = '' ) {
				// Check if nonce exists in the request
				if (
					! isset( $_POST['_settings_save_nonce'] ) ||
					! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_settings_save_nonce'] ) ), 'settings_save_action' )
				) {
					return ''; // Return empty if nonce validation fails
				}
				// First check if key exists to avoid undefined index notice
				if ( ! isset( $_POST[ $key ] ) ) {
					return $default;
				}
				$raw_value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				// Handle arrays specially
				if ( is_array( $raw_value ) ) {
					$sanitized_array = array();
					foreach ( $raw_value as $k => $v ) {
						$sanitized_array[ sanitize_key( $k ) ] = sanitize_text_field( $v );
					}

					return $sanitized_array;
				}
				// Convert to string for non-array values
				$value = strval( $raw_value );
				// Validate and sanitize based on type
				if ( is_email( $value ) ) {
					return sanitize_email( $value );
				} else if ( is_numeric( $value ) ) {
					return is_int( $value + 0 ) ? absint( $value ) : floatval( $value );
				} else if ( strpos( $key, 'url' ) !== false ) {
					return esc_url_raw( $value );
				} else if ( strpos( $key, 'html' ) !== false || strpos( $key, 'content' ) !== false ) {
					return wp_kses_post( $value );
				}

				// Default to basic text field sanitization
				return sanitize_text_field( $value );
			}

			public static function get_submit_info_get_method( $key, $default = '' ) {
				// Check if nonce exists in the request
				if (
					! isset( $_POST['_settings_save_nonce'] ) ||
					! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_settings_save_nonce'] ) ), 'settings_save_action' )
				) {
					return ''; // Return empty if nonce validation fails
				}
				// First check if key exists to avoid undefined index notice
				if ( ! isset( $_GET[ $key ] ) ) {
					return $default;
				}
				// Unslash the raw input value first
				$raw_value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
				// Handle arrays specially
				if ( is_array( $raw_value ) ) {
					$sanitized_array = array();
					foreach ( $raw_value as $k => $v ) {
						$sanitized_array[ sanitize_key( $k ) ] = sanitize_text_field( $v );
					}

					return $sanitized_array;
				}
				// Convert to string for non-array values
				$value = strval( $raw_value );
				// Validate and sanitize based on type
				if ( is_email( $value ) ) {
					return sanitize_email( $value );
				} else if ( is_numeric( $value ) ) {
					return is_int( $value + 0 ) ? absint( $value ) : floatval( $value );
				} else if ( strpos( $key, 'url' ) !== false ) {
					return esc_url_raw( $value );
				} else if ( strpos( $key, 'html' ) !== false || strpos( $key, 'content' ) !== false ) {
					return wp_kses_post( $value );
				}

				// Default to basic text field sanitization
				return sanitize_text_field( $value );
			}

			/**
			 * Unserialize a value without ever instantiating a PHP object.
			 *
			 * Security: this is the plugin's only safe entry point for untrusted
			 * serialized data. Calling unserialize() and *then* testing is_object()
			 * is not a fix -- PHP runs __wakeup()/__destruct() during the call, so a
			 * POP chain has already executed by the time the check happens, and an
			 * object nested inside an array never trips the check at all.
			 *
			 * allowed_classes => false makes PHP refuse to build any class: every
			 * serialized object becomes an inert __PHP_Incomplete_Class with no magic
			 * methods, which strip_objects() then removes at any nesting depth.
			 *
			 * @param mixed $data Possibly serialized value.
			 *
			 * @return mixed Unserialized value with every object removed. Non-serialized
			 *               input is returned untouched.
			 */
			public static function safe_maybe_unserialize( $data ) {
				if ( ! is_string( $data ) || ! is_serialized( $data ) ) {
					return $data;
				}
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Hardened with allowed_classes => false.
				$unserialized = @unserialize( trim( $data ), array( 'allowed_classes' => false ) );
				if ( false === $unserialized && 'b:0;' !== trim( $data ) ) {
					// Malformed payload: keep the raw string, callers sanitize it as text.
					return $data;
				}

				return self::strip_objects( $unserialized );
			}

			/**
			 * Recursively drop object placeholders left behind by safe_maybe_unserialize().
			 *
			 * @param mixed $data Unserialized value.
			 *
			 * @return mixed Value guaranteed to contain no objects.
			 */
			public static function strip_objects( $data ) {
				if ( is_object( $data ) ) {
					return '';
				}
				if ( is_array( $data ) ) {
					$clean = array();
					foreach ( $data as $key => $value ) {
						if ( is_object( $value ) ) {
							continue;
						}
						$clean[ $key ] = is_array( $value ) ? self::strip_objects( $value ) : $value;
					}

					return $clean;
				}

				return $data;
			}

			/**
			 * Sanitize a single scalar according to the kind of content it holds.
			 *
			 * @param mixed $value Scalar value.
			 *
			 * @return mixed Sanitized value; non-strings are returned unchanged.
			 */
			protected static function sanitize_scalar( $value ) {
				if ( ! is_scalar( $value ) ) {
					// Only null can reach this point; keep the pre-existing empty-string result.
					return '';
				}
				if ( is_email( $value ) ) {
					return sanitize_email( $value );
				} else if ( strpos( $value, 'http' ) === 0 ) {
					return esc_url_raw( $value );
				} else if ( strpos( $value, '<' ) !== false && strpos( $value, '>' ) !== false ) {
					return wp_kses_post( $value );
				}

				return sanitize_text_field( wp_strip_all_tags( $value ) );
			}

			public static function data_sanitize( $data ) {
				// Security: PHP Object Injection guard. No class is ever instantiated,
				// so no magic method can fire, and no object survives at any depth.
				$data = self::safe_maybe_unserialize( $data );
				if ( is_string( $data ) ) {
					// Double-serialized case.
					$data = self::safe_maybe_unserialize( $data );
				}
				if ( is_object( $data ) ) {
					return '';
				}
				if ( is_array( $data ) ) {
					foreach ( $data as &$value ) {
						$value = is_array( $value ) ? self::data_sanitize( $value ) : self::sanitize_scalar( $value );
					}
					unset( $value );

					return $data;
				}
				if ( is_string( $data ) ) {
					return self::sanitize_scalar( $data );
				}

				return $data;
			}

			//**************Date related*********************//
			public static function date_picker_format_without_year( $key = 'date_format' ): string {
				$format      = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', $key, 'D d M , yy' );
				$date_format = 'm-d';
				$date_format = $format == 'yy/mm/dd' ? 'm/d' : $date_format;
				$date_format = $format == 'yy-dd-mm' ? 'd-m' : $date_format;
				$date_format = $format == 'yy/dd/mm' ? 'd/m' : $date_format;
				$date_format = $format == 'dd-mm-yy' ? 'd-m' : $date_format;
				$date_format = $format == 'dd/mm/yy' ? 'd/m' : $date_format;
				$date_format = $format == 'mm-dd-yy' ? 'm-d' : $date_format;
				$date_format = $format == 'mm/dd/yy' ? 'm/d' : $date_format;
				$date_format = $format == 'd M , yy' ? 'j M' : $date_format;
				$date_format = $format == 'D d M , yy' ? 'D j M' : $date_format;
				$date_format = $format == 'M d , yy' ? 'M  j' : $date_format;

				return $format == 'D M d , yy' ? 'D M  j' : $date_format;
			}

			public static function date_picker_format( $key = 'date_format' ): string {
				$format      = MPCRBM_Global_Function::get_settings( 'mpcrbm_global_settings', $key, 'D d M , yy' );
				$date_format = 'Y-m-d';
				$date_format = $format == 'yy/mm/dd' ? 'Y/m/d' : $date_format;
				$date_format = $format == 'yy-dd-mm' ? 'Y-d-m' : $date_format;
				$date_format = $format == 'yy/dd/mm' ? 'Y/d/m' : $date_format;
				$date_format = $format == 'dd-mm-yy' ? 'd-m-Y' : $date_format;
				$date_format = $format == 'dd/mm/yy' ? 'd/m/Y' : $date_format;
				$date_format = $format == 'mm-dd-yy' ? 'm-d-Y' : $date_format;
				$date_format = $format == 'mm/dd/yy' ? 'm/d/Y' : $date_format;
				$date_format = $format == 'd M , yy' ? 'j M , Y' : $date_format;
				$date_format = $format == 'D d M , yy' ? 'D j M , Y' : $date_format;
				$date_format = $format == 'M d , yy' ? 'M  j, Y' : $date_format;

				return $format == 'D M d , yy' ? 'D M  j, Y' : $date_format;
			}

			public function date_picker_js( $selector, $dates ) {
				if ( empty( $dates ) ) {
					return;
				}
				// Extract dates
				$start_date = $dates[0];
				$end_date   = end( $dates );
				$all_date   = array_map( function ( $date ) {
					return gmdate( 'j-n-Y', strtotime( $date ) );
				}, $dates );
				// Register and enqueue script
				// Version bumped to 1.0.1 with the minimum-one-day fix in
				// mpcrbm_get_selected_days() — this file is served with a fixed version
				// string, so returning visitors keep the cached copy until it changes.
				wp_register_script(
					'date-picker',
					plugin_dir_url( __FILE__ ) . '../assets/date-picker/date-picker.js', // Corrected path
					[ 'jquery', 'jquery-ui-datepicker' ],
					'1.0.1',
					true
				);
				wp_enqueue_script( 'date-picker' );
				// Localize script for passing PHP data to JS
				wp_add_inline_script( 'date-picker', 'var datePickerData = ' . wp_json_encode( [
						'availableDates' => $all_date,
						'startDate'      => [
							'year'  => gmdate( 'Y', strtotime( $start_date ) ),
							'month' => gmdate( 'n', strtotime( $start_date ) ) - 1,
							'day'   => gmdate( 'j', strtotime( $start_date ) ),
						],
						'endDate'        => [
							'year'  => gmdate( 'Y', strtotime( $end_date ) ),
							'month' => gmdate( 'n', strtotime( $end_date ) ) - 1,
							'day'   => gmdate( 'j', strtotime( $end_date ) ),
						],
					] ), 'before' );
			}

			public static function date_format( $date, $format = 'date' ) {
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				$wp_settings = $date_format . '  ' . $time_format;
				//$timezone = wp_timezone_string();
				$timestamp = strtotime( $date );
				if ( $format == 'date' ) {
					$date = date_i18n( $date_format, $timestamp );
				} elseif ( $format == 'time' ) {
					$date = date_i18n( $time_format, $timestamp );
				} elseif ( $format == 'full' ) {
					$date = date_i18n( $wp_settings, $timestamp );
				} elseif ( $format == 'day' ) {
					$date = date_i18n( 'd', $timestamp );
				} elseif ( $format == 'month' ) {
					$date = date_i18n( 'M', $timestamp );
				} elseif ( $format == 'year' ) {
					$date = date_i18n( 'Y', $timestamp );
				} else {
					$date = date_i18n( $format, $timestamp );
				}

				return $date;
			}

			public static function date_separate_period( $start_date, $end_date, $repeat = 1 ): DatePeriod {
				$repeat    = max( $repeat, 1 );
				$_interval = "P" . $repeat . "D";
				$end_date  = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );

				return new DatePeriod( new DateTime( $start_date ), new DateInterval( $_interval ), new DateTime( $end_date ) );
			}

			public static function check_time_exit_date( $date ) {
				if ( $date ) {
					$parse_date = date_parse( $date );
					if ( ( $parse_date['hour'] && $parse_date['hour'] > 0 ) || ( $parse_date['minute'] && $parse_date['minute'] > 0 ) || ( $parse_date['second'] && $parse_date['second'] > 0 ) ) {
						return true;
					}
				}

				return false;
			}

			public static function check_licensee_date( $date ) {
				if ( $date ) {
					if ( $date == 'lifetime' ) {
						return esc_html__( 'Lifetime', 'car-rental-manager' );
					} else if ( strtotime( current_time( 'Y-m-d H:i' ) ) < strtotime( gmdate( 'Y-m-d H:i', strtotime( $date ) ) ) ) {
						return MPCRBM_Global_Function::date_format( $date, 'full' );
					} else {
						return esc_html__( 'Expired', 'car-rental-manager' );
					}
				}

				return $date;
			}

			public static function sort_date( $a, $b ) {
				return strtotime( $a ) - strtotime( $b );
			}

			public static function sort_date_array( $a, $b ) {
				$dateA = strtotime( $a['time'] );
				$dateB = strtotime( $b['time'] );
				if ( $dateA == $dateB ) {
					return 0;
				} elseif ( $dateA > $dateB ) {
					return 1;
				} else {
					return - 1;
				}
			}

			public static function date_difference( $startdate, $enddate ) {
				$starttimestamp = strtotime( $startdate );
				$endtimestamp   = strtotime( $enddate );
				$difference     = abs( $endtimestamp - $starttimestamp ) / 3600;
				//return $difference;
				$datetime1 = new DateTime( $startdate );
				$datetime2 = new DateTime( $enddate );
				$interval  = $datetime1->diff( $datetime2 );

				return $interval->format( '%h' ) . "H " . $interval->format( '%i' ) . "M";
			}

			//***********************************//
			public static function get_settings( $section, $key, $default = '' ) {
				$options = get_option( $section );
				if ( isset( $options[ $key ] ) ) {
					if ( is_array( $options[ $key ] ) ) {
						if ( ! empty( $options[ $key ] ) ) {
							return $options[ $key ];
						} else {
							return $default;
						}
					} else {
						if ( ! empty( $options[ $key ] ) ) {
							return wp_kses_post( $options[ $key ] );
						} else {
							return $default;
						}
					}
				}
				if ( is_array( $default ) ) {
					return $default;
				} else {
					return wp_kses_post( $default );
				}
			}

			public static function get_style_settings( $key, $default = '' ) {
				return self::get_settings( 'mpcrbm_style_settings', $key, $default );
			}

			public static function get_slider_settings( $key, $default = '' ) {
				return self::get_settings( 'mpcrbm_slider_settings', $key, $default );
			}

			public static function get_licence_settings( $key, $default = '' ) {
				return self::get_settings( 'mpcrbm_license_settings', $key, $default );
			}

			//***********************************//
			public static function price_convert_raw( $price ) {
				$price = wp_strip_all_tags( $price );
				if ( self::check_woocommerce() === 1 ) {
					$price = str_replace( get_woocommerce_currency_symbol(), '', $price );
					$price = str_replace( wc_get_price_thousand_separator(), 't_s', $price );
					$price = str_replace( wc_get_price_decimal_separator(), 'd_s', $price );
					$price = str_replace( 't_s', '', $price );
					$price = str_replace( 'd_s', '.', $price );
				} else {
					// Standalone (no WooCommerce): strip the admin-configured symbol +
					// separators (defaults '$', ',', '.') so the raw number parses back
					// out no matter how native_format_amount() rendered it.
					$cfg   = self::native_currency_config();
					$price = str_replace( $cfg['symbol'], '', $price );
					$price = str_replace( $cfg['thousand_separator'], 't_s', $price );
					$price = str_replace( $cfg['decimal_separator'], 'd_s', $price );
					$price = str_replace( 't_s', '', $price );
					$price = str_replace( 'd_s', '.', $price );
				}
				$price = str_replace( '&nbsp;', '', $price );

				return max( $price, 0 );
			}

			//***** Native (non-WooCommerce) currency formatting *****//

			/** Reads a single value from the Currency Settings tab (mpcrbm_currency_settings). */
			public static function native_currency_setting( $key, $default = '' ) {
				return self::get_settings( 'mpcrbm_currency_settings', $key, $default );
			}

			/**
			 * Resolved native (non-WooCommerce) currency config from the Currency Settings
			 * tab. Read straight from the option rather than through get_settings(): that
			 * helper treats a stored 0 / '' as "empty" and hands back the default, which
			 * would make 0-decimal currencies (JPY, BDT) and an intentionally blank
			 * thousands separator impossible to configure. Shared by the PHP formatter and
			 * the frontend JS constants so display never drifts between the two.
			 *
			 * @return array{symbol:string,position:string,decimals:int,decimal_separator:string,thousand_separator:string,currency_code:string}
			 */
			public static function native_currency_config(): array {
				$opt = get_option( 'mpcrbm_currency_settings' );
				$opt = is_array( $opt ) ? $opt : array();

				return array(
					'symbol'             => isset( $opt['symbol'] ) && '' !== $opt['symbol'] ? $opt['symbol'] : '$',
					'position'           => isset( $opt['position'] ) && '' !== $opt['position'] ? $opt['position'] : 'left',
					'decimals'           => isset( $opt['decimals'] ) && '' !== $opt['decimals'] ? max( 0, (int) $opt['decimals'] ) : 2,
					'decimal_separator'  => isset( $opt['decimal_separator'] ) && '' !== $opt['decimal_separator'] ? $opt['decimal_separator'] : '.',
					'thousand_separator' => array_key_exists( 'thousand_separator', $opt ) ? $opt['thousand_separator'] : ',',
					'currency_code'      => isset( $opt['currency_code'] ) && '' !== $opt['currency_code'] ? $opt['currency_code'] : 'USD',
				);
			}

			/**
			 * Formats a raw amount using the Currency Settings tab (symbol, position,
			 * decimals, separators) — the standalone counterpart of wc_price(). Falls back
			 * to "$1,234.56" defaults when nothing has been configured yet.
			 */
			public static function native_format_amount( $amount ): string {
				$cfg       = self::native_currency_config();
				$formatted = number_format( (float) $amount, $cfg['decimals'], $cfg['decimal_separator'], $cfg['thousand_separator'] );

				switch ( $cfg['position'] ) {
					case 'right':
						return $formatted . $cfg['symbol'];
					case 'left_space':
						return $cfg['symbol'] . ' ' . $formatted;
					case 'right_space':
						return $formatted . ' ' . $cfg['symbol'];
					case 'left':
					default:
						return $cfg['symbol'] . $formatted;
				}
			}

			/**
			 * WooCommerce-safe price formatter for display. Uses wc_price() when
			 * WooCommerce is active, otherwise formats through the Currency Settings tab so
			 * standalone (Custom Payment) prices carry the configured symbol/position
			 * instead of a bare number.
			 */
			public static function format_price( $price ) {
				if ( self::check_woocommerce() === 1 && function_exists( 'wc_price' ) ) {
					return wc_price( $price );
				}

				return self::native_format_amount( $price );
			}

			public static function wc_price( $post_id, $price, $args = array() ): string {
				// Standalone (Custom Payment) mode: WooCommerce's tax engine and wc_price()
				// are both unavailable, so format through the Currency Settings tab instead.
				if ( self::check_woocommerce() !== 1 ) {
					if ( '' === $price ) {
						return '';
					}
					$qty = ( is_array( $args ) && isset( $args['qty'] ) && '' !== $args['qty'] ) ? max( 0.0, (float) $args['qty'] ) : 1;

					return self::native_format_amount( (float) $price * $qty );
				}

				$num_of_decimal = get_option( 'woocommerce_price_num_decimals', 2 );
				$args           = wp_parse_args( $args, array(
					'qty'   => '',
					'price' => '',
				) );
				$_product       = self::get_post_info( $post_id, 'link_wc_product', $post_id );
				$product        = wc_get_product( $_product );
				$qty            = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;
				$tax_with_price = get_option( 'woocommerce_tax_display_shop' );
				if ( '' === $price ) {
					return '';
				} elseif ( empty( $qty ) ) {
					return 0.0;
				}
				$line_price   = (float) $price * (int) $qty;
				$return_price = $line_price;
				if ( $product && $product->is_taxable() ) {
					if ( ! wc_prices_include_tax() ) {
						$tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
						$taxes     = WC_Tax::calc_tax( $line_price, $tax_rates );
						if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
							$taxes_total = array_sum( $taxes );
						} else {
							$taxes_total = array_sum( array_map( 'wc_round_tax_total', $taxes ) );
						}
						$return_price = $tax_with_price == 'excl' ? round( $line_price, $num_of_decimal ) : round( $line_price + $taxes_total, $num_of_decimal );
					} else {
						$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
						$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
						if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) { // @codingStandardsIgnoreLine.
							$remove_taxes = WC_Tax::calc_tax( $line_price, $tax_rates, true );
							if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
								$remove_taxes_total = array_sum( $remove_taxes );
							} else {
								$remove_taxes_total = array_sum( array_map( 'wc_round_tax_total', $remove_taxes ) );
							}
							// $return_price = round( $line_price, $num_of_decimal);
							$return_price = round( $line_price - $remove_taxes_total, $num_of_decimal );
						} else {
							$base_taxes   = WC_Tax::calc_tax( $line_price, $base_tax_rates, true );
							$modded_taxes = WC_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates );
							if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
								$base_taxes_total   = array_sum( $base_taxes );
								$modded_taxes_total = array_sum( $modded_taxes );
							} else {
								$base_taxes_total   = array_sum( array_map( 'wc_round_tax_total', $base_taxes ) );
								$modded_taxes_total = array_sum( array_map( 'wc_round_tax_total', $modded_taxes ) );
							}
							$return_price = $tax_with_price == 'excl' ? round( $line_price - $base_taxes_total, $num_of_decimal ) : round( $line_price - $base_taxes_total + $modded_taxes_total, $num_of_decimal );
						}
					}
				}
				//$return_price   = apply_filters( 'woocommerce_get_price_including_tax', $return_price, $qty, $product );
				$display_suffix = get_option( 'woocommerce_price_display_suffix' ) ? get_option( 'woocommerce_price_display_suffix' ) : '';

				return wc_price( $return_price ) . ' ' . $display_suffix;
			}

			public static function get_wc_raw_price( $post_id, $price, $args = array() ) {
				$price = self::wc_price( $post_id, $price, $args = array() );

				return self::price_convert_raw( $price );
			}

			//***********************************//
			public static function get_image_url( $post_id = '', $image_id = '', $size = 'full' ) {
				if ( $post_id ) {
					$image_id = get_post_thumbnail_id( $post_id );
					$image_id = $image_id ?: self::get_post_info( $post_id, 'mp_thumbnail' );
				}

				return wp_get_attachment_image_url( $image_id, $size );
			}

			public static function get_page_by_slug( $slug ) {
				if ( $pages = get_pages() ) {
					foreach ( $pages as $page ) {
						if ( $slug === $page->post_name ) {
							return $page;
						}
					}
				}

				return false;
			}

			public static function get_id_by_slug( $page_slug ) {
				$page = get_page_by_path( $page_slug );
				if ( $page ) {
					return $page->ID;
				} else {
					return null;
				}
			}

			//***********************************//
			public static function check_plugin( $plugin_dir_name, $plugin_file ): int {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_dir_name;
				if ( is_plugin_active( $plugin_dir_name . '/' . $plugin_file ) ) {
					return 1;
				} elseif ( is_dir( $plugin_dir ) ) {
					return 2;
				} else {
					return 0;
				}
			}

			public static function check_woocommerce(): int {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_dir = WP_PLUGIN_DIR . '/woocommerce';
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					return 1;
				} elseif ( is_dir( $plugin_dir ) ) {
					return 2;
				} else {
					return 0;
				}
			}

			/**
			 * The currency symbol to show beside a bare number.
			 *
			 * WooCommerce owns the symbol when it is active; otherwise it comes from the
			 * plugin's own Currency Settings tab. Callers used to reach straight for
			 * get_woocommerce_currency_symbol(), which is a fatal error in Custom Payment
			 * mode where WooCommerce isn't loaded at all.
			 */
			public static function currency_symbol( $currency = '' ): string {
				if ( self::check_woocommerce() === 1 && function_exists( 'get_woocommerce_currency_symbol' ) ) {
					return get_woocommerce_currency_symbol( $currency );
				}

				return self::native_currency_config()['symbol'];
			}

			/**
			 * The ISO currency CODE (USD, EUR, …) — what payment gateways need, as opposed
			 * to the display symbol above. WooCommerce owns it when active; otherwise it
			 * comes from the plugin's Currency Settings tab.
			 */
			public static function currency_code(): string {
				if ( self::check_woocommerce() === 1 && function_exists( 'get_woocommerce_currency' ) ) {
					return get_woocommerce_currency();
				}

				return strtoupper( self::native_currency_config()['currency_code'] );
			}

			/**
			 * WooCommerce-safe wc_get_order().
			 *
			 * In Custom Payment (standalone) mode there is no WooCommerce, and bookings
			 * store mpcrbm_order_id = 0 — so any code that reaches for the "linked order"
			 * has to cope with there not being one, and with wc_get_order() itself not
			 * existing. Returns null in both cases instead of fataling.
			 *
			 * @return WC_Order|WC_Order_Refund|null
			 */
			public static function safe_wc_order( $order_id ) {
				$order_id = absint( $order_id );
				if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
					return null;
				}
				$order = wc_get_order( $order_id );

				return $order ? $order : null;
			}

			/**
			 * WooCommerce-safe wc_get_order_statuses(). Empty array when WooCommerce is
			 * inactive, so status dropdowns render empty rather than crashing the screen.
			 */
			public static function safe_wc_order_statuses(): array {
				return function_exists( 'wc_get_order_statuses' ) ? (array) wc_get_order_statuses() : array();
			}

			/**
			 * WooCommerce-safe wc_get_orders(). Returns an empty array when WooCommerce is
			 * inactive, so screens that list a customer's or branch's WooCommerce orders
			 * simply come up empty in Custom Payment mode instead of fataling.
			 */
			public static function safe_wc_orders( $args = array() ): array {
				if ( ! function_exists( 'wc_get_orders' ) ) {
					return array();
				}

				return (array) wc_get_orders( $args );
			}

			public static function get_order_item_meta( $item_id, $key ): string {
				// Order items only exist inside WooCommerce. In Custom Payment mode there
				// is no order to read from, and the function itself is undefined.
				if ( ! function_exists( 'wc_get_order_item_meta' ) ) {
					return '';
				}
				// wc_get_order_item_meta handles caching and the database query for you
				$value = wc_get_order_item_meta( $item_id, $key, true );
				return is_string( $value ) ? $value : '';
			}

			public static function check_product_in_cart( $post_id ) {
				$status = MPCRBM_Global_Function::check_woocommerce();
				if ( $status == 1 ) {
					$product_id = MPCRBM_Global_Function::get_post_info( $post_id, 'link_wc_product' );
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						if ( $cart_item['product_id'] == $product_id ) {
							return true;
						}
					}
				}

				return false;
			}

			public static function wc_product_sku( $product_id ) {
				if ( $product_id && class_exists( 'WC_Product' ) ) {
					return new WC_Product( $product_id );
				}

				return null;
			}

			//***********************************//
			/**
			 * Tax classes offered on the per-car Tax settings tab.
			 *
			 * WooCommerce owns tax classes, and WC_Tax simply does not exist in Custom
			 * Payment mode — calling it there fataled the whole car add/edit screen. The
			 * tab is still rendered (so a site that later switches to WooCommerce keeps
			 * its saved value), just with only the standard rate to choose from.
			 */
			public static function all_tax_list(): array {
				// Standard rate is always offered: it is WooCommerce's implicit default and
				// never appears in get_tax_classes().
				$tax_list = array(
					'standard' => __( 'Standard rate', 'car-rental-manager' ),
				);

				if ( ! class_exists( 'WC_Tax' ) ) {
					return $tax_list;
				}

				foreach ( WC_Tax::get_tax_classes() as $class ) {
					$tax_list[ sanitize_title( $class ) ] = $class;
				}

				return $tax_list;
			}

			public static function week_day(): array {
				return [
					'monday'    => esc_html__( 'Monday', 'car-rental-manager' ),
					'tuesday'   => esc_html__( 'Tuesday', 'car-rental-manager' ),
					'wednesday' => esc_html__( 'Wednesday', 'car-rental-manager' ),
					'thursday'  => esc_html__( 'Thursday', 'car-rental-manager' ),
					'friday'    => esc_html__( 'Friday', 'car-rental-manager' ),
					'saturday'  => esc_html__( 'Saturday', 'car-rental-manager' ),
					'sunday'    => esc_html__( 'Sunday', 'car-rental-manager' ),
				];
			}

			//***********************************//
			public static function license_error_text( $response, $license_data, $plugin_name ) {
				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
					$message = ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : esc_html__( 'An error occurred, please try again.', 'car-rental-manager' );
				} else {
					if ( false === $license_data->success ) {
						switch ( $license_data->error ) {
							case 'expired':
								$message = esc_html__( 'Your license key expired on', 'car-rental-manager' ) . ' ' .
								           date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) );
								break;
							case 'revoked':
								$message = esc_html__( 'Your license key has been disabled.', 'car-rental-manager' );
								break;
							case 'missing':
								$message = esc_html__( 'Missing license.', 'car-rental-manager' );
								break;
							case 'invalid':
								$message = esc_html__( 'Invalid license.', 'car-rental-manager' );
								break;
							case 'site_inactive':
								$message = esc_html__( 'Your license is not active for this URL.', 'car-rental-manager' );
								break;
							case 'item_name_mismatch':
								$message = esc_html__( 'This appears to be an invalid license key for', 'car-rental-manager' ) . ' ' . $plugin_name . '.';
								break;
							case 'no_activations_left':
								$message = esc_html__( 'Your license key has reached its activation limit.', 'car-rental-manager' );
								break;
							default:
								$message = esc_html__( 'An error occurred, please try again.', 'car-rental-manager' );
								break;
						}
					} else {
						$payment_id = $license_data->payment_id;
						$expire     = $license_data->expires;
						$message    = esc_html__( 'Success, License Key is valid for the plugin', 'car-rental-manager' ) . ' ' . $plugin_name . ' ' . esc_html__( 'Your Order id is', 'car-rental-manager' ) . ' ' . $payment_id . ' ' . $plugin_name . ' ' . esc_html__( 'Validity of this license is', 'car-rental-manager' ) . ' ' . MPCRBM_Global_Function::check_licensee_date( $expire );
					}
				}

				return $message;
			}

			//***********************************//
			public static function array_to_string( $array ) {
				$ids = '';
				if ( sizeof( $array ) > 0 ) {
					foreach ( $array as $data ) {
						if ( $data ) {
							$ids = $ids ? $ids . ',' . $data : $data;
						}
					}
				}

				return $ids;
			}

			//***********************************//
			public static function hasDecimal( $number ) {
				return fmod( $number, 1 ) != 0;
			}

            public static function format_custom_time($time_value) {
                $parts = explode('.', $time_value);
                $hour = isset($parts[0]) ? (int)$parts[0] : 0;
                $minute = isset($parts[1]) ? (int)$parts[1] : 0;
                if ($minute < 10 && isset($parts[1]) && strlen($parts[1]) == 1) {
                    $minute = $minute * 10;
                }
                $formatted = sprintf('%02d:%02d', $hour, $minute);
                return gmdate('g.ia', strtotime($formatted));
            }

            public static function get_meta_key( $post_ids ){
                $meta_keys = [
                    'mpcrbm_car_type',
                    'mpcrbm_fuel_type',
                    'mpcrbm_seating_capacity',
                    'mpcrbm_car_brand',
                    'mpcrbm_make_year',
                ];

                $result = [];

                foreach ($meta_keys as $key) {
                    $result[$key] = [];
                }

                foreach ($post_ids as $post_id) {
                    foreach ($meta_keys as $key) {
                        $value = get_post_meta($post_id, $key, true);

                        // Unserialize if needed. Security: object-safe, see safe_maybe_unserialize().
                        if (is_serialized($value)) {
                            $value = self::safe_maybe_unserialize($value);
                        }

                        // Merge arrays or single values
                        if (is_array($value)) {
                            $result[$key] = array_merge($result[$key], $value);
                        } elseif (!empty($value)) {
                            $result[$key][] = $value;
                        }
                    }
                }
                foreach ($result as $key => $values) {
                    $result[$key] = array_values(array_unique($values));
                }


                return $result;
            }

            public static function mpcrbm_get_car_data() {
                $args = array(
                    'post_type'      => 'mpcrbm_rent',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                );

                $query = new WP_Query($args);

                $cars_data = array();

                $meta_keys = [
                    'mpcrbm_car_type',
                    'mpcrbm_fuel_type',
                    'mpcrbm_seating_capacity',
                    'mpcrbm_car_brand',
                    'mpcrbm_make_year',
                ];

                $meta_data = array_fill_keys( $meta_keys, [] );

                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $post_id = get_the_ID();
                        $post_date = get_the_date('Y-m-d');

                        $mpcrbm_car_type         = get_post_meta($post_id, 'mpcrbm_car_type', true);
                        $mpcrbm_fuel_type        = get_post_meta($post_id, 'mpcrbm_fuel_type', true);
                        $mpcrbm_seating_capacity = get_post_meta($post_id, 'mpcrbm_seating_capacity', true);
                        $mpcrbm_car_brand        = get_post_meta($post_id, 'mpcrbm_car_brand', true);
                        $mpcrbm_make_year        = get_post_meta($post_id, 'mpcrbm_make_year', true);

                        $mpcrbm_car_type         = (array) (!empty($mpcrbm_car_type) ? $mpcrbm_car_type : []);
                        $mpcrbm_fuel_type        = (array) (!empty($mpcrbm_fuel_type) ? $mpcrbm_fuel_type : []);
                        $mpcrbm_seating_capacity = (array) (!empty($mpcrbm_seating_capacity) ? $mpcrbm_seating_capacity : []);
                        $mpcrbm_car_brand        = (array) (!empty($mpcrbm_car_brand) ? $mpcrbm_car_brand : []);
                        $mpcrbm_make_year        = (array) (!empty($mpcrbm_make_year) ? $mpcrbm_make_year : []);

                        $meta_data['mpcrbm_car_type']         = array_merge( $meta_data['mpcrbm_car_type'], $mpcrbm_car_type );
                        $meta_data['mpcrbm_fuel_type']        = array_merge( $meta_data['mpcrbm_fuel_type'], $mpcrbm_fuel_type );
                        $meta_data['mpcrbm_seating_capacity'] = array_merge( $meta_data['mpcrbm_seating_capacity'], $mpcrbm_seating_capacity );
                        $meta_data['mpcrbm_car_brand']        = array_merge( $meta_data['mpcrbm_car_brand'], $mpcrbm_car_brand );
                        $meta_data['mpcrbm_make_year']        = array_merge( $meta_data['mpcrbm_make_year'], $mpcrbm_make_year );

                        $cars_data[] = array(
                            'id'        => $post_id,
                            'title'     => get_the_title(),
                            'type'      => $mpcrbm_car_type,
                            'fuel_type' => $mpcrbm_fuel_type,
                            'capacity'  => $mpcrbm_seating_capacity,
                            'brand'     => $mpcrbm_car_brand,
                            'year'      => $mpcrbm_make_year,
                            'price'     => get_post_meta($post_id, 'mpcrbm_day_price', true),
                            'status'    => get_post_status($post_id),
                            'post_date'    => $post_date,
                        );
                    }

                    foreach ( $meta_data as $key => $values ) {
                        $meta_data[$key] = array_values(array_unique(array_filter($values)));
                    }

                    wp_reset_postdata();
                }

                return [
                    'cars'  => $cars_data,
                    'meta'  => $meta_data,
                ];
            }

            public static function get_all_cars_with_stock() {

                $args = array(
                    'post_type'      => 'mpcrbm_rent',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                );

                $query = new WP_Query( $args );
                $cars = [];
                if ( ! empty( $query->posts ) ) {
                    foreach ( $query->posts as $car_id ) {
//                        $stock = (int) get_post_meta( $car_id, 'mpcrbm_car_stock', true );
                        $stock = (int) MPCRBM_Global_Function::get_post_info( $car_id, 'mpcrbm_car_stock', 1 );
                        if ( $stock > 0 ) {
                            $cars[$car_id] = $stock;
                        }
                    }
                }
                return $cars;
            }

            public static function get_available_cars_by_datetime( $given_datetime ) {
                $cars = self::get_all_cars_with_stock();
                if ( empty( $cars ) ) {
                    return [];
                }

                $args = array(
                    'post_type'      => 'mpcrbm_booking',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query		
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'mpcrbm_date',
                            'value'   => $given_datetime,
                            'compare' => '<=',
                            'type'    => 'DATETIME',
                        ),
                        array(
                            'key'     => 'return_date_time',
                            'value'   => $given_datetime,
                            'compare' => '>=',
                            'type'    => 'DATETIME',
                        ),
                        array(
                            'key'     => 'mpcrbm_order_status',
                            'value'   => array( 'cancelled', 'refunded', 'failed' ),
                            'compare' => 'NOT IN',
                        ),
                    ),
                );
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                $query = new WP_Query( $args );

                $booked_counts = [];

                if ( ! empty( $query->posts ) ) {

                    foreach ( $query->posts as $post_id ) {

                        $car_id = get_post_meta( $post_id, 'mpcrbm_id', true );
                        $qty    = (int) get_post_meta( $post_id, 'mpcrbm_car_quantity', true );

                        if ( empty( $car_id ) ) {
                            continue;
                        }

                        if ( ! isset( $booked_counts[$car_id] ) ) {
                            $booked_counts[$car_id] = 0;
                        }

                        $booked_counts[$car_id] += $qty;
                    }
                }

                $available_cars = [];
                $unavailable_cars = [];

                foreach ( $cars as $car_id => $stock ) {

                    $booked    = isset( $booked_counts[$car_id] ) ? $booked_counts[$car_id] : 0;
                    $available = $stock - $booked;

                    if ( $available > 0 ) {
                        $available_cars[$car_id] = [
                            'total_stock' => $stock,
                            'booked'      => $booked,
                            'available'   => $available,
                        ];
                    }else{
                        $unavailable_cars[] = $car_id;
                    }
                }

                $data = array(
                    'available_cars'    =>$available_cars,
                    'unavailable_cars'  =>$unavailable_cars,
                );
                return $data;
            }

            public static function get_unavailable_car_ids_by_datetime( $given_datetime ) {

                // 🔹 Step 1: Get all cars with stock
                $car_args = array(
                    'post_type'      => 'mpcrbm_car',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                );

                $car_query = new WP_Query( $car_args );

                if ( empty( $car_query->posts ) ) {
                    return [];
                }

                $cars = [];

                foreach ( $car_query->posts as $car_id ) {
//                    $stock = (int) get_post_meta( $car_id, 'mpcrbm_car_stock', true );
                    $stock = 5;
                    $cars[$car_id] = $stock;
                }

                // 🔹 Step 2: Get active bookings for that datetime
                $booking_args = array(
                    'post_type'      => 'mpcrbm_booking',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => 'mpcrbm_date',
                            'value'   => $given_datetime,
                            'compare' => '<=',
                            'type'    => 'DATETIME',
                        ),
                        array(
                            'key'     => 'return_date_time',
                            'value'   => $given_datetime,
                            'compare' => '>=',
                            'type'    => 'DATETIME',
                        ),
                        array(
                            'key'     => 'mpcrbm_order_status',
                            'value'   => array( 'cancelled', 'refunded', 'failed' ),
                            'compare' => 'NOT IN',
                        ),
                    ),
                );
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                $booking_query = new WP_Query( $booking_args );

                $booked_counts = [];

                if ( ! empty( $booking_query->posts ) ) {

                    foreach ( $booking_query->posts as $post_id ) {

                        $car_id = get_post_meta( $post_id, 'mpcrbm_id', true );
                        $qty    = (int) get_post_meta( $post_id, 'mpcrbm_car_quantity', true );
                        // error_log( print_r( [ '$car_id'  =>$car_id, '$qty' =>$qty ], true ) );

                        if ( empty( $car_id ) ) {
                            continue;
                        }

                        if ( ! isset( $booked_counts[$car_id] ) ) {
                            $booked_counts[$car_id] = 0;
                        }

                        $booked_counts[$car_id] += $qty;
                    }
                }

                // 🔹 Step 3: Compare stock vs booked
                $unavailable_ids = [];

                foreach ( $cars as $car_id => $stock ) {

                    $booked = isset( $booked_counts[$car_id] ) ? $booked_counts[$car_id] : 0;

                    if ( $booked >= $stock && $stock > 0 ) {
                        $unavailable_ids[] = $car_id;
                    }
                }

                return $unavailable_ids;
            }



        }

		new MPCRBM_Global_Function();
	}
