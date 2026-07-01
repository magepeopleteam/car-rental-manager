<?php
/*
 * Branch Search Shortcode — [mpcrbm_branch_search]
 *
 * Pickup / dropoff location options are built the same way [mpcrbm_booking] does:
 * scan every published car and collect its effective pickup locations.  This covers
 * branch-managed cars (mpcrbm_current_branch / mpcrbm_home_branch meta), multi-
 * location cars (mpcrbm_location_prices), and standard operation-area cars
 * (mpcrbm_manual_price_info / mpcrbm_terms_price_info).
 *
 * For each slug we try to resolve a human-readable name from the mpcrbm_locations
 * taxonomy; if the term does not exist yet we fall back to a formatted version of
 * the slug so the dropdown is never empty.
 */
if ( ! defined( 'ABSPATH' ) ) { die; }

if ( ! class_exists( 'MPCRBM_Branch_Search' ) ) {
	class MPCRBM_Branch_Search {

		public function __construct() {
			add_shortcode( 'mpcrbm_branch_search', [ $this, 'render_shortcode' ] );
		}

		// ── Location helpers ──────────────────────────────────────────────

		/**
		 * Collect every pickup-location slug that appears across all published cars.
		 * Mirrors the logic in get_details_new.php that populates [mpcrbm_booking].
		 *
		 * @return string[]  Unique, non-empty slugs.
		 */
		private static function collect_location_slugs(): array {
			$cpt     = MPCRBM_Function::get_cpt();
			$car_ids = MPCRBM_Global_Function::get_all_post_id( $cpt );

			$slugs = [];
			foreach ( $car_ids as $car_id ) {
				// get_vehicle_pickup_locations already handles branch meta,
				// multi-location prices, and standard operation areas.
				$locs  = MPCRBM_Function::get_vehicle_branch_location( $car_id );
				$slugs = array_merge( $slugs, $locs );
			}

			// Standard (non-branch, non-multi-location) operation-area slugs
			// get_all_start_location() with no arg scans all cars.
			$slugs = array_merge( $slugs, MPCRBM_Function::get_all_start_location() );

			return array_values( array_unique( array_filter( $slugs ) ) );
		}

		/**
		 * Turn a slug into a human-readable display label.
		 * Priority: mpcrbm_locations term name → formatted slug.
		 */
		private static function slug_label( string $slug ): string {
			$term = get_term_by( 'slug', $slug, 'mpcrbm_locations' );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term->name;
			}
			return ucwords( str_replace( [ '-', '_' ], ' ', $slug ) );
		}

		/**
		 * Build the branch meta array used by the JS info-card.
		 * If no taxonomy term exists for the slug we still return a minimal entry.
		 */
		private static function build_branch_data( string $slug ): array {
			$base = [ 'name' => self::slug_label( $slug ), 'slug' => $slug, 'address' => '', 'phone' => '', 'hours' => [] ];

			$term = get_term_by( 'slug', $slug, 'mpcrbm_locations' );
			if ( ! $term || is_wp_error( $term ) ) {
				return $base;
			}

			$tid     = $term->term_id;
			$hrs_raw = get_term_meta( $tid, 'mpcrbm_branch_hours', true );

			return [
				'name'    => $term->name,
				'slug'    => $slug,
				'address' => (string) ( get_term_meta( $tid, 'mpcrbm_branch_address', true ) ?: '' ),
				'phone'   => (string) ( get_term_meta( $tid, 'mpcrbm_branch_phone',   true ) ?: '' ),
				'hours'   => is_array( $hrs_raw ) ? $hrs_raw : [],
			];
		}

		/** Format a HH:MM string to "9am" / "5:30pm". */
		private static function fmt_time( string $t ): string {
			if ( ! $t ) return '';
			[ $h, $m ] = array_pad( explode( ':', $t ), 2, '0' );
			$h   = (int) $h;
			$m   = (int) $m;
			$sfx = $h >= 12 ? 'pm' : 'am';
			$h12 = $h % 12 ?: 12;
			return $h12 . ( $m ? ':' . str_pad( $m, 2, '0', STR_PAD_LEFT ) : '' ) . $sfx;
		}

		// ── Shortcode ─────────────────────────────────────────────────────

		public function render_shortcode( $atts ): string {
			$atts = shortcode_atts(
				[ 'price_based' => 'manual' ],
				$atts,
				'mpcrbm_branch_search'
			);

			// Collect locations exactly as [mpcrbm_booking] does
			$slugs = self::collect_location_slugs();

			// Build [slug => label] map for the <select> options
			$locations = [];
			foreach ( $slugs as $slug ) {
				$locations[ $slug ] = self::slug_label( $slug );
			}
			asort( $locations );   // alphabetical by display name

			// Build branch data for the JS info card (keyed by slug)
			$branches = [];
			foreach ( $slugs as $slug ) {
				$branches[ $slug ] = self::build_branch_data( $slug );
			}

			$nonce     = wp_create_nonce( 'mpcrbm_transportation_type_nonce' );
			$today     = gmdate( 'Y-m-d' );
			$tomorrow  = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
			$today_day = strtolower( gmdate( 'D' ) ); // 'mon','tue', …

			ob_start();
			?>
			<div class="mpcrbm-bs mpcrbm"
			     data-nonce="<?php echo esc_attr( $nonce ); ?>"
			     data-price-based="<?php echo esc_attr( $atts['price_based'] ); ?>"
			     data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

				<!-- ── Header ──────────────────────────────────────────── -->
				<div class="mpcrbm-bs__header">
					<span class="mpcrbm-bs__header-icon"><i class="mi mi-car-journey"></i></span>
					<div>
						<h3 class="mpcrbm-bs__header-title"><?php esc_html_e( 'Find Your Perfect Car', 'car-rental-manager' ); ?></h3>
						<p class="mpcrbm-bs__header-sub"><?php esc_html_e( 'Choose a location, pick your dates, and explore available vehicles', 'car-rental-manager' ); ?></p>
					</div>
				</div>

				<!-- ── Form ────────────────────────────────────────────── -->
				<div class="mpcrbm-bs__form">

					<!-- Step 1: Pickup Location -->
					<div class="mpcrbm-bs__step">
						<div class="mpcrbm-bs__step-head">
							<span class="mpcrbm-bs__step-num">1</span>
							<span class="mpcrbm-bs__step-title"><?php esc_html_e( 'Choose pickup location', 'car-rental-manager' ); ?></span>
						</div>
						<div class="mpcrbm-bs__step-body">
							<label class="mpcrbm-bs__label">
								<?php esc_html_e( 'Pickup location', 'car-rental-manager' ); ?>
								<span class="mpcrbm-bs__req">*</span>
							</label>
							<div class="mpcrbm-bs__select-wrap">
								<i class="mi mi-map-location-track mpcrbm-bs__field-icon"></i>
								<select id="mpcrbm_bs_pickup" class="mpcrbm-bs__select">
									<option value=""><?php esc_html_e( '— Select a location —', 'car-rental-manager' ); ?></option>
									<?php foreach ( $locations as $slug => $label ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>">
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<i class="mi mi-angle-down mpcrbm-bs__chevron"></i>
							</div>
							<!-- Branch info card rendered by JS when location has branch meta -->
							<div id="mpcrbm_bs_branch_info" class="mpcrbm-bs__branch-card" hidden></div>
						</div>
					</div>

					<!-- Step 2: Rental Dates (Flatpickr range) -->
					<div class="mpcrbm-bs__step">
						<div class="mpcrbm-bs__step-head">
							<span class="mpcrbm-bs__step-num">2</span>
							<span class="mpcrbm-bs__step-title"><?php esc_html_e( 'Select rental dates', 'car-rental-manager' ); ?></span>
						</div>
						<div class="mpcrbm-bs__step-body">
							<!-- Hidden values consumed by the search AJAX -->
							<input type="hidden" id="mpcrbm_bs_start_date">
							<input type="hidden" id="mpcrbm_bs_return_date">
							<!-- Visible Flatpickr range input -->
							<label class="mpcrbm-bs__label" for="mpcrbm_bs_date_range">
								<?php esc_html_e( 'Pickup date', 'car-rental-manager' ); ?> &rarr; <?php esc_html_e( 'Return date', 'car-rental-manager' ); ?> <span class="mpcrbm-bs__req">*</span>
							</label>
							<div class="mpcrbm-bs__input-wrap">
								<i class="mi mi-calendar mpcrbm-bs__field-icon"></i>
								<input type="text" id="mpcrbm_bs_date_range" class="mpcrbm-bs__date"
								       placeholder="<?php esc_attr_e( 'Select pickup and return dates', 'car-rental-manager' ); ?>"
								       readonly>
							</div>
							<div id="mpcrbm_bs_duration_badge" class="mpcrbm-bs__duration-badge" hidden></div>
						</div>
					</div>

					<!-- Step 3: Return Location -->
					<div class="mpcrbm-bs__step">
						<div class="mpcrbm-bs__step-head">
							<span class="mpcrbm-bs__step-num">3</span>
							<span class="mpcrbm-bs__step-title"><?php esc_html_e( 'Return location', 'car-rental-manager' ); ?></span>
						</div>
						<div class="mpcrbm-bs__step-body">
							<label class="mpcrbm-bs__toggle-row">
								<div class="mpcrbm-bs__toggle">
									<input type="checkbox" id="mpcrbm_bs_same_loc" checked>
									<span class="mpcrbm-bs__toggle-track">
										<span class="mpcrbm-bs__toggle-thumb"></span>
									</span>
								</div>
								<span><?php esc_html_e( 'Return to same location', 'car-rental-manager' ); ?></span>
							</label>
							<div id="mpcrbm_bs_dropoff_wrap" class="mpcrbm-bs__dropoff-wrap" style="display:none">
								<label class="mpcrbm-bs__label" style="margin-top:12px">
									<?php esc_html_e( 'Return location', 'car-rental-manager' ); ?>
								</label>
								<div class="mpcrbm-bs__select-wrap">
									<i class="mi mi-marker mpcrbm-bs__field-icon"></i>
									<select id="mpcrbm_bs_dropoff" class="mpcrbm-bs__select">
										<option value=""><?php esc_html_e( '— Select return location —', 'car-rental-manager' ); ?></option>
										<?php foreach ( $locations as $slug => $label ) : ?>
											<option value="<?php echo esc_attr( $slug ); ?>">
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<i class="mi mi-angle-down mpcrbm-bs__chevron"></i>
								</div>
							</div>
						</div>
					</div>

					<!-- CTA -->
					<div class="mpcrbm-bs__cta">
						<button id="mpcrbm_bs_search_btn" class="mpcrbm-bs__btn" type="button">
							<i class="mi mi-search"></i>
							<span><?php esc_html_e( 'Search Available Cars', 'car-rental-manager' ); ?></span>
						</button>
					</div>

				</div><!-- /.mpcrbm-bs__form -->

				<!-- ── Step 4: Results ────────────────────────────────── -->
				<div class="mpcrbm-bs__results" id="mpcrbm_bs_results_section" style="display:none">
					<div class="mpcrbm-bs__step-head">
						<span class="mpcrbm-bs__step-num">4</span>
						<span class="mpcrbm-bs__step-title">
							<?php esc_html_e( 'Available cars', 'car-rental-manager' ); ?>
						</span>
					</div>
					<div id="mpcrbm_bs_cars" class="mpcrbm-bs__cars-holder"></div>
				</div>

				<!-- Placeholder -->
				<div class="mpcrbm-bs__placeholder" id="mpcrbm_bs_placeholder">
					<div class="mpcrbm-bs__placeholder-icon"><i class="mi mi-car"></i></div>
					<p><?php esc_html_e( 'Select a location and dates to see available cars', 'car-rental-manager' ); ?></p>
				</div>

			</div><!-- /.mpcrbm-bs -->

			<script>
			window.mpcrbmBsData = <?php echo wp_json_encode( [ 'branches' => $branches ] ); ?>;
			</script>
			<?php
			return ob_get_clean();
		}
	}
	new MPCRBM_Branch_Search();
}
