<?php
if (!defined('ABSPATH')) {
    die; // Exit if accessed directly
}
/*
Template Name: Transport Result
*/
// Start the session to access session variables
session_start();
// Retrieve the content from the session variable
$content = isset($_SESSION['custom_content']) ? sanitize_text_field(wp_unslash($_SESSION['custom_content'])) : '';

// Check if $content is empty, redirect to homepage if it is
if (empty($content)) {
    wp_redirect(home_url());
    exit;
}

// Unset the session variable
unset($_SESSION['custom_content']);
get_header();
?>
<?php 
function mpcrm_enqueue_redirect_if_manual_referer_inline_script() {
    $http_referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';

    $inline_script = "var httpReferrer = \"{$http_referrer}\"; document.cookie = \"httpReferrer=\" + httpReferrer + \";path=/\";";

    wp_add_inline_script('jquery', $inline_script, 'after'); 
}
add_action('wp_enqueue_scripts', 'mpcrm_enqueue_redirect_if_manual_referer_inline_script');

?>
<?php 
function mpcrm_referer_cookie_script() {
    $http_referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url($_SERVER['HTTP_REFERER']) : '';
    wp_register_script('my_custom_js', '', [], '', true);
    wp_enqueue_script('my_custom_js');
    wp_add_inline_script('my_custom_js', "
        var httpReferrer = '$http_referrer';
        document.cookie = 'httpReferrer=' + httpReferrer + ';path=/';
    ");
}
add_action('wp_enqueue_scripts', 'mpcrm_referer_cookie_script');
?>
<main role="main" id="maincontent" class="middle-align mptbm-show-search-result">
    <div class="container">
        <div class="container background-img-skin">
            <div class="mpStyle mptbm_transport_search_area">
                <div class="mpTabsNext _mT">
                    <div class="tabListsNext">
                        <div data-tabs-target-next="#mptbm_pick_up_details" class="tabItemNext active" data-open-text="1" data-close-text=" " data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                            <h4 class="circleIcon" data-class>
                                <span class="mp_zero" data-icon></span>
                                <span class="mp_zero" data-text>1</span>
                            </h4>
                            <h6 class="circleTitle" data-class><?php esc_html_e('Enter Ride Details', 'car-rental-manager'); ?></h6>
                        </div>
                        <div data-tabs-target-next="#mptbm_search_result" class="tabItemNext active" data-open-text="2" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                            <h4 class="circleIcon" data-class>
                                <span class="mp_zero" data-icon></span>
                                <span class="mp_zero" data-text>2</span>
                            </h4>
                            <h6 class="circleTitle" data-class><?php esc_html_e('Choose a vehicle', 'car-rental-manager'); ?></h6>
                        </div>
                        <div data-tabs-target-next="#mptbm_order_summary" class="tabItemNext step-place-order" data-open-text="3" data-close-text="" data-open-icon="" data-close-icon="fas fa-check" data-add-class="success">
                            <h4 class="circleIcon" data-class>
                                <span class="mp_zero" data-icon></span>
                                <span class="mp_zero" data-text>3</span>
                            </h4>
                            <h6 class="circleTitle" data-class><?php esc_html_e('Place Order', 'car-rental-manager'); ?></h6>
                        </div>
                    </div>
                    <div>
                        <?php echo wp_kses_post( $content ); ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>
<?php
get_footer();
?>
