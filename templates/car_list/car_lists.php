<?php

$car_result_data = MPCRBM_Global_Function::mpcrbm_get_car_data();
$display_limit = 20;

$car_data = isset( $car_result_data['cars'] ) && !empty( $car_result_data['cars'] )
    ? $car_result_data['cars'] : [] ;

$car_count = count( $car_data );
$load_more_display = 'flex';
if( $car_count < $display_limit  ){
    $load_more_display = 'none';
}

$remaining = 0;
if( $car_count > $display_limit ){
    $remaining = $car_count - $display_limit;
}


$car_taxonomy_data = isset( $car_result_data['meta'] ) && !empty( $car_result_data['meta'] )
    ? $car_result_data['meta'] : [] ;

$all_car_type           = $car_taxonomy_data['mpcrbm_car_type'] ?? [];
$all_fuel_type          = $car_taxonomy_data['mpcrbm_fuel_type'] ?? [];
$all_seating_capacity   = $car_taxonomy_data['mpcrbm_seating_capacity'] ?? [];
$all_car_brand          = $car_taxonomy_data['mpcrbm_car_brand'] ?? [];
$all_make_year          = $car_taxonomy_data['mpcrbm_make_year'] ?? [];

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
                    <a href="<?php echo esc_url( $add_new_url ); ?>"><button class="mpcrbm_car_list_control_btn btn-primary" >+ <?php esc_attr_e( 'Add New Car', 'car-rental-manager' );?></button></a>
                    <button class="mpcrbm_car_list_control_btn btn-secondary" style="display: none"><?php esc_attr_e( 'Export', 'car-rental-manager' );?></button>
                    <button class="mpcrbm_car_list_control_btn btn-secondary" style="display: none"><?php esc_attr_e( 'Bulk Actions', 'car-rental-manager' );?></button>
                </div>
            </div>

            <div class="mpcrbm_car_list_search_filter">
                <input type="text" id="mpcrbm_searchInput" placeholder="🔍 <?php esc_attr_e( 'Search cars...', 'car-rental-manager' );?>" oninput="searchCars()">
                <select id="mpcrbm_typeFilter">
                    <option value=""><?php esc_attr_e( 'All Car Types', 'car-rental-manager' );?></option>
                    <?php foreach ( $all_car_type as $car_type ){?>
                        <option value="<?php echo esc_attr( $car_type );?>"><?php echo esc_attr( $car_type );?></option>
                    <?php }?>
                </select>
                <select id="mpcrbm_fuelFilter">
                    <option value=""><?php esc_attr_e( 'All Fuel Types', 'car-rental-manager' );?></option>
                    <?php foreach ( $all_fuel_type as $fuel_type ){?>
                        <option value="<?php echo esc_attr( $fuel_type );?>"><?php echo esc_attr( $fuel_type );?></option>
                    <?php }?>
                </select>
                <select id="mpcrbm_yearFilter">
                    <option value=""><?php esc_attr_e( 'All Years', 'car-rental-manager' );?></option>
                    <?php foreach ( $all_make_year as $make_year ){?>
                        <option value="<?php echo esc_attr( $make_year );?>"><?php echo esc_attr( $make_year );?></option>
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
                    if( is_array( $car_data ) && !empty( $car_data ) ) {
                        $display = 0;

                        foreach( $car_data as $car ) {

                            $car_id = $car['id'];

                            if( $display < $display_limit ){
                                $display_car = '';
                            }else{
                                $display_car = 'none';
                            }

                            $cart_type = isset($car['type']) && !empty($car['type'])
                                ? implode(', ', $car['type'])
                                : '';
                            $fuel_type = isset( $car['fuel_type'] ) && !empty($car['fuel_type'])
                                ? implode( ', ', $car['fuel_type'] ) : '';
                            $seat_capacity = isset( $car['capacity'] ) && !empty($car['capacity'])
                                ? implode( ', ', $car['capacity'] ) : '';
                            $brand = isset( $car['brand'] ) && !empty($car['brand'])
                                ? implode( ', ', $car['brand'] ) : '';
                            $make_year = isset( $car['year'] ) && !empty($car['year'])
                                ? implode( ', ', $car['year'] ) : '';
                            ?>
                            <tr
                            data-title-filter="<?php echo esc_html( $car['title'] );?>"
                            data-car-type-filter="<?php echo esc_attr( $cart_type );?>"
                            data-fuel-type-filter="<?php echo esc_attr( $fuel_type );?>"
                            data-make-year-filter="<?php echo esc_attr( $make_year );?>"

                            style="display: <?php echo esc_attr( $display_car );?>"
                            >
                                <td><input type="checkbox" class="checkbox"></td>
                                <td><a href="<?php echo esc_url( get_edit_post_link( $car_id ) ); ?>"><?php echo esc_html( $car['title'] );?></a></td>
                                <td><?php echo esc_attr( $cart_type );?></td>
                                <td><span class="badge badge-petrol"><?php echo esc_attr( $fuel_type );?></span></td>
                                <td><?php echo esc_attr( $seat_capacity );?></td>
                                <td><?php echo esc_attr( $brand );?></td>
                                <td><?php echo esc_attr( $make_year );?></td>
                                <td><span class="mpcrbm_car_status status-published"><span class="mpcrbm_status_dot"></span> <?php echo esc_attr( $car['status'] );?></span></td>
                                <td><span class="mpcrbm_day_price"><?php echo esc_attr( $car['price'] );?></span></td>

                                <td>
                                    <div class="mpcrbm_actions">
                                        <a href="<?php echo esc_url( get_permalink( $car_id ) ); ?>"
                                           class="mpcrbm_action_btn view"
                                           title="<?php esc_attr_e( 'View', 'car-rental-manager' ); ?>"
                                           target="_blank">👁️</a>

                                        <a href="<?php echo esc_url( get_edit_post_link( $car_id ) ); ?>"
                                           class="mpcrbm_action_btn edit"
                                           title="<?php esc_attr_e( 'Edit', 'car-rental-manager' ); ?>">✏️</a>

                                        <?php
                                        $duplicate_url = wp_nonce_url(
                                            admin_url( 'admin.php?action=mpcrbm_duplicate_car&post=' . $car_id ),
                                            'mpcrbm_duplicate_car_' . $car_id
                                        );
                                        ?>
                                        <a href="<?php echo esc_url( $duplicate_url ); ?>"
                                           class="mpcrbm_action_btn duplicate"
                                           title="<?php esc_attr_e( 'Duplicate', 'car-rental-manager' ); ?>">📋</a>

                                        <?php
                                        $delete_url = get_delete_post_link( $car_id, '', true );
                                        ?>
                                        <a href="<?php echo esc_url( $delete_url ); ?>"
                                           class="mpcrbm_action_btn delete"
                                           title="<?php esc_attr_e( 'Delete', 'car-rental-manager' ); ?>">🗑️</a>
                                    </div>
                                </td>

                            </tr>
                            <?php
                            $display++;
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <div class="mpcrbm_loadMoreContainer" id="mpcrbm_loadMoreContainer" style="display: <?php echo esc_attr( $load_more_display );?>">
                <input id="mpcrbm_number_of_car_load"  type="hidden" value="<?php echo esc_attr( $display_limit );?>">
                <input id="mpcrbm_number_load"  type="hidden" value="<?php echo esc_attr( $display_limit );?>">
                <button class="mpcrbm_btn_load_more">
                    <span class="mpcrbm_loadmore_text" id="mpcrbm_loadmore_text">Load More </span>
                    <span class="mpcrbm_remaining_count" id="mpcrbm_remaining_count"> (<?php echo esc_attr( $remaining );?>)</span>
                </button>
            </div>
        </div>
    </div>
</div>

