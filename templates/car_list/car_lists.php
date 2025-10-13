<?php
function pa_get_car_data_array() {
    $args = array(
        'post_type'      => 'mpcrbm_rent', // your custom post type slug
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $cars_data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $cars_data[] = array(
                'id'        => $post_id,
                'title'     => get_the_title(),
                'type'      => get_post_meta($post_id, 'mpcrbm_car_type', true),
                'fuel_type' => get_post_meta($post_id, 'mpcrbm_fuel_type', true),
                'capacity'  => get_post_meta($post_id, 'mpcrbm_seating_capacity', true),
                'brand'     => get_post_meta($post_id, 'mpcrbm_car_brand', true),
                'year'      => get_post_meta($post_id, 'mpcrbm_make_year', true),
                'price'     => get_post_meta($post_id, 'mpcrbm_day_price', true),
                'status'    => get_post_status($post_id),
            );
        }
        wp_reset_postdata();
    }

    return $cars_data;
}

$car_data = pa_get_car_data_array();
//error_log( print_r( [ '$car_data' => $car_data ], true) );


?>
<div class="main-content">
    <!-- Analytics Cards -->
    <div class="car-list-section">
        <!-- Car List Tab Content -->
        <div class="list_tab_content" id="carListTab">
            <div class="controls">
                <h2>Car Inventory</h2>
                <div class="control-buttons">
                    <button class="btn btn-primary" >+ Add New Car</button>
                    <button class="btn btn-secondary" >Export</button>
                    <button class="btn btn-secondary" >Bulk Actions</button>
                </div>
            </div>

            <div class="search-filter">
                <input type="text" id="searchInput" placeholder="🔍 Search cars..." oninput="searchCars()">
                <select id="typeFilter" onchange="filterCars()">
                    <option value="">All Types</option>
                    <option value="Sedan">Sedan</option>
                    <option value="Coupe">Coupe</option>
                    <option value="Hatchback">Hatchback</option>
                    <option value="SUV">SUV</option>
                </select>
                <select id="fuelFilter" onchange="filterCars()">
                    <option value="">All Fuel Types</option>
                    <option value="Petrol">Petrol</option>
                    <option value="Electric">Electric</option>
                    <option value="Diesel">Diesel</option>
                    <option value="LPG">LPG</option>
                    <option value="CNG">CNG</option>
                </select>
                <select id="yearFilter" onchange="filterCars()">
                    <option value="">All Years</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                    <option value="2023">2023</option>
                    <option value="2022">2022</option>
                </select>
            </div>

            <div class="table-container">
                <table id="carTable">
                    <thead>
                    <tr>
                        <th><input type="checkbox" class="checkbox" ></th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Fuel Type</th>
                        <th>Capacity</th>
                        <th>Brand</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Day Price</th>
                    </tr>
                    </thead>
                    <tbody id="carTableBody">
                    <?php
                    if( is_array( $car_data ) && !empty( $car_data ) ) {
                        foreach( $car_data as $car ) {
                    ?>
                        <tr>
                            <td><input type="checkbox" class="checkbox"></td>
                            <td><a href="#" class="car-title"><?php echo esc_html( $car['title'] );?></a></td>
                            <td>Sedan</td>
                            <td><span class="badge badge-petrol">Petrol</span></td>
                            <td>5 Seater</td>
                            <td>Ford</td>
                            <td>2025</td>
                            <td><span class="status status-published"><span class="status-dot"></span> Published</span></td>
                            <td><span class="price">$10</span></td>
                        </tr>
                    <?php
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="load-more-container" id="loadMoreContainer">
<!--                <button class="btn-load-more" >Load More</button>-->
            </div>
        </div>
    </div>
</div>

