<?php
	/*
	* @Author 		magePeople
	* Copyright: 	mage-people.com
	*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MPCRBM_Shortcodes')) {
		class MPCRBM_Shortcodes {
			public function __construct() {
				add_shortcode('mpcrbm_booking', array($this, 'mpcrbm_booking'));
			}

			public function mpcrbm_booking($attribute) {
				$defaults = self::default_attribute();
				$params = shortcode_atts($defaults, $attribute);
				ob_start();
				do_action('mpcrbm_transport_search', $params);
				return ob_get_clean();
			}
			public static function default_attribute() {
				return array(
					"cat" => "0",
					"org" => "0",
					"style" => 'list',
					"show" => '9',
					"pagination" => "yes",
					"city" => "",
					"country" => "",
					'sort' => 'ASC',
					'status' => '',
					"pagination-style" => "load_more",
					"column" => 3,
					"price_based" => 'manual',
					'progressbar'=>'yes',
					'map'=>'yes',
					'form'=>'horizontal',
					'title'=>'yes',
					'ajax_search' => 'no',
					'single_page' => 'no',
					'pickup_location' => '',
				);
			}
		}
		new MPCRBM_Shortcodes();
	}