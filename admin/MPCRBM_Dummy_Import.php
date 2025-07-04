<?php
	/*
	* @Author 		MagePeople Team
	* Copyright: 	mage-people.com
	*/
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
//echo '<pre>';print_r();echo '</pre>';y.
	if ( ! class_exists( 'MPCRBM_Dummy_Import' ) ) {
		class MPCRBM_Dummy_Import {
			public function __construct() {
				add_action( 'admin_init', array( $this, 'dummy_import' ), 99 );
			}

			public function dummy_import() {
				$dummy_post_inserted  = get_option( 'mpcrbm_dummy_already_inserted', 'no' );
				$count_existing_event = wp_count_posts( 'mpcrbm_rent' )->publish;
				$plugin_active        = MPCRBM_Global_Function::check_plugin( 'car-rental-manager', 'car-rental-manager.php' );
				if ( $count_existing_event == 0 && $plugin_active == 1 && $dummy_post_inserted != 'yes' ) {
					$this->mpcrbm_post( $this->dummy_cpt() );
					$this->location_taxonomy();
					flush_rewrite_rules();
					update_option( 'mpcrbm_dummy_already_inserted', 'yes' );
				}
			}

			public static function mpcrbm_post( $dummy_cpt ) {
				if ( array_key_exists( 'custom_post', $dummy_cpt ) ) {
					foreach ( $dummy_cpt['custom_post'] as $custom_post => $dummy_post ) {
						unset( $args );
						$args = array(
							'post_type'      => $custom_post,
							'posts_per_page' => - 1,
						);
						unset( $post );
						$post = new WP_Query( $args );
						if ( $post->post_count == 0 ) {
							foreach ( $dummy_post as $dummy_data ) {
								$args = array();
								if ( isset( $dummy_data['name'] ) ) {
									$args['post_title'] = $dummy_data['name'];
								}
								if ( isset( $dummy_data['content'] ) ) {
									$args['post_content'] = $dummy_data['content'];
								}
								$args['post_status'] = 'publish';
								$args['post_type']   = $custom_post;
								$post_id             = wp_insert_post( $args );
								$ex_id               = 0;
								if ( $custom_post == 'mpcrbm_extra_services' ) {
									$ex_id = $post_id;
								}
								if ( array_key_exists( 'post_data', $dummy_data ) ) {
									foreach ( $dummy_data['post_data'] as $meta_key => $data ) {
										if ( $meta_key == 'mpcrbm_extra_services_id' ) {
											update_post_meta( $post_id, $meta_key, $ex_id );
										} else {
											update_post_meta( $post_id, $meta_key, $data );
										}
									}
								}
							}
						}
					}
				}
			}

			public function location_taxonomy(): array {
				$taxonomy_data = array(
					'mpcrbm_locations' => array(
						'Dhaka',
						'Chittagong',
						'Sylhet',
						'Rajshahi'
					),
				);
				foreach ( $taxonomy_data as $taxonomy => $terms ) {
					foreach ( $terms as $term ) {
						wp_insert_term( $term, $taxonomy );
					}
				}

				return $taxonomy_data;
			}

			public function dummy_cpt(): array {
				return [
					'custom_post' => [
						'mpcrbm_extra_services' => [
							0 => [
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
							0 => [
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
							1 => [
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
							2 => [
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
							3 => [
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
							4 => [
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
							5 => [
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
							6 => [
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
						]
					]
				];
			}
		}
		new MPCRBM_Dummy_Import();
	}
