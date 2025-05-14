<?php
/*
   * @Author 		engr.sumonazma@gmail.com
   * Copyright: 	mage-people.com
   */
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.
if (!class_exists('MPTBM_Settings_Global')) {
	class MPTBM_Settings_Global
	{
		protected $settings_api;
		public function __construct()
		{
			$this->settings_api = new MAGE_Setting_API;
			add_action('admin_menu', array($this, 'global_settings_menu'));
			add_action('admin_init', array($this, 'admin_init'));
			add_filter('mp_settings_sec_reg', array($this, 'settings_sec_reg'), 10);
			add_filter('mp_settings_sec_fields', array($this, 'settings_sec_fields'), 10);
			add_filter('filter_mp_global_settings', array($this, 'global_taxi'), 10);
		}
		public function global_settings_menu()
		{
			$cpt = MPTBM_Function::get_cpt();
			add_submenu_page('edit.php?post_type=' . $cpt, esc_html__('Global Settings', 'car-rental-manager'), esc_html__('Global Settings', 'car-rental-manager'), 'manage_options', 'mptbm_settings_page', array($this, 'settings_page'));
		}
		public function settings_page()
		{
?>
			<div class="mpStyle mp_global_settings">
				<div class="mpPanel">
					<div class="mpPanelHeader"><?php echo esc_html(esc_html__(' Global Settings', 'car-rental-manager')); ?></div>
					<div class="mpPanelBody mp_zero">
						<div class="mpTabs leftTabs">
							<?php $this->settings_api->show_navigation(); ?>
							<div class="tabsContent">
								<?php $this->settings_api->show_forms(); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
<?php
		}

		public function admin_init()
		{
			$this->settings_api->set_sections($this->get_settings_sections());
			$this->settings_api->set_fields($this->get_settings_fields());
			$this->settings_api->admin_init();
		}
		public function get_settings_sections()
		{
			$sections = array();
			return apply_filters('mp_settings_sec_reg', $sections);
		}
		public function get_settings_fields()
		{
			$settings_fields = array();
			return apply_filters('mp_settings_sec_fields', $settings_fields);
		}
		public function settings_sec_reg($default_sec): array
		{
			$label = MPTBM_Function::get_name();
			$sections = array(
				array(
					'id' => 'mptbm_general_settings',
					'icon' => 'fas fa-sliders-h',
					'title' => $label . ' ' . esc_html__('Settings', 'car-rental-manager')
				)
			);
			return array_merge($default_sec, $sections);
		}
		public function settings_sec_fields($default_fields): array
		{
			$gm_api_url = 'https://developers.google.com/maps/documentation/javascript/get-api-key';
			$label = MPTBM_Function::get_name();




			$settings_fields = array(
				'mptbm_general_settings' => apply_filters('filter_mptbm_general_settings', array(
					array(
						'name' => 'payment_system',
						'label' => esc_html__('Payment System', 'car-rental-manager'),
						'desc' => esc_html__('Please Select Payment System.', 'car-rental-manager'),
						'type' => 'multicheck',
						'default' => array(
							'direct_order' => 'direct_order',
							'woocommerce' => 'woocommerce'
						),
						'options' => array(
							'direct_order' => esc_html__('Pay on service', 'car-rental-manager'),
							'woocommerce' => esc_html__('woocommerce Payment', 'car-rental-manager'),
						)
					),
					array(
						'name' => 'direct_book_status',
						'label' => esc_html__('Pay on service Booked Status', 'car-rental-manager'),
						'desc' => esc_html__('Please Select when and which order status service Will be Booked/Reduced in Pay on service.', 'car-rental-manager'),
						'type' => 'select',
						'default' => 'completed',
						'options' => array(
							'pending' => esc_html__('Pending', 'car-rental-manager'),
							'completed' => esc_html__('completed', 'car-rental-manager')
						)
					),
					array(
						'name' => 'label',
						'label' => $label . ' ' . esc_html__('Label', 'car-rental-manager'),
						'desc' => esc_html__('If you like to change the label in the dashboard menu, you can change it here.', 'car-rental-manager'),
						'type' => 'text',
						'default' => 'Car'
					),
					array(
						'name' => 'slug',
						'label' => $label . ' ' . esc_html__('Slug', 'car-rental-manager'),
						'desc' => esc_html__('Please enter the slug name you want. Remember, after changing this slug; you need to flush permalink; go to', 'car-rental-manager') . '<strong>' . esc_html__('Settings-> Permalinks', 'car-rental-manager') . '</strong> ' . esc_html__('hit the Save Settings button.', 'car-rental-manager'),
						'type' => 'text',
						'default' => 'Car'
					),
					array(
						'name' => 'icon',
						'label' => $label . ' ' . esc_html__('Icon', 'car-rental-manager'),
						'desc' => esc_html__('If you want to change the  icon in the dashboard menu, you can change it from here, and the Dashboard icon only supports the Dashicons, So please go to ', 'car-rental-manager') . '<a href=https://developer.wordpress.org/resource/dashicons/#calendar-alt target=_blank>' . esc_html__('Dashicons Library.', 'car-rental-manager') . '</a>' . esc_html__('and copy your icon code and paste it here.', 'car-rental-manager'),
						'type' => 'text',
						'default' => 'dashicons-car'
					),
					array(
						'name' => 'category_label',
						'label' => $label . ' ' . esc_html__('Category Label', 'car-rental-manager'),
						'desc' => esc_html__('If you want to change the  category label in the dashboard menu, you can change it here.', 'car-rental-manager'),
						'type' => 'text',
						'default' => 'Category'
					),
					array(
						'name' => 'category_slug',
						'label' => $label . ' ' . esc_html__('Category Slug', 'car-rental-manager'),
						'desc' => esc_html__('Please enter the slug name you want for category. Remember after change this slug you need to flush permalink, Just go to  ', 'car-rental-manager') . '<strong>' . esc_html__('Settings-> Permalinks', 'car-rental-manager') . '</strong> ' . esc_html__('hit the Save Settings button.', 'car-rental-manager'),
						'type' => 'text',
						'default' => 'Car-category'
					),
					array(
						'name' => 'organizer_label',
						'label' => $label . ' ' . esc_html__('Organizer Label', 'car-rental-manager'),
						'desc' => esc_html__('If you want to change the  category label in the dashboard menu you can change here', 'car-rental-manager'),
						'type' => 'text',
						'default' => 'Organizer'
					),
					array(
						'name' => 'organizer_slug',
						'label' => $label . ' ' . esc_html__('Organizer Slug', 'car-rental-manager'),
						'desc' => esc_html__('Please enter the slug name you want for the  organizer. Remember, after changing this slug, you need to flush the permalinks. Just go to ', 'car-rental-manager') . '<strong>' . esc_html__('Settings-> Permalinks', 'car-rental-manager') . '</strong> ' . esc_html__('hit the Save Settings button.', 'car-rental-manager'),
						'type' => 'text',
						'default' => 'Car-organizer'
					),
					array(
						'name' => 'expire',
						'label' => $label . ' ' . esc_html__('Expired  Visibility', 'car-rental-manager'),
						'desc' => esc_html__('If you want to visible expired  ?, please select ', 'car-rental-manager') . '<strong> ' . esc_html__('Yes', 'car-rental-manager') . '</strong>' . esc_html__('or to make it hidden, select', 'car-rental-manager') . '<strong> ' . esc_html__('No', 'car-rental-manager') . '</strong>' . esc_html__('. Default is', 'car-rental-manager') . '<strong>' . esc_html__('No', 'car-rental-manager') . '</strong>',
						'type' => 'select',
						'default' => 'no',
						'options' => array(
							'yes' => esc_html__('Yes', 'car-rental-manager'),
							'no' => esc_html__('No', 'car-rental-manager')
						)
					),
					array(
						'name' => 'enable_view_search_result_page',
						'label' => $label . ' ' . esc_html__('Show Search Result In A Different Page', 'car-rental-manager'),
						'desc' => esc_html__('Enter page slug. Leave blank if you dont want to enable this setting', 'car-rental-manager'),
						'car-rental-manager' . '<strong> ' . esc_html__('Yes', 'car-rental-manager') . '</strong>' . esc_html__('or to make it hidden, select', 'car-rental-manager') . '<strong> ' . esc_html__('No', 'car-rental-manager') . '</strong>' . esc_html__('. Default is', 'car-rental-manager') . '<strong>' . esc_html__('No', 'car-rental-manager') . '</strong>',
						'type' => 'text',
						'placeholder' => 'mptbm-search'
					),
					array(
						'name' => 'enable_view_find_location_page',
						'label' => $label . ' ' . esc_html__('Take user to another page if location can not be found', 'car-rental-manager'),
						'desc' => esc_html__('Enter page url. Leave blank if you dont want to enable this setting', 'car-rental-manager'),
						'car-rental-manager' . '<strong> ' . esc_html__('Yes', 'car-rental-manager') . '</strong>' . esc_html__('or to make it hidden, select', 'car-rental-manager') . '<strong> ' . esc_html__('No', 'car-rental-manager') . '</strong>' . esc_html__('. Default is', 'car-rental-manager') . '<strong>' . esc_html__('No', 'car-rental-manager') . '</strong>',
						'type' => 'text',
						'placeholder' => 'https://mysite.com/taxi'
					),
					array(
						'name' => 'enable_buffer_time',
						'label' => $label . ' ' . esc_html__('Buffer Time', 'car-rental-manager'),
						'desc' => esc_html__('Enter buffer time per minutes. Also you have to change the timezone from', 'car-rental-manager') .
							'<strong style="color: red;">' . esc_html__('Settings --> General --> Timezone', 'car-rental-manager') . '</strong>',

						'type' => 'text',
						'placeholder' => 'Ex:10'
					),
					array(
						'name' => 'mptbm_pickup_interval_time',
						'label' => $label . ' ' . esc_html__('Interval of pickup/return time in frontend', 'car-rental-manager'),
						'desc' => esc_html__('Select frontend interval pickup and return time', 'car-rental-manager'),
						'car-rental-manager' . '<strong> ' . esc_html__('Yes', 'car-rental-manager') . '</strong>' . esc_html__('or to make it hidden, select', 'car-rental-manager') . '<strong> ' . esc_html__('No', 'car-rental-manager') . '</strong>' . esc_html__('. Default is', 'car-rental-manager') . '<strong>' . esc_html__('No', 'car-rental-manager') . '</strong>',
						'type' => 'select',
						'default' => 30,
						'options' => array(
							30 => esc_html__('30', 'car-rental-manager'),
							15 => esc_html__('15', 'car-rental-manager'),
							10 => esc_html__('10', 'car-rental-manager'),
							5 => esc_html__('5', 'car-rental-manager'),
						)
					),
					array(
						'name' => 'enable_return_in_different_date',
						'label' => $label . ' ' . esc_html__('Enable return in different date', 'car-rental-manager'),
						'desc' => esc_html__('Select yes if you want to enable different date return field', 'car-rental-manager'),
						'car-rental-manager' . '<strong> ' . esc_html__('Yes', 'car-rental-manager') . '</strong>' . esc_html__('or to make it hidden, select', 'car-rental-manager') . '<strong> ' . esc_html__('No', 'car-rental-manager') . '</strong>' . esc_html__('. Default is', 'car-rental-manager') . '<strong>' . esc_html__('No', 'car-rental-manager') . '</strong>',
						'type' => 'select',
						'default' => 'no',
						'options' => array(
							'yes' => esc_html__('Yes', 'car-rental-manager'),
							'no' => esc_html__('No', 'car-rental-manager')
						)
					),
					array(
						'name' => 'enable_filter_via_features',
						'label' => $label . ' ' . esc_html__('Enable filter via features', 'car-rental-manager'),
						'desc' => esc_html__('Select yes if you want to enable filter via passenger and bags', 'car-rental-manager'),
						'car-rental-manager' . '<strong> ' . esc_html__('Yes', 'car-rental-manager') . '</strong>' . esc_html__('or to make it hidden, select', 'car-rental-manager') . '<strong> ' . esc_html__('No', 'car-rental-manager') . '</strong>' . esc_html__('. Default is', 'car-rental-manager') . '<strong>' . esc_html__('No', 'car-rental-manager') . '</strong>',
						'type' => 'select',
						'default' => 'no',
						'options' => array(
							'yes' => esc_html__('Yes', 'car-rental-manager'),
							'no' => esc_html__('No', 'car-rental-manager')
						)
					),
					array(
						'name' => 'single_page_checkout',
						'label' => esc_html__('Disable single page checkout', 'car-rental-manager'),
						'desc' => esc_html__('If you want to disable single page checkout, please select Yes.That means active woocommerce checkout page active', 'car-rental-manager'),
						'type' => 'select',
						'default' => 'no',
						'options' => array(
							'yes' => esc_html__('Yes', 'car-rental-manager'),
							'no' => esc_html__('No', 'car-rental-manager')
						)
					)
				))
			);

			return array_merge($default_fields, $settings_fields);
		}
		public function global_taxi($default_sec)
		{
			$label = MPTBM_Function::get_name();
			$sections = array(
				array(
					'name' => 'set_book_status',
					'label' => $label . ' ' . esc_html__('Seat Booked Status', 'car-rental-manager'),
					'desc' => esc_html__('Please Select when and which order status Seat Will be Booked/Reduced.', 'car-rental-manager'),
					'type' => 'multicheck',
					'default' => array(
						'processing' => 'processing',
						'completed' => 'completed'
					),
					'options' => array(
						'on-hold' => esc_html__('On Hold', 'car-rental-manager'),
						'pending' => esc_html__('Pending', 'car-rental-manager'),
						'processing' => esc_html__('Processing', 'car-rental-manager'),
						'completed' => esc_html__('Completed', 'car-rental-manager'),
					)
				)
			);
			return array_merge($default_sec, $sections);
		}
	}
	new  MPTBM_Settings_Global();
}
