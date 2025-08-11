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
				$defaults = $this->default_attribute();
				$params = shortcode_atts($defaults, $attribute);
				ob_start();
				do_action('mpcrbm_transport_search', $params);
				return ob_get_clean();
			}
			public function default_attribute() {
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
				);
			}
		}
		new MPCRBM_Shortcodes();
	}