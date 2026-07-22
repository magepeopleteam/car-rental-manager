<?php
/**
 * MPCRBM WooCommerce Installer
 *
 * Handles the WooCommerce dependency check, a blocking popup on every admin
 * page when WooCommerce is missing, and a CHUNKED, AJAX-based download +
 * install + activation.
 *
 * Why chunked: on memory/time constrained hosts a single
 * Plugin_Upgrader::install() (download + unzip + activate in one request)
 * crashes or times out for a payload the size of WooCommerce. Instead the ZIP
 * is fetched in small HTTP Range chunks across many small AJAX requests, then
 * extracted locally, then activated.
 *
 * @package CarRentalManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'MPCRBM_Woo_Installer' ) ) {

	class MPCRBM_Woo_Installer {

		/**
		 * ZIP download URL for WooCommerce.
		 */
		private $zip_url = 'https://downloads.wordpress.org/plugin/woocommerce.zip';

		/**
		 * Expected plugin file path relative to the plugins directory.
		 */
		private $plugin_file = 'woocommerce/woocommerce.php';

		/**
		 * Bytes downloaded per AJAX request (1 MB).
		 */
		private $chunk_size = 1048576;

		/**
		 * Transient key holding the in-progress download state.
		 */
		private $state_key = 'mpcrbm_dl_woocommerce';

		/**
		 * Constructor – hooks into WordPress.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'admin_footer', array( $this, 'render_popup' ) );
			add_action( 'wp_ajax_mpcrbm_woo_download_chunk', array( $this, 'ajax_download_chunk' ) );
			add_action( 'wp_ajax_mpcrbm_woo_install', array( $this, 'ajax_install' ) );
			add_action( 'wp_ajax_mpcrbm_woo_activate', array( $this, 'ajax_activate' ) );
		}

		/**
		 * Whether the WooCommerce plugin folder/file already exists on disk.
		 *
		 * @return bool
		 */
		private function is_woo_installed() {
			return file_exists( WP_PLUGIN_DIR . '/' . $this->plugin_file );
		}

		/**
		 * Whether WooCommerce is active.
		 *
		 * @return bool
		 */
		private function is_woo_active() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return is_plugin_active( $this->plugin_file );
		}

		/**
		 * Absolute path to (and lazily created) the temp download directory.
		 *
		 * @return string
		 */
		private function tmp_dir() {
			$uploads = wp_upload_dir();
			$dir     = trailingslashit( $uploads['basedir'] ) . 'mpcrbm-installer';
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			return $dir;
		}

		/**
		 * Absolute path to the WooCommerce temp ZIP file.
		 *
		 * @return string
		 */
		private function tmp_file() {
			return $this->tmp_dir() . '/woocommerce.zip';
		}

		/**
		 * Runs on admin_init. Redirect to the car list right after activation
		 * when WooCommerce is already active, otherwise let the popup handle it.
		 */
		public function handle_activation_redirect() {
			if ( ! get_transient( 'mpcrbm_plugin_activated' ) ) {
				return;
			}

			if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
				delete_transient( 'mpcrbm_plugin_activated' );
				return;
			}

			delete_transient( 'mpcrbm_plugin_activated' );

			if ( $this->is_woo_active() ) {
				wp_safe_redirect( admin_url( 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_car_rental' ) );
				exit;
			}
		}

		/**
		 * Show the popup whenever WooCommerce is not active.
		 *
		 * @return bool
		 */
		private function should_show_popup() {
			return ! $this->is_woo_active();
		}

		/**
		 * Enqueue CSS & JS only when the popup is needed.
		 */
		public function enqueue_assets() {
			if ( ! $this->should_show_popup() ) {
				return;
			}

			wp_enqueue_style(
				'mpcrbm-woo-installer',
				MPCRBM_PLUGIN_URL . 'assets/admin/mpcrbm_woo_installer.css',
				array(),
				MPCRBM_PLUGIN_VERSION
			);

			wp_enqueue_script(
				'mpcrbm-woo-installer',
				MPCRBM_PLUGIN_URL . 'assets/admin/mpcrbm_woo_installer.js',
				array( 'jquery' ),
				MPCRBM_PLUGIN_VERSION,
				true
			);

			wp_localize_script( 'mpcrbm-woo-installer', 'mpcrbm_woo_installer', array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'mpcrbm_woo_installer' ),
				'redirect_url'  => admin_url( 'edit.php?post_type=mpcrbm_rent&page=mpcrbm_car_rental' ),
				'woo_installed' => $this->is_woo_installed() ? 'yes' : 'no',
				'i18n'          => array(
					'downloading'    => __( 'Downloading WooCommerce...', 'car-rental-manager' ),
					'installing'     => __( 'Installing WooCommerce...', 'car-rental-manager' ),
					'activating'     => __( 'Activating WooCommerce...', 'car-rental-manager' ),
					'success'        => __( 'WooCommerce activated successfully!', 'car-rental-manager' ),
					'redirecting'    => __( 'Redirecting...', 'car-rental-manager' ),
					'error'          => __( 'Something went wrong. Please try again.', 'car-rental-manager' ),
					'install_error'  => __( 'Installation failed. Please install WooCommerce manually.', 'car-rental-manager' ),
					'activate_error' => __( 'Activation failed. Please activate WooCommerce manually.', 'car-rental-manager' ),
				),
			) );
		}

		/**
		 * Render the popup markup in the admin footer.
		 */
		public function render_popup() {
			if ( ! $this->should_show_popup() ) {
				return;
			}

			$is_installed = $this->is_woo_installed();
			$btn_text     = $is_installed
				? __( 'Activate WooCommerce', 'car-rental-manager' )
				: __( 'Install & Activate WooCommerce', 'car-rental-manager' );
			?>
			<!-- MPCRBM WooCommerce Installer Popup Overlay -->
			<div id="mpcrbm-woo-overlay" class="mpcrbm-woo-overlay">
				<div class="mpcrbm-woo-popup">

					<div class="mpcrbm-woo-header">
						<div class="mpcrbm-woo-header-icon">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
								<path d="M5 11l1.5-5h11L19 11M5 11h14M5 11l-1 6h16l-1-6M8 17v2M16 17v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</div>
						<span class="mpcrbm-woo-header-text"><?php esc_html_e( 'Car Rental Manager', 'car-rental-manager' ); ?></span>
					</div>

					<div class="mpcrbm-woo-icon-wrapper">
						<div class="mpcrbm-woo-icon">
							<svg width="40" height="40" viewBox="0 0 24 24" fill="none">
								<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
								<path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
							</svg>
						</div>
					</div>

					<div class="mpcrbm-woo-content">
						<h2 class="mpcrbm-woo-title"><?php esc_html_e( 'WooCommerce Required', 'car-rental-manager' ); ?></h2>
						<p class="mpcrbm-woo-desc">
							<?php esc_html_e( 'Car Rental Manager requires WooCommerce to handle bookings, pricing, and payments. Please install and activate WooCommerce to continue using this plugin.', 'car-rental-manager' ); ?>
						</p>
					</div>

					<div class="mpcrbm-woo-features">
						<div class="mpcrbm-woo-feature">
							<span class="mpcrbm-woo-feature-icon">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6 2.7 8.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<span><?php esc_html_e( 'Vehicle booking & payments', 'car-rental-manager' ); ?></span>
						</div>
						<div class="mpcrbm-woo-feature">
							<span class="mpcrbm-woo-feature-icon">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6 2.7 8.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<span><?php esc_html_e( 'Order management', 'car-rental-manager' ); ?></span>
						</div>
						<div class="mpcrbm-woo-feature">
							<span class="mpcrbm-woo-feature-icon">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M13.3 4.3L6 11.6 2.7 8.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							</span>
							<span><?php esc_html_e( 'Customer registration', 'car-rental-manager' ); ?></span>
						</div>
					</div>

					<div id="mpcrbm-woo-progress" class="mpcrbm-woo-progress" style="display:none;">
						<div class="mpcrbm-woo-progress-bar">
							<div id="mpcrbm-woo-progress-fill" class="mpcrbm-woo-progress-fill"></div>
						</div>
						<p id="mpcrbm-woo-status-text" class="mpcrbm-woo-status-text"></p>
					</div>

					<div class="mpcrbm-woo-actions">
						<button type="button" id="mpcrbm-woo-install-btn" class="mpcrbm-woo-btn mpcrbm-woo-btn-primary">
							<span class="mpcrbm-woo-btn-icon">
								<svg width="18" height="18" viewBox="0 0 20 20" fill="none">
									<path d="M10 3v10m0 0l-4-4m4 4l4-4M3 17h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							<span class="mpcrbm-woo-btn-text"><?php echo esc_html( $btn_text ); ?></span>
						</button>
						<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ); ?>" class="mpcrbm-woo-btn mpcrbm-woo-btn-secondary">
							<?php esc_html_e( 'Install Manually', 'car-rental-manager' ); ?>
						</a>
					</div>

					<p class="mpcrbm-woo-footer-note">
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="vertical-align: -2px; flex-shrink: 0;">
							<path d="M7 1a6 6 0 100 12A6 6 0 007 1zm0 8.5a.75.75 0 110-1.5.75.75 0 010 1.5zM7.75 6.25a.75.75 0 01-1.5 0V4a.75.75 0 011.5 0v2.25z" fill="currentColor"/>
						</svg>
						<?php esc_html_e( 'WooCommerce is free, open-source, and trusted by millions of stores worldwide.', 'car-rental-manager' ); ?>
					</p>
				</div>
			</div>
			<?php
		}

		/**
		 * AJAX: download the next chunk of the WooCommerce ZIP via an HTTP
		 * Range request and append it to the temp file.
		 */
		public function ajax_download_chunk() {
			check_ajax_referer( 'mpcrbm_woo_installer', 'nonce' );

			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to install plugins.', 'car-rental-manager' ) ) );
			}

			$tmp_file = $this->tmp_file();
			$state    = get_transient( $this->state_key );

			// Fresh start: clear any stale partial file.
			if ( ! is_array( $state ) || empty( $state['started'] ) ) {
				if ( file_exists( $tmp_file ) ) {
					@unlink( $tmp_file );
				}
				$state = array( 'offset' => 0, 'total' => 0, 'started' => 1 );
			}

			$offset = (int) $state['offset'];
			$end    = $offset + $this->chunk_size - 1;

			$response = wp_remote_get( $this->zip_url, array(
				'timeout'     => 30,
				'redirection' => 5,
				'headers'     => array( 'Range' => 'bytes=' . $offset . '-' . $end ),
			) );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			// Server ignored Range and returned the whole file in one shot.
			if ( 200 === $code ) {
				$written = file_put_contents( $tmp_file, $body );
				if ( false === $written ) {
					wp_send_json_error( array( 'message' => __( 'Unable to write the download file.', 'car-rental-manager' ) ) );
				}
				set_transient( $this->state_key, array( 'offset' => $written, 'total' => $written, 'started' => 1 ), HOUR_IN_SECONDS );
				wp_send_json_success( array( 'done' => true, 'percent' => 100, 'downloaded' => $written, 'total' => $written ) );
			}

			if ( 206 !== $code ) {
				wp_send_json_error( array( 'message' => sprintf( __( 'Unexpected download response (%d).', 'car-rental-manager' ), $code ) ) );
			}

			// Determine total size from the Content-Range header once.
			$total = (int) $state['total'];
			if ( ! $total ) {
				$content_range = wp_remote_retrieve_header( $response, 'content-range' );
				if ( $content_range && preg_match( '#/(\d+)\s*$#', $content_range, $m ) ) {
					$total = (int) $m[1];
				}
			}

			$bytes = strlen( $body );
			if ( $bytes > 0 ) {
				$ok = file_put_contents( $tmp_file, $body, FILE_APPEND );
				if ( false === $ok ) {
					wp_send_json_error( array( 'message' => __( 'Unable to write the download file.', 'car-rental-manager' ) ) );
				}
			}

			$new_offset = $offset + $bytes;

			// Decide completion: prefer the known total, fall back to EOF signals.
			if ( $total > 0 && $new_offset >= $total ) {
				$done = true;
			} elseif ( 0 === $bytes ) {
				$done = true;
			} elseif ( $bytes < $this->chunk_size ) {
				$done = true;
			} else {
				$done = false;
			}

			set_transient( $this->state_key, array( 'offset' => $new_offset, 'total' => $total, 'started' => 1 ), HOUR_IN_SECONDS );

			$percent = $total > 0 ? min( 100, (int) round( $new_offset / $total * 100 ) ) : ( $done ? 100 : 50 );

			wp_send_json_success( array(
				'done'       => $done,
				'percent'    => $percent,
				'downloaded' => $new_offset,
				'total'      => $total,
			) );
		}

		/**
		 * AJAX: extract the downloaded ZIP into the plugins directory.
		 */
		public function ajax_install() {
			check_ajax_referer( 'mpcrbm_woo_installer', 'nonce' );

			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to install plugins.', 'car-rental-manager' ) ) );
			}

			$tmp_file = $this->tmp_file();
			if ( ! file_exists( $tmp_file ) || filesize( $tmp_file ) < 1 ) {
				wp_send_json_error( array( 'message' => __( 'Download file missing. Please retry.', 'car-rental-manager' ) ) );
			}

			$state = get_transient( $this->state_key );
			if ( is_array( $state ) && ! empty( $state['total'] ) && filesize( $tmp_file ) < (int) $state['total'] ) {
				wp_send_json_error( array( 'message' => __( 'Download incomplete. Please retry.', 'car-rental-manager' ) ) );
			}

			include_once ABSPATH . 'wp-admin/includes/file.php';
			include_once ABSPATH . 'wp-admin/includes/misc.php';

			global $wp_filesystem;
			if ( ! WP_Filesystem() ) {
				wp_send_json_error( array( 'message' => __( 'Could not access the filesystem.', 'car-rental-manager' ) ) );
			}

			$result = unzip_file( $tmp_file, WP_PLUGIN_DIR );

			@unlink( $tmp_file );
			delete_transient( $this->state_key );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( array( 'message' => __( 'WooCommerce installed successfully.', 'car-rental-manager' ) ) );
		}

		/**
		 * AJAX: activate WooCommerce.
		 */
		public function ajax_activate() {
			check_ajax_referer( 'mpcrbm_woo_installer', 'nonce' );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to activate plugins.', 'car-rental-manager' ) ) );
			}

			// WooCommerce boots while it is being activated (plugin_sandbox_scrape)
			// and queries a few of its own tables before WC_Install has created them,
			// and loads its textdomain early. Those are expected one-time messages —
			// silence DB errors and the doing_it_wrong notice for the duration of the
			// activation so the debug log stays clean. WC creates the tables right
			// after, via its activation hook.
			global $wpdb;
			$suppress = $wpdb->suppress_errors( true );
			add_filter( 'doing_it_wrong_trigger_error', '__return_false', 99 );

			$result = activate_plugin( $this->plugin_file );

			remove_filter( 'doing_it_wrong_trigger_error', '__return_false', 99 );
			$wpdb->suppress_errors( $suppress );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			// WooCommerce sets this transient on activation and, on the next admin
			// page load, redirects to its own setup wizard — which would override our
			// redirect to the car list. Remove it so our redirect wins.
			delete_transient( '_wc_activation_redirect' );

			wp_send_json_success( array( 'message' => __( 'WooCommerce activated successfully!', 'car-rental-manager' ) ) );
		}
	}

	new MPCRBM_Woo_Installer();
}
