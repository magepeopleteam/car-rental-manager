<?php
	/*
	   * @Author 		MagePeople Team
	   * Copyright: 	mage-people.com
	   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Damage_Management_Setting' ) ) {
		class MPCRBM_Damage_Management_Setting {
			public function __construct() {
				// Rendered inside the "Fee & Deposit" tab, right after Security Deposit
				// (same hook/position Security Deposit itself uses — MPCRBM_Security_
				// Deposit_Setting::security_deposit_settings()) — instead of its own
				// top-level tab or living under Pricing.
				add_action( 'mpcrbm_multi_location_tab_after_pricing', [ $this, 'tab_content' ] );
				add_action( 'mpcrbm_damage_part_item', [ $this, 'damage_part_item' ] );
				add_action( 'save_post', [ $this, 'save_damage_parts' ] );
			}

			// Seeded onto a brand-new car only (see tab_content()) so the admin has a
			// realistic starting point instead of an empty table; freely editable/
			// removable afterwards, and never re-applied once the car has been saved.
			private static function default_parts() {
				// Illustrative placeholder prices, not real quotes — non-zero so an
				// admin who saves without editing doesn't end up with a price list
				// that silently charges 0 for every damage type. Same generic
				// currency-agnostic numbers regardless of site currency; meant to be
				// adjusted to the admin's own market.
				return [
					[ 'name' => __( 'Small Scratch', 'car-rental-manager' ), 'price' => 50 ],
					[ 'name' => __( 'Deep Scratch', 'car-rental-manager' ), 'price' => 100 ],
					[ 'name' => __( 'Dent - Door', 'car-rental-manager' ), 'price' => 150 ],
					[ 'name' => __( 'Dent - Bumper', 'car-rental-manager' ), 'price' => 200 ],
					[ 'name' => __( 'Broken Mirror', 'car-rental-manager' ), 'price' => 80 ],
					[ 'name' => __( 'Windshield Crack', 'car-rental-manager' ), 'price' => 250 ],
					[ 'name' => __( 'Broken Headlight / Taillight', 'car-rental-manager' ), 'price' => 120 ],
					[ 'name' => __( 'Tire Damage', 'car-rental-manager' ), 'price' => 100 ],
					[ 'name' => __( 'Interior Stain / Damage', 'car-rental-manager' ), 'price' => 60 ],
					[ 'name' => __( 'Missing Fuel (per unit)', 'car-rental-manager' ), 'price' => 5 ],
				];
			}

			public function tab_content( $post_id ) {
				wp_nonce_field( 'mpcrbm_save_damage_parts', 'mpcrbm_damage_parts_nonce' );

				// Master switch: OFF means this car's damage price list can't be used
				// to charge a booking (the "Damage Charge" tab in the Order List won't
				// even show — see MPCRBM_Order_List). Meta never touched (every car
				// that existed before this switch was added, or a brand-new car) reads
				// as ON, matching the FAQ/Terms "only an explicit 'no' turns it off"
				// backward-compatible pattern used elsewhere in this plugin.
				$enabled         = get_post_meta( $post_id, 'mpcrbm_damage_mgmt_enable', true );
				$is_checked      = ( $enabled === 'off' ) ? '' : 'checked';
				$section_display = ( $enabled === 'off' ) ? 'none' : 'block';

				$parts = get_post_meta( $post_id, 'mpcrbm_damage_parts', true );
				if ( ! is_array( $parts ) || empty( $parts ) ) {
					// auto-draft = the car has never been saved yet ("Add New" screen).
					$parts = ( get_post_status( $post_id ) === 'auto-draft' ) ? self::default_parts() : [];
				}
				?>
				<div class="mpcrbm-info-card mpcrbm-damage-mgmt">
					<div class="mpcrbm-info-card-header">
						<i class="fas fa-car-burst"></i>
						<div>
							<h3><?php esc_html_e( 'Damage Management', 'car-rental-manager' ); ?></h3>
							<span class="desc"><?php esc_html_e( 'Set a repair cost for each type of damage this vehicle can have. This price list is used to calculate the damage charge when the car is returned.', 'car-rental-manager' ); ?></span>
						</div>
						<?php MPCRBM_Custom_Layout::switch_button( 'mpcrbm_damage_mgmt_enable', $is_checked ); ?>
					</div>
					<div class="mpcrbm-info-card-body mpcrbm-damage-mgmt-body" id="mpcrbm_damage_mgmt_enable_holder" style="display:<?php echo esc_attr( $section_display ); ?>" data-collapse="#mpcrbm_damage_mgmt_enable">
						<style>
						/* Row markup stays a real <table>/<tr>/<td> — the "Add" button
						   clones its row out of a <tbody class="hidden_item"> template
						   (MPCRBM_Custom_Layout::hidden_table()), and the HTML5 parser
						   foster-parents any non-table element placed directly inside a
						   <tbody> out of the table entirely. A <div>-based row would silently
						   end up with an empty clone template. So this reskins the table/row/
						   cell itself instead of replacing it. */
						.mpcrbm-damage-mgmt table{border-collapse:separate;border-spacing:0 10px;width:100%;margin:0;}
						.mpcrbm-damage-mgmt thead th{border:none!important;padding:0 10px 6px;text-align:left;}
						.mpcrbm-damage-mgmt thead th span{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#9ca3af;font-weight:700;}
						.mpcrbm-damage-mgmt tbody tr.remove_area{background:#fafbfc;border:1.5px solid #e5e9f0;border-radius:10px;transition:border-color .15s,box-shadow .15s;}
						.mpcrbm-damage-mgmt tbody tr.remove_area:hover{border-color:#c7d2e0;}
						.mpcrbm-damage-mgmt tbody tr.remove_area:focus-within{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);}
						.mpcrbm-damage-mgmt tbody td{border:none!important;padding:0;vertical-align:middle;}
						.mpcrbm-damage-mgmt tbody td:first-child{border-top-left-radius:10px;border-bottom-left-radius:10px;}
						.mpcrbm-damage-mgmt tbody td:last-child{border-top-right-radius:10px;border-bottom-right-radius:10px;padding:0 10px;}
						.mpcrbm-damage-mgmt input[name="damage_part_name[]"]{width:100%;border:none!important;outline:none!important;box-shadow:none!important;background:transparent!important;padding:12px 14px;font-size:14px;font-weight:600;color:#111827;}
						.mpcrbm-damage-mgmt input[name="damage_part_name[]"]::placeholder{color:#9ca3af;font-weight:400;}
						.mpcrbm-damage-mgmt .mpcrbm-dmg-price-wrap{display:flex;align-items:center;background:#fff;border:1.5px solid #e5e9f0;border-radius:8px;overflow:hidden;max-width:160px;transition:border-color .15s;}
						.mpcrbm-damage-mgmt .mpcrbm-dmg-price-wrap:focus-within{border-color:#2563eb;}
						/* ".mpcrbm span.<class>" (0,2,1) beats the global ".mpcrbm span{display:inline-block}"
						   (0,1,1) in mp_global/assets/mp_style/mpcrbm_global.css — same fix documented in
						   MPCRBM_Security_Deposit_Setting.php for the identical prefix-chip pattern. */
						.mpcrbm span.mpcrbm-dmg-price-prefix{padding:0 12px;background:#f3f4f6;border-right:1px solid #e5e9f0;height:40px;display:flex;align-items:center;justify-content:center;line-height:1;font-weight:700;font-size:13px;color:#374151;flex:0 0 auto;white-space:nowrap;}
						.mpcrbm-damage-mgmt input[name="damage_part_price[]"]{border:none!important;outline:none!important;box-shadow:none!important;padding:0 12px;height:40px;font-size:14px;font-weight:600;color:#111827;width:100%;background:transparent!important;}
						.mpcrbm-damage-mgmt .item_remove{background:#fef2f2!important;border:1px solid #fecaca!important;color:#b91c1c!important;border-radius:8px!important;}
						.mpcrbm-damage-mgmt .item_remove:hover{background:#fee2e2!important;}
						.mpcrbm-damage-mgmt .sortable_button{border-radius:8px!important;}
						</style>
						<section>
							<div class="settings_area">
								<div class="_ovAuto_mT_xs">
									<table>
										<thead>
										<tr>
											<th><span><?php esc_html_e( 'Damage Type / Part', 'car-rental-manager' ); ?></span></th>
											<th><span><?php esc_html_e( 'Repair Cost', 'car-rental-manager' ); ?></span></th>
											<th><span><?php esc_html_e( 'Action', 'car-rental-manager' ); ?></span></th>
										</tr>
										</thead>
										<tbody class="sortable_area item_insert">
										<?php foreach ( $parts as $part ) { $this->damage_part_item( $part ); } ?>
										</tbody>
									</table>
								</div>
								<?php MPCRBM_Custom_Layout::add_new_button( esc_html__( 'Add Damage Type', 'car-rental-manager' ) ); ?>
								<?php do_action( 'mpcrbm_hidden_table', 'mpcrbm_damage_part_item' ); ?>
							</div>
						</section>
					</div>
				</div>
				<?php
			}

			// Public static (doesn't touch $this): matches the reuse pattern
			// MPCRBM_Extra_Service::extra_service_item() uses for its own rows, so
			// the same repeatable-row markup/field names can be reused elsewhere
			// (e.g. the return-inspection screen) if needed later.
			public static function damage_part_item( $part = [] ) {
				$part     = $part ?: [];
				$name     = array_key_exists( 'name', $part ) ? $part['name'] : '';
				$price    = array_key_exists( 'price', $part ) ? $part['price'] : '';
				$currency = MPCRBM_Global_Function::currency_symbol_text();
				?>
				<tr class="remove_area">
					<td>
						<input type="text" class="name_validation" name="damage_part_name[]" placeholder="<?php esc_attr_e( 'EX: Broken Mirror', 'car-rental-manager' ); ?>" value="<?php echo esc_attr( $name ); ?>"/>
					</td>
					<td>
						<div class="mpcrbm-dmg-price-wrap">
							<span class="mpcrbm-dmg-price-prefix"><?php echo esc_html( $currency ); ?></span>
							<input type="number" pattern="[0-9]*" step="0.01" min="0" class="price_validation" name="damage_part_price[]" placeholder="0" value="<?php echo esc_attr( $price ); ?>"/>
						</div>
					</td>
					<td><?php MPCRBM_Custom_Layout::move_remove_button(); ?></td>
				</tr>
				<?php
			}

			public function save_damage_parts( $post_id ) {
				if ( ! isset( $_POST['mpcrbm_damage_parts_nonce'] ) ) {
					return;
				}
				$nonce = sanitize_text_field( wp_unslash( $_POST['mpcrbm_damage_parts_nonce'] ) );
				if ( ! wp_verify_nonce( $nonce, 'mpcrbm_save_damage_parts' ) ) {
					return;
				}
				if ( get_post_type( $post_id ) !== MPCRBM_Function::get_cpt() ) {
					return;
				}
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}

				$enable = isset( $_POST['mpcrbm_damage_mgmt_enable'] ) ? 'on' : 'off';
				update_post_meta( $post_id, 'mpcrbm_damage_mgmt_enable', $enable );

				$names  = isset( $_POST['damage_part_name'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['damage_part_name'] ) ) : [];
				$prices = isset( $_POST['damage_part_price'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['damage_part_price'] ) ) : [];

				$parts = [];
				foreach ( $names as $i => $name ) {
					if ( '' === trim( $name ) ) {
						continue;
					}
					$price   = ( isset( $prices[ $i ] ) && is_numeric( $prices[ $i ] ) ) ? abs( floatval( $prices[ $i ] ) ) : 0;
					$parts[] = [ 'name' => $name, 'price' => $price ];
				}

				update_post_meta( $post_id, 'mpcrbm_damage_parts', $parts );
			}

			/** Returns [ [ 'name' => .., 'price' => .. ], ... ] configured for a car. */
			public static function get_damage_parts( $car_id ) {
				$parts = get_post_meta( $car_id, 'mpcrbm_damage_parts', true );
				return is_array( $parts ) ? $parts : [];
			}

			/** Master switch — false only when explicitly turned off for this car. */
			public static function is_enabled( $car_id ) {
				return get_post_meta( $car_id, 'mpcrbm_damage_mgmt_enable', true ) !== 'off';
			}
		}
		new MPCRBM_Damage_Management_Setting();
	}
