<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$mpcrbm_display_limit = 10;

$mpcrbm_car_data = isset( $car_result_data['cars'] ) && !empty( $car_result_data['cars'] )
    ? $car_result_data['cars'] : [] ;

$mpcrbm_car_count = count( $mpcrbm_car_data );
$mpcrbm_load_more_display = 'flex';
if( $mpcrbm_car_count < $mpcrbm_display_limit  ){
    $mpcrbm_load_more_display = 'none';
}

$mpcrbm_remaining = 0;
if( $mpcrbm_car_count > $mpcrbm_display_limit ){
    $mpcrbm_remaining = $mpcrbm_car_count - $mpcrbm_display_limit;
}


$mpcrbm_car_taxonomy_data = isset( $car_result_data['meta'] ) && !empty( $car_result_data['meta'] )
    ? $car_result_data['meta'] : [] ;

$mpcrbm_all_car_type           = $mpcrbm_car_taxonomy_data['mpcrbm_car_type'] ?? [];
$mpcrbm_all_fuel_type          = $mpcrbm_car_taxonomy_data['mpcrbm_fuel_type'] ?? [];
$mpcrbm_all_seating_capacity   = $mpcrbm_car_taxonomy_data['mpcrbm_seating_capacity'] ?? [];
$mpcrbm_all_car_brand          = $mpcrbm_car_taxonomy_data['mpcrbm_car_brand'] ?? [];
$mpcrbm_all_make_year          = $mpcrbm_car_taxonomy_data['mpcrbm_make_year'] ?? [];

