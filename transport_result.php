<?php
/*
Template Name: Transport Result
*/
defined( 'ABSPATH' ) || exit;
// ✅ Start session in functions.php (recommended), not in template
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$content = '';

$search_date= $_SESSION['search_date'] ?? '';

$search_attribute = [ 'form'=>'inline', 'title'=>'no', 'ajax_search' => 'yes', 'progressbar' => 'no' ];
$search_defaults = MPCRBM_Shortcodes::default_attribute();
$params = shortcode_atts( $search_defaults, $search_attribute );
ob_start();
do_action( 'mpcrbm_transport_search', $params, $search_date );
$action_output = ob_get_clean();

$content .= $action_output;

$content .= $_SESSION['custom_content'] ?? '';
$progress_bar = isset($_SESSION['progress_bar']) ? $_SESSION['progress_bar'] : '';
if( $progress_bar === 'no' ){
    $progressbar_class = 'dNone';
}else{
    $progressbar_class = '';
}

// Redirect to homepage if empty
if ( empty( $content ) ) {
    wp_safe_redirect( home_url() ); 
    exit;
}

// Remove content from session after use
unset($_SESSION['custom_content']);
unset($_SESSION['search_date']);


/**
 * --------------------------
 * HEADER AREA
 * --------------------------
 */
if ( wp_is_block_theme() ) {
    if ( function_exists( 'block_header_area' ) ) {
        ob_start();
        block_header_area();
        $header_html = trim( ob_get_clean() );

        if ( $header_html ) {
            wp_head();
            wp_body_open();
            echo '<div class="wp-site-blocks">';
            echo '<header class="wp-block-template-part site-header">';
            // Use wp_kses_post to allow standard post-level HTML tags
            echo wp_kses_post( $header_html );
            echo '</header>';
            echo '</div>';
        } else {
            get_header();
        }
    } else {
        get_header();
    }
} else {
    get_header();
}
?>

    <!-- Pass HTTP referrer to cookie -->
    <script type="text/javascript">
        (function() {
            var httpReferrer = "<?php echo esc_js($_SERVER['HTTP_REFERER'] ?? ''); ?>";
            document.cookie = "httpReferrer=" + httpReferrer + ";path=/";
        })();
    </script>

    <div id="maincontent" class="transport-result-page">
        <div class="mpcrbm mpcrbm_transport_search_area">
            <div class="mpcrbm_tab_next _mT">
                <div class="tabListsNext <?php echo esc_attr($progressbar_class); ?>" id="mpcrbm_progress_bar_holder" >
                    <div data-tabs-target-next="#mpcrbm_pick_up_details" class="tabItemNext active" data-open-text="1" data-close-text=" " data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                        <h4 class="circleIcon" data-class>
                            <span class="mp_zero" data-icon></span>
                            <span class="mp_zero" data-text>1</span>
                        </h4>
                        <h6 class="circleTitle" data-class><?php esc_html_e('Enter Ride Details', 'car-rental-manager'); ?></h6>
                    </div>
                    <div data-tabs-target-next="#mpcrbm_search_result" class="tabItemNext" data-open-text="2" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                        <h4 class="circleIcon" data-class>
                            <span class="mp_zero" data-icon></span>
                            <span class="mp_zero" data-text>2</span>
                        </h4>
                        <h6 class="circleTitle" data-class><?php esc_html_e('Choose a vehicle', 'car-rental-manager'); ?></h6>
                    </div>
                    <div data-tabs-target-next="#mpcrbm_order_summary" class="tabItemNext" data-open-text="3" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                        <h4 class="circleIcon" data-class>
                            <span class="mp_zero" data-icon></span>
                            <span class="mp_zero" data-text>3</span>
                        </h4>
                        <h6 class="circleTitle" data-class><?php esc_html_e('Place Order', 'car-rental-manager'); ?></h6>
                    </div>
                </div>
                <div class="tabsContentNext">
                    <div data-tabs-next="#mpcrbm_pick_up_details" class="active mpcrbm_pick_up_details">
                      <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// ==============================
// FOOTER
// ==============================
if ( function_exists( 'block_footer_area' ) && wp_is_block_theme() ) {
    echo '<footer class="wp-block-template-part mep-site-footer">';
        block_footer_area();
    echo '</footer>';
    wp_footer();
} else {
    get_footer();
}
