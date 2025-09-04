<?php
	/*
		   * @Author 		MagePeople Team
		   * Copyright: 	mage-people.com
		   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Settings' ) ) {
		class MPCRBM_Settings {
			public function __construct() {
				add_action( 'add_meta_boxes', [ $this, 'settings_meta' ] );
			}

			//************************//
			public function settings_meta() {
				$label = sprintf(
				/* translators: %s: plugin version */
					__( 'Information Settings <span class="version">V%s</span>', 'car-rental-manager' ),
					MPCRBM_PLUGIN_VERSION
				);
				$cpt   = MPCRBM_Function::get_cpt();
				add_meta_box( 'mpcrbm_meta_box_panel', $label, array( $this, 'settings' ), $cpt, 'normal', 'high' );
			}

			//******************************//
			public function settings() {
				$post_id = get_the_id();
				wp_nonce_field( 'mpcrbm_transportation_type_nonce', 'mpcrbm_transportation_type_nonce' );
				?>
                <input type="hidden" name="mpcrbm_post_id" value="<?php echo esc_attr( $post_id ); ?>"/>
                <div class="mpcrbm mpcrbm_settings">
                    <div class="mpcrbm_tabs leftTabs">
                        <ul class="tabLists">
                            <li data-tabs-target="#mpcrbm_general_info">
                                <span class="pe-1 fas fa-tools"></span><?php esc_html_e( 'General Info', 'car-rental-manager' ); ?>
                            </li>
                            <li data-tabs-target="#mpcrbm_settings_date">
                                <span class="pe-1 fas fa-calendar-alt"></span><?php esc_html_e( 'Date', 'car-rental-manager' ); ?>
                            </li>
                            <li data-tabs-target="#mpcrbm_settings_pricing">
                                <span class="pe-1 fas fa-hand-holding-usd"></span><?php esc_html_e( 'Pricing', 'car-rental-manager' ); ?>
                            </li>
                            <li data-tabs-target="#mpcrbm_settings_ex_service">
                                <span class="pe-1 fas fa-puzzle-piece"></span><?php esc_html_e( 'Extra Service', 'car-rental-manager' ); ?>
                            </li>
                            <li data-tabs-target="#wbtm_settings_tax">
                                <span class="fas fa-hand-holding-usd"></span><?php esc_html_e( 'Tax Configure', 'car-rental-manager' ); ?>
                            </li>
                            <li data-tabs-target="#mpcrbm_setting_operation_area">
                                <span class="pe-1 fas fa-map-marker-alt"></span><?php esc_html_e( 'Operation Area', 'car-rental-manager' ); ?>
                            </li>
                            <li data-tabs-target="#mpcrbm_setting_multi_location">
                                <span class="pe-1 fas fa-map-marked-alt"></span><?php esc_html_e( 'Multi-Location', 'car-rental-manager' ); ?>
                            </li>
                            <?php
                                // Allow pro plugins to add their own tabs
                                do_action( 'mpcrbm_settings_tab_navigation' );
                            ?>
                        </ul>
                        <div class="tabsContent">
							<?php
								// Use a single hook for tab content
								do_action( 'mpcrbm_settings_tab_content', $post_id );
							?>
                        </div>
                    </div>
                </div>
				<?php
			}

			public static function description_array( $key ) {
				$des = array(
					'mpcrbm_display_faq'                     => esc_html__( 'Frequently Asked Questions about this tour that customers need to know', 'car-rental-manager' ),
					'mpcrbm_display_why_choose_us'           => esc_html__( 'Why choose us section, write a key feature list that tourist get Trust to book. you can switch it off.', 'car-rental-manager' ),
					'why_chose_us'                           => esc_html__( 'Please add why to book feature list one by one.', 'car-rental-manager' ),
					'mpcrbm_display_activities'               => esc_html__( 'By default Activities type is ON but you can keep it off by switching this option', 'car-rental-manager' ),
					'activities'                             => esc_html__( 'Add a list of tour activities for this tour.', 'car-rental-manager' ),
					'mpcrbm_activity_name'                    => esc_html__( 'The name is how it appears on your site.', 'car-rental-manager' ),
					'mpcrbm_activity_description'             => esc_html__( 'The description is not prominent by default; however, some themes may show it.', 'car-rental-manager' ),
					'mpcrbm_display_related'                  => esc_html__( 'Please select a related transport from this list.', 'car-rental-manager' ),
					'mpcrbm_section_title_style'              => esc_html__( 'By default Section title is style one', 'car-rental-manager' ),
					'mpcrbm_ticketing_system'                 => esc_html__( 'By default, the ticket purchase system is open. Once you check the availability, you can choose the system that best suits your needs.', 'car-rental-manager' ),
					'mpcrbm_display_seat_details'             => esc_html__( 'By default Seat Info is ON but you can keep it off by switching this option', 'car-rental-manager' ),
					'mpcrbm_display_get_question'             => esc_html__( 'By default Display Get a Questions is ON but you can keep it off by switching this option', 'car-rental-manager' ),
					'mpcrbm_display_sidebar'                  => esc_html__( 'By default Sidebar Widget is Off but you can keep it ON by switching this option', 'car-rental-manager' ),
					'mpcrbm_display_duration'                 => esc_html__( 'By default Duration is ON but you can keep it off by switching this option', 'car-rental-manager' ),
					'mpcrbm_contact_phone'                    => esc_html__( 'Please Enter contact phone no', 'car-rental-manager' ),
					'mpcrbm_contact_text'                     => esc_html__( 'Please Enter Contact Section Text', 'car-rental-manager' ),
					'mpcrbm_contact_email'                    => esc_html__( 'Please Enter contact phone email', 'car-rental-manager' ),
					//================//
					'display_mpcrbm_features'                => esc_html__( 'By default slider is ON but you can keep it off by switching this option', 'car-rental-manager' ),
					'display_slider'                      => esc_html__( 'By default slider is ON but you can keep it off by switching this option', 'car-rental-manager' ),
					'display_mpcrbm_extra_services'          => esc_html__( 'By default Extra services is ON but you can keep it off by switching this option', 'car-rental-manager' ),
					'mpcrbm_extra_services_global'           => esc_html__( 'Please add your global extra service which add any transport', 'car-rental-manager' ),
					'mpcrbm_extra_services_id'               => esc_html__( 'Please select your global extra service', 'car-rental-manager' ),
					'mpcrbm_maximum_passenger'                => esc_html__( 'Filters services by the maximum number of passengers allowed', 'car-rental-manager' ),
					'mpcrbm_maximum_bag'                      => esc_html__( 'Filters services by the maximum number of bags allowed', 'car-rental-manager' ),
					//================//
					'mpcrbm_slider_images'                   => esc_html__( 'Please upload images for gallery', 'car-rental-manager' ),
					//''          => esc_html__( '', 'car-rental-manager' ),
					//================//
					'mpcrbm_initial_price'                    => esc_html__( 'The initial price that will be added as the starting price', 'car-rental-manager' ),
					'mpcrbm_minimum_price'                    => esc_html__( 'Sets the minimum hour. If customer selected hour is lower, the minimum will be applied', 'car-rental-manager' ),
					'mpcrbm_return_minimum_price'             => esc_html__( 'Sets the minimum price of return trip', 'car-rental-manager' ),
					'mpcrbm_price_based'                     => esc_html__( 'This is a price calculation model, price will vary based on your choice', 'car-rental-manager' ),
					'mpcrbm_km_price'                         => esc_html__( 'Set Price per KM', 'car-rental-manager' ),
					'mpcrbm_day_price'                       => esc_html__( 'Set Price per Day', 'car-rental-manager' ),
					'mpcrbm_waiting_price'                    => esc_html__( 'Specifies the price charged per hour for waiting time', 'car-rental-manager' ),
					'mpcrbm_operation_area'                   => esc_html__( 'Select the operation area from the list provided', 'car-rental-manager' ),
					'mpcrbm_operation_area_type'              => esc_html__( "Choose the operation type: \"Single Operation Area\" for local services or \"Intercity Operation Area\" for services spanning multiple cities.", "car-rental-manager" ),
					'mpcrbm_operation_area_increase_price_by' => esc_html__( "Set the price increase amount, which can be specified as a fixed value or a percentage", "car-rental-manager" ),
					'mpcrbm_increase_price_fixed'             => esc_html__( "Specify a fixed amount to increase the price by", "car-rental-manager" ),
					'mpcrbm_increase_price_percentage'        => esc_html__( "Specify the percentage by which the price will be increased", "car-rental-manager" ),
					'mpcrbm_increase_price_direction'         => esc_html__( "Select the direction of travel: 'Origin to Destination' or 'Both ways' for round trips", "car-rental-manager" ),
					'mpcrbm_return_discount'                  => esc_html__( 'This is to way return discount fixed or percentage', 'car-rental-manager' ),
				);
				$des = apply_filters( 'mpcrbm_filter_description_array', $des );

				return $des[ $key ];
			}

			public static function info_text( $key ) {
				$data = self::description_array( $key );
				if ( $data ) {
					?>
					<?php echo esc_html( $data ); ?>
					<?php
				}
			}
		}
		new MPCRBM_Settings();
	}
