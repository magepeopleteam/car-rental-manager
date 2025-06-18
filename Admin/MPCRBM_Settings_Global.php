<?php
	/*
	   * @Author 		engr.sumonazma@gmail.com
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Settings_Global' ) ) {
		class MPCRBM_Settings_Global {
			protected $settings_api;

			public function __construct() {
				$this->settings_api = new MPCRBM_Setting_API;
				add_action( 'admin_menu', array( $this, 'global_settings_menu' ) );
				add_action( 'admin_init', array( $this, 'admin_init' ), 20 );
				add_filter( 'mpcrbm_settings_sec_reg', array( $this, 'global_sec_reg' ), 90 );
				add_action( 'mpcrbm_licence_section', [ $this, 'licence_area' ] );
			}

			public function global_settings_menu() {
				$cpt = MPCRBM_Function::get_cpt();
				add_submenu_page( 'edit.php?post_type=' . $cpt, esc_html__( 'Global Settings', 'car-rental-manager' ), esc_html__( 'Global Settings', 'car-rental-manager' ), 'manage_options', 'mpcrbm_settings_page', array( $this, 'settings_page' ) );
			}

			public function settings_page() {
				?>
                <div class="mpcrbm">
                    <div class="global_settings">
                        <div class="mpcrbm_panel">
                            <div class="panel_header"><?php esc_html_e( ' Global Settings', 'car-rental-manager' ); ?></div>
                            <div class="panel_body mp_zero">
                                <div class="mpcrbm_tabs leftTabs">
									<?php $this->settings_api->show_navigation(); ?>
                                    <div class="tabsContent">
										<?php $this->settings_api->show_forms(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				<?php
			}

			public function admin_init() {
				$sections = $this->get_settings_sections();
				$fields   = $this->get_settings_fields();
				$this->settings_api->set_sections( $sections );
				$this->settings_api->set_fields( $fields );
				$this->settings_api->admin_init();
			}

			public function get_settings_sections() {
				$label    = MPCRBM_Function::get_name();
				$sections = array(
					array(
						'id'    => 'mpcrbm_general_settings',
						'icon'  => 'fas fa-sliders-h',
						'title' => $label . ' ' . esc_html__( 'Settings', 'car-rental-manager' )
					),
					array(
						'id'    => 'mpcrbm_global_settings',
						'title' => esc_html__( 'Global Settings', 'car-rental-manager' )
					),
				);

				return apply_filters( 'mpcrbm_settings_sec_reg', $sections );
			}

			public function global_sec_reg( $default_sec ): array {
				$sections = array(
					array(
						'id' => 'mpcrbm_slider_settings',
						'title' => esc_html__('Slider Settings', 'car-rental-manager')
					),
					array(
						'id'    => 'mpcrbm_style_settings',
						'title' => esc_html__( 'Style Settings', 'car-rental-manager' )
					),
					array(
						'id'       => 'mpcrbm_license_settings',
						'title'    => esc_html__( 'Mage-People License', 'car-rental-manager' ),
						'callback' => array( $this, 'license_settings' )
					)
				);

				return array_merge( $default_sec, $sections );
			}

			public function get_settings_fields() {
				$label           = MPCRBM_Function::get_name();
				$current_date    = current_time( 'Y-m-d' );
				$settings_fields = array(
					'mpcrbm_general_settings' => apply_filters( 'filter_mpcrbm_general_settings', array(
						array(
							'name'    => 'payment_system',
							'label'   => esc_html__( 'Payment System', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Please Select Payment System.', 'car-rental-manager' ),
							'type'    => 'multicheck',
							'default' => array(
								'direct_order' => 'direct_order',
								'woocommerce'  => 'woocommerce'
							),
							'options' => array(
								'direct_order' => esc_html__( 'Pay on service', 'car-rental-manager' ),
								'woocommerce'  => esc_html__( 'woocommerce Payment', 'car-rental-manager' ),
							)
						),
						array(
							'name'    => 'direct_book_status',
							'label'   => esc_html__( 'Pay on service Booked Status', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Please Select when and which order status service Will be Booked/Reduced in Pay on service.', 'car-rental-manager' ),
							'type'    => 'select',
							'default' => 'completed',
							'options' => array(
								'pending'   => esc_html__( 'Pending', 'car-rental-manager' ),
								'completed' => esc_html__( 'completed', 'car-rental-manager' )
							)
						),
						array(
							'name'    => 'label',
							'label'   => $label . ' ' . esc_html__( 'Label', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you like to change the label in the dashboard menu, you can change it here.', 'car-rental-manager' ),
							'type'    => 'text',
							'default' => 'Car'
						),
						array(
							'name'    => 'slug',
							'label'   => $label . ' ' . esc_html__( 'Slug', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Please enter the slug name you want. Remember, after changing this slug; you need to flush permalink; go to', 'car-rental-manager' ) . '<strong>' . esc_html__( 'Settings-> Permalinks', 'car-rental-manager' ) . '</strong> ' . esc_html__( 'hit the Save Settings button.', 'car-rental-manager' ),
							'type'    => 'text',
							'default' => 'Car'
						),
						array(
							'name'    => 'icon',
							'label'   => $label . ' ' . esc_html__( 'Icon', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to change the  icon in the dashboard menu, you can change it from here, and the Dashboard icon only supports the Dashicons, So please go to ', 'car-rental-manager' ) . '<a href=https://developer.wordpress.org/resource/dashicons/#calendar-alt target=_blank>' . esc_html__( 'Dashicons Library.', 'car-rental-manager' ) . '</a>' . esc_html__( 'and copy your icon code and paste it here.', 'car-rental-manager' ),
							'type'    => 'text',
							'default' => 'dashicons-car'
						),
						array(
							'name'    => 'category_label',
							'label'   => $label . ' ' . esc_html__( 'Category Label', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to change the  category label in the dashboard menu, you can change it here.', 'car-rental-manager' ),
							'type'    => 'text',
							'default' => 'Category'
						),
						array(
							'name'    => 'category_slug',
							'label'   => $label . ' ' . esc_html__( 'Category Slug', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Please enter the slug name you want for category. Remember after change this slug you need to flush permalink, Just go to  ', 'car-rental-manager' ) . '<strong>' . esc_html__( 'Settings-> Permalinks', 'car-rental-manager' ) . '</strong> ' . esc_html__( 'hit the Save Settings button.', 'car-rental-manager' ),
							'type'    => 'text',
							'default' => 'Car-category'
						),
						array(
							'name'    => 'organizer_label',
							'label'   => $label . ' ' . esc_html__( 'Organizer Label', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to change the  category label in the dashboard menu you can change here', 'car-rental-manager' ),
							'type'    => 'text',
							'default' => 'Organizer'
						),
						array(
							'name'    => 'organizer_slug',
							'label'   => $label . ' ' . esc_html__( 'Organizer Slug', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Please enter the slug name you want for the  organizer. Remember, after changing this slug, you need to flush the permalinks. Just go to ', 'car-rental-manager' ) . '<strong>' . esc_html__( 'Settings-> Permalinks', 'car-rental-manager' ) . '</strong> ' . esc_html__( 'hit the Save Settings button.', 'car-rental-manager' ),
							'type'    => 'text',
							'default' => 'Car-organizer'
						),
						array(
							'name'    => 'expire',
							'label'   => $label . ' ' . esc_html__( 'Expired  Visibility', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to visible expired  ?, please select ', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'Yes', 'car-rental-manager' ) . '</strong>' . esc_html__( 'or to make it hidden, select', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>' . esc_html__( '. Default is', 'car-rental-manager' ) . '<strong>' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>',
							'type'    => 'select',
							'default' => 'no',
							'options' => array(
								'yes' => esc_html__( 'Yes', 'car-rental-manager' ),
								'no'  => esc_html__( 'No', 'car-rental-manager' )
							)
						),
						array(
							'name'        => 'enable_view_search_result_page',
							'label'       => $label . ' ' . esc_html__( 'Show Search Result In A Different Page', 'car-rental-manager' ),
							'desc'        => esc_html__( 'Enter page slug. Leave blank if you dont want to enable this setting', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'Yes', 'car-rental-manager' ) . '</strong>' . esc_html__( 'or to make it hidden, select', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>' . esc_html__( '. Default is', 'car-rental-manager' ) . '<strong>' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>',
							'type'        => 'text',
							'placeholder' => 'mpcrbm-search'
						),
						array(
							'name'        => 'enable_view_find_location_page',
							'label'       => $label . ' ' . esc_html__( 'Take user to another page if location can not be found', 'car-rental-manager' ),
							'desc'        => esc_html__( 'Enter page url. Leave blank if you dont want to enable this setting', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'Yes', 'car-rental-manager' ) . '</strong>' . esc_html__( 'or to make it hidden, select', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>' . esc_html__( '. Default is', 'car-rental-manager' ) . '<strong>' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>',
							'type'        => 'text',
							'placeholder' => 'https://mysite.com/taxi'
						),
						array(
							'name'        => 'enable_buffer_time',
							'label'       => $label . ' ' . esc_html__( 'Buffer Time', 'car-rental-manager' ),
							'desc'        => esc_html__( 'Enter buffer time per minutes. Also you have to change the timezone from', 'car-rental-manager' ) . '<strong style="color: red;">' . esc_html__( 'Settings --> General --> Timezone', 'car-rental-manager' ) . '</strong>',
							'type'        => 'text',
							'placeholder' => 'Ex:10'
						),
						array(
							'name'    => 'pickup_interval_time',
							'label'   => $label . ' ' . esc_html__( 'Interval of pickup/return time in frontend', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select frontend interval pickup and return time', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'Yes', 'car-rental-manager' ) . '</strong>' . esc_html__( 'or to make it hidden, select', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>' . esc_html__( '. Default is', 'car-rental-manager' ) . '<strong>' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>',
							'type'    => 'select',
							'default' => 30,
							'options' => array(
								30 => esc_html__( '30', 'car-rental-manager' ),
								15 => esc_html__( '15', 'car-rental-manager' ),
								10 => esc_html__( '10', 'car-rental-manager' ),
								5  => esc_html__( '5', 'car-rental-manager' ),
							)
						),
						array(
							'name'    => 'enable_return_in_different_date',
							'label'   => $label . ' ' . esc_html__( 'Enable return in different date', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select yes if you want to enable different date return field', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'Yes', 'car-rental-manager' ) . '</strong>' . esc_html__( 'or to make it hidden, select', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>' . esc_html__( '. Default is', 'car-rental-manager' ) . '<strong>' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>',
							'type'    => 'select',
							'default' => 'no',
							'options' => array(
								'yes' => esc_html__( 'Yes', 'car-rental-manager' ),
								'no'  => esc_html__( 'No', 'car-rental-manager' )
							)
						),
						array(
							'name'    => 'enable_filter_via_features',
							'label'   => $label . ' ' . esc_html__( 'Enable filter via features', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select yes if you want to enable filter via passenger and bags', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'Yes', 'car-rental-manager' ) . '</strong>' . esc_html__( 'or to make it hidden, select', 'car-rental-manager' ) . '<strong> ' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>' . esc_html__( '. Default is', 'car-rental-manager' ) . '<strong>' . esc_html__( 'No', 'car-rental-manager' ) . '</strong>',
							'type'    => 'select',
							'default' => 'no',
							'options' => array(
								'yes' => esc_html__( 'Yes', 'car-rental-manager' ),
								'no'  => esc_html__( 'No', 'car-rental-manager' )
							)
						),
						array(
							'name'    => 'single_page_checkout',
							'label'   => esc_html__( 'Disable single page checkout', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to disable single page checkout, please select Yes.That means active woocommerce checkout page active', 'car-rental-manager' ),
							'type'    => 'select',
							'default' => 'no',
							'options' => array(
								'yes' => esc_html__( 'Yes', 'car-rental-manager' ),
								'no'  => esc_html__( 'No', 'car-rental-manager' )
							)
						),
						array(
							'name'    => 'maximum_passenger',
							'label'   => esc_html__( 'Maximum Passenger', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Set maximum passenger capacity', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '4'
						),
						array(
							'name'    => 'maximum_bag',
							'label'   => esc_html__( 'Maximum Bag', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Set maximum bag capacity', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '4'
						),
						array(
							'name'    => 'display_features',
							'label'   => esc_html__( 'Display Features', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Enable/Disable features display', 'car-rental-manager' ),
							'type'    => 'select',
							'default' => 'on',
							'options' => array(
								'on'  => esc_html__( 'On', 'car-rental-manager' ),
								'off' => esc_html__( 'Off', 'car-rental-manager' )
							)
						)
					) ),
					'mpcrbm_global_settings'  => apply_filters( 'filter_mpcrbm_global_settings', array(
						array(
							'name'    => 'disable_block_editor',
							'label'   => esc_html__( 'Disable Block/Gutenberg Editor', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to disable WordPress\'s new Block/Gutenberg editor, please select Yes.', 'car-rental-manager' ),
							'type'    => 'select',
							'default' => 'yes',
							'options' => array(
								'yes' => esc_html__( 'Yes', 'car-rental-manager' ),
								'no'  => esc_html__( 'No', 'car-rental-manager' )
							)
						),
						array(
							'name'    => 'set_book_status',
							'label'   => $label . ' ' . esc_html__( 'Seat Booked Status', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Please Select when and which order status Seat Will be Booked/Reduced.', 'car-rental-manager' ),
							'type'    => 'multicheck',
							'default' => array(
								'processing' => 'processing',
								'completed'  => 'completed'
							),
							'options' => array(
								'on-hold'    => esc_html__( 'On Hold', 'car-rental-manager' ),
								'pending'    => esc_html__( 'Pending', 'car-rental-manager' ),
								'processing' => esc_html__( 'Processing', 'car-rental-manager' ),
								'completed'  => esc_html__( 'Completed', 'car-rental-manager' ),
							)
						),
						array(
							'name'    => 'date_format',
							'label'   => esc_html__( 'Date Picker Format', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to change Date Picker Format, please select format. Default  is D d M , yy.', 'car-rental-manager' ),
							'type'    => 'select',
							'default' => 'D d M , yy',
							'options' => array(
								'yy-mm-dd'   => $current_date,
								'yy/mm/dd'   => date_i18n( 'Y/m/d', strtotime( $current_date ) ),
								'yy-dd-mm'   => date_i18n( 'Y-d-m', strtotime( $current_date ) ),
								'yy/dd/mm'   => date_i18n( 'Y/d/m', strtotime( $current_date ) ),
								'dd-mm-yy'   => date_i18n( 'd-m-Y', strtotime( $current_date ) ),
								'dd/mm/yy'   => date_i18n( 'd/m/Y', strtotime( $current_date ) ),
								'mm-dd-yy'   => date_i18n( 'm-d-Y', strtotime( $current_date ) ),
								'mm/dd/yy'   => date_i18n( 'm/d/Y', strtotime( $current_date ) ),
								'd M , yy'   => date_i18n( 'j M , Y', strtotime( $current_date ) ),
								'D d M , yy' => date_i18n( 'D j M , Y', strtotime( $current_date ) ),
								'M d , yy'   => date_i18n( 'M  j, Y', strtotime( $current_date ) ),
								'D M d , yy' => date_i18n( 'D M  j, Y', strtotime( $current_date ) ),
							)
						),
						array(
							'name'    => 'date_format_short',
							'label'   => esc_html__( 'Short Date  Format', 'car-rental-manager' ),
							'desc'    => esc_html__( 'If you want to change Short Date  Format, please select format. Default  is M , Y.', 'car-rental-manager' ),
							'type'    => 'select',
							'default' => 'M , Y',
							'options' => array(
								'D , M d' => date_i18n( 'D , M d', strtotime( $current_date ) ),
								'M , Y'   => date_i18n( 'M , Y', strtotime( $current_date ) ),
								'M , y'   => date_i18n( 'M , y', strtotime( $current_date ) ),
								'M - Y'   => date_i18n( 'M - Y', strtotime( $current_date ) ),
								'M - y'   => date_i18n( 'M - y', strtotime( $current_date ) ),
								'F , Y'   => date_i18n( 'F , Y', strtotime( $current_date ) ),
								'F , y'   => date_i18n( 'F , y', strtotime( $current_date ) ),
								'F - Y'   => date_i18n( 'F - y', strtotime( $current_date ) ),
								'F - y'   => date_i18n( 'F - y', strtotime( $current_date ) ),
								'm - Y'   => date_i18n( 'm - Y', strtotime( $current_date ) ),
								'm - y'   => date_i18n( 'm - y', strtotime( $current_date ) ),
								'm , Y'   => date_i18n( 'm , Y', strtotime( $current_date ) ),
								'm , y'   => date_i18n( 'm , y', strtotime( $current_date ) ),
								'F'       => date_i18n( 'F', strtotime( $current_date ) ),
								'm'       => date_i18n( 'm', strtotime( $current_date ) ),
								'M'       => date_i18n( 'M', strtotime( $current_date ) ),
							)
						),
					) ),
					'mpcrbm_slider_settings' => array(
						array(
							'name' => 'slider_type',
							'label' => esc_html__('Slider Type', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Type Default Slider', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'slider',
							'options' => array(
								'slider' => esc_html__('Slider', 'car-rental-manager'),
								'single_image' => esc_html__('Post Thumbnail', 'car-rental-manager')
							)
						),
						array(
							'name' => 'slider_style',
							'label' => esc_html__('Slider Style', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Style Default Style One', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'style_1',
							'options' => array(
								'style_1' => esc_html__('Style One', 'car-rental-manager'),
								'style_2' => esc_html__('Style Two', 'car-rental-manager'),
							)
						),
						array(
							'name' => 'indicator_visible',
							'label' => esc_html__('Slider Indicator Visible?', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Indicator Visible or Not? Default ON', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'car-rental-manager'),
								'off' => esc_html__('Off', 'car-rental-manager')
							)
						),
						array(
							'name' => 'indicator_type',
							'label' => esc_html__('Slider Indicator Type', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Indicator Type Default Icon', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'icon',
							'options' => array(
								'icon' => esc_html__('Icon Indicator', 'car-rental-manager'),
								'image' => esc_html__('image Indicator', 'car-rental-manager')
							)
						),
						array(
							'name' => 'showcase_visible',
							'label' => esc_html__('Slider Showcase Visible?', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Showcase Visible or Not? Default ON', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'car-rental-manager'),
								'off' => esc_html__('Off', 'car-rental-manager')
							)
						),
						array(
							'name' => 'showcase_position',
							'label' => esc_html__('Slider Showcase Position', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Showcase Position Default Right', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'right',
							'options' => array(
								'top' => esc_html__('At Top Position', 'car-rental-manager'),
								'right' => esc_html__('At Right Position', 'car-rental-manager'),
								'bottom' => esc_html__('At Bottom Position', 'car-rental-manager'),
								'left' => esc_html__('At Left Position', 'car-rental-manager')
							)
						),
						array(
							'name' => 'popup_image_indicator',
							'label' => esc_html__('Slider Popup Image Indicator', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Popup Indicator Image ON or Off? Default ON', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'car-rental-manager'),
								'off' => esc_html__('Off', 'car-rental-manager')
							)
						),
						array(
							'name' => 'popup_icon_indicator',
							'label' => esc_html__('Slider Popup Icon Indicator', 'car-rental-manager'),
							'desc' => esc_html__('Please Select Slider Popup Indicator Icon ON or Off? Default ON', 'car-rental-manager'),
							'type' => 'select',
							'default' => 'on',
							'options' => array(
								'on' => esc_html__('ON', 'car-rental-manager'),
								'off' => esc_html__('Off', 'car-rental-manager')
							)
						)
					),
					'mpcrbm_style_settings'   => apply_filters( 'filter_mpcrbm_style_settings', array(
						array(
							'name'    => 'theme_color',
							'label'   => esc_html__( 'Theme Color', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select Default Theme Color', 'car-rental-manager' ),
							'type'    => 'color',
							'default' => '#F12971'
						),
						array(
							'name'    => 'theme_alternate_color',
							'label'   => esc_html__( 'Theme Alternate Color', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select Default Theme Alternate  Color that means, if background theme color then it will be text color.', 'car-rental-manager' ),
							'type'    => 'color',
							'default' => '#fff'
						),
						array(
							'name'    => 'default_text_color',
							'label'   => esc_html__( 'Default Text Color', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select Default Text  Color.', 'car-rental-manager' ),
							'type'    => 'color',
							'default' => '#303030'
						),
						array(
							'name'    => 'default_font_size',
							'label'   => esc_html__( 'Default Font Size', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Default Font Size(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '15'
						),
						array(
							'name'    => 'font_size_h1',
							'label'   => esc_html__( 'Font Size h1 Title', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size Main Title(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '35'
						),
						array(
							'name'    => 'font_size_h2',
							'label'   => esc_html__( 'Font Size h2 Title', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size h2 Title(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '25'
						),
						array(
							'name'    => 'font_size_h3',
							'label'   => esc_html__( 'Font Size h3 Title', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size h3 Title(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '22'
						),
						array(
							'name'    => 'font_size_h4',
							'label'   => esc_html__( 'Font Size h4 Title', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size h4 Title(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '20'
						),
						array(
							'name'    => 'font_size_h5',
							'label'   => esc_html__( 'Font Size h5 Title', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size h5 Title(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '18'
						),
						array(
							'name'    => 'font_size_h6',
							'label'   => esc_html__( 'Font Size h6 Title', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size h6 Title(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '16'
						),
						array(
							'name'    => 'button_font_size',
							'label'   => esc_html__( 'Button Font Size ', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size Button(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '18'
						),
						array(
							'name'    => 'button_color',
							'label'   => esc_html__( 'Button Text Color', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select Button Text  Color.', 'car-rental-manager' ),
							'type'    => 'color',
							'default' => '#FFF'
						),
						array(
							'name'    => 'button_bg',
							'label'   => esc_html__( 'Button Background Color', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select Button Background  Color.', 'car-rental-manager' ),
							'type'    => 'color',
							'default' => '#222'
						),
						array(
							'name'    => 'font_size_label',
							'label'   => esc_html__( 'Label Font Size ', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Type Font Size Label(in PX Unit).', 'car-rental-manager' ),
							'type'    => 'number',
							'default' => '18'
						),
						array(
							'name'    => 'warning_color',
							'label'   => esc_html__( 'Warning Color', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select Warning  Color.', 'car-rental-manager' ),
							'type'    => 'color',
							'default' => '#E67C30'
						),
						array(
							'name'    => 'section_bg',
							'label'   => esc_html__( 'Section Background color', 'car-rental-manager' ),
							'desc'    => esc_html__( 'Select Background  Color.', 'car-rental-manager' ),
							'type'    => 'color',
							'default' => '#FAFCFE'
						),
					) ),
				);

				return apply_filters( 'mpcrbm_settings_sec_fields', $settings_fields );
			}

			public function license_settings() {
				?>
                <div class="mpcrbm_license_settings">
                    <h3><?php esc_html_e( 'Mage-People License', 'car-rental-manager' ); ?></h3>
                    <div class="_dFlex">
                        <span class="fas fa-info-circle _mR_xs"></span>
                        <i><?php esc_html_e( 'Thanking you for using our Mage-People plugin. Our some plugin free and no license is required. We have some Additional addon to enhance feature of this plugin functionality. If you have any addon you need to enter a valid license for that plugin below.', 'car-rental-manager' ); ?></i>
                    </div>
                    <div class="divider"></div>
                    <div class="dLayout basic_license_area">
						<?php $this->licence_area(); ?>
                    </div>
                </div>
				<?php
			}

			public function licence_area() {
				?>
                <table>
                    <thead>
                    <tr>
                        <th colspan="4"><?php esc_html_e( 'Plugin Name', 'car-rental-manager' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'car-rental-manager' ); ?></th>
                        <th><?php esc_html_e( 'Order No', 'car-rental-manager' ); ?></th>
                        <th colspan="2"><?php esc_html_e( 'Expire on', 'car-rental-manager' ); ?></th>
                        <th colspan="3"><?php esc_html_e( 'License Key', 'car-rental-manager' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></th>
                        <th colspan="2"><?php esc_html_e( 'Action', 'car-rental-manager' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php do_action( 'mpcrbm_license_page_plugin_list' ); ?>
                    </tbody>
                </table>
				<?php
			}
		}
		new  MPCRBM_Settings_Global();
	}
