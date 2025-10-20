<?php
// Template Name: Default Theme
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$post_id = $post_id ?? get_the_id();
$thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
?>
<div class="mpcrbm mpcrbm_default_theme">
    <div class="mpContainer" style="min-height: 1000px">
        <?php do_action( 'mpcrbm_transport_search_form',$post_id ); ?>


        <div class="mpcrbm_car_details_wrapper">
            <h1 ><?php echo get_the_title( $post_id );?></h1>
            <div class="mpcrbm_car_details_container">
                <!-- LEFT CONTENT -->

                <div class="mpcrbm_car_details_left">

                    <!-- FEATURE IMAGE -->
                    <div class="mpcrbm_car_details_feature_image">
                        <img src="<?php echo esc_attr( $thumbnail_url );?>" alt="Car Image">
                    </div>

                    <!-- GALLERY -->
                    <div class="mpcrbm_car_details_gallery">
                        <img src="https://via.placeholder.com/200x120" alt="">
                        <img src="https://via.placeholder.com/200x120" alt="">
                        <img src="https://via.placeholder.com/200x120" alt="">
                        <button class="mpcrbm_car_details_view_more">View More →</button>
                    </div>

                    <!-- TABS -->
                    <div class="mpcrbm_car_details_tabs">
                        <button class="active" data-tab="description">Description</button>
                        <button data-tab="carinfo">Car Info</button>
                        <button data-tab="benefits">Benefits</button>
                        <button data-tab="include">Include/Exclude</button>
                        <button data-tab="location">Location</button>
                        <button data-tab="reviews">Reviews</button>
                        <button data-tab="faq">FAQ’s</button>
                        <button data-tab="terms">Terms & Conditions</button>
                    </div>

                    <!-- TAB CONTENT -->
                    <div id="description" class="mpcrbm_car_details_tab_content active">
                        <p>Engineered for adventure, the Desert Storm is a rugged yet luxurious SUV designed to conquer challenging terrains with ease. Equipped with advanced off-road capabilities, premium interiors, and a powerful engine, this vehicle ensures you travel in comfort and style, no matter where the journey takes you.</p>
                    </div>

                    <div id="carinfo" class="mpcrbm_car_details_tab_content">
                        <div class="mpcrbm_car_details_info_grid">
                            <div>👤 5 Persons</div>
                            <div>🧳 3 Bags</div>
                            <div>⚡ Electric</div>
                            <div>📅 2022</div>
                            <div>∞ Unlimited</div>
                            <div>⚙️ Auto</div>
                            <div>⛽ Full to full</div>
                        </div>
                    </div>

                    <div id="benefits" class="mpcrbm_car_details_tab_content">
                        <ul class="mpcrbm_car_details_benefit_list">
                            <li>✅ Most popular fuel policy</li>
                            <li>✅ Short waiting times</li>
                            <li>✅ Superior safety and durability</li>
                            <li>✅ Convenient pick-up location</li>
                            <li>✅ Free cancellation</li>
                            <li>✅ 100% luxurious fleet</li>
                            <li>✅ Pay at pickup option</li>
                        </ul>
                    </div>

                    <div id="include" class="mpcrbm_car_details_tab_content">
                        <div class="mpcrbm_car_details_include_exclude">
                            <div class="mpcrbm_car_details_include">
                                <h4>Include</h4>
                                <ul>
                                    <li>✅ Unlimited Mileage</li>
                                    <li>✅ Collision Damage Waiver (CDW)</li>
                                    <li>✅ Third-Party Liability Insurance</li>
                                    <li>✅ 24/7 Roadside Assistance</li>
                                </ul>
                            </div>
                            <div class="mpcrbm_car_details_exclude">
                                <h4>Exclude</h4>
                                <ul>
                                    <li>❌ Additional Insurance</li>
                                    <li>❌ Additional Driver Fee</li>
                                    <li>❌ Child Safety Seat</li>
                                    <li>❌ Tolls and Fines</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div id="location" class="mpcrbm_car_details_tab_content">
                        <div class="mpcrbm_car_details_map_box">
                            <iframe src="https://maps.google.com/maps?q=Bangkok&t=&z=13&ie=UTF8&iwloc=&output=embed"></iframe>
                        </div>
                    </div>

                    <div id="reviews" class="mpcrbm_car_details_tab_content">
                        <p>No reviews yet. Be the first to share your experience!</p>
                    </div>

                    <div id="faq" class="mpcrbm_car_details_tab_content">
                        <h4>Frequently Asked Questions</h4>
                        <p><strong>Q:</strong> Is there a mileage limit?</p>
                        <p><strong>A:</strong> No, the mileage is unlimited.</p>
                        <p><strong>Q:</strong> Can I cancel for free?</p>
                        <p><strong>A:</strong> Yes, you can cancel anytime before the pickup date.</p>
                    </div>

                    <div id="terms" class="mpcrbm_car_details_tab_content">
                        <p>All bookings are subject to availability. The renter must possess a valid driver’s license. Fuel policy, insurance coverage, and other conditions apply based on local regulations.</p>
                    </div>

                </div>

                <!-- RIGHT CONTENT -->
                <div class="mpcrbm_car_details_right">
                    <div class="mpcrbm_car_details_price_box">
                        <h3>Total: <span>$240.00</span> / Day</h3>
                        <p>Without Taxes</p>

                        <div class="mpcrbm_car_details_pickup_box">
                            <div class="mpcrbm_car_details_row">
                                <label>Pick-up</label>
                                <input type="text" value="Bangkok">
                            </div>
                            <div class="mpcrbm_car_details_row">
                                <label>Drop-off</label>
                                <input type="text" value="Bangkok">
                            </div>
                            <div class="mpcrbm_car_details_row">
                                <label>Pick-up date</label>
                                <input type="date" value="2025-10-16">
                                <label>Time</label>
                                <input type="time" value="10:00">
                            </div>
                            <div class="mpcrbm_car_details_row">
                                <label>Drop-off date</label>
                                <input type="date" value="2025-10-17">
                                <label>Time</label>
                                <input type="time" value="10:00">
                            </div>
                        </div>

                        <button class="mpcrbm_car_details_continue_btn">Continue →</button>
                    </div>

                    <!-- DRIVER INFO -->
                    <div class="mpcrbm_car_details_driver_box">
                        <h4>Driver details <span class="verified">✔ Verified</span></h4>
                        <p><strong>Abdullah Khan</strong></p>
                        <p>Age 24 Years</p>
                    </div>

                    <!-- RENTER INFO -->
                    <div class="mpcrbm_car_details_renter_box">
                        <h4>Renters Information</h4>
                        <p><strong>Shelley Mcconnell</strong></p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>