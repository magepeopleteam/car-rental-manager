<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRBM_Dummy_Import')) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		class MPCRBM_Dummy_Import {
			public function __construct() {
				add_action('admin_init', array($this, 'dummy_import'), 99);
			}

			public function dummy_import() {
				$dummy_post_inserted = get_option('mpcrbm_dummy_already_inserted', 'no');
				$count_existing_event = wp_count_posts('mpcrbm_rent')->publish;
				$plugin_active = MPCRBM_Global_Function::check_plugin( 'car-rental-manager', 'car-rental-manager.php' );
				if ($count_existing_event == 0 && $plugin_active == 1 && $dummy_post_inserted != 'yes') {
					$dummy_taxonomies = $this->dummy_taxonomy();
					if (array_key_exists('taxonomy', $dummy_taxonomies)) {
						foreach ($dummy_taxonomies['taxonomy'] as $taxonomy => $dummy_taxonomy) {
							if (taxonomy_exists($taxonomy)) {
								$check_terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
								if (is_string($check_terms) || sizeof($check_terms) == 0) {
									foreach ($dummy_taxonomy as $taxonomy_data) {
										unset($term);
										$term = wp_insert_term($taxonomy_data['name'], $taxonomy);
						
										if (array_key_exists('meta_data', $taxonomy_data)) {
											foreach ($taxonomy_data['meta_data'] as $meta_key => $data) {
												update_term_meta($term['term_id'], $meta_key, $data);
											}
										}
									}
								}
							}
						}
					}
					$dummy_cpt = $this->dummy_cpt();
					if (array_key_exists('custom_post', $dummy_cpt)) {
						$dummy_images = self::dummy_images();
						foreach ($dummy_cpt['custom_post'] as $custom_post => $dummy_post) {
							unset($args);
							$args = array(
								'post_type' => $custom_post,
								'posts_per_page' => -1,
							);
							unset($post);
							$post = new WP_Query($args);
							if ($post->post_count == 0) {
								foreach ($dummy_post as $dummy_data) {
									$args = array();
									if (isset($dummy_data['name']))
										$args['post_title'] = $dummy_data['name'];
									if (isset($dummy_data['content']))
										$args['post_content'] = $dummy_data['content'];
									$args['post_status'] = 'publish';
									$args['post_type'] = $custom_post;
									$post_id = wp_insert_post($args);
									$ex_id = 0;
									if (array_key_exists('taxonomy_terms', $dummy_data) && count($dummy_data['taxonomy_terms'])) {
										foreach ($dummy_data['taxonomy_terms'] as $taxonomy_term) {
											wp_set_object_terms($post_id, $taxonomy_term['terms'], $taxonomy_term['taxonomy_name'], true);
										}
									}
									if (array_key_exists('post_data', $dummy_data)) {
										foreach ($dummy_data['post_data'] as $meta_key => $data) {
											
											if ($meta_key == 'mpcrbm_car_type') {
												$term_ids = [];
												foreach ($data as $item) {
													$term = get_term_by('name', $item, 'mpcrbm_car_type');
													if ($term && !is_wp_error($term)) {
														$term_ids[] = (int) $term->term_id;
													}
												}
												update_post_meta($post_id, $meta_key, $term_ids);
											}

											if ( $meta_key == 'mpcrbm_extra_services_id' ) {
												update_post_meta( $post_id, $meta_key, $ex_id );
											} else {
												update_post_meta( $post_id, $meta_key, $data );
											}

											if ($meta_key == 'mpcrbm_gallery_images') {
												if (is_array($data)) {
													$thumnail_ids = array();
													foreach ($data as $url_index) {
														if (isset($dummy_images[$url_index])) {
															$thumnail_ids[] = $dummy_images[$url_index];
														}
													}
													update_post_meta($post_id, 'mpcrbm_gallery_images', $thumnail_ids);
													if (count($thumnail_ids)) {
														set_post_thumbnail($post_id, $thumnail_ids[0]);
													}
												}
											} else {
												update_post_meta($post_id, $meta_key, $data);
											}
										}
									}									
								}
							}
						}
					}
					//$this->craete_pages();
					//$this->update_related_products($custom_post);
					flush_rewrite_rules();
					update_option('mpcrbm_dummy_already_inserted', 'yes');
				}
			}

			public function update_related_products($custom_post) {
				$args = array( 'fields' => 'ids', 'post_type' => $custom_post, 'numberposts' => - 1, 'post_status' => 'publish' );
				$ids  = get_posts( $args );
				foreach ( $ids as $id ) {
					update_post_meta($id, 'ttbm_related_tour', $ids);
				}
			}
			
			public static function dummy_images() {
				$urls = array(
					'https://img.freepik.com/free-photo/blue-villa-beautiful-sea-hotel_1203-5316.jpg',
					'https://img.freepik.com/free-photo/beautiful-mountains-ratchaprapha-dam-khao-sok-national-park-surat-thani-province-thailand_335224-851.jpg',
					'https://img.freepik.com/free-photo/photographer-taking-picture-ocean-coast_657883-287.jpg',
					'https://img.freepik.com/free-photo/pileh-blue-lagoon-phi-phi-island-thailand_231208-1487.jpg',
					'https://img.freepik.com/free-photo/godafoss-waterfall-sunset-winter-iceland-guy-red-jacket-looks-godafoss-waterfall_335224-673.jpg',
				);
				unset($image_ids);
				$image_ids = array();
				foreach ($urls as $url) {
					$image_ids[] = media_sideload_image($url, '0', $url, 'id');
				}
				return $image_ids;
			}

			public function dummy_taxonomy(): array {
				return [
					'taxonomy' => [
						'mpcrbm_locations' => [
							['name' => 'Dhaka'],
							['name' => 'Chittagong'],
							['name' => 'Sylhet'],
							['name' => 'Rajshahi'],
							['name' => 'Khulna'],
							['name' => 'Barishal'],
						],
						'mpcrbm_car_type' => [
							['name' => 'Sedan'],
							['name' => 'SUV'],
							['name' => 'Hatchback'],
							['name' => 'Microbus'],
							['name' => 'Pickup Truck'],
							['name' => 'Luxury Car'],
						],
						'mpcrbm_fuel_type' => [
							['name' => 'Petrol'],
							['name' => 'Diesel'],
							['name' => 'Octane'],
							['name' => 'CNG'],
							['name' => 'Hybrid'],
							['name' => 'Electric'],
						],
						'mpcrbm_car_brand' => [
							['name' => 'Toyota'],
							['name' => 'Honda'],
							['name' => 'Nissan'],
							['name' => 'Hyundai'],
							['name' => 'Mitsubishi'],
							['name' => 'BMW'],
							['name' => 'Mercedes-Benz'],
						],
						'mpcrbm_seating_capacity' => [
							['name' => '2 Seater'],
							['name' => '4 Seater'],
							['name' => '5 Seater'],
							['name' => '7 Seater'],
							['name' => '10 Seater'],
							['name' => '15 Seater'],
						],
						'mpcrbm_make_year' => [
							['name' => '2025'],
							['name' => '2024'],
							['name' => '2023'],
							['name' => '2022'],
							['name' => '2021'],
							['name' => '2020'],
							['name' => '2019'],
							['name' => '2018'],
						],
						'mpcrbm_car_feature' => [
							['name' => 'Air Conditioning'],
							['name' => 'Automatic Transmission'],
							['name' => 'Manual Transmission'],
							['name' => 'Power Steering'],
							['name' => 'Power Windows'],
							['name' => 'Bluetooth Connectivity'],
							['name' => 'USB Port'],
							['name' => 'Rear Camera'],
							['name' => 'GPS Navigation'],
							['name' => 'Keyless Entry'],
							['name' => 'ABS Brakes'],
							['name' => 'Airbags'],
							['name' => 'Alloy Wheels'],
							['name' => 'Child Seat Available'],
							['name' => 'Tinted Windows'],
							['name' => 'Cruise Control'],
							['name' => 'Fog Lights'],
							['name' => 'Sunroof'],
							['name' => 'Leather Seats'],
							['name' => 'Android Auto / Apple CarPlay'],
						]
					],
				];
			}

			public function dummy_cpt(): array {
				return [
					'custom_post' => [
						'mpcrbm_extra_services' => [
							[
								'name'      => 'Pre-defined Extra Services',
								'post_data' => array(
									'mpcrbm_extra_service_infos' => array(
										0 => array(
											'service_icon'              => 'fas fa-baby',
											'service_name'              => 'Child Seat',
											'service_price'             => '50',
											'service_qty_type'          => 'inputbox',
											'extra_service_description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
										),
										1 => array(
											'service_icon'              => 'fas fa-seedling',
											'service_name'              => 'Bouquet of Flowers',
											'service_price'             => '150',
											'service_qty_type'          => 'inputbox',
											'extra_service_description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
										),
										2 => array(
											'service_icon'              => 'fas fa-wine-glass-alt',
											'service_name'              => 'Welcome Drink',
											'service_price'             => '30',
											'service_qty_type'          => 'inputbox',
											'extra_service_description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
										),
										3 => array(
											'service_icon'              => 'fas fa-user-alt',
											'service_name'              => 'Airport Assistance and Hostess Service',
											'service_price'             => '30',
											'service_qty_type'          => 'inputbox',
											'extra_service_description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
										),
										4 => array(
											'service_icon'              => 'fas fa-skating',
											'service_name'              => 'Bodyguard Service',
											'service_price'             => '30',
											'service_qty_type'          => 'inputbox',
											'extra_service_description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
										),
									)
								)
							],
						],
						'mpcrbm_rent'          => [
							[
								'name'      => 'BMW 5 Series',
								'post_data' => [
									//General_settings
									'mpcrbm_features'                => [
										0 => array(
											'label' => 'Name',
											'icon'  => 'fas fa-car-side',
											'image' => '',
											'text'  => 'BMW 5 Series Long'
										),
										1 => array(
											'label' => 'Model',
											'icon'  => 'fas fa-car',
											'image' => '',
											'text'  => 'EXPRW'
										),
										2 => array(
											'label' => 'Engine',
											'icon'  => 'fas fa-cogs',
											'image' => '',
											'text'  => '3000'
										),
										3 => array(
											'label' => 'Fuel Type',
											'icon'  => 'fas fa-gas-pump',
											'image' => '',
											'text'  => 'Diesel'
										),
									],
									'mpcrbm_maximum_passenger' => 4,
									'mpcrbm_maximum_bag' => 4,
									'mpcrbm_car_type' => [
										'Hatchback'
									],
									'mpcrbm_fuel_type' => [
										'Octane'
									],
									'mpcrbm_seating_capacity' => [
										'4 Seater'
									],
									'mpcrbm_car_brand' => [
										'Toyota'
									],
									'mpcrbm_make_year' => [
										'2025'
									],
									//gallery_settings
									'mpcrbm_gallery_images' => array(2, 0, 3, 4, 1),

									//price_settings
									'mpcrbm_price_based'             => 'manual',
									'mpcrbm_day_price'               => 10,
									'mpcrbm_terms_price_info'       => [
										[
											'start_location' => 'chittagong',
											'end_location'   => 'chittagong'
										],
										[
											'start_location' => 'rajshahi',
											'end_location'   => 'rajshahi'
										]
									],
									//Extra Services
									'display_mpcrbm_extra_services' => 'on',
									'mpcrbm_extra_services_id'      => '',
									//faq_settings
									'mpcrbm_display_faq'             => 'on',
									'mpcrbm_faq'                     => [
										0 => [
											'title'   => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'title'   => 'Where is The Mentalist located?',
											'content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'title'   => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'title'   => 'Is The Mentalist appropriate for children?',
											'content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'title'   => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'mpcrbm_display_why_choose_us'   => 'on',
									'mpcrbm_why_choose_us'           => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//gallery_settings
									'mpcrbm_slider_images'              => [ 10, 20, 30, 40, 50, 60, 70, 80, 90, 100 ],
									//date_settings
									'mpcrbm_available_for_all_time'  => 'on',
									'mpcrbm_active_days'             => '60',
									'mpcrbm_default_start_time'      => '0.5',
									'mpcrbm_default_end_time'        => '23.5',
									//extras_settings
									'mpcrbm_display_contact'         => 'on',
									'mpcrbm_email'                   => 'example.gmail.com',
									'mpcrbm_phone'                   => '123456789',
									'mpcrbm_text'                    => 'Do not hesitate to give us a call. We are an expert team and we are happy to talk to you.',
								]
							],
							[
								'name'      => 'Cadillac Escalade Limousine',
								'post_data' => [
									//General_settings
									'mpcrbm_features'                => [
										0 => array(
											'label' => 'Name',
											'icon'  => 'fas fa-car-side',
											'image' => '',
											'text'  => 'Cadillac Escalade Limousine'
										),
										1 => array(
											'label' => 'Model',
											'icon'  => 'fas fa-car',
											'image' => '',
											'text'  => 'CADESR'
										),
										2 => array(
											'label' => 'Engine',
											'icon'  => 'fas fa-cogs',
											'image' => '',
											'text'  => '2500'
										),
										3 => array(
											'label' => 'Fuel Type',
											'icon'  => 'fas fa-gas-pump',
											'image' => '',
											'text'  => 'Diesel'
										),
									],
									'mpcrbm_maximum_passenger' => 4,
									'mpcrbm_maximum_bag' => 4,
									'mpcrbm_car_type' => [
										'Hatchback'
									],
									'mpcrbm_fuel_type' => [
										'Octane'
									],
									'mpcrbm_seating_capacity' => [
										'4 Seater'
									],
									'mpcrbm_car_brand' => [
										'Toyota'
									],
									'mpcrbm_make_year' => [
										'2025'
									],
									//gallery_settings
									'mpcrbm_gallery_images' => array(4, 2, 0, 3, 1),
									//price_settings
									'mpcrbm_price_based'             => 'manual',
									'mpcrbm_day_price'               => 10,
									'mpcrbm_terms_price_info'       => [
										[
											'start_location' => 'chittagong',
											'end_location'   => 'chittagong'
										],
										[
											'start_location' => 'rajshahi',
											'end_location'   => 'rajshahi'
										]
									],
									//Extra Services
									'display_mpcrbm_extra_services' => 'on',
									'mpcrbm_extra_services_id'      => '',
									//faq_settings
									'mpcrbm_display_faq'             => 'on',
									'mpcrbm_faq'                     => [
										0 => [
											'title'   => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'title'   => 'Where is The Mentalist located?',
											'content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'title'   => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'title'   => 'Is The Mentalist appropriate for children?',
											'content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'title'   => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'mpcrbm_display_why_choose_us'   => 'on',
									'mpcrbm_why_choose_us'           => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//date_settings
									'mpcrbm_available_for_all_time'  => 'on',
									'mpcrbm_active_days'             => '60',
									'mpcrbm_default_start_time'      => '0.5',
									'mpcrbm_default_end_time'        => '23.5',
									//gallery_settings
									'mpcrbm_slider_images'              => [ 10, 20, 30, 40, 50, 60, 70, 80, 90, 100 ],
									//extras_settings
									'mpcrbm_display_contact'         => 'on',
									'mpcrbm_email'                   => 'example.gmail.com',
									'mpcrbm_phone'                   => '123456789',
									'mpcrbm_text'                    => 'Do not hesitage to give us a call. We are an expert team and we are happy to talk to you.',
								]
							],
							[
								'name'      => 'Hummer New York Limousine',
								'post_data' => [
									//General_settings
									'mpcrbm_features'                => [
										0 => array(
											'label' => 'Name',
											'icon'  => 'fas fa-car-side',
											'image' => '',
											'text'  => 'Hummer New York Limousine'
										),
										1 => array(
											'label' => 'Model',
											'icon'  => 'fas fa-car',
											'image' => '',
											'text'  => 'HUMYL'
										),
										2 => array(
											'label' => 'Engine',
											'icon'  => 'fas fa-cogs',
											'image' => '',
											'text'  => '3500'
										),
										3 => array(
											'label' => 'Fuel Type',
											'icon'  => 'fas fa-gas-pump',
											'image' => '',
											'text'  => 'Diesel'
										),
									],
									'mpcrbm_maximum_passenger' => 4,
									'mpcrbm_maximum_bag' => 4,
									'mpcrbm_car_type' => [
										'Hatchback'
									],
									'mpcrbm_fuel_type' => [
										'Octane'
									],
									'mpcrbm_seating_capacity' => [
										'4 Seater'
									],
									'mpcrbm_car_brand' => [
										'Toyota'
									],
									'mpcrbm_make_year' => [
										'2025'
									],
									//gallery_settings
									'mpcrbm_gallery_images' => array(1, 2, 0, 3, 4),
									//price_settings
									'mpcrbm_price_based'             => 'manual',
									'mpcrbm_day_price'               => 10,
									'mpcrbm_terms_price_info'       => [
										[
											'start_location' => 'chittagong',
											'end_location'   => 'chittagong'
										],
										[
											'start_location' => 'rajshahi',
											'end_location'   => 'rajshahi'
										]
									],
									//Extra Services
									'display_mpcrbm_extra_services' => 'on',
									'mpcrbm_extra_services_id'      => '',
									//faq_settings
									'mpcrbm_display_faq'             => 'on',
									'mpcrbm_faq'                     => [
										0 => [
											'title'   => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'title'   => 'Where is The Mentalist located?',
											'content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'title'   => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'title'   => 'Is The Mentalist appropriate for children?',
											'content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'title'   => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'mpcrbm_display_why_choose_us'   => 'on',
									'mpcrbm_why_choose_us'           => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//gallery_settings
									'mpcrbm_slider_images'              => [ 10, 20, 30, 40, 50, 60, 70, 80, 90, 100 ],
									//date_settings
									'mpcrbm_available_for_all_time'  => 'on',
									'mpcrbm_active_days'             => '60',
									'mpcrbm_default_start_time'      => '0.5',
									'mpcrbm_default_end_time'        => '23.5',
									//extras_settings
									'mpcrbm_display_contact'         => 'on',
									'mpcrbm_email'                   => 'example.gmail.com',
									'mpcrbm_phone'                   => '123456789',
									'mpcrbm_text'                    => 'Do not hesitage to give us a call. We are an expert team and we are happy to talk to you.',
								]
							],
							[
								'name'      => 'Cadillac Escalade SUV',
								'post_data' => [
									//General_settings
									'mpcrbm_features'                => [
										0 => array(
											'label' => 'Name',
											'icon'  => 'fas fa-car-side',
											'image' => '',
											'text'  => 'Cadillac Escalade SUV'
										),
										1 => array(
											'label' => 'Model',
											'icon'  => 'fas fa-car',
											'image' => '',
											'text'  => 'CASUV'
										),
										2 => array(
											'label' => 'Engine',
											'icon'  => 'fas fa-cogs',
											'image' => '',
											'text'  => '2800'
										),
										3 => array(
											'label' => 'Fuel Type',
											'icon'  => 'fas fa-gas-pump',
											'image' => '',
											'text'  => 'Diesel'
										),
									],
									'mpcrbm_maximum_passenger' => 4,
									'mpcrbm_maximum_bag' => 4,
									'mpcrbm_car_type' => [
										'Hatchback'
									],
									'mpcrbm_fuel_type' => [
										'Octane'
									],
									'mpcrbm_seating_capacity' => [
										'4 Seater'
									],
									'mpcrbm_car_brand' => [
										'Toyota'
									],
									'mpcrbm_make_year' => [
										'2025'
									],
									//gallery_settings
									'mpcrbm_gallery_images' => array(2, 0,3,1,4),
									//price_settings
									'mpcrbm_price_based'             => 'manual',
									'mpcrbm_day_price'               => 10,
									'mpcrbm_terms_price_info'       => [
										[
											'start_location' => 'chittagong',
											'end_location'   => 'chittagong'
										],
										[
											'start_location' => 'rajshahi',
											'end_location'   => 'rajshahi'
										]
									],
									//Extra Services
									'display_mpcrbm_extra_services' => 'on',
									'mpcrbm_extra_services_id'      => '',
									//faq_settings
									'mpcrbm_display_faq'             => 'on',
									'mpcrbm_faq'                     => [
										0 => [
											'title'   => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'title'   => 'Where is The Mentalist located?',
											'content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'title'   => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'title'   => 'Is The Mentalist appropriate for children?',
											'content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'title'   => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'mpcrbm_display_why_choose_us'   => 'on',
									'mpcrbm_why_choose_us'           => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//gallery_settings
									'mpcrbm_slider_images'              => [ 10, 20, 30, 40, 50, 60, 70, 80, 90, 100 ],
									//date_settings
									'mpcrbm_available_for_all_time'  => 'on',
									'mpcrbm_active_days'             => '60',
									'mpcrbm_default_start_time'      => '0.5',
									'mpcrbm_default_end_time'        => '23.5',
									//extras_settings
									'mpcrbm_display_contact'         => 'on',
									'mpcrbm_email'                   => 'example.gmail.com',
									'mpcrbm_phone'                   => '123456789',
									'mpcrbm_text'                    => 'Do not hesitate to give us a call. We are an expert team and we are happy to talk to you.',
								]
							],
							[
								'name'      => 'Ford Tourneo',
								'post_data' => [
									//General_settings
									'mpcrbm_features'                => [
										0 => array(
											'label' => 'Name',
											'icon'  => 'fas fa-car-side',
											'image' => '',
											'text'  => 'Ford Tourneo'
										),
										1 => array(
											'label' => 'Model',
											'icon'  => 'fas fa-car',
											'image' => '',
											'text'  => 'FORD_DD'
										),
										2 => array(
											'label' => 'Engine',
											'icon'  => 'fas fa-cogs',
											'image' => '',
											'text'  => '3200'
										),
										3 => array(
											'label' => 'Fuel Type',
											'icon'  => 'fas fa-gas-pump',
											'image' => '',
											'text'  => 'Diesel'
										),
									],
									'mpcrbm_maximum_passenger' => 4,
									'mpcrbm_maximum_bag' => 4,
									'mpcrbm_car_type' => [
										'Hatchback'
									],
									'mpcrbm_fuel_type' => [
										'Octane'
									],
									'mpcrbm_seating_capacity' => [
										'4 Seater'
									],
									'mpcrbm_car_brand' => [
										'Toyota'
									],
									'mpcrbm_make_year' => [
										'2025'
									],
									//gallery_settings
									'mpcrbm_gallery_images' => array(2,3, 4,1,0),
									//price_settings
									'mpcrbm_price_based'             => 'manual',
									'mpcrbm_day_price'               => 10,
									'mpcrbm_terms_price_info'       => [
										[
											'start_location' => 'chittagong',
											'end_location'   => 'chittagong'
										],
										[
											'start_location' => 'rajshahi',
											'end_location'   => 'rajshahi'
										]
									],
									//extra_settings
									'display_mpcrbm_extra_services' => 'on',
									'mpcrbm_extra_services_id'      => '',
									//faq_settings
									'mpcrbm_display_faq'             => 'on',
									'mpcrbm_faq'                     => [
										0 => [
											'title'   => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'title'   => 'Where is The Mentalist located?',
											'content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'title'   => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'title'   => 'Is The Mentalist appropriate for children?',
											'content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'title'   => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'mpcrbm_display_why_choose_us'   => 'on',
									'mpcrbm_why_choose_us'           => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//gallery_settings
									'mpcrbm_slider_images'              => [ 10, 20, 30, 40, 50, 60, 70, 80, 90, 100 ],
									//date_settings
									'mpcrbm_available_for_all_time'  => 'on',
									'mpcrbm_active_days'             => '60',
									'mpcrbm_default_start_time'      => '0.5',
									'mpcrbm_default_end_time'        => '23.5',
									//extras_settings
									'mpcrbm_display_contact'         => 'on',
									'mpcrbm_email'                   => 'example.gmail.com',
									'mpcrbm_phone'                   => '123456789',
									'mpcrbm_text'                    => 'Do not hesitate to give us a call. We are an expert team and we are happy to talk to you.',
								]
							],
							[
								'name'      => 'Mercedes-Benz E220',
								'post_data' => [
									//General_settings
									'mpcrbm_features'                => [
										0 => array(
											'label' => 'Name',
											'icon'  => 'fas fa-car-side',
											'image' => '',
											'text'  => 'Mercedes-Benz E220'
										),
										1 => array(
											'label' => 'Model',
											'icon'  => 'fas fa-car',
											'image' => '',
											'text'  => 'Mercedes'
										),
										2 => array(
											'label' => 'Engine',
											'icon'  => 'fas fa-cogs',
											'image' => '',
											'text'  => '3200'
										),
										3 => array(
											'label' => 'Fuel Type',
											'icon'  => 'fas fa-gas-pump',
											'image' => '',
											'text'  => 'Octane'
										),
									],
									'mpcrbm_maximum_passenger' => 4,
									'mpcrbm_maximum_bag' => 4,
									'mpcrbm_car_type' => [
										'Hatchback'
									],
									'mpcrbm_fuel_type' => [
										'Octane'
									],
									'mpcrbm_seating_capacity' => [
										'4 Seater'
									],
									'mpcrbm_car_brand' => [
										'Toyota'
									],
									'mpcrbm_make_year' => [
										'2025'
									],
									//gallery_settings
									'mpcrbm_gallery_images' => array(4,1,2,0,3),
									//price_settings
									'mpcrbm_price_based'             => 'manual',
									'mpcrbm_day_price'               => 10,
									'mpcrbm_terms_price_info'       => [
										[
											'start_location' => 'chittagong',
											'end_location'   => 'chittagong'
										],
										[
											'start_location' => 'rajshahi',
											'end_location'   => 'rajshahi'
										]
									],
									//extra_settings
									'display_mpcrbm_extra_services' => 'on',
									'mpcrbm_extra_services_id'      => '',
									//faq_settings
									'mpcrbm_display_faq'             => 'on',
									'mpcrbm_faq'                     => [
										0 => [
											'title'   => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'title'   => 'Where is The Mentalist located?',
											'content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'title'   => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'title'   => 'Is The Mentalist appropriate for children?',
											'content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'title'   => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'mpcrbm_display_why_choose_us'   => 'on',
									'mpcrbm_why_choose_us'           => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//gallery_settings
									'mpcrbm_slider_images'              => [ 10, 20, 30, 40, 50, 60, 70, 80, 90, 100 ],
									//date_settings
									'mpcrbm_available_for_all_time'  => 'on',
									'mpcrbm_active_days'             => '60',
									'mpcrbm_default_start_time'      => '0.5',
									'mpcrbm_default_end_time'        => '23.5',
									//extras_settings
									'mpcrbm_display_contact'         => 'on',
									'mpcrbm_email'                   => 'example.gmail.com',
									'mpcrbm_phone'                   => '123456789',
									'mpcrbm_text'                    => 'Do not hesitage to give us a call. We are an expert team and we are happy to talk to you.',
								]
							],
							[
								'name'      => 'Fiat Panda',
								'post_data' => [
									//General_settings
									'mpcrbm_features'                => [
										0 => array(
											'label' => 'Name',
											'icon'  => 'fas fa-car-side',
											'image' => '',
											'text'  => 'Fiat Panda'
										),
										1 => array(
											'label' => 'Model',
											'icon'  => 'fas fa-car',
											'image' => '',
											'text'  => 'FIAT'
										),
										2 => array(
											'label' => 'Engine',
											'icon'  => 'fas fa-cogs',
											'image' => '',
											'text'  => '2200'
										),
										3 => array(
											'label' => 'Fuel Type',
											'icon'  => 'fas fa-gas-pump',
											'image' => '',
											'text'  => 'Octane'
										),
									],
									'mpcrbm_maximum_passenger' => 4,
									'mpcrbm_maximum_bag' => 4,
									'mpcrbm_car_type' => [
										'Hatchback'
									],
									'mpcrbm_fuel_type' => [
										'Octane'
									],
									'mpcrbm_seating_capacity' => [
										'4 Seater'
									],
									'mpcrbm_car_brand' => [
										'Toyota'
									],
									'mpcrbm_make_year' => [
										'2025'
									],
									//gallery_settings
									'mpcrbm_gallery_images' => array(2, 1, 0, 3, 4),
									//price_settings
									'mpcrbm_price_based'             => 'manual',
									'mpcrbm_day_price'               => 10,
									'mpcrbm_terms_price_info'       => [
										[
											'start_location' => 'chittagong',
											'end_location'   => 'chittagong'
										],
										[
											'start_location' => 'rajshahi',
											'end_location'   => 'rajshahi'
										]
									],
									//extra_settings
									'display_mpcrbm_extra_services' => 'on',
									'mpcrbm_extra_services_id'      => '',
									//faq_settings
									'mpcrbm_display_faq'             => 'on',
									'mpcrbm_faq'                     => [
										0 => [
											'title'   => 'What can I expect to see at The Mentalist at Planet Hollywood Resort and Casino?',
											'content' => 'Comedy, magic and mind-reading! The Mentalist has the ability to get inside the minds of audience members, revealing everything from their names, hometowns and anniversaries to their wildest wishes.',
										],
										1 => [
											'title'   => 'Where is The Mentalist located?',
											'content' => 'The V Theater is located inside the Miracle Mile Shops at the Planet Hollywood Resort & Casino.',
										],
										2 => [
											'title'   => 'Can I purchase alcohol at the venue during The Mentalist!?',
											'content' => 'Absolutely! Drinks are available for purchase at the Showgirl Bar outside of the theater and may be brought into the showroom, however, no other outside food or drink will be allowed in the theater.',
										],
										3 => [
											'title'   => 'Is The Mentalist appropriate for children?',
											'content' => 'Due to language, this show is recommended for guests 16 years old and over.',
										],
										4 => [
											'title'   => 'Do I need to exchange my ticket upon arrival at The Mentalist!?',
											'content' => 'Please pick up your tickets at the V Theater Box Office with a valid photo ID for the lead traveler at least 30 minutes prior to show time (box office opens at 11 am). Seating will begin 15 minutes before showtime.',
										],
									],
									//why chose us_settings
									'mpcrbm_display_why_choose_us'   => 'on',
									'mpcrbm_why_choose_us'           => [
										0 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										1 => 'Enjoy a taste of Las Vegas glitz at the mind-bending magic show',
										2 => 'Watch as Gerry McCambridge performs comedy and magic',
									],
									//gallery_settings
									'mpcrbm_slider_images'              => [ 10, 20, 30, 40, 50, 60, 70, 80, 90, 100 ],
									//date_settings
									'mpcrbm_available_for_all_time'  => 'on',
									'mpcrbm_active_days'             => '60',
									'mpcrbm_default_start_time'      => '0.5',
									'mpcrbm_default_end_time'        => '23.5',
									//extras_settings
									'mpcrbm_display_contact'         => 'on',
									'mpcrbm_email'                   => 'example.gmail.com',
									'mpcrbm_phone'                   => '123456789',
									'mpcrbm_text'                    => 'Do not hesitage to give us a call. We are an expert team and we are happy to talk to you.',
								]
							],
						],
					]
				];
			}
		}
		new MPCRBM_Dummy_Import();
	}

