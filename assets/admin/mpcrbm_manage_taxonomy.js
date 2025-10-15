(function ($) {
    $(document).ready(function () {

        let currentType = 'mpcrbm_car_list';
        // loadTaxonomyData( currentType );

        $('.mpcrbm_taxonomies_tab').on('click', function () {
            $('.mpcrbm_taxonomies_tab').removeClass('active');
            $(this).addClass('active');
            currentType = $(this).data('target');
            loadTaxonomyData(currentType);
        });

        $(document).on('click', '.mpcrbm_taxonomies_add_btn', function () {
            $('.mpcrbm_taxonomies_popup_overlay').fadeIn();
        });

        $(document).on('click', '.mpcrbm_taxonomies_cancel_btn', function () {
            $('.mpcrbm_taxonomies_popup_overlay').fadeOut();
        });

        $(document).on('click', '.mpcrbm_taxonomies_save_btn', function () {
            let name = $('#mpcrbm_taxonomies_name').val();
            let slug = $('#mpcrbm_taxonomies_slug').val();
            let desc = $('#mpcrbm_taxonomies_desc').val();

            if (name === '') {
                alert('Name field is required');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpcrbm_save_taxonomy',
                    taxonomy_type: currentType,
                    name: name,
                    slug: slug,
                    // security: mpcrbm_taxonomy_ajax.nonce,
                },
                success: function (response) {
                    alert(response.data.message);

                    $('#mpcrbm_taxonomies_name').val('');
                    $('#mpcrbm_taxonomies_slug').val('');
                    $('#mpcrbm_taxonomies_desc').val('');

                    $('.mpcrbm_taxonomies_popup_overlay').fadeOut();
                    loadTaxonomyData(currentType);
                }
            });
        });

        // Search filter
        $('.mpcrbm_taxonomies_search').on('input', function () {
            let query = $(this).val().toLowerCase();
            $('#mpcrbm_taxonomies_holder .mpcrbm_taxonomy_item').filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(query) > -1);
            });
        });

        function loadTaxonomyData(type) {
            $('#mpcrbm_taxonomies_holder').html('<p>Loading...</p>');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpcrbm_load_taxonomies',
                    taxonomy_type: type
                },
                success: function (response) {
                    $('#mpcrbm_taxonomies_holder').html(response.data.html);
                }
            });
        }


        // const ajaxurl = mpcrbm_admin.ajax_url;
        // const nonce = mpcrbm_admin.nonce;
        const nonce = '';

        // Hover effect to show buttons
       /* $(document).on('mouseenter', '.mpcrbm_taxonomy_item', function () {
            $(this).find('.mpcrbm_taxonomy_actions').fadeIn(150);
        }).on('mouseleave', '.mpcrbm_taxonomy_item', function () {
            $(this).find('.mpcrbm_taxonomy_actions').fadeOut(150);
        });*/

        // Edit button click
        $(document).on('click', '.mpcrbm_edit_taxonomy', function () {
            let item = $(this).closest('.mpcrbm_taxonomy_item');
            let id = item.data('term-id');
            let type = item.data('type');
            let name = item.data('term-name');
            let slug = item.data('term-slug');
            let desc = item.data('term-desc');
            if( desc === undefined ){
                desc = '';
            }

            let popup = `
            <div class="mpcrbm_popup">
                <div class="mpcrbm_popup_inner">
                    <h3>Edit Taxonomy</h3>
                    <label>Name:</label>
                    <input type="text" id="edit_name" placeholder="Enter name" value="${name}">
                    <label>Slug:</label>
                    <input type="text" id="mpcrbm_taxonomies_slug" placeholder="Optional slug" value="${slug}">
                    <label>Description:</label>
                    <textarea id="edit_description" placeholder="Short description">${desc}</textarea>
    
                    <div class="mpcrbm_taxonomies_popup_actions">
                         <button class="button button-primary mpcrbm_update_taxonomy" data-id="${id}" data-type="${type}">Update</button>
                        <button class="button mpcrbm_close_popup">Cancel</button>
                    </div>
                </div>
            </div>`;
            $('body').append(popup);
        });

        // Update taxonomy
        $(document).on('click', '.mpcrbm_update_taxonomy', function () {
            let term_id = $(this).data('id');
            let type = $(this).data('type');
            let name = $('#edit_name').val();
            let desc = $('#edit_description').val();
            let slug = $('#mpcrbm_taxonomies_slug').val();

            $.post(ajaxurl, {
                action: 'mpcrbm_update_taxonomy',
                security: nonce,
                term_id,
                taxonomy_type: type,
                name,
                slug,
                description: desc
            }, function (res) {
                alert(res.data.message);
                $('.mpcrbm_popup').remove();
                // Reload taxonomy list
                loadTaxonomyData( type );
            });
        });

        // Delete button click
        $(document).on('click', '.mpcrbm_delete_taxonomy', function () {
            let item = $(this).closest('.mpcrbm_taxonomy_item');
            let id = item.data('term-id');
            let type = item.data('type');

            if (confirm('Are you sure you want to delete this taxonomy?')) {
                $.post(ajaxurl, {
                    action: 'mpcrbm_delete_taxonomy',
                    security: nonce,
                    term_id: id,
                    taxonomy_type: type
                }, function (res) {
                    alert(res.data.message);
                    if (res.success) item.remove();
                });
            }
        });

        // Close popup
        $(document).on('click', '.mpcrbm_close_popup', function () {
            $('.mpcrbm_popup').remove();
        });

        function mpcrbm_filterCars_and() {
            const searchInput = String($('#mpcrbm_searchInput').val() || '').toLowerCase();
            const selectedType = String($('#mpcrbm_typeFilter').val() || '').toLowerCase();
            const selectedFuel = String($('#mpcrbm_fuelFilter').val() || '').toLowerCase();
            const selectedYear = String($('#mpcrbm_yearFilter').val() || '').toLowerCase();

            let shownRows = 0;
            const visibleCount = parseInt($('#mpcrbm_number_of_car_load').val()) || 5;

            // Count total matching rows for Load More logic
            let totalMatched = 0;

            $('#mpcrbm_carTableBody tr').each(function () {
                const carRow = $(this);

                const title = String(carRow.data('title-filter') || '').toLowerCase();
                const carType = String(carRow.data('car-type-filter') || '').toLowerCase();
                const fuelType = String(carRow.data('fuel-type-filter') || '').toLowerCase();
                const makeYear = String(carRow.data('make-year-filter') || '').toLowerCase();

                const matchTitle = !searchInput || title.includes(searchInput);
                const matchType = !selectedType || carType.includes(selectedType);
                const matchFuel = !selectedFuel || fuelType.includes(selectedFuel);
                const matchYear = !selectedYear || makeYear.includes(selectedYear);

                if (matchTitle && matchType && matchFuel && matchYear) {
                    totalMatched++;

                    if (shownRows < visibleCount) {
                        carRow.fadeIn();
                        shownRows++;
                    } else {
                        carRow.hide();
                    }
                } else {
                    carRow.hide();
                }
            });

            // Show/hide Load More button
            if (shownRows >= totalMatched) {
                $('#mpcrbm_loadMoreContainer .mpcrbm_btn_load_more').hide();
            } else {
                $('#mpcrbm_loadMoreContainer .mpcrbm_btn_load_more').show();
            }
        }

        // Event bindings
        $(document).on('input', '#mpcrbm_searchInput', function () {
            let number_load = parseInt($('#mpcrbm_number_load').val()) || 5;
            $('#mpcrbm_number_of_car_load').val(number_load ); // reset visible count on new filter
            mpcrbm_filterCars_and();
        });
        $(document).on('change', '#mpcrbm_typeFilter, #mpcrbm_fuelFilter, #mpcrbm_yearFilter', function () {
            let number_load = parseInt($('#mpcrbm_number_load').val()) || 5;
            $('#mpcrbm_number_of_car_load').val( number_load ); // reset visible count on new filter
            mpcrbm_filterCars_and();
        });

        $(document).on('click', '.mpcrbm_btn_load_more', function () {
            let currentCount = parseInt($('#mpcrbm_number_of_car_load').val()) || 5;
            let number_load = parseInt($('#mpcrbm_number_load').val()) || 5;

            let total_load = currentCount + number_load;
            $('#mpcrbm_number_of_car_load').val(total_load );

            let totalRows =  $('#mpcrbm_carTableBody tr' ).length;
            let remaining = totalRows - total_load;
            $("#mpcrbm_remaining_count").text( '('+remaining+')' );
            mpcrbm_filterCars_and();
        });


    });

})(jQuery);
