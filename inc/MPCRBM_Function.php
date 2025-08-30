<?php
	/*
	* @Author 		MagePeople Team
	* Copyright: 	mage-people.com
	*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Function' ) ) {
		class MPCRBM_Function {
			//**************Support multi Language*********************//
			public static function post_id_multi_language( $post_id ) {
				if ( function_exists( 'wpml_loaded' ) ) {
					global $sitepress;
					$default_language = function_exists( 'wpml_loaded' ) ? $sitepress->get_default_language() : get_locale();

					return apply_filters( 'wpml_object_id', $post_id, MPCRBM_Function::get_cpt(), true, $default_language );
				}
				if ( function_exists( 'pll_get_post_translations' ) ) {
					$defaultLanguage = function_exists( 'pll_default_language' ) ? pll_default_language() : get_locale();
					$translations    = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post_id ) : [];

					return sizeof( $translations ) > 0 ? $translations[ $defaultLanguage ] : $post_id;
				}

				return $post_id;
			}

			public static function get_schedule( $post_id ) {
				$days      = MPCRBM_Global_Function::week_day();
				$days_name = array_keys( $days );
				$all_empty = true;
				$schedule  = [];
				foreach ( $days_name as $name ) {
					$start_time = get_post_meta( $post_id, "mpcrbm_" . $name . "_start_time", true );
					$end_time   = get_post_meta( $post_id, "mpcrbm_" . $name . "_end_time", true );
					if ( $start_time !== "" && $end_time !== "" ) {
						$schedule[ $name ] = [ $start_time, $end_time ];
					}
				}
				foreach ( $schedule as $times ) {
					if ( ! empty( $times[0] ) || ! empty( $times[1] ) ) {
						$all_empty = false;
						break;
					}
				}
				if ( $all_empty ) {
					$default_start_time  = get_post_meta( $post_id, "mpcrbm_default_start_time", true );
					$default_end_time    = get_post_meta( $post_id, "mpcrbm_default_end_time", true );
					$schedule['default'] = [ $default_start_time, $default_end_time ];
				}

				return $schedule;
			}

			public static function details_template_path(): string {
				$tour_id       = get_the_id();
				$template_name = MPCRBM_Global_Function::get_post_info( $tour_id, 'mpcrbm_theme_file', 'default.php' );
				$file_name     = 'themes/' . $template_name;
				$dir           = MPCRBM_PLUGIN_DIR . '/templates/' . $file_name;
				if ( ! file_exists( $dir ) ) {
					$file_name = 'themes/default.php';
				}

				return self::template_path( $file_name );
			}

			public static function get_taxonomy_name_by_slug( $slug, $taxonomy ) {
				global $wpdb;
				// Prepare the query
				$query = $wpdb->prepare(
					"SELECT t.name 
                 FROM {$wpdb->terms} t
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                 WHERE t.slug = %s AND tt.taxonomy = %s",
					$slug,
					$taxonomy
				);
				// Execute the query
				$term_name = $wpdb->get_var( $query );

				return $term_name;
			}

			public static function template_path( $file_name ): string {
				$template_path = get_stylesheet_directory() . '/mpcrbm_templates/';
				$default_dir   = MPCRBM_PLUGIN_DIR . '/templates/';
				$dir           = is_dir( $template_path ) ? $template_path : $default_dir;
				$file_path     = $dir . $file_name;

				return locate_template( array( 'mpcrbm_templates/' . $file_name ) ) ? $file_path : $default_dir . $file_name;
			}

			//************************//
			public static function get_general_settings( $key, $default = '' ) {
				return MPCRBM_Global_Function::get_settings( 'mpcrbm_general_settings', $key, $default );
			}

			public static function get_cpt(): string {
				return 'mpcrbm_rent';
			}

			public static function get_name() {
				return self::get_general_settings( 'label', esc_html__( 'Car', 'car-rental-manager' ) );
			}

			public static function get_slug() {
				return self::get_general_settings( 'slug', 'Car' );
			}

			public static function get_icon() {
				return self::get_general_settings( 'icon', 'dashicons-car' );
			}

			public static function get_category_label() {
				return self::get_general_settings( 'category_label', esc_html__( 'Category', 'car-rental-manager' ) );
			}

			public static function get_category_slug() {
				return self::get_general_settings( 'category_slug', 'Car-category' );
			}

			public static function get_organizer_label() {
				return self::get_general_settings( 'organizer_label', esc_html__( 'Organizer', 'car-rental-manager' ) );
			}

			public static function get_organizer_slug() {
				return self::get_general_settings( 'organizer_slug', 'Car-organizer' );
			}
			//*************************************************************Full Custom Function******************************//
			//*************Date*********************************//
			public static function get_date( $post_id, $expire = false ) {
				$now           = current_time( 'Y-m-d' );
				$date_type     = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_date_type', 'repeated' );
				$all_dates     = [];
				$off_days      = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_off_days' );
				$all_off_days  = explode( ',', $off_days );
				$all_off_dates = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_off_dates', array() );
				$off_dates     = [];
				foreach ( $all_off_dates as $off_date ) {
					$off_dates[] = gmdate( 'Y-m-d', strtotime( $off_date ) );
				}
				if ( $date_type == 'repeated' ) {
					$start_date = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_repeated_start_date', $now );
					if ( strtotime( $now ) >= strtotime( $start_date ) && ! $expire ) {
						$start_date = $now;
					}
					$repeated_after = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_repeated_after', 1 );
					$active_days    = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_active_days', 10 ) - 1;
					$end_date       = gmdate( 'Y-m-d', strtotime( $start_date . ' +' . $active_days . ' day' ) );
					$dates          = MPCRBM_Global_Function::date_separate_period( $start_date, $end_date, $repeated_after );
					foreach ( $dates as $date ) {
						$date = $date->format( 'Y-m-d' );
						$day  = strtolower( gmdate( 'l', strtotime( $date ) ) );
						if ( ! in_array( $date, $off_dates ) && ! in_array( $day, $all_off_days ) ) {
							$all_dates[] = $date;
						}
					}
				} else {
					$particular_date_lists = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_particular_dates', array() );
					if ( sizeof( $particular_date_lists ) ) {
						foreach ( $particular_date_lists as $particular_date ) {
							if ( $particular_date && ( $expire || strtotime( $now ) <= strtotime( $particular_date ) ) && ! in_array( $particular_date, $off_dates ) && ! in_array( $particular_date, $all_off_days ) ) {
								$all_dates[] = $particular_date;
							}
						}
					}
				}

				return apply_filters( 'mpcrbm_get_date', $all_dates, $post_id );
			}

			public static function get_all_dates( $price_based = 'dynamic', $expire = false ) {
				$all_posts = MPCRBM_Query::query_transport_list( $price_based );
				$all_dates = [];
				if ( $all_posts->found_posts > 0 ) {
					$posts = $all_posts->posts;
					foreach ( $posts as $post ) {
						$post_id   = $post->ID;
						$dates     = MPCRBM_Function::get_date( $post_id, $expire );
						$all_dates = array_merge( $all_dates, $dates );
					}
				}
				$all_dates = array_unique( $all_dates );
				usort( $all_dates, "MPCRBM_Global_Function::sort_date" );

				return $all_dates;
			}

			//*************Price*********************************//
			public static function get_price( $post_id, $start_place = '', $destination_place = '', $start_date_time = '', $return_date_time = '' ) {
				if ( session_status() !== PHP_SESSION_ACTIVE ) {
					session_start();
				}
				// Create DateTime objects from the input strings
				$startDate  = new DateTime( $start_date_time );
				$returnDate = new DateTime( $return_date_time );
				// Calculate the difference
				$interval = $startDate->diff( $returnDate );
				// Convert the difference to total minutes
				$minutes        = ( $interval->days * 24 * 60 ) + ( $interval->h * 60 ) + $interval->i;
				$minutes_to_day = ceil( $minutes / 1440 );
				$manual_prices  = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_terms_price_info', [] );
				if ( sizeof( $manual_prices ) > 0 ) {
					foreach ( $manual_prices as $manual_price ) {
						$price_per_day = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_day_price', 0 );
						$price         = $price_per_day * $minutes_to_day;
					}
				}
				if ( class_exists( 'MPCRBM_Datewise_Discount_Addon' ) ) {
					if ( isset( $_POST['mpcrbm_transportation_type_nonce'] ) ) {
						$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_transportation_type_nonce'] ) );
						if ( ! wp_verify_nonce( $nonce, 'mpcrbm_transportation_type_nonce' ) ) {
							wp_die( esc_html__( 'Nonce verification failed', 'car-rental-manager' ) );
						}
					}
					$selected_start_date = isset( $_POST["start_date"] ) ? sanitize_text_field( wp_unslash( $_POST["start_date"] ) ) : "";
					$selected_start_time = isset( $_POST["start_time"] ) ? sanitize_text_field( wp_unslash( $_POST["start_time"] ) ) : "";
					if ( strlen( $selected_start_time ) == 2 ) {
						$selected_start_time .= ":00";
					}
					$selected_start_date = gmdate( 'Y-m-d', strtotime( $selected_start_date ) );
					$selected_start_time = gmdate( 'H:i', strtotime( $selected_start_time ) );
					$discounts           = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_discounts', [] );
					if ( ! empty( $discounts ) ) {
						foreach ( $discounts as $discount ) {
							$start_date = isset( $discount['start_date'] ) ? gmdate( 'Y-m-d', strtotime( $discount['start_date'] ) ) : '';
							$end_date   = isset( $discount['end_date'] ) ? gmdate( 'Y-m-d', strtotime( $discount['end_date'] ) ) : '';
							$time_slots = $discount['time_slots'] ?? [];
							if ( $selected_start_date >= $start_date && $selected_start_date <= $end_date ) {
								foreach ( $time_slots as $slot ) {
									$start_time = isset( $slot['start_time'] ) ? gmdate( 'H:i', strtotime( $slot['start_time'] ) ) : '';
									$end_time   = isset( $slot['end_time'] ) ? gmdate( 'H:i', strtotime( $slot['end_time'] ) ) : '';
									$percentage = floatval( rtrim( $slot['percentage'], '%' ) );
									$type       = $slot['type'] ?? 'increase'; // Use default if not set
									if ( $selected_start_time >= $start_time && $selected_start_time <= $end_time ) {
										$discount_amount = ( $percentage / 100 ) * $price;
										if ( $type === 'decrease' ) {
											$price -= abs( $discount_amount );
										} else {
											$price += $discount_amount;
										}
									}
								}
							}
						}
					}
				}
				// Check if session key exists for the specific post_id
				if ( isset( $_SESSION[ 'geo_fence_post_' . $post_id ] ) ) {
					$session_data = sanitize_text_field( $_SESSION[ 'geo_fence_post_' . $post_id ] );
					if ( isset( $session_data[0] ) ) {
						if ( isset( $session_data[1] ) && $session_data[1] == 'geo-fence-fixed-price' ) {
							$price += (float) $session_data[0];
						} else {
							$price += ( (float) $session_data[0] / 100 ) * $price;
						}
					}
				}
				session_write_close();

//                $price = self::mpcrbm_calculate_price( $post_id, $start_date_time, $minutes_to_day, $price );

				//delete_transient('original_price_based');
				return $price;
			}

			public static function calculate_return_discount( $post_id, $price ) {
				$return_discount = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_return_discount', 0 );
				// Check if the return discount is a percentage or a fixed amount
				if ( strpos( $return_discount, '%' ) !== false ) {
					// It's a percentage discount
					$percentage             = floatval( trim( $return_discount, '%' ) );
					$return_discount_amount = ( $percentage / 100 ) * $price;
				} else {
					// It's a fixed amount discount
					$return_discount_amount = floatval( $return_discount );
				}

				return $return_discount_amount;
			}

			public static function get_extra_service_price_by_name( $post_id, $service_name ) {
				$display_extra_services = MPCRBM_Global_Function::get_post_info( $post_id, 'display_mpcrbm_extra_services', 'on' );
				$service_id             = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_extra_services_id', $post_id );
				$extra_services         = MPCRBM_Global_Function::get_post_info( $service_id, 'mpcrbm_extra_service_infos', [] );
				$price                  = 0;
				if ( $display_extra_services == 'on' && is_array( $extra_services ) && sizeof( $extra_services ) > 0 ) {
					foreach ( $extra_services as $service ) {
						$ex_service_name = array_key_exists( 'service_name', $service ) ? $service['service_name'] : '';
						if ( $ex_service_name == $service_name ) {
							return array_key_exists( 'service_price', $service ) ? $service['service_price'] : 0;
						}
					}
				}

				return $price;
			}

			public static function get_all_start_location( $post_id = '' ) {
				$all_location = [];
				if ( $post_id && $post_id > 0 ) {
					$manual_prices         = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_manual_price_info', [] );
					$terms_location_prices = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_terms_start_location', [] );
					if ( sizeof( $manual_prices ) > 0 ) {
						foreach ( $manual_prices as $manual_price ) {
							$start_location = array_key_exists( 'start_location', $manual_price ) ? $manual_price['start_location'] : '';
							if ( $start_location ) {
								$all_location[] = $start_location;
							}
						}
					}
				} else {
					$all_posts = MPCRBM_Query::query_transport_list( 'manual' );
					if ( $all_posts->found_posts > 0 ) {
						$posts = $all_posts->posts;
						foreach ( $posts as $post ) {
							$post_id               = $post->ID;
							$manual_prices         = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_manual_price_info', [] );
							$terms_location_prices = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_terms_price_info', [] );
							if ( sizeof( $manual_prices ) > 0 ) {
								foreach ( $manual_prices as $manual_price ) {
									$start_location = array_key_exists( 'start_location', $manual_price ) ? $manual_price['start_location'] : '';
									if ( $start_location ) {
										$all_location[] = $start_location;
									}
								}
							}
							if ( sizeof( $terms_location_prices ) > 0 ) {
								foreach ( $terms_location_prices as $terms_location_price ) {
									$start_location = array_key_exists( 'start_location', $terms_location_price ) ? $terms_location_price['start_location'] : '';
									if ( $start_location ) {
										$all_location[] = $start_location;
									}
								}
							}
						}
					}
				}

				return array_unique( $all_location );
			}

			public static function get_end_location( $start_place, $post_id = '' ) {
				$all_location = [];
				if ( $post_id && $post_id > 0 ) {
					$manual_prices = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_manual_price_info', [] );
					if ( sizeof( $manual_prices ) > 0 ) {
						foreach ( $manual_prices as $manual_price ) {
							$start_location = array_key_exists( 'start_location', $manual_price ) ? $manual_price['start_location'] : '';
							$end_location   = array_key_exists( 'end_location', $manual_price ) ? $manual_price['end_location'] : '';
							if ( $start_location && $end_location && $start_location == $start_place ) {
								$all_location[] = $end_location;
							}
						}
					}
				} else {
					$all_posts = MPCRBM_Query::query_transport_list( 'manual' );
					if ( $all_posts->found_posts > 0 ) {
						$posts = $all_posts->posts;
						foreach ( $posts as $post ) {
							$post_id               = $post->ID;
							$manual_prices         = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_manual_price_info', [] );
							$terms_location_prices = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_terms_price_info', [] );
							if ( sizeof( $manual_prices ) > 0 ) {
								foreach ( $manual_prices as $manual_price ) {
									$start_location = array_key_exists( 'start_location', $manual_price ) ? $manual_price['start_location'] : '';
									$end_location   = array_key_exists( 'end_location', $manual_price ) ? $manual_price['end_location'] : '';
									if ( $start_location && $end_location && $start_location == $start_place ) {
										$all_location[] = $end_location;
									}
								}
							}
							if ( sizeof( $terms_location_prices ) > 0 ) {
								foreach ( $terms_location_prices as $terms_location_price ) {
									$start_location = array_key_exists( 'start_location', $terms_location_price ) ? $terms_location_price['start_location'] : '';
									$end_location   = array_key_exists( 'end_location', $terms_location_price ) ? $terms_location_price['end_location'] : '';
									if ( $start_location && $end_location && $start_location == $start_place ) {
										$all_location[] = $end_location;
									}
								}
							}
						}
					}
				}

				return array_unique( $all_location );
			}

            /**
             * Calculate rental price per day according to priority rules
             *
             * @param int $post_id
             * @param string $start_date YYYY-MM-DD
             * @param int $days Number of rental days
             * @return float Final daily price
             */
            public static function mpcrbm_calculate_price( $post_id, $start_date, $days, $price ) {

                // Get metadata
//                $base_price = (float) get_post_meta($post_id, 'mpcrbm_base_daily_price', true);
                $base_price = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_day_price', 0 );
                $daywise    = (array) get_post_meta($post_id, 'mpcrbm_daywise_pricing', true);
                $tiered     = (array) get_post_meta($post_id, 'mpcrbm_tiered_discounts', true);
                $seasonal   = (array) get_post_meta($post_id, 'mpcrbm_seasonal_pricing', true);

                $enable_seasonal    = (int)get_post_meta( $post_id, 'mpcrbm_enable_seasonal_discount', true );
                $enable_day_wise    = (int)get_post_meta( $post_id, 'mpcrbm_enable_day_wise_discount', true );
                $enable_tired       =  (int)get_post_meta( $post_id, 'mpcrbm_enable_tired_discount', true );

                $start_timestamp = strtotime($start_date);


                $day_of_week = strtolower(date('D', $start_timestamp)); // mon, tue, wed, etc.


                // 1. Seasonal Pricing
                if ( $enable_seasonal === 1 && is_array( $seasonal[0] ) && !empty($seasonal[0])) {

                    foreach ($seasonal as $s) {
                        $season_start = strtotime( $s['start'] );
                        $season_end   = strtotime( $s['end'] );
                        if ( $start_timestamp >= $season_start && $start_timestamp <= $season_end ) {
                            switch ($s['type']) {
                                case 'percentage_increase':
                                    $price += $price * ($s['value'] / 100);
                                    break;
                                case 'percentage_decrease':
                                    $price -= $price * ($s['value'] / 100);
                                    break;
                                case 'fixed_increase':
                                    $price =  $price + $s['value'];
                                    break;
                                case 'fixed_decrease':
                                    $price =  $price - $s['value'];
                                    break;
                            }
                            break;
                        }
                    }
                }


                if ( $enable_day_wise === 1 && (int)$days === 1 && (float)$price === (float)$base_price ) {
                    if (isset($daywise[$day_of_week]) && $daywise[$day_of_week] !== '' && $daywise[$day_of_week] > 0 ) {
                        $price = (float) $daywise[$day_of_week];
                    }
                }

                // 3. Tiered discount based on rental duration
                if ( $enable_tired === 1 && is_array($tiered) && !empty($tiered) && isset($tiered[0]) && is_array($tiered[0]) && (int)$days > 1 ) {
                    foreach ($tiered as $t) {
                        $min = isset($t['min']) ? (int)$t['min'] : 0;
                        $max = isset($t['max']) ? (int)$t['max'] : PHP_INT_MAX;
                        if ( $days >= $min && $days <= $max ) {
                            $discount = isset($t['percent']) ? (float)$t['percent'] : 0;
                            $price -= $price * ($discount / 100);

                            break;
                        }
                    }
                }

                return round($price, 2);
            }

            public static function get_seasonal_rate( $post_id, $price_per_day, $start_date, $enable_seasonal,  ){
                $seasonal   = (array) get_post_meta($post_id, 'mpcrbm_seasonal_pricing', true);
                $seasonal_price_per_day = $price_per_day;
                $name = '';
                if ( $enable_seasonal === 1 && is_array( $seasonal[0] ) && !empty( $seasonal[0] ) ) {
                    $start_timestamp = strtotime( $start_date );
                    foreach ( $seasonal as $s ) {
                        $season_start = strtotime( $s['start'] );
                        $season_end   = strtotime( $s['end'] );

                        $name = isset( $s['name'] ) ? $s['name'] : '';
                        if ( $start_timestamp >= $season_start && $start_timestamp <= $season_end ) {
                            switch ($s['type']) {
                                case 'percentage_increase':
                                    $seasonal_price_per_day += $price_per_day * ($s['value'] / 100);
                                    break;
                                case 'percentage_decrease':
                                    $seasonal_price_per_day -= $price_per_day * ($s['value'] / 100);
                                    break;
                                case 'fixed_increase':
                                    $seasonal_price_per_day =  $price_per_day + $s['value'];
                                    break;
                                case 'fixed_decrease':
                                    $seasonal_price_per_day =  $price_per_day - $s['value'];
                                    break;
                            }
                            break;
                        }
                    }
                }

                return array(
                    'name' => $name,
                    'seasonal_price_per_day' => $seasonal_price_per_day,
                );
            }

        }
	}
