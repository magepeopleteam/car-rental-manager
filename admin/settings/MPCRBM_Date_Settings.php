<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Date_Settings' ) ) {
		class MPCRBM_Date_Settings {
			public function __construct() {
				// Renders inside the "Operation Area & Date Time" tab (formerly
				// "Operation Area") rather than as its own tab — see
				// MPCRBM_Operation_Area_Settings::operation_area_settings()'s
				// do_action() call and the removed "Date" nav <li> in
				// MPCRBM_Settings.php.
				add_action( 'mpcrbm_operation_area_tab_after_content', [ $this, 'date_settings' ] );
				add_action( 'save_post', [ $this, 'save_date_time_settings' ] );
			}

			public function default_text( $day ) {
				if ( $day == 'default' ) {
					esc_html_e( 'Please select', 'car-rental-manager' );
				} else {
					esc_html_e( 'Default', 'car-rental-manager' );
				}
			}

			public function time_slot( $time, $stat_time = '', $end_time = '' ) {
				if ( $stat_time >= 0 || $stat_time == '' ) {
					$time_count = $stat_time == '' ? 0 : $stat_time;
					$end_time   = $end_time != '' ? $end_time : 48 * 30;
					for ( $i = 30; $i <= $end_time; $i += 30 ) {
						// Calculate hours and minutes
						$hours   = floor( $i / 60 );
						$minutes = $i % 60;
						// Generate the data-value as hours + fraction (minutes / 60)
						$data_value = $hours + ( $minutes / 100 );
						// Format the time for display
						$time_formatted = sprintf( '%02d:%02d', $hours, $minutes );
						?>
                        <option value="<?php echo esc_attr( $data_value ); ?>" <?php echo esc_attr( $time != '' && $time == $data_value ? 'selected' : '' ); ?>><?php echo esc_html( MPCRBM_Global_Function::date_format( $time_formatted, 'time' ) ); ?></option>
					<?php }
				}
			}

			public function end_time_slot( $post_id, $day, $start_time ) {
				$end_name         = 'mpcrbm_' . $day . '_end_time';
				$default_end_time = $day == 'default' ? 24 : '';
				$end_time         = MPCRBM_Global_Function::get_post_info( $post_id, $end_name, $default_end_time );
				?>
                <label>
                    <select class="formControl " name="<?php echo esc_attr( $end_name ); ?>">
						<?php if ( $start_time == '' ) { ?>
                            <option value="" selected><?php $this->default_text( $day ); ?></option>
						<?php } ?>
						<?php $this->time_slot( $end_time, $start_time ); ?>
                    </select>
                </label>
				<?php
			}
			/*************************************/
			//			public function get_mpcrbm_end_time_slot() {
			//				$post_id = isset($_REQUEST['post_id']) ? MPCRBM_Global_Function::data_sanitize($_REQUEST['post_id']) : '';
			//				$day = isset($_REQUEST['day_name']) ? MPCRBM_Global_Function::data_sanitize($_REQUEST['day_name']) : '';
			//				$start_time = isset($_REQUEST['start_time']) ? MPCRBM_Global_Function::data_sanitize($_REQUEST['start_time']) : '';
			//				$this->end_time_slot($post_id, $day, $start_time);
			//				die();
			//			}
			public function time_slot_tr( $post_id, $day ) {
				$start_name         = 'mpcrbm_' . $day . '_start_time';
				$default_start_time = $day == 'default' ? 0.5 : '';
				$start_time = MPCRBM_Global_Function::get_post_info( $post_id, $start_name, $default_start_time );
				$end_name         = 'mpcrbm_' . $day . '_end_time';
				$default_end_time = $day == 'default' ? 24 : '';
				$end_time = MPCRBM_Global_Function::get_post_info( $post_id, $end_name, $default_end_time );

				$off_days      = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_off_days' );
				$off_day_array = $off_days ? explode( ',', $off_days ) : array();
				$is_off        = in_array( $day, $off_day_array, true );
				?>
                <tr class="mpcrbm-schedule-row<?php echo $is_off ? ' is-off-day' : ''; ?>">
                    <th class="mpcrbm-schedule-day">
                        <span class="mpcrbm-schedule-day-dot"></span>
                        <strong><?php echo esc_html( $day ); ?></strong>
                    </th>
                    <td class="mpcrbm_start_time" data-day-name="<?php echo esc_attr( $day ); ?>">
                        <label>
                            <select class="formControl mpcrbm-schedule-select" name="<?php echo esc_attr( $start_name ); ?>">
                                <option value="" <?php echo esc_attr( $start_time == '' ? 'selected' : '' ); ?>>
									<?php $this->default_text( $day ); ?>
                                </option>
								<?php $this->time_slot( $start_time ); ?>
                            </select>
                        </label>
                        <span class="mpcrbm-schedule-closed-text"><i class="mi mi-lock"></i> <?php esc_html_e( 'Closed', 'car-rental-manager' ); ?></span>
                    </td>
                    <td class="mpcrbm-schedule-to">
                        <?php esc_html_e( 'to', 'car-rental-manager' ); ?>
                    </td>
                    <td class="mpcrbm_end_time">
                        <select class="formControl mpcrbm-schedule-select" name="<?php echo esc_attr( $end_name ); ?>">
                            <option value="" <?php echo esc_attr( $end_time == '' ? 'selected' : '' ); ?>>
								<?php $this->default_text( $day ); ?>
                            </option>
							<?php $this->time_slot( $end_time ); ?>
                        </select>
                        <span class="mpcrbm-schedule-closed-text"><i class="mi mi-lock"></i> <?php esc_html_e( 'Closed', 'car-rental-manager' ); ?></span>
                    </td>
                    <td class="mpcrbm-schedule-offday">
                        <label class="customCheckboxLabel mpcrbm-schedule-offday-label">
                            <input type="checkbox" class="mpcrbm-schedule-offday-checkbox" data-checked="<?php echo esc_attr( $day ); ?>" <?php echo esc_attr( $is_off ? 'checked' : '' ); ?>/>
                            <span class="customCheckbox me-1"><?php esc_html_e( 'Off Day', 'car-rental-manager' ); ?></span>
                        </label>
                    </td>
                </tr>
				<?php
			}

			/**
			 * The "Default Schedule" master row — global start/end applied
			 * to every day via #mpcrbm-apply-all-days (mpcrbm_admin.js),
			 * rendered above the per-day table rather than as one of its rows.
			 */
			public function default_schedule_master( $post_id ) {
				$start_time = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_default_start_time', 0.5 );
				$end_time   = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_default_end_time', 24 );
				?>
                <div class="mpcrbm-schedule-master">
                    <div class="mpcrbm-schedule-master-icon"><i class="fas fa-business-time"></i></div>
                    <div class="mpcrbm-schedule-master-info">
                        <span class="mpcrbm-schedule-master-eyebrow"><?php esc_html_e( 'Master', 'car-rental-manager' ); ?></span>
                        <strong><?php esc_html_e( 'Default Schedule', 'car-rental-manager' ); ?></strong>
                    </div>
                    <div class="mpcrbm-schedule-master-field">
                        <label><?php esc_html_e( 'Global Start', 'car-rental-manager' ); ?></label>
                        <select class="formControl" id="mpcrbm_default_start_time" name="mpcrbm_default_start_time">
                            <option value="" <?php echo esc_attr( $start_time == '' ? 'selected' : '' ); ?>><?php esc_html_e( 'Please select', 'car-rental-manager' ); ?></option>
							<?php $this->time_slot( $start_time ); ?>
                        </select>
                    </div>
                    <i class="fas fa-arrow-right mpcrbm-schedule-master-arrow"></i>
                    <div class="mpcrbm-schedule-master-field">
                        <label><?php esc_html_e( 'Global End', 'car-rental-manager' ); ?></label>
                        <select class="formControl" id="mpcrbm_default_end_time" name="mpcrbm_default_end_time">
                            <option value="" <?php echo esc_attr( $end_time == '' ? 'selected' : '' ); ?>><?php esc_html_e( 'Please select', 'car-rental-manager' ); ?></option>
							<?php $this->time_slot( $end_time ); ?>
                        </select>
                    </div>
                    <button type="button" id="mpcrbm-apply-all-days" class="mpcrbm-schedule-apply-btn">
						<?php esc_html_e( 'Apply to All Days', 'car-rental-manager' ); ?> <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
				<?php
			}

			public function date_settings( $post_id ) {
				wp_nonce_field( 'mpcrbm_save_date_time_settings', 'mpcrbm_date_nonce' );
				$date_format = MPCRBM_Global_Function::date_picker_format();
				$now         = date_i18n( $date_format, strtotime( current_time( 'Y-m-d' ) ) );
				$date_type   = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_date_type', 'repeated' );
				?>
                <div class="mpcrbm-date-setting">
                    <!-- General Date config -->
                    <div class="mpcrbm-info-card">
                        <div class="mpcrbm-info-card-header">
                            <i class="fas fa-calendar-check"></i>
                            <h3><?php esc_html_e( 'General Date Configuration', 'car-rental-manager' ); ?></h3>
                        </div>
                        <div class="mpcrbm-info-card-body">
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Date Type', 'car-rental-manager' ); ?><span class="textRequired">&nbsp;*</span></h6>
                                <span class="desc"><?php esc_html_e( 'Specifies the date type: "Repeated" for recurring dates, or "Particular" for a specific date', "car-rental-manager" ); ?></span>
                            </div>
                            <select class="formControl" name="mpcrbm_date_type" data-collapse-target required>
                                <option disabled selected><?php esc_html_e( 'Please select ...', 'car-rental-manager' ); ?></option>
                                <option value="particular" data-option-target="#mp_particular" <?php echo esc_attr( $date_type == 'particular' ? 'selected' : '' ); ?>><?php esc_html_e( 'Particular', 'car-rental-manager' ); ?></option>
                                <option value="repeated" data-option-target="#mp_repeated" <?php echo esc_attr( $date_type == 'repeated' ? 'selected' : '' ); ?>><?php esc_html_e( 'Repeated', 'car-rental-manager' ); ?></option>
                            </select>
                        </label>
                    </section>
                    <section data-collapse="#mp_particular" class="<?php echo esc_attr( $date_type == 'particular' ? 'mActive' : '' ); ?>">
                        <label class="label" style="align-items: start;">
                            <div>
                                <h6><?php esc_html_e( 'Particular Dates', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php esc_html_e( 'Add Particular Dates', 'car-rental-manager' ); ?></span>
                            </div>
                            <div class="settings_area">
                                <div class="item_insert sortable_area">
									<?php
										$particular_date_lists = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_particular_dates', array() );
										if ( sizeof( $particular_date_lists ) ) {
											foreach ( $particular_date_lists as $particular_date ) {
												if ( $particular_date ) {
													$this->particular_date_item( 'mpcrbm_particular_dates[]', $particular_date );
												}
											}
										}
									?>
                                </div>
								<?php MPCRBM_Custom_Layout::add_new_button( esc_html__( 'Add New Particular date', 'car-rental-manager' ) ); ?>
                                <div class="hidden_content">
                                    <div class="hidden_item">
										<?php $this->particular_date_item( 'mpcrbm_particular_dates[]' ); ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </section>
					<?php
						$repeated_start_date         = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_repeated_start_date' );
						$hidden_repeated_start_date  = $repeated_start_date ? gmdate( 'Y-m-d', strtotime( $repeated_start_date ) ) : '';
						$visible_repeated_start_date = $repeated_start_date ? date_i18n( $date_format, strtotime( $repeated_start_date ) ) : '';
						$repeated_after              = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_repeated_after', 1 );
						$active_days                 = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_active_days', 60 );
						$available_for_all_time      = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_available_for_all_time', 'on' );
						$active                      = $available_for_all_time == 'off' ? '' : 'mActive';
						$checked                     = $available_for_all_time == 'off' ? '' : 'checked';
					?>
                    <section data-collapse="#mp_repeated" class="<?php echo esc_attr( $date_type == 'repeated' ? 'mActive' : '' ); ?>">
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Repeated Start Date', 'car-rental-manager' ); ?><span class="textRequired">&nbsp;*</span></h6>
                                <span class="desc"><?php esc_html_e( 'Sets the start date for recurring services', 'car-rental-manager' ); ?></span>
                            </div>
                            <div>
                                <input type="hidden" name="mpcrbm_repeated_start_date" value="<?php echo esc_attr( $hidden_repeated_start_date ); ?>" required/>
                                <input type="text" readonly required name="" class="formControl date_type" value="<?php echo esc_attr( $visible_repeated_start_date ); ?>" placeholder="<?php echo esc_attr( $now ); ?>"/>
                            </div>
                        </label>
                    </section>
                    <section data-collapse="#mp_repeated" class="<?php echo esc_attr( $date_type == 'repeated' ? 'mActive' : '' ); ?>">
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Repeated after', 'car-rental-manager' ); ?><span class="textRequired">&nbsp;*</span></h6>
                                <span class="desc"><?php esc_html_e( 'Defines the number of days after which the service or event will repeat', 'car-rental-manager' ); ?></span>
                            </div>
                            <input type="text" name="mpcrbm_repeated_after" class="formControl number_validation" value="<?php echo esc_attr( $repeated_after ); ?>"/>
                        </label>
                    </section>
                    <section data-collapse="#mp_repeated" class="<?php echo esc_attr( $date_type == 'repeated' ? 'mActive' : '' ); ?>">
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Maximum Advanced Day Booking', 'car-rental-manager' ); ?><span class="textRequired">&nbsp;*</span></h6>
                                <span class="desc"><?php esc_html_e( 'Sets the maximum number of days in advance a booking can be made', 'car-rental-manager' ); ?></span>
                            </div>
                            <input type="text" name="mpcrbm_active_days" class="formControl number_validation" value="<?php echo esc_attr( $active_days ); ?>"/>
                        </label>
                    </section>
                    <section>
                        <label class="label">
                            <div>
                                <h6><?php esc_html_e( 'Make Transport Available For 24 Hours', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php MPCRBM_Settings::info_text( 'display_mpcrbm_features' ); ?></span>
                            </div>
							<?php MPCRBM_Custom_Layout::switch_button( 'mpcrbm_available_for_all_time', $checked ); ?>
                        </label>
                    </section>
                        </div>
                    </div>
                    <div class="mpcrbm-info-card mpcrbm-schedule-card">
                        <div class="mpcrbm-info-card-header">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h3><?php esc_html_e( 'Operation Schedule', 'car-rental-manager' ); ?></h3>
                                <span class="desc"><?php esc_html_e( "Configure your fleet's service availability. These hours define when vehicles are available for dispatch and branch pickup across all active regions.", 'car-rental-manager' ); ?></span>
                            </div>
                        </div>
                        <div class="mpcrbm-info-card-body">
                    <section>
						<?php $this->default_schedule_master( $post_id ); ?>
						<?php $off_days_value = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_off_days' ); ?>
                        <div class="groupCheckBox mpcrbm-schedule-offday-group">
                            <input type="hidden" name="mpcrbm_off_days" value="<?php echo esc_attr( $off_days_value ); ?>"/>
                            <table class="mpcrbm-schedule-table">
                                <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Day of Week', 'car-rental-manager' ); ?></th>
                                    <th><?php esc_html_e( 'Shift Start', 'car-rental-manager' ); ?></th>
                                    <th></th>
                                    <th><?php esc_html_e( 'Shift End', 'car-rental-manager' ); ?></th>
                                    <th><?php esc_html_e( 'Off Day', 'car-rental-manager' ); ?></th>
                                </tr>
                                </thead>
                                <tbody>
								<?php
									$days = MPCRBM_Global_Function::week_day();
									foreach ( $days as $key => $day ) {
										$this->time_slot_tr( $post_id, $key );
									}
								?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <!-- End Schedule date config -->
                        </div>
                    </div>
                    <div class="mpcrbm-info-card">
                        <div class="mpcrbm-info-card-header">
                            <i class="fas fa-calendar-xmark"></i>
                            <h3><?php esc_html_e( 'Off Dates Configuration', 'car-rental-manager' ); ?></h3>
                        </div>
                        <div class="mpcrbm-info-card-body">
                    <section>
                        <label class="label" style="align-items: start;">
                            <div>
                                <h6><?php esc_html_e( 'Off Dates', 'car-rental-manager' ); ?></h6>
                                <span class="desc"><?php esc_html_e( 'Add off dates', 'car-rental-manager' ); ?></span>
                            </div>
                            <div class="settings_area">
                                <div class="item_insert sortable_area mb-1">
									<?php
										$off_day_lists = MPCRBM_Global_Function::get_post_info( $post_id, 'mpcrbm_off_dates', array() );
										if ( sizeof( $off_day_lists ) ) {
											foreach ( $off_day_lists as $off_day ) {
												if ( $off_day ) {
													$this->particular_date_item( 'mpcrbm_off_dates[]', $off_day );
												}
											}
										}
									?>
                                </div>
								<?php MPCRBM_Custom_Layout::add_new_button( esc_html__( 'Add New Off date', 'car-rental-manager' ) ); ?>
                                <div class="hidden_content">
                                    <div class="hidden_item">
										<?php $this->particular_date_item( 'mpcrbm_off_dates[]' ); ?>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </section>
                    <!-- End Off days and date config -->
                        </div>
                    </div>
                </div>
				<?php
			}

			public function particular_date_item( $name, $date = '' ) {
				$date_format  = MPCRBM_Global_Function::date_picker_format();
				$now          = date_i18n( $date_format, strtotime( current_time( 'Y-m-d' ) ) );
				$hidden_date  = $date ? gmdate( 'Y-m-d', strtotime( $date ) ) : '';
				$visible_date = $date ? date_i18n( $date_format, strtotime( $date ) ) : '';
				?>
                <div class="remove_area my-1">
                    <div class="justifyBetween bg-light p-1">
                        <label class="col_8">
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $hidden_date ); ?>"/>
                            <input value="<?php echo esc_attr( $visible_date ); ?>" class="formControl date_type" placeholder="<?php echo esc_attr( $now ); ?>"/>
                        </label>
						<?php MPCRBM_Custom_Layout::move_remove_button(); ?>
                    </div>
                </div>
				<?php
			}

			/*************************************/
			public function save_date_time_settings( $post_id ) {
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_date_nonce'] ) ) {
					return;
				}
				// Unslash and sanitize the nonce
				$nonce = isset( $_POST['mpcrbm_date_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_date_nonce'] ) ) : '';
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_date_time_settings' ) ) {
					return;
				}
				if ( get_post_type( $post_id ) == MPCRBM_Function::get_cpt() ) {
					//************************************//
					$mpcrbm_date_type = isset( $_POST['mpcrbm_date_type'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_date_type'] ) ) : '';
					update_post_meta( $post_id, 'mpcrbm_date_type', $mpcrbm_date_type );
					//**********************//
					$particular_dates = isset( $_POST['mpcrbm_particular_dates'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_particular_dates'] ) ) : [];
					$particular       = array();
					if ( sizeof( $particular_dates ) > 0 ) {
						foreach ( $particular_dates as $particular_date ) {
							if ( $particular_date ) {
								$particular[] = gmdate( 'Y-m-d', strtotime( $particular_date ) );
							}
						}
					}
					$mpcrbm_available_for_all_time = isset( $_POST['mpcrbm_available_for_all_time'] ) && sanitize_text_field( wp_unslash( $_POST['mpcrbm_available_for_all_time'] ) ) ? 'on' : 'off';
					update_post_meta( $post_id, 'mpcrbm_available_for_all_time', $mpcrbm_available_for_all_time );
					update_post_meta( $post_id, 'mpcrbm_particular_dates', $particular );
					//*************************//
					$repeated_start_date = isset( $_POST['mpcrbm_repeated_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_repeated_start_date'] ) ) : '';
					$repeated_start_date = $repeated_start_date ? gmdate( 'Y-m-d', strtotime( $repeated_start_date ) ) : '';
					update_post_meta( $post_id, 'mpcrbm_repeated_start_date', $repeated_start_date );
					$repeated_after = isset( $_POST['mpcrbm_repeated_after'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_repeated_after'] ) ) : '';
					update_post_meta( $post_id, 'mpcrbm_repeated_after', $repeated_after );
					$active_days = isset( $_POST['mpcrbm_active_days'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_active_days'] ) ) : '';
					update_post_meta( $post_id, 'mpcrbm_active_days', $active_days );
					//**********************//
					if ( isset( $_POST['mpcrbm_off_days'] ) ) {
						$off_days_arr = explode( ',', sanitize_text_field( wp_unslash( $_POST['mpcrbm_off_days'] ) ) );
						$off_days     = is_array( $off_days_arr ) ? array_map( 'sanitize_text_field', $off_days_arr ) : [];
						$off_days     = implode( ',', $off_days );
						update_post_meta( $post_id, 'mpcrbm_off_days', $off_days );
					}
					//**********************//
					$off_dates  = isset( $_POST['mpcrbm_off_dates'] ) && is_array( $_POST['mpcrbm_off_dates'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mpcrbm_off_dates'] ) ) : [];
					$_off_dates = array();
					if ( sizeof( $off_dates ) > 0 ) {
						foreach ( $off_dates as $off_date ) {
							if ( $off_date ) {
								$_off_dates[] = gmdate( 'Y-m-d', strtotime( $off_date ) );
							}
						}
					}
					update_post_meta( $post_id, 'mpcrbm_off_dates', $_off_dates );
					$this->save_schedule( $post_id, 'default' );
					$days = MPCRBM_Global_Function::week_day();
					foreach ( $days as $key => $day ) {
						$this->save_schedule( $post_id, $key );
					}
				}
			}

			public function get_submit_info( $key, $default = '' ) {
				// Check if nonce is set
				if ( ! isset( $_POST['mpcrbm_date_nonce'] ) ) {
					return;
				}
				// Unslash and verify the nonce
				$nonce = isset( $_POST['mpcrbm_date_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mpcrbm_date_nonce'] ) ) : '';
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_date_time_settings' ) ) {
					return;
				}
				$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;

				return $this->data_sanitize( $value );
			}

			public function data_sanitize( $data ) {
				$data = maybe_unserialize( $data );
				if ( is_string( $data ) ) {
					$data = maybe_unserialize( $data );
					if ( is_array( $data ) ) {
						$data = $this->data_sanitize( $data );
					} else {
						$data = sanitize_text_field( stripslashes( wp_strip_all_tags( $data ) ) );
					}
				} elseif ( is_array( $data ) ) {
					foreach ( $data as &$value ) {
						if ( is_array( $value ) ) {
							$value = $this->data_sanitize( $value );
						} else {
							$value = sanitize_text_field( stripslashes( wp_strip_all_tags( $value ) ) );
						}
					}
				}

				return $data;
			}

			public function save_schedule( $post_id, $day ) {
				$start_name = 'mpcrbm_' . $day . '_start_time';
				$start_time = $this->get_submit_info( $start_name );
				update_post_meta( $post_id, $start_name, $start_time );
				$end_name = 'mpcrbm_' . $day . '_end_time';
				$end_time = $this->get_submit_info( $end_name );
				update_post_meta( $post_id, $end_name, $end_time );
			}
		}
		new MPCRBM_Date_Settings();
	}
