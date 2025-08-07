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
				add_shortcode('mpcrbm_booking_new', array($this, 'mpcrbm_booking_new'));
			}

            public function mpcrbm_booking_new( $attribute ){
                $is_title = isset( $attribute['title'] ) ? sanitize_text_field( $attribute['title'] ) : 'yes';
                ob_start();
                ?>
                <div class="booking-container">
                    <?php if( $is_title === 'yes'){?>
                    <div class="booking-header">
                        <div class="header-content">
                            <div class="header-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"></path>
                                </svg>
                            </div>
                            <div class="header-text">
                                <h2><?php esc_attr_e( 'Car Rental Booking', 'car-rental-manager' );?></h2>
                                <p><?php esc_attr_e( 'Find and reserve your perfect vehicle', 'car-rental-manager' );?></p>
                            </div>
                        </div>
                        <div class="header-badge">
                            <?php esc_attr_e( 'Quick &amp; Easy', 'car-rental-manager' );?>
                        </div>
                    </div>
                    <?php }?>

                    <form class="booking-form" id="bookingForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="icon icon-location">📍</span>
                                    <?php esc_attr_e( 'Pick-Up Location', 'car-rental-manager' );?>
                                </label>
                                <select class="form-control input_width" required="">
                                    <option value=""><?php esc_attr_e( 'Select Pick-Up Location', 'car-rental-manager' );?></option>
                                    <option value="location1">Downtown Office</option>
                                    <option value="location2">Airport Terminal</option>
                                    <option value="location3">Hotel District</option>
                                    <option value="location4">Shopping Center</option>
                                </select>
                            </div>

                            <div class="form-group dropoff-group" id="dropOffGroup">
                                <label class="form-label">
                                    <span class="icon icon-dropoff">📍</span>
                                    Drop-Off Location
                                </label>
                                <select class="form-control input_width" id="dropOffSelect">
                                    <option value=""><?php esc_attr_e( 'Select Return Location', 'car-rental-manager' );?></option>
                                    <option value="location1">Downtown Office</option>
                                    <option value="location2">Airport Terminal</option>
                                    <option value="location3">Hotel District</option>
                                    <option value="location4">Shopping Center</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <span class="icon icon-calendar">🗓️</span>
                                    <?php esc_attr_e( 'Pickup Date', 'car-rental-manager' );?>
                                </label>
                                <input type="date" id="mpcrbm_start_date" class="form-control date_input_width " placeholder="Select Date" required="">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <span class="icon icon-clock">🕐</span>
                                    <?php esc_attr_e( 'Pickup Time', 'car-rental-manager' );?>
                                </label>
                                <select class="form-control input_width" required="">
                                    <option value="">Please Select Time</option>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                    <option value="17:00">05:00 PM</option>
                                    <option value="18:00">06:00 PM</option>
                                </select>
                            </div>

                        </div>

                        <div class="checkbox-container">
                            <div class="checkbox-group">
                                <input type="checkbox" id="returnSameLocation" class="checkbox" checked="">
                                <label for="returnSameLocation" class="checkbox-label"><?php esc_attr_e( 'Return Car In Same Location', 'car-rental-manager' );?></label>
                            </div>
                        </div>

                        <div class="return-section" id="returnSection">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="icon icon-calendar">🗓️</span>
                                    <?php esc_attr_e( 'Return Date', 'car-rental-manager' );?>
                                </label>
                                <input type="date" id="mpcrbm_return_date" class="form-control date_input_width" placeholder="Select Date">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <span class="icon icon-clock">🕐</span>
                                    Return Time
                                </label>
                                <select class="form-control input_width">
                                    <option value=""><?php esc_attr_e( 'Please Select Time', 'car-rental-manager' );?></option>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                    <option value="17:00">05:00 PM</option>
                                    <option value="18:00">06:00 PM</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="search-button">
                            <svg class="search-icon" viewBox="0 0 24 24">
                                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>
                            </svg>
                            <?php esc_attr_e( 'Search', 'car-rental-manager' );?>
                        </button>
                    </form>
                </div>

            <?php
                return ob_get_clean();
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
				);
			}
		}
		new MPCRBM_Shortcodes();
	}