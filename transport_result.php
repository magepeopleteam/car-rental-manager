<?php
/*
Template Name: Transport Result
*/

// ✅ Start session in functions.php (recommended), not in template
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$content = '';

$search_attribute = [ 'form'=>'inline', 'title'=>'no', 'ajax_search' => 'yes', 'progressbar' => 'no' ];
$search_defaults = MPCRBM_Shortcodes::default_attribute();
$params = shortcode_atts( $search_defaults, $search_attribute );
ob_start();
do_action( 'mpcrbm_transport_search', $params );
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
if (empty($content)) {
    wp_redirect(home_url());
    exit;
}

// Remove content from session after use
unset($_SESSION['custom_content']);

 if ( wp_is_block_theme() ) {  ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php
	$block_content = do_blocks( '
		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
		<!-- wp:post-content /-->
		</div>
		<!-- /wp:group -->'
 	);
    wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
<header class="wp-block-template-part site-header">
    <?php block_header_area(); ?>
</header>
</div>
<?php
} else {
    get_header();	
    the_post();
}
?>

    <!-- Pass HTTP referrer to cookie -->
    <script type="text/javascript">
        (function() {
            var httpReferrer = "<?php echo esc_js($_SERVER['HTTP_REFERER'] ?? ''); ?>";
            document.cookie = "httpReferrer=" + httpReferrer + ";path=/";
        })();
    </script>

    <main id="maincontent" class="transport-result-page">
        <div class="mpcrbm mpcrbm_transport_search_area" style="margin: auto">
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
                        <?php echo $content; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php
if ( wp_is_block_theme() ) {
// Code for block themes goes here.
?>
<footer class="wp-block-template-part">
    <?php block_footer_area(); ?>
</footer>
<?php wp_footer(); ?>
</body>    
<?php
} else {
    get_footer();
}