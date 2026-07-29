<?php
	/*
   * @Author 		MagePeople Team
   * Copyright: 	mage-people.com
   */
	if ( ! defined( 'ABSPATH' ) ) {
		die;
	} // Cannot access pages directly.
	if ( ! class_exists( 'MPCRBM_Status' ) ) {
		class MPCRBM_Status {
			public function __construct() {
				add_action( 'admin_menu', array( $this, 'status_menu' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			}

			public function status_menu() {
				$cpt = MPCRBM_Function::get_cpt();
				add_submenu_page( 'edit.php?post_type=' . $cpt, esc_html__( 'Status', 'car-rental-manager' ), '<span style="color:yellow">' . esc_html__( 'Status', 'car-rental-manager' ) . '</span>', 'manage_options', 'mpcrbm_status_page', array( $this, 'status_page' ) );
			}

			public function enqueue_assets( $hook ) {
				if ( 'mpcrbm_rent_page_mpcrbm_status_page' !== $hook ) {
					return;
				}

				$css_path = MPCRBM_PLUGIN_DIR . '/assets/admin/mpcrbm-status-page.css';
				wp_enqueue_style(
					'mpcrbm-status-page',
					MPCRBM_PLUGIN_URL . 'assets/admin/mpcrbm-status-page.css',
					array( 'mpcrbm-shell' ),
					file_exists( $css_path ) ? filemtime( $css_path ) : MPCRBM_PLUGIN_VERSION
				);
			}

			public function status_page() {
				global $wpdb;

				$label              = MPCRBM_Function::get_name();
				$wp_version         = get_bloginfo( 'version' );
				$wc_active          = 1 === (int) MPCRBM_Global_Function::check_woocommerce();
				$wc_version         = $wc_active && defined( 'WC_VERSION' ) ? WC_VERSION : '';
				$from_name          = (string) get_option( 'woocommerce_email_from_name', '' );
				$from_email         = (string) get_option( 'woocommerce_email_from_address', '' );
				$active_plugins     = (array) get_option( 'active_plugins', array() );
				$network_plugins    = is_multisite() ? (array) get_site_option( 'active_sitewide_plugins', array() ) : array();
				$theme              = wp_get_theme();
				$server             = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : __( 'Unknown', 'car-rental-manager' );
				$php_memory         = ini_get( 'memory_limit' ) ?: __( 'Unknown', 'car-rental-manager' );
				$php_memory_bytes   = wp_convert_hr_to_bytes( $php_memory );
				$execution_time     = (int) ini_get( 'max_execution_time' );
				$input_vars         = (int) ini_get( 'max_input_vars' );
				$upload_limit       = size_format( wp_max_upload_size() );
				$uploads            = wp_upload_dir( null, false );
				$uploads_writable   = empty( $uploads['error'] ) && wp_is_writable( $uploads['basedir'] );
				$debug_enabled      = defined( 'WP_DEBUG' ) && WP_DEBUG;
				$cron_disabled      = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
				$wp_version_ok      = version_compare( $wp_version, '6.0', '>=' );
				$php_version_ok     = version_compare( PHP_VERSION, '7.4', '>=' );
				$wc_version_ok      = $wc_active && version_compare( $wc_version, '7.0', '>=' );
				$memory_ok          = -1 === $php_memory_bytes || $php_memory_bytes >= 128 * MB_IN_BYTES;
				$execution_time_ok  = 0 === $execution_time || $execution_time >= 60;
				$input_vars_ok      = $input_vars >= 1000;
				$email_name_ok      = '' !== trim( $from_name );
				$email_address_ok   = (bool) is_email( $from_email );
				$required_ext       = array( 'curl', 'dom', 'json', 'mbstring', 'openssl' );
				$missing_extensions = array_values(
					array_filter(
						$required_ext,
						static function ( $extension ) {
							return ! extension_loaded( $extension );
						}
					)
				);
				$plugin_headers     = get_file_data(
					MPCRBM_PLUGIN_DIR . '/car-rental-manager.php',
					array( 'Version' => 'Version' ),
					'plugin'
				);
				$plugin_version     = ! empty( $plugin_headers['Version'] ) ? $plugin_headers['Version'] : MPCRBM_PLUGIN_VERSION;
				$health_checks      = array(
					$wp_version_ok,
					$php_version_ok,
					$wc_version_ok,
					$memory_ok,
					$execution_time_ok,
					$input_vars_ok,
					$uploads_writable,
					empty( $missing_extensions ),
					$email_name_ok && $email_address_ok,
				);
				$passed_checks      = count( array_filter( $health_checks ) );
				$issue_count        = count( $health_checks ) - $passed_checks;
				$recommendations    = array();

				if ( ! $memory_ok ) {
					$recommendations[] = __( 'Increase the PHP memory limit to at least 128 MB for larger inventories and concurrent bookings.', 'car-rental-manager' );
				}
				if ( ! $execution_time_ok ) {
					$recommendations[] = __( 'Increase the PHP maximum execution time to 60 seconds or more for imports and large booking operations.', 'car-rental-manager' );
				}
				if ( ! $input_vars_ok ) {
					$recommendations[] = __( 'Increase max_input_vars to at least 1000 so large vehicle settings forms are not truncated.', 'car-rental-manager' );
				}
				if ( ! $uploads_writable ) {
					$recommendations[] = __( 'Make the WordPress uploads directory writable so vehicle images and generated files can be saved.', 'car-rental-manager' );
				}
				if ( ! $email_name_ok || ! $email_address_ok ) {
					$recommendations[] = __( 'Configure a valid WooCommerce sender name and email address for reliable booking notifications.', 'car-rental-manager' );
				}

				$cards = array(
					array(
						'title' => __( 'WordPress & Site', 'car-rental-manager' ),
						'icon'  => 'fab fa-wordpress',
						'rows'  => array(
							array( __( 'WordPress Version', 'car-rental-manager' ), $wp_version, $wp_version_ok ? 'ok' : 'warning' ),
							array( __( 'Site Language', 'car-rental-manager' ), get_locale(), 'neutral' ),
							array( __( 'Timezone', 'car-rental-manager' ), wp_timezone_string() ?: 'UTC', 'neutral' ),
							array( __( 'Multisite', 'car-rental-manager' ), is_multisite() ? __( 'Enabled', 'car-rental-manager' ) : __( 'Disabled', 'car-rental-manager' ), 'neutral' ),
							array( __( 'Debug Mode', 'car-rental-manager' ), $debug_enabled ? __( 'Enabled', 'car-rental-manager' ) : __( 'Disabled', 'car-rental-manager' ), $debug_enabled ? 'warning' : 'ok' ),
							array( __( 'Uploads Directory', 'car-rental-manager' ), $uploads_writable ? __( 'Writable', 'car-rental-manager' ) : __( 'Not writable', 'car-rental-manager' ), $uploads_writable ? 'ok' : 'error' ),
						),
					),
					array(
						'title' => __( 'PHP & Resource Limits', 'car-rental-manager' ),
						'icon'  => 'fas fa-code',
						'rows'  => array(
							array( __( 'PHP Version', 'car-rental-manager' ), PHP_VERSION, $php_version_ok ? 'ok' : 'error' ),
							array( __( 'PHP Memory Limit', 'car-rental-manager' ), $php_memory, $memory_ok ? 'ok' : 'warning' ),
							array( __( 'WordPress Memory Limit', 'car-rental-manager' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'Default', 'car-rental-manager' ), 'neutral' ),
							array( __( 'Maximum Execution Time', 'car-rental-manager' ), 0 === $execution_time ? __( 'Unlimited', 'car-rental-manager' ) : sprintf( __( '%d seconds', 'car-rental-manager' ), $execution_time ), $execution_time_ok ? 'ok' : 'warning' ),
							array( __( 'Maximum Input Variables', 'car-rental-manager' ), number_format_i18n( $input_vars ), $input_vars_ok ? 'ok' : 'warning' ),
							array( __( 'Maximum Post Size', 'car-rental-manager' ), ini_get( 'post_max_size' ) ?: __( 'Unknown', 'car-rental-manager' ), 'neutral' ),
							array( __( 'Maximum Upload Size', 'car-rental-manager' ), $upload_limit, 'neutral' ),
						),
					),
					array(
						'title' => __( 'Server & Database', 'car-rental-manager' ),
						'icon'  => 'fas fa-server',
						'rows'  => array(
							array( __( 'Operating System', 'car-rental-manager' ), PHP_OS_FAMILY, 'neutral' ),
							array( __( 'Web Server', 'car-rental-manager' ), $server, 'neutral' ),
							array( __( 'PHP SAPI', 'car-rental-manager' ), PHP_SAPI, 'neutral' ),
							array( __( 'Database Version', 'car-rental-manager' ), $wpdb->db_version(), 'neutral' ),
							array( __( 'Database Charset', 'car-rental-manager' ), $wpdb->charset ?: __( 'Default', 'car-rental-manager' ), 'neutral' ),
							array( __( 'HTTPS', 'car-rental-manager' ), is_ssl() ? __( 'Active', 'car-rental-manager' ) : __( 'Not active', 'car-rental-manager' ), is_ssl() ? 'ok' : 'warning' ),
						),
					),
					array(
						'title' => __( 'WooCommerce & Plugin', 'car-rental-manager' ),
						'icon'  => 'fas fa-plug',
						'rows'  => array(
							array( __( 'Car Rental Manager', 'car-rental-manager' ), $plugin_version, 'ok' ),
							array( __( 'WooCommerce', 'car-rental-manager' ), $wc_active ? $wc_version : __( 'Not active', 'car-rental-manager' ), $wc_version_ok ? 'ok' : 'error' ),
							array( __( 'Email Sender Name', 'car-rental-manager' ), $email_name_ok ? $from_name : __( 'Not configured', 'car-rental-manager' ), $email_name_ok ? 'ok' : 'warning' ),
							array( __( 'Email Sender Address', 'car-rental-manager' ), $email_address_ok ? $from_email : __( 'Not configured or invalid', 'car-rental-manager' ), $email_address_ok ? 'ok' : 'warning' ),
							array( __( 'Active Plugins', 'car-rental-manager' ), (string) ( count( $active_plugins ) + count( $network_plugins ) ), 'neutral' ),
							array( __( 'Active Theme', 'car-rental-manager' ), trim( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ), 'neutral' ),
						),
					),
				);

				MPCRBM_Admin_Shell::render_shell_open( esc_html__( 'Status', 'car-rental-manager' ) );
				?>
				<div class="mpcrbm-status-dashboard">
					<?php do_action( 'mpcrbm_status_notice_sec' ); ?>

					<section class="mpcrbm-status-hero">
						<div class="mpcrbm-status-hero__icon" aria-hidden="true"><i class="fas fa-heartbeat"></i></div>
						<div class="mpcrbm-status-hero__copy">
							<span class="mpcrbm-status-eyebrow"><?php esc_html_e( 'Environment health', 'car-rental-manager' ); ?></span>
							<h1>
								<?php
								/* translators: %s: the plugin's configurable "Car" label. */
								echo esc_html( sprintf( __( '%s System Status', 'car-rental-manager' ), $label ) );
								?>
							</h1>
							<p><?php esc_html_e( 'Review the WordPress, WooCommerce, server, PHP, and workload limits used by your rental operation.', 'car-rental-manager' ); ?></p>
						</div>
						<div class="mpcrbm-status-score <?php echo $issue_count ? 'has-issues' : 'is-healthy'; ?>">
							<strong><?php echo esc_html( $passed_checks . '/' . count( $health_checks ) ); ?></strong>
							<span>
								<?php
								if ( $issue_count ) {
									echo esc_html(
										sprintf(
											/* translators: %d: number of environment issues. */
											_n( '%d item needs attention', '%d items need attention', $issue_count, 'car-rental-manager' ),
											$issue_count
										)
									);
								} else {
									esc_html_e( 'All checks passed', 'car-rental-manager' );
								}
								?>
							</span>
						</div>
					</section>

					<div class="mpcrbm-status-quick-stats">
						<div><span><?php esc_html_e( 'PHP', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( PHP_VERSION ); ?></strong></div>
						<div><span><?php esc_html_e( 'WordPress', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $wp_version ); ?></strong></div>
						<div><span><?php esc_html_e( 'WooCommerce', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $wc_active ? $wc_version : __( 'Missing', 'car-rental-manager' ) ); ?></strong></div>
						<div><span><?php esc_html_e( 'PHP Memory', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $php_memory ); ?></strong></div>
						<div><span><?php esc_html_e( 'Upload Limit', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $upload_limit ); ?></strong></div>
					</div>

					<?php if ( ! empty( $missing_extensions ) ) { ?>
						<div class="mpcrbm-status-alert is-error">
							<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
							<div>
								<strong><?php esc_html_e( 'Missing recommended PHP extensions', 'car-rental-manager' ); ?></strong>
								<span><?php echo esc_html( implode( ', ', $missing_extensions ) ); ?></span>
							</div>
						</div>
					<?php } ?>

					<?php if ( ! empty( $recommendations ) ) { ?>
						<div class="mpcrbm-status-alert">
							<i class="fas fa-lightbulb" aria-hidden="true"></i>
							<div>
								<strong><?php esc_html_e( 'Recommended for heavier workloads', 'car-rental-manager' ); ?></strong>
								<ul>
									<?php foreach ( $recommendations as $recommendation ) { ?>
										<li><?php echo esc_html( $recommendation ); ?></li>
									<?php } ?>
								</ul>
							</div>
						</div>
					<?php } ?>

					<div class="mpcrbm-status-grid">
						<?php foreach ( $cards as $card ) { ?>
							<section class="mpcrbm-status-card">
								<header>
									<span class="mpcrbm-status-card__icon" aria-hidden="true"><i class="<?php echo esc_attr( $card['icon'] ); ?>"></i></span>
									<h2><?php echo esc_html( $card['title'] ); ?></h2>
								</header>
								<div class="mpcrbm-status-card__rows">
									<?php foreach ( $card['rows'] as $row ) { ?>
										<div class="mpcrbm-status-row">
											<span><?php echo esc_html( $row[0] ); ?></span>
											<strong class="is-<?php echo esc_attr( $row[2] ); ?>"><?php echo esc_html( $row[1] ); ?></strong>
										</div>
									<?php } ?>
								</div>
							</section>
						<?php } ?>
					</div>

					<section class="mpcrbm-status-card mpcrbm-status-readiness">
						<header>
							<span class="mpcrbm-status-card__icon" aria-hidden="true"><i class="fas fa-tachometer-alt"></i></span>
							<div>
								<h2><?php esc_html_e( 'Workload Readiness', 'car-rental-manager' ); ?></h2>
								<p><?php esc_html_e( 'Configuration that matters most for large fleets, image-heavy listings, imports, and busy booking periods.', 'car-rental-manager' ); ?></p>
							</div>
						</header>
						<div class="mpcrbm-status-readiness__grid">
							<div class="<?php echo $memory_ok ? 'is-ready' : 'needs-attention'; ?>"><i class="fas fa-memory"></i><span><?php esc_html_e( 'Memory', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $memory_ok ? __( 'Ready', 'car-rental-manager' ) : __( 'Increase limit', 'car-rental-manager' ) ); ?></strong></div>
							<div class="<?php echo $execution_time_ok ? 'is-ready' : 'needs-attention'; ?>"><i class="fas fa-stopwatch"></i><span><?php esc_html_e( 'Processing Time', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $execution_time_ok ? __( 'Ready', 'car-rental-manager' ) : __( 'Increase limit', 'car-rental-manager' ) ); ?></strong></div>
							<div class="<?php echo $input_vars_ok ? 'is-ready' : 'needs-attention'; ?>"><i class="fas fa-tasks"></i><span><?php esc_html_e( 'Large Forms', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $input_vars_ok ? __( 'Ready', 'car-rental-manager' ) : __( 'Increase limit', 'car-rental-manager' ) ); ?></strong></div>
							<div class="<?php echo $uploads_writable ? 'is-ready' : 'needs-attention'; ?>"><i class="fas fa-cloud-upload-alt"></i><span><?php esc_html_e( 'File Storage', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( $uploads_writable ? __( 'Ready', 'car-rental-manager' ) : __( 'Check permissions', 'car-rental-manager' ) ); ?></strong></div>
							<div class="<?php echo empty( $missing_extensions ) ? 'is-ready' : 'needs-attention'; ?>"><i class="fas fa-puzzle-piece"></i><span><?php esc_html_e( 'PHP Extensions', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( empty( $missing_extensions ) ? __( 'Ready', 'car-rental-manager' ) : __( 'Missing items', 'car-rental-manager' ) ); ?></strong></div>
							<div class="<?php echo ! $cron_disabled ? 'is-ready' : 'needs-attention'; ?>"><i class="fas fa-history"></i><span><?php esc_html_e( 'Scheduled Tasks', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( ! $cron_disabled ? __( 'WP-Cron enabled', 'car-rental-manager' ) : __( 'WP-Cron disabled', 'car-rental-manager' ) ); ?></strong></div>
							<div class="<?php echo wp_using_ext_object_cache() ? 'is-ready' : 'is-optional'; ?>"><i class="fas fa-bolt"></i><span><?php esc_html_e( 'Object Cache', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( wp_using_ext_object_cache() ? __( 'External cache active', 'car-rental-manager' ) : __( 'Optional for high traffic', 'car-rental-manager' ) ); ?></strong></div>
							<div class="<?php echo is_ssl() ? 'is-ready' : 'needs-attention'; ?>"><i class="fas fa-shield-alt"></i><span><?php esc_html_e( 'Secure Checkout', 'car-rental-manager' ); ?></span><strong><?php echo esc_html( is_ssl() ? __( 'HTTPS active', 'car-rental-manager' ) : __( 'HTTPS inactive', 'car-rental-manager' ) ); ?></strong></div>
						</div>
					</section>

					<?php
					ob_start();
					do_action( 'mpcrbm_status_table_item_sec' );
					$addon_checks = ob_get_clean();
					if ( '' !== trim( $addon_checks ) ) {
						?>
						<section class="mpcrbm-status-card mpcrbm-status-addons">
							<header>
								<span class="mpcrbm-status-card__icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
								<h2><?php esc_html_e( 'Add-on Checks', 'car-rental-manager' ); ?></h2>
							</header>
							<div class="mpcrbm-status-table-wrap">
								<table class="mpcrbm-status-table"><tbody><?php echo $addon_checks; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted add-on callbacks historically print complete table rows through this hook. ?></tbody></table>
							</div>
						</section>
						<?php
					}
					?>
				</div>
				<?php
				MPCRBM_Admin_Shell::render_shell_close();
			}
		}
		new MPCRBM_Status();
	}
