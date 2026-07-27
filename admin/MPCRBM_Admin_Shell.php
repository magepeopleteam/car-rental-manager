<?php
	/*
   * @Author 		MagePeople Team
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.

	if ( ! class_exists( 'MPCRBM_Admin_Shell' ) ) {
		class MPCRBM_Admin_Shell {

			const SCREEN_IDS = [
				'mpcrbm_rent_page_mpcrbm_car_rental',
				'mpcrbm_rent_page_mpcrbm_settings_page',
				'mpcrbm_rent_page_mpcrbm_status_page',
				'mpcrbm_rent_page_mpcrbm_guideline_page',
				'mpcrbm_rent_page_mpcrbm_branch_managers',
				'mpcrbm_rent_page_mpcrbm_my_branch',
				'mpcrbm_rent_page_mpcrbm_bm_bookings',
				'mpcrbm_rent_page_mpcrbm_locations_manager',
				'mpcrbm_rent_page_mpcrbm_ex_services_manager',
			];

			public function __construct() {
				add_filter( 'admin_body_class', [ $this, 'add_body_class' ] );
				add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_shell_assets' ] );
				add_action( 'wp_ajax_mpcrbm_set_menu_layout_style', [ $this, 'ajax_set_menu_layout_style' ] );
				add_action( 'in_admin_header', [ $this, 'render_edit_screen_chrome' ] );
				add_filter( 'wp_editor_settings', [ $this, 'simplify_content_editor_toolbar' ], 10, 2 );
				add_action( 'admin_head', [ $this, 'print_metabox_reveal_style' ] );
			}

			// Hides the per-car metabox panel from FIRST PAINT (inline <style> in
			// <head>, so it applies immediately with no network round-trip) until
			// mpcrbm-shell.js finishes relocating the title/content/featured-image
			// into their final card positions. Without this, the browser paints the
			// original narrow layout (native tabsContent width, before the "General
			// Info" tab even has its Basic Information card) and then visibly snaps
			// to the wider, restructured layout once that JS runs a moment later —
			// per explicit request to stop "hiding with CSS" a transient state that
			// still visibly flashes; this instead prevents that transient state from
			// ever being painted at all. mpcrbm-shell.js removes this inline style
			// once its synchronous relocation block finishes (see the bottom of that
			// file). visibility (not display) is used so the panel still reserves
			// its layout space and TinyMCE can still size the editor correctly while
			// hidden.
			public function print_metabox_reveal_style() {
				if ( ! self::is_metabox_screen() ) {
					return;
				}
				echo '<style id="mpcrbm-metabox-reveal">#mpcrbm_meta_box_panel{visibility:hidden;}</style>';
			}

			// Reduces the native content editor (the "Vehicle Description" field inside the
			// Basic Information card) down to a minimal toolbar per explicit request — no
			// paragraph/format dropdown, no kitchen-sink second row, no Add Media button.
			// wp_editor() merges the 'tinymce' array over its own defaults (doesn't replace
			// them), so plugins/valid_elements/etc. from WP core are left untouched — only
			// toolbar1/toolbar2/height are overridden here.
			// IMPORTANT: quicktags is deliberately left enabled (WP's default) rather than
			// disabled — it's the only fallback if TinyMCE ever fails to initialize (slow
			// script load, a conflicting plugin, a disabled-visual-editor user preference,
			// etc.), and turning it off left editors with a content box that accepted no
			// input at all when that happened. The Visual/Text tab switcher is hidden with
			// CSS instead (see mpcrbm-shell.css), which gets the same clean look without
			// removing the fallback.
			public function simplify_content_editor_toolbar( $settings, $editor_id ) {
				if ( 'content' !== $editor_id || ! self::is_metabox_screen() ) {
					return $settings;
				}

				$settings['media_buttons'] = false;
				$settings['tinymce']       = is_array( $settings['tinymce'] ?? null ) ? $settings['tinymce'] : [];
				$settings['tinymce']['toolbar1'] = 'bold,italic,bullist,link,alignleft,aligncenter,alignright,alignjustify';
				$settings['tinymce']['toolbar2'] = '';
				$settings['tinymce']['height']   = 200;
				$settings['tinymce']['wp_autoresize_on'] = false;

				return $settings;
			}

			// SCREEN_IDS plus whatever add-on plugins (e.g. car-rental-manager-pro's
			// Google Reviews / Calendar Settings / Analytics pages) register via this
			// filter — same "apply_filters, add-on merges its own entries in" idiom as
			// MPCRBM_Settings_Global's "mpcrbm_settings_sec_reg"/"mpcrbm_settings_sec_fields".
			public static function get_screen_ids(): array {
				return apply_filters( 'mpcrbm_shell_screen_ids', self::SCREEN_IDS );
			}

			// Detects whether the current wp-admin screen is one of this plugin's own dashboard-style pages.
			public static function is_plugin_screen(): bool {
				if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
					return false;
				}
				$screen = get_current_screen();

				return $screen && in_array( $screen->id, self::get_screen_ids(), true );
			}

			// The native Add/Edit Car screen (post.php/post-new.php for our CPT). WordPress
			// renders this screen itself (we don't control the callback), so unlike the 7
			// dashboard-style pages above it can't be wrapped with render_shell_open/close —
			// instead the sidebar/topbar are injected via in_admin_header as a fixed overlay
			// (see render_edit_screen_chrome() and the "post-type-mpcrbm_rent" CSS rules).
			public static function is_metabox_screen(): bool {
				if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
					return false;
				}
				$screen = get_current_screen();

				return $screen && $screen->base === 'post' && $screen->post_type === MPCRBM_Function::get_cpt();
			}

			public function add_body_class( $classes ) {
				if ( self::is_plugin_screen() ) {
					// mpcrbm-admin: generic "hide WP's own chrome" marker, safe on any of our screens.
					// mpcrbm-admin-shell: only the 7 dashboard-style pages that get the full flex
					// shell + notice/screen-meta suppression (the edit screen needs those visible).
					$classes .= ' mpcrbm-admin mpcrbm-admin-shell';
				} elseif ( self::is_metabox_screen() ) {
					$classes .= ' mpcrbm-admin';
				}

				return $classes;
			}

			// Injects a fixed-position sidebar + topbar around WordPress's native post-edit
			// screen for our CPT. Reuses the same markup/classes as render_shell_open()'s
			// sidebar so all existing hover/active styling applies unchanged; only the
			// outer positioning (fixed vs the flex layout's sticky) differs, via the
			// ".mpcrbm-shell-fixed" modifier class and its CSS in mpcrbm-shell.css.
			public function render_edit_screen_chrome(): void {
				if ( ! self::is_metabox_screen() ) {
					return;
				}

				$menu_items   = self::get_menu_items();
				$post         = get_post();
				$post_title   = $post ? get_the_title( $post ) : '';
				$post_status  = $post ? get_post_status( $post ) : '';
				$status_obj   = $post_status ? get_post_status_object( $post_status ) : null;
				$status_label = $status_obj ? $status_obj->label : '';
				$car_list_url = admin_url( 'edit.php?post_type=' . MPCRBM_Function::get_cpt() . '&page=mpcrbm_car_rental' );
				?>
				<div class="mpcrbm-shell-sidebar mpcrbm-shell-fixed">
					<div class="mpcrbm-shell-sidebar-top">
						<div class="mpcrbm-shell-logo">
							<span class="mpcrbm-shell-logo-icon"><i class="fas fa-car-side"></i></span>
							<span class="mpcrbm-shell-logo-text"><?php echo esc_html( MPCRBM_Function::get_name() ); ?> <?php esc_html_e( 'Rental', 'car-rental-manager' ); ?></span>
						</div>
					</div>
					<ul class="mpcrbm-shell-menu">
						<?php foreach ( $menu_items as $item ) :
							// While editing a car, highlight "Car Rental" — there's no separate "Cars" item.
							$is_active = ( 'mpcrbm_car_rental' === $item['slug'] );
							?>
							<li class="<?php echo $is_active ? 'is-active has-children' : ''; ?>">
								<a href="<?php echo esc_url( $item['link'] ); ?>">
									<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
									<span><?php echo esc_html( $item['label'] ); ?></span>
									<?php if ( 'mpcrbm_car_rental' === $item['slug'] ) : ?>
										<i class="fas fa-chevron-down mpcrbm-shell-menu-arrow"></i>
									<?php endif; ?>
								</a>
								<?php if ( $is_active ) : ?>
									<ul class="mpcrbm-shell-submenu">
										<?php foreach ( self::get_car_rental_taxonomy_tabs() as $tab ) : ?>
											<li>
												<a href="<?php echo esc_url( add_query_arg( 'mpcrbm_tab', $tab['target'], $item['link'] ) ); ?>">
													<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>
													<span><?php echo esc_html( $tab['label'] ); ?></span>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url( admin_url() ); ?>" class="mpcrbm-shell-back-to-wp">
						<i class="fab fa-wordpress"></i>
						<span><?php esc_html_e( 'Back to WordPress', 'car-rental-manager' ); ?></span>
					</a>
				</div>
				<div class="mpcrbm-shell-topbar mpcrbm-shell-fixed mpcrbm-edit-topbar">
					<a href="<?php echo esc_url( $car_list_url ); ?>" class="mpcrbm-edit-topbar-back">
						<i class="fas fa-arrow-left"></i>
						<span><?php esc_html_e( 'Back to Cars', 'car-rental-manager' ); ?></span>
					</a>
					<div class="mpcrbm-edit-topbar-title" id="mpcrbm-edit-topbar-title"><?php echo esc_html( $post_title ); ?></div>
					<div class="mpcrbm-edit-topbar-actions">
						<?php if ( $status_label ) : ?>
							<span class="mpcrbm-edit-topbar-status mpcrbm-edit-topbar-status-<?php echo esc_attr( $post_status ); ?>" id="mpcrbm-edit-topbar-status"><?php echo esc_html( $status_label ); ?></span>
						<?php endif; ?>
						<button type="button" class="mpcrbm-btn mpcrbm-btn-outline mpcrbm-btn-sm" id="mpcrbm-edit-topbar-preview">
							<i class="far fa-eye"></i> <?php esc_html_e( 'Preview', 'car-rental-manager' ); ?>
						</button>
						<?php
						// Split "Update" button + "Save Draft" dropdown, matching
						// tour-booking-manager's .ttbm-split-publish component. Buttons
						// stay type="button" (not type="submit") since this whole top
						// bar is injected outside <form id="post"> — see the proxy-click
						// note in mpcrbm-shell.js. Always rendered as the full split
						// button — the dropdown's "Save Draft" item also doubles as the
						// way to change an already-published car back to a draft (WP
						// itself has no single native control for that; see the click
						// handler in mpcrbm-shell.js for how it's done).
						?>
						<div class="mpcrbm-split-publish" id="mpcrbm-edit-topbar-publish">
							<button type="button" class="mpcrbm-split-publish__main" id="mpcrbm-edit-topbar-update">
								<?php esc_html_e( 'Update', 'car-rental-manager' ); ?>
							</button>
							<button type="button" class="mpcrbm-split-publish__toggle" id="mpcrbm-edit-topbar-publish-toggle" aria-expanded="false" aria-haspopup="true" aria-label="<?php esc_attr_e( 'Toggle publish options', 'car-rental-manager' ); ?>">
								<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
							</button>
							<div class="mpcrbm-split-publish__menu" role="menu" id="mpcrbm-edit-topbar-publish-menu">
								<button type="button" class="mpcrbm-split-publish__item mpcrbm-split-publish__draft" id="mpcrbm-edit-topbar-save-draft" role="menuitem">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
									<?php esc_html_e( 'Save Draft', 'car-rental-manager' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
				<?php
			}

			public function enqueue_shell_assets() {
				if ( ! self::is_plugin_screen() && ! self::is_metabox_screen() ) {
					return;
				}

				$css_path = MPCRBM_PLUGIN_DIR . '/assets/admin/mpcrbm-shell.css';
				$js_path  = MPCRBM_PLUGIN_DIR . '/assets/admin/mpcrbm-shell.js';

				wp_enqueue_style( 'mpcrbm-shell', MPCRBM_PLUGIN_URL . 'assets/admin/mpcrbm-shell.css', [ 'mpcrbm_admin' ], file_exists( $css_path ) ? filemtime( $css_path ) : MPCRBM_PLUGIN_VERSION );
				wp_enqueue_script( 'mpcrbm-shell', MPCRBM_PLUGIN_URL . 'assets/admin/mpcrbm-shell.js', [ 'jquery' ], file_exists( $js_path ) ? filemtime( $js_path ) : MPCRBM_PLUGIN_VERSION, true );

				wp_localize_script( 'mpcrbm-shell', 'mpcrbmShell', [
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'mpcrbm_shell_nonce' ),
				] );
			}

			public function ajax_set_menu_layout_style() {
				check_ajax_referer( 'mpcrbm_shell_nonce', 'nonce' );
				$style = ( isset( $_POST['style'] ) && in_array( $_POST['style'], [ 'full', 'compact' ], true ) ) ? sanitize_key( $_POST['style'] ) : 'full';
				update_user_meta( get_current_user_id(), 'mpcrbm_admin_menu_style', $style );
				wp_send_json_success( [ 'style' => $style ] );
			}

			public static function get_menu_layout_style(): string {
				$style = get_user_meta( get_current_user_id(), 'mpcrbm_admin_menu_style', true );

				return in_array( $style, [ 'full', 'compact' ], true ) ? $style : 'full';
			}

			// Sidebar nav items. Kept in one place so every wrapped page shows identical, capability-aware navigation.
			public static function get_menu_items(): array {
				$cpt      = MPCRBM_Function::get_cpt();
				$base_url = admin_url( 'edit.php?post_type=' . $cpt );
				$items    = [];

				$items[] = [
					'slug'  => 'mpcrbm_car_rental',
					'label' => esc_html__( 'Car Rental', 'car-rental-manager' ),
					'icon'  => 'fas fa-car-side',
					'link'  => $base_url . '&page=mpcrbm_car_rental',
				];

				if ( current_user_can( 'manage_options' ) ) {
					// Both render inside this shell (MPCRBM_Locations_Manager /
					// MPCRBM_Extra_Services_Manager) rather than linking out to their
					// native WP screens, so they get the same chrome as Branch
					// Managers / Global Settings below.
					$items[] = [
						'slug'  => 'mpcrbm_locations_manager',
						'label' => esc_html__( 'Locations', 'car-rental-manager' ),
						'icon'  => 'fas fa-map-marker-alt',
						'link'  => $base_url . '&page=mpcrbm_locations_manager',
					];
					$items[] = [
						'slug'  => 'mpcrbm_ex_services_manager',
						'label' => esc_html__( 'Extra Services', 'car-rental-manager' ),
						'icon'  => 'fas fa-concierge-bell',
						'link'  => $base_url . '&page=mpcrbm_ex_services_manager',
					];
					$items[] = [
						'slug'  => 'mpcrbm_branch_managers',
						'label' => esc_html__( 'Branch Managers', 'car-rental-manager' ),
						'icon'  => 'fas fa-user-tie',
						'link'  => $base_url . '&page=mpcrbm_branch_managers',
					];
				}

				if ( class_exists( 'MPCRBM_User_Branch_Manager' ) && MPCRBM_User_Branch_Manager::is_branch_manager() ) {
					$items[] = [
						'slug'  => 'mpcrbm_my_branch',
						'label' => esc_html__( 'My Branch', 'car-rental-manager' ),
						'icon'  => 'fas fa-store',
						'link'  => $base_url . '&page=mpcrbm_my_branch',
					];
					$items[] = [
						'slug'  => 'mpcrbm_bm_bookings',
						'label' => esc_html__( 'Bookings', 'car-rental-manager' ),
						'icon'  => 'fas fa-calendar-check',
						'link'  => $base_url . '&page=mpcrbm_bm_bookings',
					];
				}

				// Add-on plugins (car-rental-manager-pro's Google Reviews / Calendar
				// Settings / Analytics, etc.) inject their own ['slug','label','icon','link']
				// entries here — same idiom as the "mpcrbm_settings_sec_reg" filter above.
				// Applied here (before Global Settings/Status/Guideline below), not at
				// the very end, so those add-on items land right after Branch
				// Managers/Bookings and before this plugin's own settings/status/docs
				// items, per explicit request.
				$items = apply_filters( 'mpcrbm_shell_menu_items', $items );

				if ( current_user_can( 'manage_options' ) ) {
					$items[] = [
						'slug'  => 'mpcrbm_settings_page',
						'label' => esc_html__( 'Global Settings', 'car-rental-manager' ),
						'icon'  => 'fas fa-sliders-h',
						'link'  => $base_url . '&page=mpcrbm_settings_page',
					];
					$items[] = [
						'slug'  => 'mpcrbm_status_page',
						'label' => esc_html__( 'Status', 'car-rental-manager' ),
						'icon'  => 'fas fa-heartbeat',
						'link'  => $base_url . '&page=mpcrbm_status_page',
					];
					$items[] = [
						'slug'  => 'mpcrbm_guideline_page',
						'label' => esc_html__( 'Guideline', 'car-rental-manager' ),
						'icon'  => 'fas fa-book',
						'link'  => $base_url . '&page=mpcrbm_guideline_page',
					];
				}

				return $items;
			}

			// The Car Rental dashboard's own tabs (Car List, Car Type, ...) — kept in one
			// place so both the dashboard page's sidebar sub-menu (JS-driven data-target
			// buttons, see MPCRBM_Taxonomies.php) and the Add/Edit Car screen's sidebar
			// sub-menu (plain links, see render_edit_screen_chrome()) stay in sync.
			public static function get_car_rental_taxonomy_tabs(): array {
				return [
					[ 'target' => 'mpcrbm_car_list', 'icon' => 'mi mi-cars', 'label' => esc_html__( 'Car List', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_car_type', 'icon' => 'mi mi-tachometer-fast', 'label' => esc_html__( 'Car Type', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_fuel_type', 'icon' => 'mi mi-gas-pump-alt', 'label' => esc_html__( 'Fuel Type', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_seating_capacity', 'icon' => 'mi mi-person-seat', 'label' => esc_html__( 'Seating Capacity', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_car_brand', 'icon' => 'mi mi-bonus', 'label' => esc_html__( 'Car Brand', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_make_year', 'icon' => 'mi mi-time-quarter-to', 'label' => esc_html__( 'Make Year', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_car_feature', 'icon' => 'mi mi-list-timeline', 'label' => esc_html__( 'Car Feature', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_manage_faq', 'icon' => 'mi mi-messages-question', 'label' => esc_html__( 'Manage Faq', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_manage_term_condition', 'icon' => 'mi mi-blog-text', 'label' => esc_html__( 'Term & Condition', 'car-rental-manager' ) ],
					[ 'target' => 'mpcrbm_branch_manager', 'icon' => 'mi mi-map-location-track', 'label' => esc_html__( 'Branch List', 'car-rental-manager' ) ],
				];
			}

			// Opens the shell: sidebar + top bar + page header/tabs + content wrapper.
			// $tabs (optional): array of ['label'=>string,'icon'=>string,'link'=>url] OR ['label','icon','target'=>'#css-selector','active'=>bool] for JS-driven in-page tabs.
			// $sidebar_submenu_html (optional): raw <li>...</li> HTML, nested under whichever
			// sidebar item's slug matches the current page — used by pages whose own in-page
			// tabs are shown as a sidebar sub-menu instead of page-header tabs (e.g. Car Rental).
			public static function render_shell_open( string $page_title, ?array $tabs = null, ?string $sidebar_submenu_html = null ) {
				$menu_items   = self::get_menu_items();
				$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
				$menu_style   = self::get_menu_layout_style();
				?>
				<div class="mpcrbm-shell side-menu-<?php echo esc_attr( $menu_style ); ?>">
					<div class="mpcrbm-shell-content-and-menu">
						<div class="mpcrbm-shell-sidebar">
							<div class="mpcrbm-shell-sidebar-top">
								<div class="mpcrbm-shell-logo">
									<span class="mpcrbm-shell-logo-icon"><i class="fas fa-car-side"></i></span>
									<span class="mpcrbm-shell-logo-text"><?php echo esc_html( MPCRBM_Function::get_name() ); ?> <?php esc_html_e( 'Rental', 'car-rental-manager' ); ?></span>
								</div>
								<a href="#" class="mpcrbm-shell-fold-trigger" title="<?php esc_attr_e( 'Collapse menu', 'car-rental-manager' ); ?>">
									<i class="fas fa-bars"></i>
								</a>
							</div>
							<ul class="mpcrbm-shell-menu">
								<?php foreach ( $menu_items as $item ) :
									$is_active     = ( $current_page === $item['slug'] );
									$is_car_rental = ( 'mpcrbm_car_rental' === $item['slug'] );
									// The Car List/Car Type/.../Branch Manager sub-menu now stays
									// visible under "Car Rental" on every shell page (Locations,
									// Extra Services, Branch Managers, Global Settings, Status,
									// Guideline, ...), not just while actually on the Car Rental
									// page — same always-there navigation the Add/Edit Car screen's
									// render_edit_screen_chrome() already gives this same item.
									$has_submenu = $is_car_rental || ( $is_active && ! empty( $sidebar_submenu_html ) );
									$li_class    = trim( ( $is_active ? 'is-active' : '' ) . ( $has_submenu ? ' has-children' : '' ) );
									?>
									<li class="<?php echo esc_attr( $li_class ); ?>">
										<a href="<?php echo esc_url( $item['link'] ); ?>">
											<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
											<span><?php echo esc_html( $item['label'] ); ?></span>
											<?php if ( $is_car_rental ) : ?>
												<i class="fas fa-chevron-down mpcrbm-shell-menu-arrow"></i>
											<?php endif; ?>
										</a>
										<?php if ( $has_submenu ) : ?>
											<ul class="mpcrbm-shell-submenu">
												<?php if ( $is_active && ! empty( $sidebar_submenu_html ) ) : ?>
													<?php echo $sidebar_submenu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-built, already-escaped markup from the calling page. ?>
												<?php else : ?>
													<?php foreach ( self::get_car_rental_taxonomy_tabs() as $tab ) : ?>
														<li>
															<a href="<?php echo esc_url( add_query_arg( 'mpcrbm_tab', $tab['target'], $item['link'] ) ); ?>">
																<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>
																<span><?php echo esc_html( $tab['label'] ); ?></span>
															</a>
														</li>
													<?php endforeach; ?>
												<?php endif; ?>
											</ul>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<a href="<?php echo esc_url( admin_url() ); ?>" class="mpcrbm-shell-back-to-wp">
								<i class="fab fa-wordpress"></i>
								<span><?php esc_html_e( 'Back to WordPress', 'car-rental-manager' ); ?></span>
							</a>
						</div>
						<div class="mpcrbm-shell-content">
							<div class="mpcrbm-shell-topbar">
								<a href="#" class="mpcrbm-shell-mobile-trigger" title="<?php esc_attr_e( 'Menu', 'car-rental-manager' ); ?>"><i class="fas fa-bars"></i></a>
								<div class="mpcrbm-shell-topbar-spacer"></div>
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener" class="mpcrbm-shell-topbar-link" title="<?php esc_attr_e( 'View Site', 'car-rental-manager' ); ?>">
									<i class="fas fa-external-link-alt"></i>
								</a>
								<div class="mpcrbm-shell-user">
									<div class="mpcrbm-shell-user-avatar" style="background-image:url('<?php echo esc_url( get_avatar_url( get_current_user_id() ) ); ?>')"></div>
								</div>
							</div>
							<div class="mpcrbm-shell-body mpcrbm">
				<?php
			}

			public static function render_shell_close() {
				?>
							</div><!-- .mpcrbm-shell-body -->
						</div><!-- .mpcrbm-shell-content -->
					</div><!-- .mpcrbm-shell-content-and-menu -->
				</div><!-- .mpcrbm-shell -->
				<?php
			}
		}
		new MPCRBM_Admin_Shell();
	}
