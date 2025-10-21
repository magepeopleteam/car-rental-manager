<?php
	// Template Name: Default Theme
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	$post_id = $post_id ?? get_the_id();
?>
	<div class="mpcrbm mpcrbm_default_theme">
		<div class="mpContainer">
			<?php do_action( 'mpcrbm_transport_search_form',$post_id );

            include_once MPCRBM_Function::template_path('car-details/car_details.php');
            ?>
		</div>
	</div>
<?php do_action( 'mpcrbm_after_details_page' ); ?>