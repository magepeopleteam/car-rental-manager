<?php
/*
 * @Author      MagePeople Team
 * Copyright:   mage-people.com
 *
 * Branch Manager — extends mpcrbm_locations taxonomy into full branch objects.
 *
 * DATA MODEL
 * ----------
 * Branch  = mpcrbm_locations term + term meta:
 *   mpcrbm_branch_address      string
 *   mpcrbm_branch_phone        string
 *   mpcrbm_branch_multiplier   float  (1.0 = no change to base price)
 *   mpcrbm_branch_one_way_fee  float  (charged when car returned to a different branch)
 *   mpcrbm_branch_hours        array  {mon:{open,close,closed}, …, sun:{…}}
 *
 * Car (mpcrbm_rent CPT) + post meta:
 *   mpcrbm_home_branch         string slug  — registered branch
 *   mpcrbm_current_branch      string slug  — physical location (changes on transfers)
 *   mpcrbm_branch_transfer_log array        — up to 50 most-recent transfer entries
 *
 * PRICING FORMULA
 * ---------------
 * total = base_day_price × days × multiplier(pickup_branch)
 *       + one_way_fee(dropoff_branch)   [only if pickup ≠ dropoff]
 *       + extra services
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'MPCRBM_Branch_Manager' ) ) {
	class MPCRBM_Branch_Manager {

		private static $days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];
		private static $day_labels = [
			'mon' => 'Monday',
			'tue' => 'Tuesday',
			'wed' => 'Wednesday',
			'thu' => 'Thursday',
			'fri' => 'Friday',
			'sat' => 'Saturday',
			'sun' => 'Sunday',
		];

		public function __construct() {
			// ── Term meta hooks ──────────────────────────────────────────────
			add_action( 'mpcrbm_locations_add_form_fields', [ $this, 'add_branch_fields' ] );
			add_action( 'mpcrbm_locations_edit_form_fields', [ $this, 'edit_branch_fields' ] );
			add_action( 'edited_mpcrbm_locations', [ $this, 'save_branch_meta' ] );
			add_action( 'created_mpcrbm_locations', [ $this, 'save_branch_meta' ] );

			// ── Car settings tab ─────────────────────────────────────────────
			add_action( 'mpcrbm_settings_tab_navigation', [ $this, 'branch_tab_nav' ] );
			add_action( 'mpcrbm_settings_tab_content', [ $this, 'branch_tab_content' ] );
			add_action( 'save_post', [ $this, 'save_car_branch_meta' ], 99 );

			// ── AJAX endpoints ───────────────────────────────────────────────
			add_action( 'wp_ajax_mpcrbm_get_branch_info', [ $this, 'ajax_get_branch_info' ] );
			add_action( 'wp_ajax_nopriv_mpcrbm_get_branch_info', [ $this, 'ajax_get_branch_info' ] );
			add_action( 'wp_ajax_mpcrbm_transfer_car_branch', [ $this, 'ajax_transfer_car' ] );
			add_action( 'wp_ajax_mpcrbm_get_branch_cars', [ $this, 'ajax_get_branch_cars' ] );
		}

		// ═══════════════════════════════════════════════════════════════════════
		// TERM META — Branch Details
		// ═══════════════════════════════════════════════════════════════════════

		public function add_branch_fields( $taxonomy ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			?>
			<div class="form-field mpcrbm-branch-field-group">
				<h3 class="mpcrbm-branch-section-title"><?php esc_html_e( 'Branch Details', 'car-rental-manager' ); ?></h3>

				<div class="form-field">
					<label for="mpcrbm_branch_address"><?php esc_html_e( 'Address', 'car-rental-manager' ); ?></label>
					<input type="text" id="mpcrbm_branch_address" name="mpcrbm_branch_address" value="">
					<p class="description"><?php esc_html_e( 'Full street address of this branch', 'car-rental-manager' ); ?></p>
				</div>

				<div class="form-field">
					<label for="mpcrbm_branch_phone"><?php esc_html_e( 'Phone', 'car-rental-manager' ); ?></label>
					<input type="text" id="mpcrbm_branch_phone" name="mpcrbm_branch_phone" value="">
				</div>

				<?php $this->render_hours_fields( [] ); ?>
			</div>
			<?php
		}

		public function edit_branch_fields( $term ) {
			$tid     = $term->term_id;
			$address = get_term_meta( $tid, 'mpcrbm_branch_address', true );
			$phone   = get_term_meta( $tid, 'mpcrbm_branch_phone', true );
			$hours   = get_term_meta( $tid, 'mpcrbm_branch_hours', true );

			$hours = is_array( $hours ) ? $hours : [];
			?>

			<tr class="form-field mpcrbm-branch-section-row">
				<td colspan="2"><h3 class="mpcrbm-branch-section-title"><?php esc_html_e( 'Branch Details', 'car-rental-manager' ); ?></h3></td>
			</tr>

			<tr class="form-field">
				<th><label for="mpcrbm_branch_address"><?php esc_html_e( 'Address', 'car-rental-manager' ); ?></label></th>
				<td>
					<input type="text" id="mpcrbm_branch_address" name="mpcrbm_branch_address" value="<?php echo esc_attr( $address ); ?>">
					<p class="description"><?php esc_html_e( 'Full street address of this branch', 'car-rental-manager' ); ?></p>
				</td>
			</tr>

			<tr class="form-field">
				<th><label for="mpcrbm_branch_phone"><?php esc_html_e( 'Phone', 'car-rental-manager' ); ?></label></th>
				<td><input type="text" id="mpcrbm_branch_phone" name="mpcrbm_branch_phone" value="<?php echo esc_attr( $phone ); ?>"></td>
			</tr>

			<tr class="form-field">
				<th><?php esc_html_e( 'Operating Hours', 'car-rental-manager' ); ?></th>
				<td><?php $this->render_hours_fields( $hours ); ?></td>
			</tr>

			<?php
		}

		private function render_hours_fields( array $hours ) {
			?>
			<table class="mpcrbm-hours-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Day', 'car-rental-manager' ); ?></th>
						<th><?php esc_html_e( 'Open', 'car-rental-manager' ); ?></th>
						<th><?php esc_html_e( 'Close', 'car-rental-manager' ); ?></th>
						<th><?php esc_html_e( 'Closed', 'car-rental-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( self::$day_labels as $key => $label ) :
						$day_data = isset( $hours[ $key ] ) ? $hours[ $key ] : [];
						$open     = isset( $day_data['open'] )   ? $day_data['open']   : '08:00';
						$close    = isset( $day_data['close'] )  ? $day_data['close']  : '18:00';
						$closed   = ! empty( $day_data['closed'] );
					?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td>
							<input type="time" name="mpcrbm_branch_hours[<?php echo esc_attr( $key ); ?>][open]"
								   value="<?php echo esc_attr( $open ); ?>"
								   <?php echo $closed ? 'disabled' : ''; ?>>
						</td>
						<td>
							<input type="time" name="mpcrbm_branch_hours[<?php echo esc_attr( $key ); ?>][close]"
								   value="<?php echo esc_attr( $close ); ?>"
								   <?php echo $closed ? 'disabled' : ''; ?>>
						</td>
						<td>
							<input type="checkbox" class="mpcrbm-day-closed"
								   name="mpcrbm_branch_hours[<?php echo esc_attr( $key ); ?>][closed]"
								   value="1" <?php checked( $closed ); ?>>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}

		public function save_branch_meta( $term_id ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['mpcrbm_branch_address'] ) ) {
				update_term_meta( $term_id, 'mpcrbm_branch_address', sanitize_text_field( wp_unslash( $_POST['mpcrbm_branch_address'] ) ) );
			}
			if ( isset( $_POST['mpcrbm_branch_phone'] ) ) {
				update_term_meta( $term_id, 'mpcrbm_branch_phone', sanitize_text_field( wp_unslash( $_POST['mpcrbm_branch_phone'] ) ) );
			}
			$hours = [];
			if ( isset( $_POST['mpcrbm_branch_hours'] ) && is_array( $_POST['mpcrbm_branch_hours'] ) ) {
				$posted = $_POST['mpcrbm_branch_hours']; // phpcs:ignore
				foreach ( self::$days as $day ) {
					if ( isset( $posted[ $day ] ) ) {
						$hours[ $day ] = [
							'open'   => sanitize_text_field( $posted[ $day ]['open'] ?? '08:00' ),
							'close'  => sanitize_text_field( $posted[ $day ]['close'] ?? '18:00' ),
							'closed' => ! empty( $posted[ $day ]['closed'] ),
						];
					} else {
						// Day present in POST but no sub-array means checkbox wasn't checked — still save open/close
						$hours[ $day ] = [
							'open'   => '08:00',
							'close'  => '18:00',
							'closed' => false,
						];
					}
				}
			}
			update_term_meta( $term_id, 'mpcrbm_branch_hours', $hours );
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		// ═══════════════════════════════════════════════════════════════════════
		// CAR SETTINGS TAB — Branch Assignment
		// ═══════════════════════════════════════════════════════════════════════

		public function branch_tab_nav() {
			?>
			<li data-tabs-target="#mpcrbm_branch_assignment">
				<span class="mi mi-map-location-track"></span>
				<?php esc_html_e( 'Branch Assignment', 'car-rental-manager' ); ?>
			</li>
			<?php
		}

		public function branch_tab_content( $post_id ) {
			$cpt = MPCRBM_Function::get_cpt();
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== $cpt ) {
				return;
			}

			$branch_enabled = get_post_meta( $post_id, 'mpcrbm_branch_enabled', true );
			$home_branch    = get_post_meta( $post_id, 'mpcrbm_home_branch', true );
			$current_branch = get_post_meta( $post_id, 'mpcrbm_current_branch', true );
			$branches       = get_terms( [ 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ] );
			$transfer_log   = get_post_meta( $post_id, 'mpcrbm_branch_transfer_log', true );
			$transfer_log   = is_array( $transfer_log ) ? $transfer_log : [];

			wp_nonce_field( 'mpcrbm_branch_nonce', 'mpcrbm_branch_nonce_field' );
			$enabled_checked = $branch_enabled === '1' ? 'checked' : '';
			$enabled_display = $branch_enabled === '1' ? 'block' : 'none';
			?>
			<div class="tabsItem" data-tabs="#mpcrbm_branch_assignment">
				<h2><?php esc_html_e( 'Branch Assignment', 'car-rental-manager' ); ?></h2>
				<p><?php esc_html_e( 'Assign this car to a branch and track its current physical location.', 'car-rental-manager' ); ?></p>

				<section class="bg-light" style="display: flex; justify-content: space-between">
					<div>
						<h6><?php esc_html_e( 'Branch Settings', 'car-rental-manager' ); ?></h6>
						<span><?php esc_html_e( 'Changing "Current Branch" here creates an audit log entry.', 'car-rental-manager' ); ?></span>
					</div>
					<?php MPCRBM_Custom_Layout::switch_checkbox_button( 'mpcrbm_branch_enabled', $enabled_checked ); ?>
				</section>

				<div class="mpcrbm-section" style="display: <?php echo esc_attr( $enabled_display ); ?>" data-collapse="#mpcrbm_branch_enabled">

				<section>
					<div class="label">
						<div>
							<h6><?php esc_html_e( 'Home Branch', 'car-rental-manager' ); ?></h6>
							<span class="desc"><?php esc_html_e( 'The branch this car is registered to / normally returns to', 'car-rental-manager' ); ?></span>
						</div>
						<div>
							<select name="mpcrbm_home_branch" id="mpcrbm_home_branch" class="formControl">
								<option value=""><?php esc_html_e( '— None —', 'car-rental-manager' ); ?></option>
								<?php foreach ( $branches as $branch ) : ?>
									<option value="<?php echo esc_attr( $branch->slug ); ?>"
											<?php selected( $home_branch, $branch->slug ); ?>>
										<?php echo esc_html( $branch->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</section>

				<section>
					<div class="label">
						<div>
							<h6><?php esc_html_e( 'Current Branch', 'car-rental-manager' ); ?></h6>
							<span class="desc"><?php esc_html_e( 'Where this car physically is right now (updated automatically on one-way bookings)', 'car-rental-manager' ); ?></span>
						</div>
						<div>
							<select name="mpcrbm_current_branch" id="mpcrbm_current_branch" class="formControl">
								<option value=""><?php esc_html_e( '— Same as Home Branch —', 'car-rental-manager' ); ?></option>
								<?php foreach ( $branches as $branch ) : ?>
									<option value="<?php echo esc_attr( $branch->slug ); ?>"
											<?php selected( $current_branch, $branch->slug ); ?>>
										<?php echo esc_html( $branch->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</section>

				</div><!-- data-collapse="#mpcrbm_branch_enabled" -->

				<?php if ( ! empty( $transfer_log ) ) : ?>

				<section class="bg-light">
					<h6><?php esc_html_e( 'Transfer History', 'car-rental-manager' ); ?></h6>
					<span><?php esc_html_e( 'Last 50 branch transfers for this car', 'car-rental-manager' ); ?></span>
				</section>

				<section>
					<table class="mpcrbm-transfer-log-table widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'From', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'To', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Reason', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'By', 'car-rental-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_reverse( $transfer_log ) as $entry ) :
								$from_name = MPCRBM_Function::get_taxonomy_name_by_slug( $entry['from'] ?? '', 'mpcrbm_locations' );
								$to_name   = MPCRBM_Function::get_taxonomy_name_by_slug( $entry['to'] ?? '', 'mpcrbm_locations' );
								$user      = get_userdata( $entry['by'] ?? 0 );
							?>
							<tr>
								<td><?php echo esc_html( $entry['date'] ?? '' ); ?></td>
								<td><?php echo esc_html( $from_name ?: ( $entry['from'] ?? '—' ) ); ?></td>
								<td><?php echo esc_html( $to_name   ?: ( $entry['to']   ?? '—' ) ); ?></td>
								<td><?php echo esc_html( $entry['reason'] ?? '—' ); ?></td>
								<td><?php echo esc_html( $user ? $user->display_name : '—' ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<?php endif; ?>
			</div>
			<?php
		}

		public function save_car_branch_meta( $post_id ) {
			if ( ! isset( $_POST['mpcrbm_branch_nonce_field'] ) ) {
				return;
			}
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpcrbm_branch_nonce_field'] ) ), 'mpcrbm_branch_nonce' ) ) {
				return;
			}
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$enabled     = isset( $_POST['mpcrbm_branch_enabled'] ) ? '1' : '0';
			$old_current = get_post_meta( $post_id, 'mpcrbm_current_branch', true );
			$home        = sanitize_text_field( wp_unslash( $_POST['mpcrbm_home_branch'] ?? '' ) );
			$current     = sanitize_text_field( wp_unslash( $_POST['mpcrbm_current_branch'] ?? '' ) );

			update_post_meta( $post_id, 'mpcrbm_branch_enabled', $enabled );
			update_post_meta( $post_id, 'mpcrbm_home_branch', $home );
			update_post_meta( $post_id, 'mpcrbm_current_branch', $current );

			// Auto-log when admin manually changes current branch
			if ( $old_current && $current && $old_current !== $current ) {
				self::log_transfer( $post_id, $old_current, $current, 'Manual admin update', get_current_user_id() );
			}
		}

		// ═══════════════════════════════════════════════════════════════════════
		// STATIC HELPERS — callable from pricing hooks
		// ═══════════════════════════════════════════════════════════════════════

		/**
		 * Returns all branch meta for a given location slug.
		 *
		 * @param  string $slug mpcrbm_locations term slug
		 * @return array  Associative meta array, or empty array if slug not found.
		 */
		public static function get_branch_meta( string $slug ): array {
			$term = get_term_by( 'slug', $slug, 'mpcrbm_locations' );
			if ( ! $term || is_wp_error( $term ) ) {
				return [];
			}
			$tid = $term->term_id;
			return [
				'name'        => $term->name,
				'slug'        => $slug,
				'address' => (string) ( get_term_meta( $tid, 'mpcrbm_branch_address', true ) ?: '' ),
				'phone'   => (string) ( get_term_meta( $tid, 'mpcrbm_branch_phone', true ) ?: '' ),
				'hours'   => (array) ( get_term_meta( $tid, 'mpcrbm_branch_hours', true ) ?: [] ),
			];
		}

		/** Price multiplier for a branch slug (1.0 if not set). */
		public static function get_multiplier( string $slug ): float {
			$term = get_term_by( 'slug', $slug, 'mpcrbm_locations' );
			if ( ! $term || is_wp_error( $term ) ) {
				return 1.0;
			}
			$val = get_term_meta( $term->term_id, 'mpcrbm_branch_multiplier', true );
			return $val !== '' ? floatval( $val ) : 1.0;
		}

		/** One-way return fee for a branch slug (0 if not set). */
		public static function get_one_way_fee( string $slug ): float {
			$term = get_term_by( 'slug', $slug, 'mpcrbm_locations' );
			if ( ! $term || is_wp_error( $term ) ) {
				return 0.0;
			}
			return floatval( get_term_meta( $term->term_id, 'mpcrbm_branch_one_way_fee', true ) ?: 0 );
		}

		/**
		 * Branch availability check for the search filter.
		 *
		 * Rules:
		 *  - Car has NO branch meta at all  → show it (not branch-restricted)
		 *  - Car has branch meta            → only show when effective_branch === pickup_slug
		 *    effective_branch = mpcrbm_current_branch if set, else mpcrbm_home_branch
		 *
		 * Example: BMW 5 Series
		 *   home_branch=chittagong, current_branch=sylhet
		 *   pickup=chittagong → false (car is at sylhet)
		 *   pickup=sylhet     → true  (car is physically here)
		 *
		 * @param  int    $car_id      Car post ID.
		 * @param  string $pickup_slug Pickup location slug from the search form.
		 * @return bool
		 */
		public static function check_car_branch( int $car_id, string $pickup_slug ): bool {
			// No pickup selected → no branch filter applied
			if ( ! $pickup_slug ) {
				return true;
			}

			$current_branch = get_post_meta( $car_id, 'mpcrbm_current_branch', true );
			$home_branch    = get_post_meta( $car_id, 'mpcrbm_home_branch', true );

			// Car has no branch assignment → not restricted, always show
			if ( ! $current_branch && ! $home_branch ) {
				return true;
			}

			// Effective location: current_branch wins if set, fallback to home_branch
			$effective_branch = $current_branch ?: $home_branch;

			return $effective_branch === $pickup_slug;
		}

		/**
		 * Returns all published cars whose current_branch (or home_branch if current not set) = $slug.
		 *
		 * @param  string $slug Branch slug.
		 * @return WP_Post[]
		 */
		public static function get_cars_at_branch( string $slug ): array {
			$cpt = MPCRBM_Function::get_cpt();
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			return get_posts( [
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'OR',
					// Car explicitly set to this branch as current location
					[
						'key'     => 'mpcrbm_current_branch',
						'value'   => $slug,
						'compare' => '=',
					],
					// Car has no current_branch override but home_branch matches
					[
						'relation' => 'AND',
						[
							'key'     => 'mpcrbm_current_branch',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => 'mpcrbm_home_branch',
							'value'   => $slug,
							'compare' => '=',
						],
					],
					// Car has empty current_branch and home_branch matches
					[
						'relation' => 'AND',
						[
							'key'     => 'mpcrbm_current_branch',
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => 'mpcrbm_home_branch',
							'value'   => $slug,
							'compare' => '=',
						],
					],
				],
			] );
		}

		/**
		 * Appends a transfer entry to a car's audit log (capped at 50 entries).
		 *
		 * @param int    $car_id  Post ID of the car.
		 * @param string $from    Origin branch slug.
		 * @param string $to      Destination branch slug.
		 * @param string $reason  Human-readable reason.
		 * @param int    $user_id WP user ID performing the transfer.
		 */
		public static function log_transfer( int $car_id, string $from, string $to, string $reason = '', int $user_id = 0 ): void {
			$log   = get_post_meta( $car_id, 'mpcrbm_branch_transfer_log', true );
			$log   = is_array( $log ) ? $log : [];
			$log[] = [
				'from'   => $from,
				'to'     => $to,
				'date'   => current_time( 'Y-m-d H:i' ),
				'reason' => $reason,
				'by'     => $user_id ?: get_current_user_id(),
			];
			if ( count( $log ) > 50 ) {
				$log = array_slice( $log, -50 );
			}
			update_post_meta( $car_id, 'mpcrbm_branch_transfer_log', $log );
		}

		// ═══════════════════════════════════════════════════════════════════════
		// AJAX ENDPOINTS
		// ═══════════════════════════════════════════════════════════════════════

		/** Public endpoint — frontend JS calls this to show branch info card. */
		public function ajax_get_branch_info() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$slug = sanitize_text_field( wp_unslash( $_POST['branch_slug'] ?? '' ) );
			if ( ! $slug ) {
				wp_send_json_error( [ 'message' => 'No branch slug provided' ] );
			}
			$meta = self::get_branch_meta( $slug );
			if ( empty( $meta ) ) {
				wp_send_json_error( [ 'message' => 'Branch not found' ] );
			}
			wp_send_json_success( $meta );
		}

		/** Admin-only — moves a car to a different branch. */
		public function ajax_transfer_car() {
			check_ajax_referer( 'mpcrbm_branch_transfer', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
			}

			$car_id    = absint( $_POST['car_id'] ?? 0 );
			$to_branch = sanitize_text_field( wp_unslash( $_POST['to_branch'] ?? '' ) );
			$reason    = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

			if ( ! $car_id || ! $to_branch ) {
				wp_send_json_error( [ 'message' => __( 'Car ID and target branch are required', 'car-rental-manager' ) ] );
			}

			$from_branch = get_post_meta( $car_id, 'mpcrbm_current_branch', true );
			if ( ! $from_branch ) {
				$from_branch = get_post_meta( $car_id, 'mpcrbm_home_branch', true );
			}

			if ( $from_branch === $to_branch ) {
				wp_send_json_error( [ 'message' => __( 'Car is already at that branch', 'car-rental-manager' ) ] );
			}

			update_post_meta( $car_id, 'mpcrbm_current_branch', $to_branch );
			self::log_transfer( $car_id, $from_branch, $to_branch, $reason );

			$to_name   = MPCRBM_Function::get_taxonomy_name_by_slug( $to_branch, 'mpcrbm_locations' );
			$from_name = MPCRBM_Function::get_taxonomy_name_by_slug( $from_branch, 'mpcrbm_locations' );

			wp_send_json_success( [
				'message'   => sprintf(
					/* translators: 1: car post ID, 2: origin branch name, 3: destination branch name */
					__( 'Car #%1$d transferred from %2$s to %3$s', 'car-rental-manager' ),
					$car_id,
					$from_name,
					$to_name
				),
				'car_id'    => $car_id,
				'from'      => $from_branch,
				'to'        => $to_branch,
				'from_name' => $from_name,
				'to_name'   => $to_name,
			] );
		}

		/** Admin-only — returns car cards HTML for the branch dashboard. */
		public function ajax_get_branch_cars() {
			check_ajax_referer( 'mpcrbm_branch_transfer', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
			}

			$slug        = sanitize_text_field( wp_unslash( $_POST['branch_slug'] ?? '' ) );
			$cars        = self::get_cars_at_branch( $slug );
			$all_branches = get_terms( [ 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ] );

			ob_start();
			if ( empty( $cars ) ) {
				echo '<p class="mpcrbm-no-cars">' . esc_html__( 'No cars currently at this branch.', 'car-rental-manager' ) . '</p>';
			} else {
				echo '<div class="mpcrbm-branch-cars-grid">';
				foreach ( $cars as $car ) {
					$home_slug  = get_post_meta( $car->ID, 'mpcrbm_home_branch', true );
					$home_name  = MPCRBM_Function::get_taxonomy_name_by_slug( $home_slug, 'mpcrbm_locations' );
					$thumb      = get_the_post_thumbnail_url( $car->ID, 'thumbnail' );
					$day_price  = get_post_meta( $car->ID, 'mpcrbm_day_price', true );
					$edit_link  = get_edit_post_link( $car->ID );
					?>
					<div class="mpcrbm-branch-car-card" data-car-id="<?php echo esc_attr( $car->ID ); ?>">
						<div class="mpcrbm-car-thumb">
							<?php if ( $thumb ) : ?>
								<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $car->post_title ); ?>">
							<?php else : ?>
								<div class="mpcrbm-car-thumb-placeholder"><i class="mi mi-car"></i></div>
							<?php endif; ?>
						</div>
						<div class="mpcrbm-car-info">
							<strong><?php echo esc_html( $car->post_title ); ?></strong>
							<?php if ( $home_name ) : ?>
								<span class="mpcrbm-home-badge"><?php esc_html_e( 'Home:', 'car-rental-manager' ); ?> <?php echo esc_html( $home_name ); ?></span>
							<?php endif; ?>
							<?php if ( $day_price ) : ?>
								<span class="mpcrbm-price-badge"><?php echo wc_price( $day_price ); ?>/<?php esc_html_e( 'day', 'car-rental-manager' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="mpcrbm-car-transfer-form">
							<select class="mpcrbm-transfer-target">
								<option value=""><?php esc_html_e( 'Transfer to branch…', 'car-rental-manager' ); ?></option>
								<?php foreach ( $all_branches as $branch ) :
									if ( $branch->slug === $slug ) continue; ?>
									<option value="<?php echo esc_attr( $branch->slug ); ?>">
										<?php echo esc_html( $branch->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<input type="text" class="mpcrbm-transfer-reason"
								   placeholder="<?php esc_attr_e( 'Reason (optional)', 'car-rental-manager' ); ?>">
							<button class="button button-primary mpcrbm-do-transfer"
									data-car-id="<?php echo esc_attr( $car->ID ); ?>">
								<?php esc_html_e( 'Transfer', 'car-rental-manager' ); ?>
							</button>
							<?php if ( $edit_link ) : ?>
								<a href="<?php echo esc_url( $edit_link ); ?>" class="button" target="_blank">
									<?php esc_html_e( 'Edit Car', 'car-rental-manager' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
					<?php
				}
				echo '</div>';
			}

			wp_send_json_success( [ 'html' => ob_get_clean(), 'count' => count( $cars ) ] );
		}

		// ═══════════════════════════════════════════════════════════════════════
		// BRANCH DASHBOARD — rendered inside the taxonomies admin panel
		// ═══════════════════════════════════════════════════════════════════════

		public static function render_branch_dashboard() {
			$branches    = get_terms( [ 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ] );
			$nonce       = wp_create_nonce( 'mpcrbm_branch_transfer' );
			$cpt         = MPCRBM_Function::get_cpt();
			$add_new_url = admin_url( 'edit-tags.php?taxonomy=mpcrbm_locations&post_type=' . $cpt );
			?>
			<div class="mpcrbm-branch-dashboard" data-nonce="<?php echo esc_attr( $nonce ); ?>"
				 data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

				<div class="mpcrbm-branch-sidebar">

					<div class="mpcrbm-branch-sidebar-header">
						<div class="mpcrbm-branch-sidebar-title">
							<i class="mi mi-map-location-track"></i>
							<span><?php esc_html_e( 'Branches', 'car-rental-manager' ); ?></span>
						</div>
						<?php if ( ! empty( $branches ) && ! is_wp_error( $branches ) ) : ?>
							<span class="mpcrbm-branch-total-pill"><?php echo esc_html( count( $branches ) ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( empty( $branches ) || is_wp_error( $branches ) ) : ?>
						<p class="mpcrbm-no-branches">
							<?php esc_html_e( 'No branches configured yet. Add locations from WP Admin → Car Rental locations taxonomy.', 'car-rental-manager' ); ?>
						</p>
					<?php else : ?>
						<div class="mpcrbm-branch-list">
							<?php foreach ( $branches as $branch ) :
								$meta      = self::get_branch_meta( $branch->slug );
								$car_count = count( self::get_cars_at_branch( $branch->slug ) );
								$edit_url  = get_edit_term_link( $branch->term_id, 'mpcrbm_locations' );
							?>
							<div class="mpcrbm-branch-card" data-branch-slug="<?php echo esc_attr( $branch->slug ); ?>">
								<div class="mpcrbm-branch-card-header">
									<span class="mpcrbm-branch-name"><?php echo esc_html( $branch->name ); ?></span>
									<span class="mpcrbm-car-count-badge"><?php echo esc_html( $car_count ); ?></span>
								</div>
								<?php if ( $meta['address'] ) : ?>
									<div class="mpcrbm-branch-meta-row">
										<i class="mi mi-map-marker"></i>
										<span><?php echo esc_html( $meta['address'] ); ?></span>
									</div>
								<?php endif; ?>
								<?php if ( $meta['phone'] ) : ?>
									<div class="mpcrbm-branch-meta-row">
										<i class="mi mi-phone"></i>
										<span><?php echo esc_html( $meta['phone'] ); ?></span>
									</div>
								<?php endif; ?>
								<div class="mpcrbm-branch-badges"></div>
								<div class="mpcrbm-branch-card-actions">
									<button class="button button-primary mpcrbm-view-branch-cars"
											data-branch-slug="<?php echo esc_attr( $branch->slug ); ?>"
											data-branch-name="<?php echo esc_attr( $branch->name ); ?>">
										<i class="mi mi-car"></i>
										<?php esc_html_e( 'View Cars', 'car-rental-manager' ); ?>
									</button>
									<?php if ( $edit_url ) : ?>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="button">
											<i class="mi mi-edit"></i>
											<?php esc_html_e( 'Edit', 'car-rental-manager' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
							<?php endforeach; ?>
						</div>

						<div class="mpcrbm-branch-sidebar-footer">
							<a href="<?php echo esc_url( $add_new_url ); ?>" class="mpcrbm-add-branch-link">
								<i class="mi mi-plus"></i>
								<?php esc_html_e( 'Add New Branch', 'car-rental-manager' ); ?>
							</a>
						</div>

					<?php endif; ?>

				</div><!-- .mpcrbm-branch-sidebar -->

				<div class="mpcrbm-branch-cars-panel">
					<div class="mpcrbm-branch-cars-panel-header" style="display:none">
						<h3 class="mpcrbm-panel-branch-name"></h3>
						<span class="mpcrbm-panel-car-count"></span>
					</div>
					<div class="mpcrbm-branch-cars-panel-body">
						<div class="mpcrbm-select-prompt">
							<i class="mi mi-map-location-track"></i>
							<div>
								<p><?php esc_html_e( 'Select a branch to view and transfer cars.', 'car-rental-manager' ); ?></p>
								<span><?php esc_html_e( 'Click any branch from the left panel to get started.', 'car-rental-manager' ); ?></span>
							</div>
						</div>
					</div>
				</div><!-- .mpcrbm-branch-cars-panel -->

			</div><!-- .mpcrbm-branch-dashboard -->
			<?php
		}
	}

	new MPCRBM_Branch_Manager();
}