$cpt = MPCRBM_Function::get_cpt();
$add_new_url = admin_url( 'post-new.php?post_type='.$cpt );
?>
<div class="mpcrbm_car_list_main-content">
    <!-- Analytics Cards -->
    <div class="mpcrbm_car-list-section">
        <!-- Car List Tab Content -->
        <div class="mpcrbm_list_tab_content" id="mpcrbm_carListTab">
            <div class="mpcrbm_car_list_controls">
                <h2><?php esc_attr_e( 'Car Inventory', 'car-rental-manager' );?></h2>
                <div class="mpcrbm_car_list_control_buttons">
                    <a href="<?php echo esc_url( $add_new_url ); ?>"><button class="mpcrbm_car_list_control_btn btn-primary" ><i class="mi mi-plus"></i> <?php esc_attr_e( 'Add New Car', 'car-rental-manager' );?></button></a>
                    <button class="mpcrbm_car_list_control_btn btn-secondary" style="display: none"><?php esc_attr_e( 'Export', 'car-rental-manager' );?></button>
                    <button class="mpcrbm_car_list_control_btn btn-secondary" style="display: none"><?php esc_attr_e( 'Bulk Actions', 'car-rental-manager' );?></button>
                </div>
            </div>

            <div class="mpcrbm_car_list_search_filter">
                <input type="text" id="mpcrbm_searchInput" placeholder="<?php esc_attr_e( 'Search cars...', 'car-rental-manager' );?>" oninput="searchCars()">
                <select id="mpcrbm_typeFilter">
                    <option value=""><?php esc_attr_e( 'All Car Types', 'car-rental-manager' );?></option>
                    <?php foreach ( $mpcrbm_all_car_type as $mpcrbm_car_type ){?>
                        <option value="<?php echo esc_attr( $mpcrbm_car_type );?>"><?php echo esc_attr( $mpcrbm_car_type );?></option>
                    <?php }?>
                </select>
                <select id="mpcrbm_fuelFilter">
                    <option value=""><?php esc_attr_e( 'All Fuel Types', 'car-rental-manager' );?></option>
                    <?php foreach ( $mpcrbm_all_fuel_type as $mpcrbm_get_fuel_type ){?>
                        <option value="<?php echo esc_attr( $mpcrbm_get_fuel_type );?>"><?php echo esc_attr( $mpcrbm_get_fuel_type );?></option>
                    <?php }?>
                </select>
                <select id="mpcrbm_yearFilter">
                    <option value=""><?php esc_attr_e( 'All Years', 'car-rental-manager' );?></option>
                    <?php foreach ( $mpcrbm_all_make_year as $mpcrbm_get_make_year ){?>
                        <option value="<?php echo esc_attr( $mpcrbm_get_make_year );?>"><?php echo esc_attr( $mpcrbm_get_make_year );?></option>
                    <?php }?>
                </select>
            </div>

            <div class="mpcrbm_car_list_table_container">
                <table id="mpcrbm_car_list_car_table" class="mpcrbm_car_list_car_table">
                    <thead class="mpcrbm_car_list_car_thead">
                    <tr>
                        <th><input type="checkbox" class="checkbox" ></th>
                        <th><?php esc_attr_e( 'Title', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Type', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Fuel Type', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Capacity', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Brand', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Year', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Status', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Day Price', 'car-rental-manager' );?></th>
                        <th><?php esc_attr_e( 'Actions', 'car-rental-manager' );?></th>
                    </tr>
                    </thead>
                    <tbody id="mpcrbm_carTableBody">
                    <?php
                    if( is_array( $mpcrbm_car_data ) && !empty( $mpcrbm_car_data ) ) {
                        $mpcrbm_display = 0;

                        foreach( $mpcrbm_car_data as $mpcrbm_car ) {

                            $mpcrbm_car_id = $mpcrbm_car['id'];

                            if( $mpcrbm_display < $mpcrbm_display_limit ){
                                $mpcrbm_display_car = '';
                            }else{
                                $mpcrbm_display_car = 'none';
                            }

                            $mpcrbm_cart_type = isset($mpcrbm_car['type']) && !empty($mpcrbm_car['type'])
                                ? implode(', ', $mpcrbm_car['type'])
                                : '';
                            $mpcrbm_fuel_type = isset( $mpcrbm_car['fuel_type'] ) && !empty($mpcrbm_car['fuel_type'])
                                ? implode( ', ', $mpcrbm_car['fuel_type'] ) : '';
                            $mpcrbm_seat_capacity = isset( $mpcrbm_car['capacity'] ) && !empty($mpcrbm_car['capacity'])
                                ? implode( ', ', $mpcrbm_car['capacity'] ) : '';
                            $mpcrbm_brand = isset( $mpcrbm_car['brand'] ) && !empty($mpcrbm_car['brand'])
                                ? implode( ', ', $mpcrbm_car['brand'] ) : '';
                            $mpcrbm_make_year = isset( $mpcrbm_car['year'] ) && !empty($mpcrbm_car['year'])
                                ? implode( ', ', $mpcrbm_car['year'] ) : '';
                            ?>
                            <tr
                            data-title-filter="<?php echo esc_html( $mpcrbm_car['title'] );?>"
                            data-car-type-filter="<?php echo esc_attr( $mpcrbm_cart_type );?>"
                            data-fuel-type-filter="<?php echo esc_attr( $mpcrbm_fuel_type );?>"
                            data-make-year-filter="<?php echo esc_attr( $mpcrbm_make_year );?>"
                            data-post-id="<?php echo esc_attr( $mpcrbm_car_id );?>"

                            style="display: <?php echo esc_attr( $mpcrbm_display_car );?>"
                            >
                                <td><input type="checkbox" class="checkbox"></td>
                                <td><a class="car-title" href="<?php echo esc_url( get_edit_post_link( $mpcrbm_car_id ) ); ?>"><?php echo esc_html( $mpcrbm_car['title'] );?></a></td>
                                <td><?php echo esc_attr( $mpcrbm_cart_type );?></td>
                                <td><span class="badge badge-petrol"><?php echo esc_attr( $mpcrbm_fuel_type );?></span></td>
                                <td><?php echo esc_attr( $mpcrbm_seat_capacity );?></td>
                                <td><?php echo esc_attr( $mpcrbm_brand );?></td>
                                <td><?php echo esc_attr( $mpcrbm_make_year );?></td>
                                <td><span class="mpcrbm_car_status status-published"><?php echo esc_attr( $mpcrbm_car['status'] );?></span></td>
                                <td><span class="mpcrbm_day_price"><?php echo esc_attr( $mpcrbm_car['price'] );?></span></td>

                                <td>
                                    <div class="mpcrbm_actions">
                                        <a href="<?php echo esc_url( get_permalink( $mpcrbm_car_id ) ); ?>"
                                           class="mpcrbm_action_btn view"
                                           title="<?php esc_attr_e( 'View', 'car-rental-manager' ); ?>"
                                           target="_blank"><i class="mi mi-eye"></i></a>

                                        <a href="<?php echo esc_url( get_edit_post_link( $mpcrbm_car_id ) ); ?>"
                                           class="mpcrbm_action_btn edit"
                                           title="<?php esc_attr_e( 'Edit', 'car-rental-manager' ); ?>"><i class="mi mi-pencil"></i></a>

                                        <?php
                                        $mpcrbm_duplicate_url = wp_nonce_url(
                                            admin_url( 'admin.php?action=mpcrbm_duplicate_car&post=' . $mpcrbm_car_id ),
                                            'mpcrbm_duplicate_car_' . $mpcrbm_car_id
                                        );
                                        ?>
                                        <a href="<?php echo esc_url( $mpcrbm_duplicate_url ); ?>"
                                           class="mpcrbm_action_btn duplicate"
                                           title="<?php esc_attr_e( 'Duplicate', 'car-rental-manager' ); ?>"><i class="mi mi-copy-alt"></i></a>

                                        <?php
                                        $mpcrbm_delete_url = get_delete_post_link( $mpcrbm_car_id, '', true );
                                        ?>
                                        <a href="<?php echo esc_url( $mpcrbm_delete_url ); ?>"
                                           class="mpcrbm_action_btn delete"
                                           title="<?php esc_attr_e( 'Delete', 'car-rental-manager' ); ?>"><i class="mi mi-trash"></i></a>
                                    </div>
                                </td>

                            </tr>
                            <?php
                            $mpcrbm_display++;
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="mpcrbm_multiple_delete_btn_holder" style="display: none">
                <input type="hidden" id="mpcrbm_delete_car_ids" value="" name="">
                <span class="mpcrbm_multiple_delete"><?php esc_html_e( 'Delete', 'car-rental-manager' );?></span>
            </div>

            <div class="mpcrbm_loadMoreContainer" id="mpcrbm_loadMoreContainer" style="display: <?php echo esc_attr( $mpcrbm_load_more_display );?>">
                <input id="mpcrbm_number_of_car_load"  type="hidden" value="<?php echo esc_attr( $mpcrbm_display_limit );?>">
                <input id="mpcrbm_number_load"  type="hidden" value="<?php echo esc_attr( $mpcrbm_display_limit );?>">
                <button class="mpcrbm_btn_load_more">
                    <span class="mpcrbm_loadmore_text" id="mpcrbm_loadmore_text"><?php esc_html_e( 'Load More', 'car-rental-manager' );?> </span>
                    <span class="mpcrbm_remaining_count" id="mpcrbm_remaining_count"> (<?php echo esc_attr( $mpcrbm_remaining );?>)</span>
                </button>
            </div>
        </div>
    </div>
</div>

