(function ($) {
    $(document).ready(function () {

        let currentType = 'mpcrbm_car_list';
        // loadTaxonomyData( currentType );

        $(document).on( 'click','.mpcrbm_taxonomies_tab', function () {
            $('.mpcrbm_taxonomies_content_holder').hide();
            $('.mpcrbm_taxonomies_tab').removeClass('active');
            $(this).addClass('active');
            currentType = $(this).data('target');
            let content_holder_id = currentType+'_holder';
            $("#"+content_holder_id).fadeIn();

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
                    nonce: mpcrbm_admin_nonce.nonce,
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
            let content_holder = type+'_holder';
            $('#'+content_holder).html('<p>Loading...</p>');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpcrbm_load_taxonomies',
                    taxonomy_type: type
                },
                success: function (response) {
                    $('#'+content_holder).html(response.data.html);
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
                description: desc,
                nonce: mpcrbm_admin_nonce.nonce,
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
                    taxonomy_type: type,
                    nonce: mpcrbm_admin_nonce.nonce,
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


        // Function to update delete holder visibility and IDs
        function updateDeleteHolder() {
            let ids = [];
            $('#mpcrbm_carTableBody input[type="checkbox"]:checked').each(function () {
                let postId = $(this).closest('tr').data('post-id');
                if (postId) ids.push(postId);
            });

            if (ids.length > 0) {
                $('.mpcrbm_multiple_delete_btn_holder').show();
                $('#mpcrbm_delete_car_ids').val(ids.join(','));
            } else {
                $('.mpcrbm_multiple_delete_btn_holder').hide();
                $('#mpcrbm_delete_car_ids').val('');
            }
        }

        $(document).on( 'change','#mpcrbm_carTableBody','input[type="checkbox"]', function () {
            if (!$(this).is(':checked')) {
                $('#mpcrbm_car_list_car_table thead input[type="checkbox"]').prop('checked', false);
            } else {
                let allChecked = $('#mpcrbm_carTableBody input[type="checkbox"]').length === $('#mpcrbm_carTableBody input[type="checkbox"]:checked').length;
                $('#mpcrbm_car_list_car_table thead input[type="checkbox"]').prop('checked', allChecked);
            }
            updateDeleteHolder();
        });

        $(document).on( 'change','#mpcrbm_car_list_car_table thead input[type="checkbox"]',function () {
            let isChecked = $(this).is(':checked');
            $('#mpcrbm_carTableBody input[type="checkbox"]').prop('checked', isChecked);
            updateDeleteHolder();
        });

        $(document).on( 'click','.mpcrbm_multiple_delete',function () {
            let ids = $('#mpcrbm_delete_car_ids').val();
            if (!ids) {
                alert('Please select at least one car to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete selected cars?')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mpcrbm_delete_multiple_cars',
                    ids: ids,
                    _wpnonce: mpcrbm_admin_nonce.nonce
                },
                success: function (response) {
                    if (response.success) {
                        ids.split(',').forEach(function (id) {
                            $('#mpcrbm_carTableBody tr[data-post-id="' + id + '"]').remove();
                        });
                        alert('Selected cars deleted successfully.');
                        updateDeleteHolder();
                    } else {
                        alert('Failed to delete cars.');
                    }
                }
            });
        });


        /*FAQ*/
        function closeModal() {
            $('#mpcrbm_faq_modal').hide();
            $('#mpcrbm_faq_key').val('');
            $('#mpcrbm_faq_title').val('');
            if (tinymce.get('mpcrbm_faq_answer_editor')) {
                tinymce.get('mpcrbm_faq_answer_editor').setContent('');
            } else {
                $('#mpcrbm_faq_answer_editor').val('');
            }
        }
        function closeTermModal() {
            $('#mpcrbm_term_condition_modal').hide();
            $('#mpcrbm_term_condition_key').val('');
            $('#mpcrbm_term_condition_title').val('');
            if (tinymce.get('mpcrbm_term_condition_answer_editor')) {
                tinymce.get('mpcrbm_term_condition_answer_editor').setContent('');
            } else {
                $('#mpcrbm_term_condition_answer_editor').val('');
            }
        }

        $(document).on('click', '#mpcrbm_add_faq_btn',function() {
            $('#mpcrbm_modal_title').text('Add FAQ');
            closeModal();
            let targetBtn = $('#mpcrbm_save_term_condition_btn');

            if (targetBtn.length) {
                targetBtn.attr('id', 'mpcrbm_save_faq_btn');
            }
            $('#mpcrbm_faq_modal').show();
        });

        $(document).on('click', '#mpcrbm_add_term_condition_btn',function() {
            $('#mpcrbm_term_modal_title').text('Add Term & Condition');
            closeTermModal();

            let targetBtn = $('#mpcrbm_save_term_condition_btn');

            if (targetBtn.length) {
                targetBtn.attr('id', 'mpcrbm_save_term_condition_btn');
            }
            $('#mpcrbm_term_condition_modal').show();
        });

        $(document).on( 'click', '#mpcrbm_cancel_faq_btn', function() {
            closeModal();
        });

        $(document).on( 'click', '#mpcrbm_cancel_term_condition_btn', function() {
            closeTermModal();
        });

        // Edit FAQ
        $(document).on('click', '.edit-faq', function() {
            const row = $(this).closest('tr');
            $('#mpcrbm_faq_key').val(row.data('key'));
            $('#mpcrbm_faq_title').val(row.find('.faq-title').text());
            $('#mpcrbm_modal_title').text('Edit FAQ');
            $('#mpcrbm_faq_modal').show();

            let targetBtn = $('#mpcrbm_save_term_condition_btn');

            if (targetBtn.length) {
                targetBtn.attr('id', 'mpcrbm_save_faq_btn');
            }

            const answer = row.find('.faq-answer').text();
            setTimeout(() => {
                if (tinymce.get('mpcrbm_faq_answer_editor')) {
                    tinymce.get('mpcrbm_faq_answer_editor').setContent(answer);
                } else {
                    $('#mpcrbm_faq_answer_editor').val(answer);
                }
            }, 300);
        });
        // Edit TERM
        $(document).on('click', '.mpcrbm_edit_term', function() {
            const row = $(this).closest('tr');
            $('#mpcrbm_term_condition_key').val(row.data('key'));
            $('#mpcrbm_term_condition_title').val(row.find('.faq-title').text());
            $('#mpcrbm_term_modal_title').text('Edit Term & Condition');
            $('#mpcrbm_term_condition_modal').show();

            let targetBtn = $('#mpcrbm_save_term_condition_btn');

            if (targetBtn.length) {
                targetBtn.attr('id', 'mpcrbm_save_term_condition_btn');
            }

            const answer = row.find('.faq-answer').text();
            setTimeout(() => {
                if (tinymce.get('mpcrbm_term_condition_answer_editor')) {
                    tinymce.get('mpcrbm_term_condition_answer_editor').setContent(answer);
                } else {
                    $('#mpcrbm_term_condition_answer_editor').val(answer);
                }
            }, 300);
        });

        // Delete FAQ
        $(document).on('click', '.delete-faq', function() {
            if (!confirm('Are you sure you want to delete this FAQ?')) return;
            const key = $(this).closest('tr').data('key');
            $.post(ajaxurl, {
                action: 'mpcrbm_delete_faq',
                key: key,
                nonce: mpcrbm_admin_nonce.nonce
            }, function(response){
                if (response.success) location.reload();
                else alert(response.data);
            });
        });

        // Delete ERM
        $(document).on('click', '.mpcrbm_delete_term', function() {
            if (!confirm('Are you sure you want to delete this FAQ?')) return;
            const key = $(this).closest('tr').data('key');
            $.post(ajaxurl, {
                action: 'mpcrbm_delete_term',
                key: key,
                nonce: mpcrbm_admin_nonce.nonce
            }, function(response){
                if (response.success) location.reload();
                else alert(response.data);
            });
        });

        // Save FAQ
        $(document).on( 'click','#mpcrbm_save_faq_btn', function( e ) {
            e.preventDefault();
            const title = $('#mpcrbm_faq_title').val().trim();
            let answer = '';
            if (tinymce.get('mpcrbm_faq_answer_editor')) {
                answer = tinymce.get('mpcrbm_faq_answer_editor').getContent();
            } else {
                answer = $('#mpcrbm_faq_answer_editor').val();
            }
            const key = $('#mpcrbm_faq_key').val();

            if (title === '' || answer === '') {
                alert('Please fill all fields.');
                return;
            }

            $.post( ajaxurl, {
                action: 'mpcrbm_save_faq',
                title: title,
                answer: answer,
                key: key,
                nonce: mpcrbm_admin_nonce.nonce
            }, function(response){
                if (response.success) location.reload();
                else alert(response.data);
            });
        });
        // Save FAQ
        $(document).on( 'click','#mpcrbm_save_term_condition_btn', function( e ) {
            e.preventDefault();
            const title = $('#mpcrbm_term_condition_title').val().trim();
            let answer = '';
            if (tinymce.get('mpcrbm_term_condition_answer_editor')) {
                answer = tinymce.get('mpcrbm_term_condition_answer_editor').getContent();
            } else {
                answer = $('#mpcrbm_term_condition_answer_editor').val();
            }
            const key = $('#mpcrbm_term_condition_key').val();

            if (title === '' || answer === '') {
                alert('Please fill all fields.');
                return;
            }

            $.post( ajaxurl, {
                action: 'mpcrbm_save_term_and_condition',
                title: title,
                answer: answer,
                key: key,
                nonce: mpcrbm_admin_nonce.nonce
            }, function(response){
                if (response.success) location.reload();
                else alert(response.data);
            });
        });

        // ===== ADD FAQ =====
        $(document).on('click', '.mpcrbm_add_faq', function() {
            let item = $(this).closest('.mpcrbm_faq_item');
            let $this = $(this);
            $this.text( 'adding...');
            let key = item.data('key');
            let title = item.data('title');

            let html = `
            <div class="mpcrbm_selected_item" 
            data-key="${key}"
            data-title="${title}"
            >
                <div class="mpcrbm_faq_title">${title}</div>
                <button type="button" class="button button-small mpcrbm_remove_faq">Remove</button>
            </div>`;

            updateFaqMeta( $this, item, 'add', 'mpcrbm_selected_faq_question', html );

        });
        // ===== REMOVE FAQ =====
        $(document).on('click', '.mpcrbm_remove_faq', function() {
            let $this = $(this);
            $this.text( 'removing...');
            let item = $(this).closest('.mpcrbm_selected_item');
            let key = item.data('key');
            let title = item.data('title');

            let html = `
            <div class="mpcrbm_faq_item" 
            data-key="${key}"
            data-title="${title}"
            >
                <div class="mpcrbm_faq_title">${title}</div>
                <button type="button" class="button button-small mpcrbm_add_faq">Add</button>
            </div>`;

            updateFaqMeta( $this, item, 'remove', 'mpcrbm_faq_all_question', html  );

        });

        // ===== ADD TERM =====
        $(document).on('click', '.mpcrbm_add_term_condition', function() {
            let item = $(this).closest('.mpcrbm_faq_item');
            let $this = $(this);
            $this.text( 'adding...');
            let key = item.data('key');
            let title = item.data('title');

            let html = `
            <div class="mpcrbm_selected_item" 
            data-key="${key}"
            data-key="${title}"
            >
                <div class="mpcrbm_faq_title">${title}</div>
                <button type="button" class="button button-small mpcrbm_remove_faq">Remove</button>
            </div>`;

            updateTermMeta( $this, item, 'add', 'mpcrbm_selected_term_condition', html );

        });
        // ===== REMOVE FAQ =====
        $(document).on('click', '.mpcrbm_remove_term_condition', function() {
            let $this = $(this);
            $this.text( 'removing...');
            let item = $(this).closest('.mpcrbm_selected_item');
            let key = item.data('key');
            let title = item.data('title');

            let html = `
            <div class="mpcrbm_faq_item" 
            data-key="${key}"
            data-key="${title}"
            >
                <div class="mpcrbm_faq_title">${title}</div>
                <button type="button" class="button button-small mpcrbm_add_faq">Add</button>
            </div>`;

            updateTermMeta( $this, item, 'remove', 'mpcrbm_all_term_condition', html  );

        });

        function updateFaqMeta( clickBtn, item, action, append_section, html ) {

            let post_id = $('[name="mpcrbm_post_id"]').val();
            let data = [];

            $('.mpcrbm_selected_item').each(function() {
                let key = $(this).data('key');
                data.push(key);
            });
            let key = $(item).data('key');

            if ( action === 'add' ) {
                if (!data.includes(key)) {
                    data.push(key);
                }
            } else if (action === 'remove') {
                let index = data.indexOf(key);
                if (index !== -1) {
                    data.splice(index, 1);
                }
            }

            let faq_keys = JSON.stringify(data);
            $('#mpcrbm_added_faq_input').val(JSON.stringify( faq_keys ));

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'mpcrbm_save_added_faq',
                    post_id: post_id,
                    mpcrbm_added_faq: faq_keys,
                    nonce: mpcrbm_admin_nonce.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // alert('✅ FAQs saved successfully');

                        clickBtn.text( action );
                        $('.'+append_section).append(html);
                        item.remove();
                    } else {
                        alert('❌ Error saving FAQs:', response.data.message);
                    }
                }
            });
        }
        function updateTermMeta( clickBtn, item, action, append_section, html ) {

            let post_id = $('[name="mpcrbm_post_id"]').val();
            let data = [];

            $('.mpcrbm_selected_item').each(function() {
                let key = $(this).data('key');
                data.push(key);
            });
            let key = $(item).data('key');

            if ( action === 'add' ) {
                if (!data.includes(key)) {
                    data.push(key);
                }
            } else if (action === 'remove') {
                let index = data.indexOf(key);
                if (index !== -1) {
                    data.splice(index, 1);
                }
            }

            let faq_keys = JSON.stringify(data);
            $('#mpcrbm_added_faq_input').val(JSON.stringify( faq_keys ));

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'mpcrbm_save_added_term_condition',
                    post_id: post_id,
                    mpcrbm_added_term: faq_keys,
                    nonce: mpcrbm_admin_nonce.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // alert('✅ FAQs saved successfully');

                        console.log(append_section);
                        clickBtn.text( action );
                        $('.'+append_section).append(html);
                        item.remove();
                    } else {
                        alert('❌ Error saving FAQs:', response.data.message);
                    }
                }
            });
        }

        function updateFeatureMeta( actionType, termId, featureType) {
            let post_id = $('[name="mpcrbm_post_id"]').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'mpcrbm_update_feature_meta',
                    nonce: mpcrbm_admin_nonce.nonce,
                    post_id: post_id,
                    term_id: termId,
                    feature_type: featureType,
                    action_type: actionType,
                },
                success: function(res) {
                    if (res.success) {
                        console.log('Feature meta updated');
                    }
                }
            });
        }

        // Include checkboxes
        $(document).on('change', '.mpcrbm_include_checkbox', function() {
            let termId = $(this).val();
            let actionType = $(this).is(':checked') ? 'add' : 'remove';
            updateFeatureMeta(actionType, termId, 'include');
        });

        // Exclude checkboxes
        $(document).on('change', '.mpcrbm_exclude_checkbox', function() {
            let termId = $(this).val();
            let actionType = $(this).is(':checked') ? 'add' : 'remove';
            updateFeatureMeta(actionType, termId, 'exclude');
        });


    });

})(jQuery);
