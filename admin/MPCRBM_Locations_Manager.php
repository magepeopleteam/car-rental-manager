<?php
/*
 * @Author      MagePeople Team
 * Copyright:   mage-people.com
 *
 * In-shell "Locations" page — a full custom editor for the mpcrbm_locations
 * taxonomy (name/slug/description + the Branch Manager's address/phone/hours
 * term meta), rendered inside MPCRBM_Admin_Shell instead of linking out to
 * the native edit-tags.php screen. Saves go through wp_insert_term()/
 * wp_update_term(), which fire the created_mpcrbm_locations/
 * edited_mpcrbm_locations hooks MPCRBM_Branch_Manager::save_branch_meta()
 * is already listening on — as long as this page's AJAX POST includes the
 * same mpcrbm_branch_address/mpcrbm_branch_phone/mpcrbm_branch_hours[...]
 * field names that screen posts, that existing handler saves them with no
 * new meta-saving code needed here.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access pages directly.

if ( ! class_exists( 'MPCRBM_Locations_Manager' ) ) {
	class MPCRBM_Locations_Manager {

		public function __construct() {
			add_action( 'admin_menu', [ $this, 'register_menu' ] );
			add_action( 'wp_ajax_mpcrbm_save_location', [ $this, 'ajax_save_location' ] );
			add_action( 'wp_ajax_mpcrbm_update_location', [ $this, 'ajax_update_location' ] );
			add_action( 'wp_ajax_mpcrbm_delete_location', [ $this, 'ajax_delete_location' ] );
		}

		public function register_menu() {
			$cpt = MPCRBM_Function::get_cpt();
			add_submenu_page(
				'edit.php?post_type=' . $cpt,
				esc_html__( 'Locations', 'car-rental-manager' ),
				esc_html__( 'Locations', 'car-rental-manager' ),
				'manage_options',
				'mpcrbm_locations_manager',
				[ $this, 'render_page' ]
			);
		}

		// ═══════════════════════════════════════════════════════════════════
		// LIST — re-rendered wholesale after every save/delete (same
		// "swap the holder's HTML" pattern MPCRBM_Taxonomies.php already uses).
		// ═══════════════════════════════════════════════════════════════════

		public static function render_locations_list(): string {
			$terms = get_terms( [ 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ] );

			ob_start();

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				?>
				<div class="mpcrbm-locations-empty">
					<i class="mi mi-map-marker"></i>
					<p><?php esc_html_e( 'No branches yet', 'car-rental-manager' ); ?></p>
					<span><?php esc_html_e( 'Click "Add New Branch" to create your first branch location.', 'car-rental-manager' ); ?></span>
				</div>
				<?php
			} else {
				echo '<div class="mpcrbm-locations-grid">';
				foreach ( $terms as $term ) {
					$tid       = $term->term_id;
					$address   = (string) ( get_term_meta( $tid, 'mpcrbm_branch_address', true ) ?: '' );
					$phone     = (string) ( get_term_meta( $tid, 'mpcrbm_branch_phone', true ) ?: '' );
					$hours     = get_term_meta( $tid, 'mpcrbm_branch_hours', true );
					$hours     = is_array( $hours ) ? $hours : [];
					$has_cars  = $term->count > 0;
					?>
					<div class="mpcrbm-location-card"
						 data-term-id="<?php echo esc_attr( $tid ); ?>"
						 data-name="<?php echo esc_attr( $term->name ); ?>"
						 data-slug="<?php echo esc_attr( $term->slug ); ?>"
						 data-desc="<?php echo esc_attr( $term->description ); ?>"
						 data-address="<?php echo esc_attr( $address ); ?>"
						 data-phone="<?php echo esc_attr( $phone ); ?>"
						 data-hours='<?php echo esc_attr( wp_json_encode( $hours ) ); ?>'>
						<div class="mpcrbm-location-card-top">
							<strong class="mpcrbm-location-name"><?php echo esc_html( $term->name ); ?></strong>
							<div class="mpcrbm-location-card-icon-actions">
								<button type="button" class="mpcrbm-location-edit-btn" title="<?php esc_attr_e( 'Edit', 'car-rental-manager' ); ?>"><i class="mi mi-pencil"></i></button>
								<button type="button" class="mpcrbm-location-delete-btn" title="<?php esc_attr_e( 'Delete', 'car-rental-manager' ); ?>"><i class="mi mi-trash"></i></button>
							</div>
						</div>
						<?php if ( $term->description ) : ?>
							<div class="mpcrbm-location-region"><i class="mi mi-map-marker"></i><span><?php echo esc_html( $term->description ); ?></span></div>
						<?php endif; ?>

						<div class="mpcrbm-location-stat">
							<div class="mpcrbm-location-stat-text">
								<span class="mpcrbm-location-stat-label"><?php esc_html_e( 'Vehicle Count', 'car-rental-manager' ); ?></span>
								<span class="mpcrbm-location-stat-value"><?php echo esc_html( $term->count ); ?></span>
							</div>
							<span class="mpcrbm-location-status-pill <?php echo $has_cars ? 'is-active' : 'is-empty'; ?>">
								<?php echo $has_cars ? esc_html__( 'Active', 'car-rental-manager' ) : esc_html__( 'Empty', 'car-rental-manager' ); ?>
							</span>
						</div>

						<button type="button" class="mpcrbm-view-branch-cars mpcrbm-location-view-cars-btn" data-branch-slug="<?php echo esc_attr( $term->slug ); ?>" data-branch-name="<?php echo esc_attr( $term->name ); ?>">
							<?php esc_html_e( 'View Cars', 'car-rental-manager' ); ?> <i class="mi mi-arrow-right"></i>
						</button>
					</div>
					<?php
				}
				echo '</div>';
			}

			return (string) ob_get_clean();
		}

		// ═══════════════════════════════════════════════════════════════════
		// PAGE
		// ═══════════════════════════════════════════════════════════════════

		public function render_page() {
			$nonce = wp_create_nonce( 'mpcrbm_locations_nonce' );

			MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Locations', 'car-rental-manager' ) );
			?>
			<!-- Data-only marker (display:none) so mpcrbm-branch-manager.js's dashboard()/
			     ajaxUrl()/nonce() helpers — which look up ".mpcrbm-branch-dashboard" — resolve
			     correctly on this page too, letting the "View Cars" buttons below reuse that
			     existing AJAX flow (action=mpcrbm_get_branch_cars) with zero new JS/PHP. -->
			<div class="mpcrbm-branch-dashboard" data-nonce="<?php echo esc_attr( wp_create_nonce( 'mpcrbm_branch_transfer' ) ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" style="display:none"></div>

			<div class="mpcrbm-locations-manager" data-nonce="<?php echo esc_attr( $nonce ); ?>">

				<div class="mpcrbm-locations-head">
					<div class="mpcrbm-locations-head-text">
						<span class="mpcrbm-locations-head-eyebrow"><?php esc_html_e( 'Regional Operations', 'car-rental-manager' ); ?></span>
						<h2><?php esc_html_e( 'Branch Management', 'car-rental-manager' ); ?></h2>
						<p class="mpcrbm-locations-head-subtitle"><?php esc_html_e( 'Manage regional operations and fleet distribution across your network. Real-time monitoring of vehicle availability and branch performance metrics.', 'car-rental-manager' ); ?></p>
					</div>
					<button type="button" class="mpcrbm-locations-add-btn" id="mpcrbm_add_location_btn">
						<i class="mi mi-plus"></i> <?php esc_html_e( 'Add New Branch', 'car-rental-manager' ); ?>
					</button>
				</div>

				<div class="mpcrbm-locations-list" id="mpcrbm_locations_list">
					<?php echo self::render_locations_list(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_html()/esc_attr()'d pieces above. ?>
				</div>

				<!-- Populated by the existing mpcrbm-branch-manager.js ".mpcrbm-view-branch-cars"
				     click handler — same panel markup/classes as the Branch Manager tab's own
				     dashboard (MPCRBM_Branch_Manager::render_branch_dashboard()), so it already
				     has full styling from mpcrbm-branch-manager.css with nothing new to write. -->
				<div class="mpcrbm-branch-cars-panel">
					<div class="mpcrbm-branch-cars-panel-header" style="display:none">
						<h3 class="mpcrbm-panel-branch-name"></h3>
						<span class="mpcrbm-panel-car-count"></span>
					</div>
					<div class="mpcrbm-branch-cars-panel-body">
						<div class="mpcrbm-select-prompt">
							<i class="mi mi-car"></i>
							<div>
								<p><?php esc_html_e( 'Select a branch to view its cars.', 'car-rental-manager' ); ?></p>
								<span><?php esc_html_e( 'Click "View Cars" on any branch above to get started.', 'car-rental-manager' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Add/Edit modal — one shared markup block, populated by JS for both add and edit. -->
			<div class="mpcrbm-location-modal-overlay" id="mpcrbm_location_modal_overlay">
				<div class="mpcrbm-location-modal">
					<input type="hidden" id="mpcrbm_location_term_id" value="">

					<div class="mpcrbm-location-modal-header">
						<h3 id="mpcrbm_location_modal_title"><?php esc_html_e( 'Add Location', 'car-rental-manager' ); ?></h3>
						<button type="button" class="mpcrbm-location-modal-close" id="mpcrbm_close_location_modal" aria-label="<?php esc_attr_e( 'Close', 'car-rental-manager' ); ?>">&times;</button>
					</div>

					<div class="mpcrbm-location-modal-body">

						<div class="mpcrbm-location-modal-section">
							<h4 class="mpcrbm-location-modal-subhead"><i class="mi mi-info"></i> <?php esc_html_e( 'General', 'car-rental-manager' ); ?></h4>

							<div class="mpcrbm-location-modal-row">
								<div class="mpcrbm-location-field">
									<label for="mpcrbm_location_name"><?php esc_html_e( 'Name', 'car-rental-manager' ); ?></label>
									<input type="text" id="mpcrbm_location_name" placeholder="<?php esc_attr_e( 'e.g. Downtown Branch', 'car-rental-manager' ); ?>">
								</div>
								<div class="mpcrbm-location-field">
									<label for="mpcrbm_location_slug"><?php esc_html_e( 'Slug', 'car-rental-manager' ); ?></label>
									<input type="text" id="mpcrbm_location_slug" placeholder="<?php esc_attr_e( 'Auto-generated from name', 'car-rental-manager' ); ?>">
								</div>
							</div>

							<div class="mpcrbm-location-field">
								<label for="mpcrbm_location_desc"><?php esc_html_e( 'Description', 'car-rental-manager' ); ?></label>
								<textarea id="mpcrbm_location_desc" placeholder="<?php esc_attr_e( 'Short description', 'car-rental-manager' ); ?>"></textarea>
							</div>
						</div>

						<div class="mpcrbm-location-modal-section">
							<h4 class="mpcrbm-location-modal-subhead"><i class="mi mi-building"></i> <?php esc_html_e( 'Branch Details', 'car-rental-manager' ); ?></h4>

							<div class="mpcrbm-location-modal-row">
								<div class="mpcrbm-location-field">
									<label for="mpcrbm_location_address"><?php esc_html_e( 'Address', 'car-rental-manager' ); ?></label>
									<input type="text" id="mpcrbm_location_address" placeholder="<?php esc_attr_e( 'Full street address', 'car-rental-manager' ); ?>">
								</div>
								<div class="mpcrbm-location-field">
									<label for="mpcrbm_location_phone"><?php esc_html_e( 'Phone', 'car-rental-manager' ); ?></label>
									<input type="text" id="mpcrbm_location_phone">
								</div>
							</div>
						</div>

						<div class="mpcrbm-location-modal-section">
							<h4 class="mpcrbm-location-modal-subhead"><i class="mi mi-clock"></i> <?php esc_html_e( 'Operating Hours', 'car-rental-manager' ); ?></h4>
							<div class="mpcrbm-location-hours-wrap">
								<?php MPCRBM_Branch_Manager::render_hours_fields( [] ); ?>
							</div>
						</div>

					</div>

					<div class="mpcrbm-location-modal-actions">
						<button type="button" class="mpcrbm-location-cancel-btn" id="mpcrbm_cancel_location_btn"><?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?></button>
						<button type="button" class="mpcrbm-location-save-btn" id="mpcrbm_save_location_btn"><?php esc_html_e( 'Save Changes', 'car-rental-manager' ); ?></button>
					</div>
				</div>
			</div>
			<?php
			MPCRBM_Admin_Shell::render_shell_close();
		}

		// ═══════════════════════════════════════════════════════════════════
		// AJAX
		// ═══════════════════════════════════════════════════════════════════

		public function ajax_save_location() {
			check_ajax_referer( 'mpcrbm_locations_nonce', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
			}

			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
			$desc = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

			if ( empty( $name ) ) {
				wp_send_json_error( [ 'message' => __( 'Name is required.', 'car-rental-manager' ) ], 400 );
			}

			$result = wp_insert_term( $name, 'mpcrbm_locations', [
				'slug'        => $slug,
				'description' => $desc,
			] );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
			}

			// wp_insert_term() fires created_mpcrbm_locations synchronously —
			// MPCRBM_Branch_Manager::save_branch_meta() (already hooked to it)
			// reads mpcrbm_branch_address/_phone/_hours straight from $_POST
			// and has already run by the time we get here.

			wp_send_json_success( [
				'message' => __( 'Location added successfully!', 'car-rental-manager' ),
				'html'    => self::render_locations_list(),
			] );
		}

		public function ajax_update_location() {
			check_ajax_referer( 'mpcrbm_locations_nonce', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
			}

			$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
			$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$slug    = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
			$desc    = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

			if ( ! $term_id || empty( $name ) ) {
				wp_send_json_error( [ 'message' => __( 'Name is required.', 'car-rental-manager' ) ], 400 );
			}

			$result = wp_update_term( $term_id, 'mpcrbm_locations', [
				'name'        => $name,
				'slug'        => $slug,
				'description' => $desc,
			] );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
			}

			// Same edited_mpcrbm_locations → save_branch_meta() mechanism as above.

			wp_send_json_success( [
				'message' => __( 'Location updated successfully!', 'car-rental-manager' ),
				'html'    => self::render_locations_list(),
			] );
		}

		public function ajax_delete_location() {
			check_ajax_referer( 'mpcrbm_locations_nonce', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized', 'car-rental-manager' ) ], 403 );
			}

			$term_id = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;
			if ( ! $term_id ) {
				wp_send_json_error( [ 'message' => __( 'Invalid location.', 'car-rental-manager' ) ], 400 );
			}

			$result = wp_delete_term( $term_id, 'mpcrbm_locations' );
			if ( is_wp_error( $result ) || ! $result ) {
				wp_send_json_error( [ 'message' => __( 'Unable to delete location.', 'car-rental-manager' ) ], 400 );
			}

			wp_send_json_success( [
				'message' => __( 'Location deleted.', 'car-rental-manager' ),
				'html'    => self::render_locations_list(),
			] );
		}
	}

	new MPCRBM_Locations_Manager();
}
