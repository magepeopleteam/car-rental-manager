<?php
/*
 * Branch Manager User System
 * ==========================
 * Super Admin can:
 *   - Create Branch Manager users (name, email, username, password, branches, status)
 *   - Assign one or more branches to each manager
 *   - Edit / delete managers and change their branch assignments
 *   - See and manage everything across all branches
 *
 * Branch Manager can:
 *   - Access ALL WordPress admin menus (no restrictions on navigation)
 *   - Within THIS PLUGIN only: see / edit / delete data for their assigned branch(es)
 *   - Car list shows only their branch's cars
 *   - Branch taxonomy list shows only their branch
 *   - New cars are auto-assigned to their branch
 *   - Cannot edit cars, branches, or bookings belonging to other branches
 *   - Inactive account is blocked at login time
 *
 * User meta stored per Branch Manager:
 *   mpcrbm_managed_branches  array   branch slugs assigned to this user
 *   mpcrbm_bm_status         string  'active' | 'inactive'
 *
 * Order item meta keys used for filtering (set by MPCRBM_Woocommerce.php):
 *   _mpcrbm_id               int     car post ID
 *   _mpcrbm_start_place      string  pickup branch slug
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'MPCRBM_User_Branch_Manager' ) ) {
	class MPCRBM_User_Branch_Manager {

		const ROLE_SLUG     = 'mpcrbm_branch_manager';
		const BRANCHES_META = 'mpcrbm_managed_branches'; // string[]
		const STATUS_META   = 'mpcrbm_bm_status';        // 'active'|'inactive'

		public function __construct() {
			$this->register_role();

			// Block inactive branch managers at login
			add_filter( 'authenticate', [ $this, 'block_inactive_login' ], 30, 1 );

			// Admin panel pages + form handlers
			add_action( 'admin_menu',            [ $this, 'register_menus' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
			add_action( 'admin_post_mpcrbm_save_bm',   [ $this, 'handle_save_bm' ] );
			add_action( 'admin_post_mpcrbm_delete_bm', [ $this, 'handle_delete_bm' ] );

			// Data-level access control (fires every admin request)
			add_action( 'admin_init', [ $this, 'setup_data_filters' ] );

			// Tag each WooCommerce order with pickup branch slug for fast querying
			add_action( 'woocommerce_checkout_order_created', [ $this, 'tag_order_with_branch' ] );
		}

		// ═══════════════════════════════════════════════════════════════════
		// ROLE
		// ═══════════════════════════════════════════════════════════════════

		private function register_role(): void {
			$required_caps = [
				'read'                   => true,
				'upload_files'           => true,
				'edit_posts'             => true,
				'edit_published_posts'   => true,
				'publish_posts'          => true,
				'delete_posts'           => true,
				'delete_published_posts' => true,
				'manage_categories'      => true,
				self::ROLE_SLUG          => true,
			];

			$role = get_role( self::ROLE_SLUG );
			if ( ! $role ) {
				add_role( self::ROLE_SLUG, __( 'Branch Manager', 'car-rental-manager' ), $required_caps );
				return;
			}

			// Ensure existing role has all required caps (handles upgrades where caps were added).
			foreach ( $required_caps as $cap => $grant ) {
				if ( $grant && empty( $role->capabilities[ $cap ] ) ) {
					$role->add_cap( $cap );
				}
			}
		}

		// ═══════════════════════════════════════════════════════════════════
		// STATIC HELPERS
		// ═══════════════════════════════════════════════════════════════════

		public static function is_branch_manager(): bool {
			$u = wp_get_current_user();
			return $u instanceof WP_User
				&& in_array( self::ROLE_SLUG, (array) $u->roles, true );
		}

		public static function get_current_user_branches(): array {
			return self::get_user_branches( get_current_user_id() );
		}

		public static function get_user_branches( int $uid ): array {
			$val = get_user_meta( $uid, self::BRANCHES_META, true );
			if ( is_array( $val ) ) {
				return array_values( array_filter( $val ) );
			}
			// backward-compat: single slug from the old meta key
			$single = (string) get_user_meta( $uid, 'mpcrbm_managed_branch', true );
			return $single !== '' ? [ $single ] : [];
		}

		// ═══════════════════════════════════════════════════════════════════
		// BLOCK INACTIVE MANAGERS AT LOGIN
		// ═══════════════════════════════════════════════════════════════════

		public function block_inactive_login( $user ) {
			if ( ! ( $user instanceof WP_User ) ) {
				return $user;
			}
			if ( ! in_array( self::ROLE_SLUG, (array) $user->roles, true ) ) {
				return $user;
			}
			if ( (string) get_user_meta( $user->ID, self::STATUS_META, true ) === 'inactive' ) {
				return new WP_Error(
					'mpcrbm_bm_inactive',
					__( '<strong>Error:</strong> Your branch manager account is inactive. Please contact the administrator.', 'car-rental-manager' )
				);
			}
			return $user;
		}

		// ═══════════════════════════════════════════════════════════════════
		// ADMIN MENU
		// ═══════════════════════════════════════════════════════════════════

		public function register_menus(): void {
			$cpt = MPCRBM_Function::get_cpt();

			// Super admin: Branch Managers management page
			if ( current_user_can( 'manage_options' ) ) {
				add_submenu_page(
					'edit.php?post_type=' . $cpt,
					__( 'Branch Managers', 'car-rental-manager' ),
					__( 'Branch Managers', 'car-rental-manager' ),
					'manage_options',
					'mpcrbm_branch_managers',
					[ $this, 'render_branch_managers_page' ]
				);
			}

			// Branch manager: My Branch dashboard + Bookings
			if ( self::is_branch_manager() ) {
				add_submenu_page(
					'edit.php?post_type=' . $cpt,
					__( 'My Branch', 'car-rental-manager' ),
					__( 'My Branch', 'car-rental-manager' ),
					self::ROLE_SLUG,
					'mpcrbm_my_branch',
					[ $this, 'render_my_branch_page' ]
				);
				add_submenu_page(
					'edit.php?post_type=' . $cpt,
					__( 'Bookings', 'car-rental-manager' ),
					__( 'Bookings', 'car-rental-manager' ),
					self::ROLE_SLUG,
					'mpcrbm_bm_bookings',
					[ $this, 'render_bookings_page' ]
				);
			}
		}

		// ═══════════════════════════════════════════════════════════════════
		// DATA-LEVEL FILTERS  (only restrict plugin data, not admin navigation)
		// ═══════════════════════════════════════════════════════════════════

		public function setup_data_filters(): void {
			if ( ! self::is_branch_manager() ) {
				return;
			}

			// Inactive check (belt-and-suspenders — login hook already blocks at login)
			if ( (string) get_user_meta( get_current_user_id(), self::STATUS_META, true ) === 'inactive' ) {
				wp_die(
					esc_html__( 'Your branch manager account is inactive. Please contact the administrator.', 'car-rental-manager' ),
					esc_html__( 'Account Inactive', 'car-rental-manager' ),
					[ 'response' => 403, 'back_link' => true ]
				);
			}

			// Filter the car CPT list to only show this branch's cars
			add_action( 'pre_get_posts', [ $this, 'filter_car_query' ] );

			// Filter the branch taxonomy list to only show assigned branches
			add_filter( 'terms_clauses', [ $this, 'filter_branch_terms_sql' ], 10, 3 );

			// Auto-assign branch when a new car is created
			add_action( 'save_post', [ $this, 'auto_assign_branch_on_save' ], 10, 2 );

			// Grant/revoke edit caps per-car based on branch ownership
			add_filter( 'user_has_cap', [ $this, 'dynamic_post_caps' ], 10, 3 );

			// Grant taxonomy management caps for mpcrbm_locations (safety net for edit-tags.php)
			add_filter( 'user_has_cap', [ $this, 'dynamic_taxonomy_caps' ], 10, 3 );

			// Prevent editing another branch's taxonomy term via direct URL
			add_action( 'current_screen', [ $this, 'block_foreign_branch_edit' ] );
		}

		// ── Car list query filter ─────────────────────────────────────────────

		public function filter_car_query( WP_Query $q ): void {
			if ( ! $q->is_main_query() || ! is_admin() ) {
				return;
			}
			if ( $q->get( 'post_type' ) !== MPCRBM_Function::get_cpt() ) {
				return;
			}

			$branches = self::get_current_user_branches();
			if ( empty( $branches ) ) {
				$q->set( 'post__in', [ 0 ] ); // no branch assigned → show nothing
				return;
			}

			$meta = [ 'relation' => 'OR' ];
			foreach ( $branches as $slug ) {
				$meta[] = [ 'key' => 'mpcrbm_home_branch',    'value' => $slug, 'compare' => '=' ];
				$meta[] = [ 'key' => 'mpcrbm_current_branch', 'value' => $slug, 'compare' => '=' ];
			}
			$q->set( 'meta_query', $meta );
		}

		// ── Branch taxonomy list filter ───────────────────────────────────────

		public function filter_branch_terms_sql( array $clauses, array $taxes, array $args ): array {
			if ( ! is_admin() ) {
				return $clauses;
			}
			if ( ! in_array( 'mpcrbm_locations', $taxes, true ) ) {
				return $clauses;
			}

			$branches = self::get_current_user_branches();
			if ( empty( $branches ) ) {
				$clauses['where'] .= ' AND 1=0';
				return $clauses;
			}

			global $wpdb;
			$ph               = implode( ',', array_fill( 0, count( $branches ), '%s' ) );
			$clauses['where'] .= $wpdb->prepare( " AND t.slug IN ($ph)", ...$branches );
			return $clauses;
		}

		// ── Auto-assign branch when BM creates a new car ─────────────────────

		public function auto_assign_branch_on_save( int $post_id, WP_Post $post ): void {
			if ( $post->post_type !== MPCRBM_Function::get_cpt() ) {
				return;
			}
			if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
				return;
			}

			$branches = self::get_current_user_branches();
			if ( empty( $branches ) ) {
				return;
			}

			if ( ! get_post_meta( $post_id, 'mpcrbm_home_branch', true ) ) {
				$primary = $branches[0];
				update_post_meta( $post_id, 'mpcrbm_branch_enabled', '1' );
				update_post_meta( $post_id, 'mpcrbm_home_branch',    $primary );
				update_post_meta( $post_id, 'mpcrbm_current_branch', $primary );
			}
		}

		// ── Dynamic post capabilities ─────────────────────────────────────────
		//
		// How WordPress capability resolution works:
		//   current_user_can('edit_post', $id)
		//     → map_meta_cap()  resolves the META cap to PRIMITIVE caps:
		//       e.g. 'edit_others_posts', 'edit_published_posts'  (stored in $caps)
		//     → user_has_cap filter fires with ($allcaps, $caps, $args)
		//       $args[0] = meta cap ('edit_post')
		//       $caps    = primitive caps WordPress needs to be TRUE in $allcaps
		//
		// Bug in previous version: it set $allcaps['edit_post'] = true, which is the
		// meta cap key — WordPress doesn't check that. It checks the PRIMITIVE caps
		// in $caps ('edit_others_posts' etc.). Fixed by iterating over $caps below.

		public function dynamic_post_caps( array $allcaps, array $caps, array $args ): array {
			$meta_cap = $args[0] ?? '';
			$post_id  = isset( $args[2] ) ? (int) $args[2] : 0;

			// Meta caps we intercept for branch-owned cars
			$handled = [
				'edit_post', 'delete_post', 'read_post',
				'publish_post', 'set_post_terms',
			];
			if ( ! in_array( $meta_cap, $handled, true ) || ! $post_id ) {
				return $allcaps;
			}

			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== MPCRBM_Function::get_cpt() ) {
				return $allcaps;
			}

			$branches = self::get_current_user_branches();
			$home     = (string) get_post_meta( $post_id, 'mpcrbm_home_branch',    true );
			$current  = (string) get_post_meta( $post_id, 'mpcrbm_current_branch', true );

			if ( in_array( $home, $branches, true ) || in_array( $current, $branches, true ) ) {
				// Car belongs to their branch → grant every primitive cap WordPress resolved.
				// This covers edit_others_posts, edit_published_posts, delete_others_posts, etc.
				foreach ( $caps as $cap ) {
					$allcaps[ $cap ] = true;
				}
			} else {
				// Not their car → explicitly deny every primitive cap resolved for this check.
				foreach ( $caps as $cap ) {
					$allcaps[ $cap ] = false;
				}
			}

			return $allcaps;
		}

		// ── Taxonomy capability safety net ───────────────────────────────────
		//
		// edit-tags.php checks current_user_can( $tax->cap->manage_terms ) before
		// current_screen fires — so if the role DB entry is stale and manage_categories
		// was not persisted yet, this filter ensures the check passes for branch managers
		// accessing their own mpcrbm_locations terms.

		public function dynamic_taxonomy_caps( array $allcaps, array $caps, array $args ): array {
			// Only act when the request involves mpcrbm_locations taxonomy management
			$is_tax_page = (
				isset( $_REQUEST['taxonomy'] ) &&
				sanitize_key( wp_unslash( $_REQUEST['taxonomy'] ) ) === 'mpcrbm_locations'
			);
			if ( ! $is_tax_page ) {
				return $allcaps;
			}

			$tax_caps = [ 'manage_categories', 'manage_terms', 'edit_terms', 'delete_terms', 'assign_terms' ];
			foreach ( $caps as $cap ) {
				if ( in_array( $cap, $tax_caps, true ) ) {
					$allcaps[ $cap ] = true;
				}
			}
			return $allcaps;
		}

		// ── Prevent editing another branch's taxonomy term via direct URL ─────

		public function block_foreign_branch_edit(): void {
			$screen = get_current_screen();
			if ( ! $screen || $screen->base !== 'term' || empty( $_GET['tag_id'] ) ) {
				return;
			}
			if ( $screen->taxonomy !== 'mpcrbm_locations' ) {
				return;
			}

			$term_id = (int) $_GET['tag_id'];
			$term    = get_term( $term_id, 'mpcrbm_locations' );
			if ( ! $term || is_wp_error( $term ) ) {
				return;
			}

			if ( ! in_array( $term->slug, self::get_current_user_branches(), true ) ) {
				// Redirect to their own branch list instead of showing a foreign branch
				wp_redirect( admin_url( 'edit-tags.php?taxonomy=mpcrbm_locations&post_type=' . MPCRBM_Function::get_cpt() ) );
				exit;
			}
		}

		// ═══════════════════════════════════════════════════════════════════
		// ORDER SUPPORT
		// ═══════════════════════════════════════════════════════════════════

		/** Save pickup branch slug to order postmeta at checkout for fast querying. */
		public function tag_order_with_branch( WC_Order $order ): void {
			foreach ( $order->get_items() as $item ) {
				$branch = $item->get_meta( '_mpcrbm_start_place' );
				if ( $branch ) {
					$order->update_meta_data( '_mpcrbm_order_branch', sanitize_text_field( $branch ) );
					break;
				}
			}
		}

		/** Return order IDs for the current BM's branches. Returns [0] when none found. */
		private function get_branch_order_ids(): array {
			$branches = self::get_current_user_branches();
			if ( empty( $branches ) ) {
				return [ 0 ];
			}

			// Primary: orders tagged with _mpcrbm_order_branch at checkout.
			// wc_get_orders() is HPOS-compatible and avoids direct DB queries.
			$tagged = wc_get_orders( [
				'limit'      => -1,
				'return'     => 'ids',
				'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => '_mpcrbm_order_branch',
						'value'   => $branches,
						'compare' => 'IN',
					],
				],
			] );

			// Fallback: older orders placed before the branch-tag hook existed.
			// WooCommerce has no API for filtering by order item meta, so we fetch
			// only untagged orders and check items in PHP. This set shrinks over time
			// as new orders are tagged at checkout via tag_order_with_branch().
			$untagged = wc_get_orders( [
				'limit'      => -1,
				'return'     => 'ids',
				'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => '_mpcrbm_order_branch',
						'compare' => 'NOT EXISTS',
					],
				],
			] );

			$via_item = [];
			foreach ( $untagged as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					continue;
				}
				foreach ( $order->get_items() as $item ) {
					$start_place = (string) $item->get_meta( '_mpcrbm_start_place' );
					if ( $start_place && in_array( $start_place, $branches, true ) ) {
						$via_item[] = (int) $order_id;
						break;
					}
				}
			}

			$merged = array_unique( array_merge( (array) $tagged, $via_item ) );
			return ! empty( $merged ) ? array_map( 'intval', $merged ) : [ 0 ];
		}

		// ═══════════════════════════════════════════════════════════════════
		// SUPER ADMIN — BRANCH MANAGERS PAGE
		// ═══════════════════════════════════════════════════════════════════

		public function render_branch_managers_page(): void {
			$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
			$bm_id  = isset( $_GET['bm_id'] )  ? (int) $_GET['bm_id']           : 0;

			$this->render_admin_notices();

			switch ( $action ) {
				case 'add':
				case 'edit':
					$this->render_bm_form( $bm_id );
					break;
				default:
					$this->render_bm_list();
			}
		}

		private function render_admin_notices(): void {
			if ( isset( $_GET['saved'] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>'
					. esc_html__( 'Branch manager saved successfully.', 'car-rental-manager' )
					. '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>'
					. esc_html__( 'Branch manager deleted.', 'car-rental-manager' )
					. '</p></div>';
			}
			if ( isset( $_GET['error'] ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>'
					. esc_html( urldecode( wp_unslash( $_GET['error'] ) ) )
					. '</p></div>';
			}
		}

		// ── List all branch managers ──────────────────────────────────────────

		private function render_bm_list(): void {
			$managers = get_users( [ 'role' => self::ROLE_SLUG, 'orderby' => 'display_name' ] );
			$branches = get_terms( [ 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ] );
			$bmap     = [];
			if ( ! is_wp_error( $branches ) ) {
				foreach ( $branches as $b ) {
					$bmap[ $b->slug ] = $b->name;
				}
			}
			$page_url = admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=mpcrbm_branch_managers' );
			$add_url  = add_query_arg( 'action', 'add', $page_url );
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Branch Managers', 'car-rental-manager' ); ?></h1>
				<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action">
					+ <?php esc_html_e( 'Add Branch Manager', 'car-rental-manager' ); ?>
				</a>
				<hr class="wp-header-end">

				<?php if ( empty( $managers ) ) : ?>
					<div class="notice notice-info inline" style="margin-top:16px">
						<p><?php esc_html_e( 'No branch managers yet. Click "Add Branch Manager" to create one.', 'car-rental-manager' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped mpcrbm-bm-table" style="margin-top:16px">
						<thead>
							<tr>
								<th style="width:220px"><?php esc_html_e( 'Name', 'car-rental-manager' ); ?></th>
								<th style="width:140px"><?php esc_html_e( 'Username', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Email', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Assigned Branches', 'car-rental-manager' ); ?></th>
								<th style="width:100px"><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></th>
								<th style="width:160px"><?php esc_html_e( 'Actions', 'car-rental-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $managers as $mgr ) :
								$slugs  = self::get_user_branches( $mgr->ID );
								$names  = array_map( fn( $s ) => $bmap[ $s ] ?? $s, $slugs );
								$status = (string) ( get_user_meta( $mgr->ID, self::STATUS_META, true ) ?: 'active' );
								$edit_url = add_query_arg( [ 'action' => 'edit', 'bm_id' => $mgr->ID ], $page_url );
								$del_url  = wp_nonce_url(
									admin_url( 'admin-post.php?action=mpcrbm_delete_bm&bm_id=' . $mgr->ID ),
									'mpcrbm_delete_bm'
								);
							?>
							<tr>
								<td>
									<?php echo get_avatar( $mgr->ID, 32, '', '', [ 'class' => 'mpcrbm-bm-avatar' ] ); ?>
									<strong style="vertical-align:middle;margin-left:8px"><?php echo esc_html( $mgr->display_name ); ?></strong>
								</td>
								<td><?php echo esc_html( $mgr->user_login ); ?></td>
								<td><?php echo esc_html( $mgr->user_email ); ?></td>
								<td>
									<?php if ( ! empty( $names ) ) : ?>
										<?php foreach ( $names as $n ) : ?>
											<span class="mpcrbm-branch-tag"><?php echo esc_html( $n ); ?></span>
										<?php endforeach; ?>
									<?php else : ?>
										<span style="color:#d63638"><?php esc_html_e( 'None assigned', 'car-rental-manager' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<span class="mpcrbm-status-badge mpcrbm-status-<?php echo esc_attr( $status ); ?>">
										<?php echo esc_html( ucfirst( $status ) ); ?>
									</span>
								</td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'car-rental-manager' ); ?>
									</a>
									<a href="<?php echo esc_url( $del_url ); ?>"
									   class="button button-small mpcrbm-btn-delete"
									   onclick="return confirm('<?php esc_attr_e( 'Delete this branch manager? This cannot be undone.', 'car-rental-manager' ); ?>')">
										<?php esc_html_e( 'Delete', 'car-rental-manager' ); ?>
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<?php
		}

		// ── Add / Edit form ───────────────────────────────────────────────────

		private function render_bm_form( int $bm_id = 0 ): void {
			$user     = $bm_id ? get_user_by( 'id', $bm_id ) : null;
			$assigned = $user ? self::get_user_branches( $bm_id ) : [];
			$status   = $user ? (string) ( get_user_meta( $bm_id, self::STATUS_META, true ) ?: 'active' ) : 'active';
			$branches = get_terms( [ 'taxonomy' => 'mpcrbm_locations', 'hide_empty' => false ] );
			$page_url = admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=mpcrbm_branch_managers' );
			?>
			<div class="wrap">
				<h1>
					<?php echo $bm_id
						? esc_html__( 'Edit Branch Manager', 'car-rental-manager' )
						: esc_html__( 'Add Branch Manager', 'car-rental-manager' ); ?>
				</h1>
				<hr class="wp-header-end">

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mpcrbm-bm-form" style="max-width:780px">
					<?php wp_nonce_field( 'mpcrbm_save_bm_' . $bm_id, 'mpcrbm_bm_nonce' ); ?>
					<input type="hidden" name="action" value="mpcrbm_save_bm">
					<input type="hidden" name="bm_id"  value="<?php echo esc_attr( $bm_id ); ?>">

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Full Name', 'car-rental-manager' ); ?></th>
							<td>
								<div style="display:flex;gap:10px;flex-wrap:wrap">
									<input type="text" name="bm_first_name"
									       value="<?php echo $user ? esc_attr( $user->first_name ) : ''; ?>"
									       placeholder="<?php esc_attr_e( 'First Name', 'car-rental-manager' ); ?>"
									       class="regular-text">
									<input type="text" name="bm_last_name"
									       value="<?php echo $user ? esc_attr( $user->last_name ) : ''; ?>"
									       placeholder="<?php esc_attr_e( 'Last Name', 'car-rental-manager' ); ?>"
									       class="regular-text">
								</div>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bm_email"><?php esc_html_e( 'Email', 'car-rental-manager' ); ?></label></th>
							<td>
								<input type="email" id="bm_email" name="bm_email"
								       value="<?php echo $user ? esc_attr( $user->user_email ) : ''; ?>"
								       class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bm_username"><?php esc_html_e( 'Username', 'car-rental-manager' ); ?></label></th>
							<td>
								<input type="text" id="bm_username" name="bm_username"
								       value="<?php echo $user ? esc_attr( $user->user_login ) : ''; ?>"
								       class="regular-text"
								       <?php echo $bm_id ? 'readonly' : 'required'; ?>>
								<?php if ( $bm_id ) : ?>
									<p class="description"><?php esc_html_e( 'Username cannot be changed after creation.', 'car-rental-manager' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bm_password"><?php esc_html_e( 'Password', 'car-rental-manager' ); ?></label></th>
							<td>
								<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
									<input type="password" id="bm_password" name="bm_password"
									       class="regular-text" autocomplete="new-password"
									       <?php echo $bm_id ? '' : 'required'; ?>>
									<button type="button" class="button mpcrbm-gen-pwd">
										<?php esc_html_e( 'Generate', 'car-rental-manager' ); ?>
									</button>
								</div>
								<?php if ( $bm_id ) : ?>
									<p class="description"><?php esc_html_e( 'Leave blank to keep the current password.', 'car-rental-manager' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label><?php esc_html_e( 'Assigned Branches', 'car-rental-manager' ); ?></label></th>
							<td>
								<?php if ( is_wp_error( $branches ) || empty( $branches ) ) : ?>
									<p class="description">
										<?php esc_html_e( 'No branches found.', 'car-rental-manager' ); ?>
										<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=mpcrbm_locations&post_type=' . MPCRBM_Function::get_cpt() ) ); ?>">
											<?php esc_html_e( 'Create a branch first →', 'car-rental-manager' ); ?>
										</a>
									</p>
								<?php else : ?>
									<div class="mpcrbm-branch-checkboxes">
										<?php foreach ( $branches as $b ) : ?>
											<label class="mpcrbm-branch-chk-label">
												<input type="checkbox" name="bm_branches[]"
												       value="<?php echo esc_attr( $b->slug ); ?>"
												       <?php checked( in_array( $b->slug, $assigned, true ) ); ?>>
												<span><?php echo esc_html( $b->name ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
									<p class="description"><?php esc_html_e( 'The manager can only see and manage cars in the checked branches.', 'car-rental-manager' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bm_status"><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></label></th>
							<td>
								<select name="bm_status" id="bm_status">
									<option value="active"   <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'car-rental-manager' ); ?></option>
									<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'car-rental-manager' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Inactive managers cannot log in.', 'car-rental-manager' ); ?></p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary">
							<?php echo $bm_id
								? esc_html__( 'Update Branch Manager', 'car-rental-manager' )
								: esc_html__( 'Create Branch Manager', 'car-rental-manager' ); ?>
						</button>
						<a href="<?php echo esc_url( $page_url ); ?>" class="button" style="margin-left:8px">
							<?php esc_html_e( 'Cancel', 'car-rental-manager' ); ?>
						</a>
					</p>
				</form>
			</div>
			<script>
			document.querySelector('.mpcrbm-gen-pwd')?.addEventListener('click', function () {
				var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
				var pwd   = Array.from({ length: 14 }, function () {
					return chars[ Math.floor( Math.random() * chars.length ) ];
				}).join('');
				var el   = document.getElementById('bm_password');
				el.value = pwd;
				el.type  = 'text';
			});
			</script>
			<?php
		}

		// ═══════════════════════════════════════════════════════════════════
		// SAVE / DELETE HANDLERS  (admin-post.php)
		// ═══════════════════════════════════════════════════════════════════

		public function handle_save_bm(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'car-rental-manager' ) );
			}

			$bm_id    = isset( $_POST['bm_id'] ) ? (int) $_POST['bm_id'] : 0;
			$page_url = admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=mpcrbm_branch_managers' );
			$err_url  = $bm_id
				? add_query_arg( [ 'action' => 'edit', 'bm_id' => $bm_id ], $page_url )
				: add_query_arg( 'action', 'add', $page_url );

			if ( ! isset( $_POST['mpcrbm_bm_nonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['mpcrbm_bm_nonce'] ) ),
					'mpcrbm_save_bm_' . $bm_id
				) ) {
				wp_die( esc_html__( 'Security check failed.', 'car-rental-manager' ) );
			}

			$first    = sanitize_text_field( wp_unslash( $_POST['bm_first_name'] ?? '' ) );
			$last     = sanitize_text_field( wp_unslash( $_POST['bm_last_name']  ?? '' ) );
			$email    = sanitize_email( wp_unslash( $_POST['bm_email']    ?? '' ) );
			$username = sanitize_user( wp_unslash(  $_POST['bm_username'] ?? '' ) );
			$password = wp_unslash( $_POST['bm_password'] ?? '' );
			$branches = isset( $_POST['bm_branches'] )
				? array_map( 'sanitize_text_field', (array) $_POST['bm_branches'] )
				: [];
			$status   = in_array( $_POST['bm_status'] ?? '', [ 'active', 'inactive' ], true )
				? sanitize_text_field( $_POST['bm_status'] )
				: 'active';

			if ( $bm_id ) {
				// ── Update existing user ──
				$data = [ 'ID' => $bm_id ];
				if ( $email )    { $data['user_email'] = $email; }
				if ( $password ) { $data['user_pass']  = $password; }
				if ( $first )    { $data['first_name'] = $first; }
				if ( $last )     { $data['last_name']  = $last; }
				$display = trim( "$first $last" );
				if ( $display )  { $data['display_name'] = $display; }

				$result = wp_update_user( $data );
				if ( is_wp_error( $result ) ) {
					wp_redirect( add_query_arg( 'error', rawurlencode( $result->get_error_message() ), $err_url ) );
					exit;
				}
			} else {
				// ── Create new user ──
				if ( ! $username || ! $email || ! $password ) {
					wp_redirect( add_query_arg( 'error', rawurlencode(
						__( 'Username, email, and password are all required.', 'car-rental-manager' )
					), $err_url ) );
					exit;
				}
				$uid = wp_create_user( $username, $password, $email );
				if ( is_wp_error( $uid ) ) {
					wp_redirect( add_query_arg( 'error', rawurlencode( $uid->get_error_message() ), $err_url ) );
					exit;
				}
				$bm_id   = $uid;
				$display = trim( "$first $last" ) ?: $username;
				wp_update_user( [
					'ID'           => $bm_id,
					'first_name'   => $first,
					'last_name'    => $last,
					'display_name' => $display,
				] );
			}

			// Assign Branch Manager role (never demote an administrator)
			$target = new WP_User( $bm_id );
			if ( ! in_array( 'administrator', (array) $target->roles, true ) ) {
				$target->set_role( self::ROLE_SLUG );
			}

			update_user_meta( $bm_id, self::BRANCHES_META, $branches );
			update_user_meta( $bm_id, self::STATUS_META,   $status );

			wp_redirect( add_query_arg( 'saved', '1', $page_url ) );
			exit;
		}

		public function handle_delete_bm(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'car-rental-manager' ) );
			}
			if ( ! isset( $_GET['_wpnonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
					'mpcrbm_delete_bm'
				) ) {
				wp_die( esc_html__( 'Security check failed.', 'car-rental-manager' ) );
			}

			$bm_id    = isset( $_GET['bm_id'] ) ? (int) $_GET['bm_id'] : 0;
			$page_url = admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=mpcrbm_branch_managers' );

			if ( ! $bm_id ) {
				wp_redirect( add_query_arg( 'error', rawurlencode( 'Invalid user ID.' ), $page_url ) );
				exit;
			}

			$user = get_user_by( 'id', $bm_id );
			if ( ! $user || ! in_array( self::ROLE_SLUG, (array) $user->roles, true ) ) {
				wp_redirect( add_query_arg( 'error', rawurlencode( 'User is not a branch manager.' ), $page_url ) );
				exit;
			}

			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $bm_id );

			wp_redirect( add_query_arg( 'deleted', '1', $page_url ) );
			exit;
		}

		// ═══════════════════════════════════════════════════════════════════
		// BRANCH MANAGER — MY BRANCH PAGE
		// ═══════════════════════════════════════════════════════════════════

		public function render_my_branch_page(): void {
			$branches = self::get_current_user_branches();
			$cpt      = MPCRBM_Function::get_cpt();

			if ( empty( $branches ) ) {
				?>
				<div class="wrap">
					<div class="notice notice-warning inline" style="margin-top:16px"><p>
						<?php esc_html_e( 'No branch has been assigned to your account yet. Please contact the administrator.', 'car-rental-manager' ); ?>
					</p></div>
				</div>
				<?php
				return;
			}
			?>
			<div class="wrap mpcrbm-my-branch-wrap">
				<h1><?php esc_html_e( 'My Branch Dashboard', 'car-rental-manager' ); ?></h1>
				<hr class="wp-header-end">

				<?php foreach ( $branches as $slug ) :
					$term = get_term_by( 'slug', $slug, 'mpcrbm_locations' );
					if ( ! $term ) { continue; }
					$meta     = MPCRBM_Branch_Manager::get_branch_meta( $slug );
					$cars     = MPCRBM_Branch_Manager::get_cars_at_branch( $slug );
					$edit_url = admin_url( sprintf(
						'edit-tags.php?action=edit&taxonomy=mpcrbm_locations&tag_id=%d&post_type=%s',
						$term->term_id, $cpt
					) );
				?>
				<div class="mpcrbm-branch-section">
					<div class="mpcrbm-branch-section-header">
						<h2><?php echo esc_html( $term->name ); ?></h2>
						<div class="mpcrbm-branch-section-actions">
							<a href="<?php echo esc_url( $edit_url ); ?>" class="button">
								<?php esc_html_e( 'Edit Branch Details', 'car-rental-manager' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $cpt ) ); ?>" class="button button-primary">
								+ <?php esc_html_e( 'Add Car', 'car-rental-manager' ); ?>
							</a>
						</div>
					</div>

					<!-- Stats row -->
					<div class="mpcrbm-bm-stats">
						<div class="mpcrbm-bm-stat-box">
							<div class="mpcrbm-bm-stat-num"><?php echo count( $cars ); ?></div>
							<div class="mpcrbm-bm-stat-lbl"><?php esc_html_e( 'Total Cars', 'car-rental-manager' ); ?></div>
						</div>
						<?php if ( ! empty( $meta['address'] ) ) : ?>
						<div class="mpcrbm-bm-stat-box">
							<span class="dashicons dashicons-location" style="font-size:1.5em;color:#2271b1"></span>
							<div class="mpcrbm-bm-stat-lbl"><?php echo esc_html( $meta['address'] ); ?></div>
						</div>
						<?php endif; ?>
						<?php if ( ! empty( $meta['phone'] ) ) : ?>
						<div class="mpcrbm-bm-stat-box">
							<span class="dashicons dashicons-phone" style="font-size:1.5em;color:#2271b1"></span>
							<div class="mpcrbm-bm-stat-lbl"><?php echo esc_html( $meta['phone'] ); ?></div>
						</div>
						<?php endif; ?>
						<?php if ( ! empty( $meta['one_way_fee'] ) && (float) $meta['one_way_fee'] > 0 ) : ?>
						<div class="mpcrbm-bm-stat-box">
							<div class="mpcrbm-bm-stat-num"><?php echo wp_kses_post( wc_price( (float) $meta['one_way_fee'] ) ); ?></div>
							<div class="mpcrbm-bm-stat-lbl"><?php esc_html_e( 'One-Way Fee', 'car-rental-manager' ); ?></div>
						</div>
						<?php endif; ?>
					</div>

					<!-- Cars table -->
					<?php if ( empty( $cars ) ) : ?>
						<p>
							<?php esc_html_e( 'No cars at this branch yet.', 'car-rental-manager' ); ?>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $cpt ) ); ?>">
								<?php esc_html_e( 'Add the first car →', 'car-rental-manager' ); ?>
							</a>
						</p>
					<?php else : ?>
						<table class="widefat striped mpcrbm-bm-cars-table">
							<thead>
								<tr>
									<th style="width:48px"></th>
									<th><?php esc_html_e( 'Car', 'car-rental-manager' ); ?></th>
									<th><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></th>
									<th><?php esc_html_e( 'Home Branch', 'car-rental-manager' ); ?></th>
									<th><?php esc_html_e( 'Current Branch', 'car-rental-manager' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'car-rental-manager' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $cars as $car ) :
									$home    = (string) get_post_meta( $car->ID, 'mpcrbm_home_branch',    true );
									$current = (string) get_post_meta( $car->ID, 'mpcrbm_current_branch', true );
									$pstatus = get_post_status( $car->ID );
								?>
								<tr>
									<td><?php echo get_the_post_thumbnail( $car->ID, [ 40, 40 ], [ 'style' => 'border-radius:4px;object-fit:cover;width:40px;height:40px;display:block' ] ); ?></td>
									<td><strong><?php echo esc_html( get_the_title( $car->ID ) ); ?></strong></td>
									<td>
										<span class="mpcrbm-car-status mpcrbm-status-<?php echo esc_attr( $pstatus ); ?>">
											<?php echo esc_html( ucfirst( $pstatus ) ); ?>
										</span>
									</td>
									<td><?php echo esc_html( $home ?: '—' ); ?></td>
									<td>
										<?php if ( $current && $current !== $home ) : ?>
											<strong><?php echo esc_html( $current ); ?></strong>
										<?php else : ?>
											<?php echo esc_html( $current ?: '—' ); ?>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( (string) get_edit_post_link( $car->ID ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit', 'car-rental-manager' ); ?>
										</a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div><!-- .mpcrbm-branch-section -->
				<?php endforeach; ?>
			</div>
			<?php
		}

		// ═══════════════════════════════════════════════════════════════════
		// BRANCH MANAGER — BOOKINGS PAGE
		// ═══════════════════════════════════════════════════════════════════

		public function render_bookings_page(): void {
			if ( ! function_exists( 'wc_get_order' ) ) {
				echo '<div class="wrap"><div class="notice notice-warning inline" style="margin-top:16px"><p>';
				esc_html_e( 'WooCommerce is required to view bookings.', 'car-rental-manager' );
				echo '</p></div></div>';
				return;
			}

			$all_ids    = $this->get_branch_order_ids();
			$has_orders = ! empty( $all_ids ) && $all_ids !== [ 0 ];

			$per_page   = 20;
			$current_pg = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
			$total      = $has_orders ? count( $all_ids ) : 0;
			$pages      = $total ? (int) ceil( $total / $per_page ) : 0;
			$page_ids   = $has_orders ? array_slice( $all_ids, ( $current_pg - 1 ) * $per_page, $per_page ) : [];
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Branch Bookings', 'car-rental-manager' ); ?></h1>
				<hr class="wp-header-end">

				<?php if ( ! $has_orders ) : ?>
					<div class="notice notice-info inline" style="margin-top:16px">
						<p><?php esc_html_e( 'No bookings found for your assigned branch(es).', 'car-rental-manager' ); ?></p>
					</div>
				<?php else : ?>
					<p class="description" style="margin:12px 0">
						<?php printf(
							esc_html( _n( '%d booking found.', '%d bookings found.', $total, 'car-rental-manager' ) ),
							(int) $total
						); ?>
					</p>
					<table class="widefat striped mpcrbm-bookings-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Order #', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Customer', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Date', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Status', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Total', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Car', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Pickup Location', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Drop-off Location', 'car-rental-manager' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'car-rental-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $page_ids as $oid ) :
								$order = wc_get_order( $oid );
								if ( ! $order ) { continue; }
								$items = $order->get_items();
								$item  = ! empty( $items ) ? reset( $items ) : null;
								$car_id = $item ? (int) $item->get_meta( '_mpcrbm_id' ) : 0;

								// Pickup location
								$pickup_slug = (string) $order->get_meta( '_mpcrbm_order_branch' );
								if ( ! $pickup_slug && $item ) {
									$pickup_slug = (string) $item->get_meta( '_mpcrbm_start_place' );
								}
								$pickup_term  = $pickup_slug ? get_term_by( 'slug', $pickup_slug, 'mpcrbm_locations' ) : null;
								$pickup_label = $pickup_term ? $pickup_term->name : ( $pickup_slug ?: '—' );

								// Drop-off location
								$dropoff_slug  = $item ? (string) $item->get_meta( '_mpcrbm_end_place' ) : '';
								$dropoff_term  = $dropoff_slug ? get_term_by( 'slug', $dropoff_slug, 'mpcrbm_locations' ) : null;
								$dropoff_label = $dropoff_term ? $dropoff_term->name : ( $dropoff_slug ?: '—' );
								$is_same       = ( $dropoff_slug === '' || $dropoff_slug === $pickup_slug );
							?>
							<tr>
								<td><strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong></td>
								<td>
									<?php echo esc_html( $order->get_formatted_billing_full_name() ?: __( 'Guest', 'car-rental-manager' ) ); ?>
									<br><small style="color:#888"><?php echo esc_html( $order->get_billing_email() ); ?></small>
								</td>
								<td>
									<?php
									$dt = $order->get_date_created();
									echo $dt ? esc_html( $dt->date_i18n( get_option( 'date_format' ) ) ) : '—';
									?>
								</td>
								<td>
									<span class="mpcrbm-order-status mpcrbm-wc-status-<?php echo esc_attr( $order->get_status() ); ?>">
										<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
									</span>
								</td>
								<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
								<td><?php echo $car_id ? esc_html( get_the_title( $car_id ) ) : '—'; ?></td>
								<td>
									<span class="mpcrbm-location-tag mpcrbm-location-pickup">
										<?php echo esc_html( $pickup_label ); ?>
									</span>
								</td>
								<td>
									<?php if ( $is_same ) : ?>
										<span class="mpcrbm-location-same"><?php esc_html_e( 'Same as pickup', 'car-rental-manager' ); ?></span>
									<?php else : ?>
										<span class="mpcrbm-location-tag mpcrbm-location-dropoff">
											<?php echo esc_html( $dropoff_label ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( (string) get_edit_post_link( $oid ) ); ?>" class="button button-small">
										<?php esc_html_e( 'View', 'car-rental-manager' ); ?>
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php if ( $pages > 1 ) : ?>
					<div class="tablenav bottom" style="margin-top:12px">
						<?php
						echo paginate_links( [
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $current_pg,
							'total'     => $pages,
							'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'car-rental-manager' ),
							'next_text' => esc_html__( 'Next', 'car-rental-manager' ) . ' &raquo;',
						] );
						?>
					</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php
		}

		// ═══════════════════════════════════════════════════════════════════
		// STYLES
		// ═══════════════════════════════════════════════════════════════════

		public function enqueue_styles( string $hook ): void {
			$is_bm_page = strpos( $hook, 'mpcrbm_branch_managers' ) !== false
			           || strpos( $hook, 'mpcrbm_my_branch' )       !== false
			           || strpos( $hook, 'mpcrbm_bm_bookings' )     !== false;

			if ( ! $is_bm_page ) {
				return;
			}

			echo '<style>
			/* ── Stats row ─────────────────────────── */
			.mpcrbm-bm-stats {
				display:flex; gap:14px; flex-wrap:wrap; margin:14px 0 22px;
			}
			.mpcrbm-bm-stat-box {
				background:#fff; border:1px solid #dcdcde; border-radius:8px;
				padding:14px 20px; min-width:110px;
				display:flex; flex-direction:column; align-items:center;
				gap:4px; text-align:center; box-shadow:0 1px 2px rgba(0,0,0,.05);
			}
			.mpcrbm-bm-stat-num { font-size:1.75em; font-weight:700; color:#2271b1; line-height:1.2; }
			.mpcrbm-bm-stat-lbl { font-size:.8em; color:#555; max-width:140px; }

			/* ── Branch section card ──────────────── */
			.mpcrbm-branch-section {
				background:#fff; border:1px solid #dcdcde; border-radius:8px;
				padding:20px 24px; margin-bottom:22px;
			}
			.mpcrbm-branch-section-header {
				display:flex; align-items:center; justify-content:space-between;
				flex-wrap:wrap; gap:12px; margin-bottom:14px;
			}
			.mpcrbm-branch-section-header h2 { margin:0; }
			.mpcrbm-branch-section-actions   { display:flex; gap:8px; }

			/* ── Branch Managers list table ───────── */
			.mpcrbm-bm-table td  { vertical-align:middle; }
			.mpcrbm-bm-avatar    { border-radius:50%; vertical-align:middle; }
			.mpcrbm-branch-tag {
				display:inline-block; background:#e5f0fb; color:#2271b1;
				border-radius:4px; padding:2px 8px; font-size:.82em; margin:2px 2px 2px 0;
			}
			.mpcrbm-status-badge { padding:3px 10px; border-radius:3px; font-size:.82em; font-weight:600; }
			.mpcrbm-status-active   { background:#d1fae5; color:#065f46; }
			.mpcrbm-status-inactive { background:#fee2e2; color:#991b1b; }
			.mpcrbm-btn-delete { color:#d63638 !important; border-color:#d63638 !important; }
			.mpcrbm-btn-delete:hover { background:#d63638 !important; color:#fff !important; }

			/* ── Add/Edit form ────────────────────── */
			.mpcrbm-bm-form .form-table th { width:190px; }
			.mpcrbm-branch-checkboxes {
				display:flex; flex-wrap:wrap; gap:8px 24px; margin-bottom:6px;
			}
			.mpcrbm-branch-chk-label {
				display:flex; align-items:center; gap:6px; cursor:pointer; padding:4px 0;
			}

			/* ── Car table ────────────────────────── */
			.mpcrbm-bm-cars-table td { vertical-align:middle; padding:8px 10px; }
			.mpcrbm-car-status        { font-weight:600; }
			.mpcrbm-status-publish    { color:#008a20; }
			.mpcrbm-status-draft      { color:#646970; }
			.mpcrbm-status-pending    { color:#b45309; }

			/* ── Bookings table ───────────────────── */
			.mpcrbm-bookings-table td  { vertical-align:middle; padding:8px 10px; }
			.mpcrbm-order-status       { padding:2px 8px; border-radius:3px; font-size:.82em; }
			.mpcrbm-wc-status-completed  { background:#d1fae5; color:#065f46; }
			.mpcrbm-wc-status-processing { background:#fef3c7; color:#92400e; }
			.mpcrbm-wc-status-pending    { background:#e0e7ff; color:#3730a3; }
			.mpcrbm-wc-status-on-hold    { background:#fef3c7; color:#92400e; }
			.mpcrbm-wc-status-cancelled  { background:#fee2e2; color:#991b1b; }
			.mpcrbm-wc-status-refunded   { background:#f3f4f6; color:#374151; }
			.mpcrbm-location-tag {
				display:inline-flex; align-items:center; gap:4px;
				padding:2px 8px; border-radius:4px; font-size:.82em; font-weight:500;
			}
			.mpcrbm-location-pickup  { background:#e0f2fe; color:#075985; }
			.mpcrbm-location-dropoff { background:#fef9c3; color:#713f12; }
			.mpcrbm-location-same    { color:#888; font-size:.82em; font-style:italic; }
			</style>';
		}
	}

	new MPCRBM_User_Branch_Manager();
}
