<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.
if (!class_exists('MPTBM_Function')) {
	class MPTBM_Function
	{

		//**************Support multi Language*********************//
		public static function post_id_multi_language($post_id)
		{
			if (function_exists('wpml_loaded')) {
				global $sitepress;
				$default_language = function_exists('wpml_loaded') ? $sitepress->get_default_language() : get_locale();
				return apply_filters('wpml_object_id', $post_id, MPTBM_Function::get_cpt(), TRUE, $default_language);
			}
			if (function_exists('pll_get_post_translations')) {
				$defaultLanguage = function_exists('pll_default_language') ? pll_default_language() : get_locale();
				$translations = function_exists('pll_get_post_translations') ? pll_get_post_translations($post_id) : [];
				return sizeof($translations) > 0 ? $translations[$defaultLanguage] : $post_id;
			}
			return $post_id;
		}
		//***********Template********************//
//		public static function all_details_template()
//		{
//			$template_path = get_stylesheet_directory() . '/mptbm_templates/themes/';
//			$default_path = MPTBM_PLUGIN_DIR . '/templates/themes/';
//			$dir = is_dir($template_path) ? glob($template_path . "*") : glob($default_path . "*");
//			$names = array();
//			foreach ($dir as $filename) {
//				if (is_file($filename)) {
//					$file = basename($filename);
/*					$name = str_replace("?>", "", strip_tags(file_get_contents($filename, false, null, 24, 16)));*/
//					$names[$file] = $name;
//				}
//			}
//			$name = [];
//			foreach ($names as $key => $value) {
//				$name[$key] = $value;
//			}
//			return apply_filters('filter_mptbm_details_template', $name);
//		}

		public static function get_feature_bag($post_id)
		{
			return get_post_meta($post_id, "mptbm_maximum_bag", 0);
		}

		public static function get_feature_passenger($post_id)
		{
			return get_post_meta($post_id, "mptbm_maximum_passenger", 0);
		}

		public static function get_schedule($post_id)
		{
			$days = MP_Global_Function::week_day();
			$days_name = array_keys($days);
			$all_empty = true;
			$schedule = [];
			foreach ($days_name as $name) {
				$start_time = get_post_meta($post_id, "mptbm_" . $name . "_start_time", true);
				$end_time = get_post_meta($post_id, "mptbm_" . $name . "_end_time", true);
				if ($start_time !== "" && $end_time !== "") {
					$schedule[$name] = [$start_time, $end_time];
				}
			}
			foreach ($schedule as $times) {
				if (!empty($times[0]) || !empty($times[1])) {
					$all_empty = false;
					break;
				}
			}
			if ($all_empty) {
				$default_start_time = get_post_meta($post_id, "mptbm_default_start_time", true);
				$default_end_time = get_post_meta($post_id, "mptbm_default_end_time", true);
				$schedule['default'] = [$default_start_time, $default_end_time];
			}
			return $schedule;
		}

		public static function details_template_path(): string
		{
			$tour_id = get_the_id();
			$template_name = MP_Global_Function::get_post_info($tour_id, 'mptbm_theme_file', 'default.php');
			$file_name = 'themes/' . $template_name;
			$dir = MPTBM_PLUGIN_DIR . '/templates/' . $file_name;
			if (!file_exists($dir)) {
				$file_name = 'themes/default.php';
			}
			return self::template_path($file_name);
		}

		public static function get_taxonomy_name_by_slug($slug, $taxonomy)
		{
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
			$term_name = $wpdb->get_var($query);

			return $term_name;
		}

		public static function template_path($file_name): string
		{
			$template_path = get_stylesheet_directory() . '/mptbm_templates/';
			$default_dir = MPTBM_PLUGIN_DIR . '/templates/';
			$dir = is_dir($template_path) ? $template_path : $default_dir;
			$file_path = $dir . $file_name;
			return locate_template(array('mptbm_templates/' . $file_name)) ? $file_path : $default_dir . $file_name;
		}
		//************************//
		public static function get_general_settings($key, $default = '')
		{
			return MP_Global_Function::get_settings('mptbm_general_settings', $key, $default);
		}
		public static function get_cpt(): string
		{
			return 'mptbm_rent';
		}
		public static function get_name()
		{
			return self::get_general_settings('label', esc_html__('Car', 'car-rental-manager'));
		}
		public static function get_slug()
		{
			return self::get_general_settings('slug', 'Car');
		}
		public static function get_icon()
		{
			return self::get_general_settings('icon', 'dashicons-car');
		}
		public static function get_category_label()
		{
			return self::get_general_settings('category_label', esc_html__('Category', 'car-rental-manager'));
		}
		public static function get_category_slug()
		{
			return self::get_general_settings('category_slug', 'Car-category');
		}
		public static function get_organizer_label()
		{
			return self::get_general_settings('organizer_label', esc_html__('Organizer', 'car-rental-manager'));
		}
		public static function get_organizer_slug()
		{
			return self::get_general_settings('organizer_slug', 'Car-organizer');
		}
		//*************************************************************Full Custom Function******************************//
		//*************Date*********************************//
		public static function get_date($post_id, $expire = false)
		{
			$now = current_time('Y-m-d');
			$date_type = MP_Global_Function::get_post_info($post_id, 'mptbm_date_type', 'repeated');
			$all_dates = [];
			$off_days = MP_Global_Function::get_post_info($post_id, 'mptbm_off_days');
			$all_off_days = explode(',', $off_days);
			$all_off_dates = MP_Global_Function::get_post_info($post_id, 'mptbm_off_dates', array());
			$off_dates = [];
			foreach ($all_off_dates as $off_date) {
				$off_dates[] = gmdate('Y-m-d', strtotime($off_date));
			}
			if ($date_type == 'repeated') {
				$start_date = MP_Global_Function::get_post_info($post_id, 'mptbm_repeated_start_date', $now);
				if (strtotime($now) >= strtotime($start_date) && !$expire) {
					$start_date = $now;
				}
				$repeated_after = MP_Global_Function::get_post_info($post_id, 'mptbm_repeated_after', 1);
				$active_days = MP_Global_Function::get_post_info($post_id, 'mptbm_active_days', 10) - 1;
				$end_date = gmdate('Y-m-d', strtotime($start_date . ' +' . $active_days . ' day'));
				$dates = MP_Global_Function::date_separate_period($start_date, $end_date, $repeated_after);
				foreach ($dates as $date) {
					$date = $date->format('Y-m-d');
					$day = strtolower(gmdate('l', strtotime($date)));
					if (!in_array($date, $off_dates) && !in_array($day, $all_off_days)) {
						$all_dates[] = $date;
					}
				}
			} else {
				$particular_date_lists = MP_Global_Function::get_post_info($post_id, 'mptbm_particular_dates', array());
				if (sizeof($particular_date_lists)) {
					foreach ($particular_date_lists as $particular_date) {
						if ($particular_date && ($expire || strtotime($now) <= strtotime($particular_date)) && !in_array($particular_date, $off_dates) && !in_array($particular_date, $all_off_days)) {
							$all_dates[] = $particular_date;
						}
					}
				}
			}
			return apply_filters('mptbm_get_date', $all_dates, $post_id);
		}
		public static function get_all_dates($price_based = 'dynamic', $expire = false)
		{
			$all_posts = MPTBM_Query::query_transport_list($price_based);
			$all_dates = [];
			if ($all_posts->found_posts > 0) {
				$posts = $all_posts->posts;
				foreach ($posts as $post) {
					$post_id = $post->ID;
					$dates = MPTBM_Function::get_date($post_id, $expire);
					$all_dates = array_merge($all_dates, $dates);
				}
			}

			$all_dates = array_unique($all_dates);
			usort($all_dates, "MP_Global_Function::sort_date");
			return $all_dates;
		}

		//*************Price*********************************//
		public static function get_price($post_id,  $start_place = '', $destination_place = '', $start_date_time = '', $return_date_time = '')
		{

			if (session_status() !== PHP_SESSION_ACTIVE) {
				session_start();
			}
			
			
			// Create DateTime objects from the input strings
			$startDate = new DateTime($start_date_time);
			$returnDate = new DateTime($return_date_time);
			// Calculate the difference
			$interval = $startDate->diff($returnDate);
			
			// Convert the difference to total minutes
			$minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
			$minutes_to_day = ceil($minutes/1440);
			

			$manual_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_terms_price_info', []);
			
			if (sizeof($manual_prices) > 0) {
				foreach ($manual_prices as $manual_price) {
					
						
						$price_per_day = MP_Global_Function::get_post_info($post_id, 'mptbm_day_price', 0);
						
						$price = $price_per_day * $minutes_to_day;
					
				}
			}
			if (class_exists('MPTBM_Datewise_Discount_Addon')) {
				if (!isset($_POST['mptbm_transportation_type_nonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash ($_POST['mptbm_transportation_type_nonce'])), 'mptbm_transportation_type_nonce')) {
					return;
				}
				
				$selected_start_date = isset($_POST["start_date"]) ? sanitize_text_field(wp_unslash($_POST["start_date"])) : "";
				$selected_start_time = isset($_POST["start_time"]) ? sanitize_text_field(wp_unslash($_POST["start_time"])) : "";

				if (strlen($selected_start_time) == 2) {
					$selected_start_time .= ":00"; // Convert '17' to '17:00'
				}

				$selected_start_date = gmdate('Y-m-d', strtotime($selected_start_date));
				$selected_start_time = gmdate('H:i', strtotime($selected_start_time));

				$discounts = MP_Global_Function::get_post_info($post_id, 'mptbm_discounts', []);
				if (!empty($discounts)) {
					foreach ($discounts as $discount) {
						$start_date = isset($discount['start_date']) ? gmdate('Y-m-d', strtotime($discount['start_date'])) : '';
						$end_date = isset($discount['end_date']) ? gmdate('Y-m-d', strtotime($discount['end_date'])) : '';
						$time_slots = $discount['time_slots'] ?? [];
						if ($selected_start_date >= $start_date && $selected_start_date <= $end_date) {
							foreach ($time_slots as $slot) {
								$start_time = isset($slot['start_time']) ? gmdate('H:i', strtotime($slot['start_time'])) : '';
								$end_time = isset($slot['end_time']) ? gmdate('H:i', strtotime($slot['end_time'])) : '';
								$percentage = floatval(rtrim($slot['percentage'], '%'));
								$type = $slot['type'] ?? 'increase'; // Use default if not set
								if ($selected_start_time >= $start_time && $selected_start_time <= $end_time) {
									$discount_amount = ($percentage / 100) * $price;
									if ($type === 'decrease') {
										$price -= abs($discount_amount);
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
			if (isset($_SESSION['geo_fence_post_' . $post_id])) {
				$session_data = sanitize_text_field( $_SESSION['geo_fence_post_' . $post_id] );
				if (isset($session_data[0])) {
					if (isset($session_data[1]) && $session_data[1] == 'geo-fence-fixed-price') {
						$price += (float) $session_data[0];
					} else {
						$price += ((float) $session_data[0] / 100) * $price;
					}
				}
			}

			session_write_close();
			//delete_transient('original_price_based');
			return $price;
		}
		public static function calculate_return_discount($post_id, $price)
		{
			$return_discount = MP_Global_Function::get_post_info($post_id, 'mptbm_return_discount', 0);

			// Check if the return discount is a percentage or a fixed amount
			if (strpos($return_discount, '%') !== false) {
				// It's a percentage discount
				$percentage = floatval(trim($return_discount, '%'));
				$return_discount_amount = ($percentage / 100) * $price;
			} else {
				// It's a fixed amount discount
				$return_discount_amount = floatval($return_discount);
			}

			return $return_discount_amount;
		}
		public static function get_extra_service_price_by_name($post_id, $service_name)
		{
			$display_extra_services = MP_Global_Function::get_post_info($post_id, 'display_mptbm_extra_services', 'on');
			$service_id = MP_Global_Function::get_post_info($post_id, 'mptbm_extra_services_id', $post_id);
			$extra_services = MP_Global_Function::get_post_info($service_id, 'mptbm_extra_service_infos', []);
			$price = 0;
			if ($display_extra_services == 'on' && is_array($extra_services) && sizeof($extra_services) > 0) {
				foreach ($extra_services as $service) {
					$ex_service_name = array_key_exists('service_name', $service) ? $service['service_name'] : '';
					if ($ex_service_name == $service_name) {
						return array_key_exists('service_price', $service) ? $service['service_price'] : 0;
					}
				}
			}
			return $price;
		}
		
		public static function get_all_start_location($post_id = '')
		{
			$all_location = [];
			if ($post_id && $post_id > 0) {
				$manual_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_manual_price_info', []);
				$terms_location_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_terms_start_location', []);
				if (sizeof($manual_prices) > 0) {
					foreach ($manual_prices as $manual_price) {
						$start_location = array_key_exists('start_location', $manual_price) ? $manual_price['start_location'] : '';
						if ($start_location) {
							$all_location[] = $start_location;
						}
					}
				}
			} else {
				$all_posts = MPTBM_Query::query_transport_list('manual');
				if ($all_posts->found_posts > 0) {
					$posts = $all_posts->posts;
					foreach ($posts as $post) {
						$post_id = $post->ID;
						$manual_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_manual_price_info', []);
						$terms_location_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_terms_price_info', []);
						if (sizeof($manual_prices) > 0) {
							foreach ($manual_prices as $manual_price) {
								$start_location = array_key_exists('start_location', $manual_price) ? $manual_price['start_location'] : '';
								if ($start_location) {
									$all_location[] = $start_location;
								}
							}
						}
						if (sizeof($terms_location_prices) > 0) {
							foreach ($terms_location_prices as $terms_location_price) {
								$start_location = array_key_exists('start_location', $terms_location_price) ? $terms_location_price['start_location'] : '';
								if ($start_location) {
									$all_location[] = $start_location;
								}
							}
						}
					}
				}
			}
			return array_unique($all_location);
		}
		public static function get_end_location($start_place, $post_id = '')
		{
			$all_location = [];
			if ($post_id && $post_id > 0) {
				$manual_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_manual_price_info', []);
				if (sizeof($manual_prices) > 0) {
					foreach ($manual_prices as $manual_price) {
						$start_location = array_key_exists('start_location', $manual_price) ? $manual_price['start_location'] : '';
						$end_location = array_key_exists('end_location', $manual_price) ? $manual_price['end_location'] : '';
						if ($start_location && $end_location && $start_location == $start_place) {
							$all_location[] = $end_location;
						}
					}
				}
			} else {
				$all_posts = MPTBM_Query::query_transport_list('manual');
				if ($all_posts->found_posts > 0) {
					$posts = $all_posts->posts;
					foreach ($posts as $post) {
						$post_id = $post->ID;
						$manual_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_manual_price_info', []);
						$terms_location_prices = MP_Global_Function::get_post_info($post_id, 'mptbm_terms_price_info', []);
						if (sizeof($manual_prices) > 0) {
							foreach ($manual_prices as $manual_price) {
								$start_location = array_key_exists('start_location', $manual_price) ? $manual_price['start_location'] : '';
								$end_location = array_key_exists('end_location', $manual_price) ? $manual_price['end_location'] : '';
								if ($start_location && $end_location && $start_location == $start_place) {
									$all_location[] = $end_location;
								}
							}
						}
						if (sizeof($terms_location_prices) > 0) {
							foreach ($terms_location_prices as $terms_location_price) {
								$start_location = array_key_exists('start_location', $terms_location_price) ? $terms_location_price['start_location'] : '';
								$end_location = array_key_exists('end_location', $terms_location_price) ? $terms_location_price['end_location'] : '';
								if ($start_location && $end_location && $start_location == $start_place) {
									$all_location[] = $end_location;
								}
							}
						}
					}
				}
			}
			return array_unique($all_location);
		}
	}
	new MPTBM_Function();
}
