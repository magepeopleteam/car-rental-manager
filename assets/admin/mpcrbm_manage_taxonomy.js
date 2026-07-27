(function ($) {
    $(document).ready(function () {

        let currentType = 'mpcrbm_car_list';
        // loadTaxonomyData( currentType );

        $(document).on( 'click','.mpcrbm_taxonomies_tab', function () {
            $('.mpcrbm_taxonomies_content_holder').hide();
            $('.mpcrbm_taxonomies_tab').removeClass('active');
            $(this).addClass('active');
            currentType = $(this).data('target');
            var content_holder_id = currentType + '_holder';
            $('#' + content_holder_id).fadeIn();

            // Fleet stat cards only make sense on the Car List tab.
            $('#mpcrbm_analytics_holder').toggle( currentType === 'mpcrbm_car_list' );

            // Pro gate: show upgrade popup if tab requires pro and pro is not active
            var isProRequired = $(this).data('pro-required') == 1;
            var isPro = (typeof mpcrbmBranchAdmin !== 'undefined') && !!mpcrbmBranchAdmin.isPro;
            if ( isProRequired && !isPro ) {
                $('#mpcrbm-pro-upgrade-overlay').fadeIn(200);
                return;
            }

            // Lazy-load branch dashboard on first click (avoids heavy queries on page load)
            if ( currentType === 'mpcrbm_branch_manager' ) {
                var $holder = $('#mpcrbm_branch_manager_holder');
                if ( $holder.find('.mpcrbm-branch-lazy-placeholder').length && !$holder.data('bm-loaded') ) {
                    $holder.html('<div class="mpcrbm-branch-loading">' + ( (typeof mpcrbmBranchAdmin !== 'undefined' && mpcrbmBranchAdmin.loadingText) || 'Loading…' ) + '</div>');
                    $.post(ajaxurl, {
                        action: 'mpcrbm_render_branch_dashboard',
                        nonce:  mpcrbm_admin_nonce.nonce,
                    }, function (res) {
                        if ( res.success ) {
                            $holder.html(res.data.html);
                            $holder.data('bm-loaded', true);
                        } else {
                            $holder.html('<p style="padding:24px;color:#64748b;">Failed to load Branch Manager. Please refresh the page.</p>');
                        }
                    }).fail(function () {
                        $holder.html('<p style="padding:24px;color:#64748b;">Network error. Please refresh the page.</p>');
                    });
                }
            }
        });

        // Pro popup close — button
        $(document).on('click', '#mpcrbm-pro-upgrade-close', function () {
            $('#mpcrbm-pro-upgrade-overlay').fadeOut(200);
        });

        // Pro popup close — click backdrop
        $(document).on('click', '.mpcrbm-pro-upgrade-overlay', function (e) {
            if ( $(e.target).hasClass('mpcrbm-pro-upgrade-overlay') ) {
                $(this).fadeOut(200);
            }
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
            $('.mpcrbm_taxonomy_item').filter(function () {
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
            const searchInput  = String($('#mpcrbm_searchInput').val() || '').toLowerCase();
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

        // ===== TOGGLE FAQ (single-list: clicking an item adds/removes it in place) =====
        $(document).on('click', '.mpcrbm-faq-toggle-item', function() {
            let $item = $(this);
            if ($item.hasClass('is-saving')) return;

            let willBeSelected = !$item.hasClass('is-selected');
            let post_id = $('[name="mpcrbm_post_id"]').val();

            $item.toggleClass('is-selected', willBeSelected).addClass('is-saving');

            let data = [];
            $('.mpcrbm-faq-toggle-item.is-selected').each(function() {
                data.push($(this).data('key'));
            });
            let faq_keys = JSON.stringify(data);

            $('#mpcrbm_added_faq_input').val(faq_keys);
            $('#mpcrbm_faq_selected_count').text(data.length);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'mpcrbm_save_added_faq',
                    post_id: post_id,
                    mpcrbm_added_faq: faq_keys,
                    nonce: mpcrbm_admin_nonce.nonce
                },
                success: function(response) {
                    $item.removeClass('is-saving');
                    if (!response.success) {
                        // Revert this item's state and the counter on failure
                        $item.toggleClass('is-selected', !willBeSelected);
                        $('#mpcrbm_faq_selected_count').text($('.mpcrbm-faq-toggle-item.is-selected').length);
                        alert('Error saving FAQs' + (response.data && response.data.message ? ': ' + response.data.message : ''));
                    }
                },
                error: function() {
                    $item.removeClass('is-saving').toggleClass('is-selected', !willBeSelected);
                    $('#mpcrbm_faq_selected_count').text($('.mpcrbm-faq-toggle-item.is-selected').length);
                }
            });
        });

        // ===== "Add New FAQ" modal (per-car FAQ tab, MPCRBM_Faq_Settings.php) =====
        $(document).on('click', '#mpcrbm_toggle_new_faq', function() {
            $('#mpcrbm_new_faq_panel').show();
        });

        $(document).on('click', '#mpcrbm_cancel_new_faq_btn', function() {
            $('#mpcrbm_new_faq_title').val('');
            if (typeof tinymce !== 'undefined' && tinymce.get('mpcrbm_new_faq_answer_editor')) {
                tinymce.get('mpcrbm_new_faq_answer_editor').setContent('');
            } else {
                $('#mpcrbm_new_faq_answer_editor').val('');
            }
            $('#mpcrbm_new_faq_panel').hide();
        });

        $(document).on('click', '#mpcrbm_save_new_faq_btn', function(e) {
            e.preventDefault();
            let $btn = $(this);
            let title = $.trim($('#mpcrbm_new_faq_title').val());
            let answer = (typeof tinymce !== 'undefined' && tinymce.get('mpcrbm_new_faq_answer_editor'))
                ? tinymce.get('mpcrbm_new_faq_answer_editor').getContent()
                : $('#mpcrbm_new_faq_answer_editor').val();

            if (title === '' || answer === '') {
                alert('Please fill in both the question and the answer.');
                return;
            }

            let originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            $.post(ajaxurl, {
                action: 'mpcrbm_save_faq',
                title: title,
                answer: answer,
                key: '',
                nonce: mpcrbm_admin_nonce.nonce
            }, function(response) {
                $btn.prop('disabled', false).text(originalText);

                if (!response.success) {
                    alert(typeof response.data === 'string' ? response.data : 'Could not save the FAQ.');
                    return;
                }

                let key = response.data.key;
                let savedTitle = response.data.title;

                // Drop the "no FAQs available yet" placeholder, if this was the first one.
                $('.mpcrbm-faq-list p').remove();

                let $newItem = $(
                    '<div class="mpcrbm_faq_item mpcrbm-faq-toggle-item" data-key="' + key + '">' +
                    '<span class="mpcrbm-faq-toggle-check"><i class="fas fa-check"></i></span>' +
                    '<div class="mpcrbm_faq_title"></div>' +
                    '<span class="mpcrbm-faq-toggle-status">Selected</span>' +
                    '</div>'
                );
                $newItem.attr('data-title', savedTitle);
                $newItem.find('.mpcrbm_faq_title').text(savedTitle);
                $newItem.prependTo('.mpcrbm-faq-list');

                let $totalCount = $('#mpcrbm_faq_total_count');
                if ($totalCount.length) {
                    $totalCount.text(parseInt($totalCount.text(), 10) + 1);
                }

                // Also mark it "added" to this car — reuses the existing
                // single-item toggle handler above rather than duplicating
                // its save-to-car AJAX logic.
                $newItem.trigger('click');

                $('#mpcrbm_new_faq_title').val('');
                if (typeof tinymce !== 'undefined' && tinymce.get('mpcrbm_new_faq_answer_editor')) {
                    tinymce.get('mpcrbm_new_faq_answer_editor').setContent('');
                } else {
                    $('#mpcrbm_new_faq_answer_editor').val('');
                }
                $('#mpcrbm_new_faq_panel').hide();
            });
        });

        // ===== TOGGLE TERM & CONDITION (single-list: clicking an item adds/removes it in place) =====
        $(document).on('click', '.mpcrbm-term-toggle-item', function() {
            let $item = $(this);
            if ($item.hasClass('is-saving')) return;

            let willBeSelected = !$item.hasClass('is-selected');
            let post_id = $('[name="mpcrbm_post_id"]').val();

            $item.toggleClass('is-selected', willBeSelected).addClass('is-saving');

            let data = [];
            $('.mpcrbm-term-toggle-item.is-selected').each(function() {
                data.push($(this).data('key'));
            });
            let term_keys = JSON.stringify(data);

            $('#mpcrbm_added_term_condition_input').val(term_keys);
            $('#mpcrbm_term_selected_count').text(data.length);

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'mpcrbm_save_added_term_condition',
                    post_id: post_id,
                    mpcrbm_added_term: term_keys,
                    nonce: mpcrbm_admin_nonce.nonce
                },
                success: function(response) {
                    $item.removeClass('is-saving');
                    if (!response.success) {
                        $item.toggleClass('is-selected', !willBeSelected);
                        $('#mpcrbm_term_selected_count').text($('.mpcrbm-term-toggle-item.is-selected').length);
                        alert('Error saving Terms & Conditions' + (response.data && response.data.message ? ': ' + response.data.message : ''));
                    }
                },
                error: function() {
                    $item.removeClass('is-saving').toggleClass('is-selected', !willBeSelected);
                    $('#mpcrbm_term_selected_count').text($('.mpcrbm-term-toggle-item.is-selected').length);
                }
            });
        });

        // ===== "Add New Term & Condition" modal (per-car Term & Condition tab) =====
        $(document).on('click', '#mpcrbm_toggle_new_term', function() {
            $('#mpcrbm_new_term_panel').show();
        });

        $(document).on('click', '#mpcrbm_cancel_new_term_btn', function() {
            $('#mpcrbm_new_term_title').val('');
            if (typeof tinymce !== 'undefined' && tinymce.get('mpcrbm_new_term_answer_editor')) {
                tinymce.get('mpcrbm_new_term_answer_editor').setContent('');
            } else {
                $('#mpcrbm_new_term_answer_editor').val('');
            }
            $('#mpcrbm_new_term_panel').hide();
        });

        $(document).on('click', '#mpcrbm_save_new_term_btn', function(e) {
            e.preventDefault();
            let $btn = $(this);
            let title = $.trim($('#mpcrbm_new_term_title').val());
            let answer = (typeof tinymce !== 'undefined' && tinymce.get('mpcrbm_new_term_answer_editor'))
                ? tinymce.get('mpcrbm_new_term_answer_editor').getContent()
                : $('#mpcrbm_new_term_answer_editor').val();

            if (title === '' || answer === '') {
                alert('Please fill in both the title and the description.');
                return;
            }

            let originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            $.post(ajaxurl, {
                action: 'mpcrbm_save_term_and_condition',
                title: title,
                answer: answer,
                key: '',
                nonce: mpcrbm_admin_nonce.nonce
            }, function(response) {
                $btn.prop('disabled', false).text(originalText);

                if (!response.success) {
                    alert(typeof response.data === 'string' ? response.data : 'Could not save the Term & Condition.');
                    return;
                }

                let key = response.data.key;
                let savedTitle = response.data.title;

                // Drop the "no terms available yet" placeholder, if this was the first one.
                $('.mpcrbm-term-list p').remove();

                let $newItem = $(
                    '<div class="mpcrbm_faq_item mpcrbm-term-toggle-item" data-key="' + key + '">' +
                    '<span class="mpcrbm-faq-toggle-check"><i class="fas fa-check"></i></span>' +
                    '<div class="mpcrbm_faq_title"></div>' +
                    '<span class="mpcrbm-faq-toggle-status">Selected</span>' +
                    '</div>'
                );
                $newItem.attr('data-title', savedTitle);
                $newItem.find('.mpcrbm_faq_title').text(savedTitle);
                $newItem.prependTo('.mpcrbm-term-list');

                let $totalCount = $('#mpcrbm_term_total_count');
                if ($totalCount.length) {
                    $totalCount.text(parseInt($totalCount.text(), 10) + 1);
                }

                // Also mark it "added" to this car — reuses the toggle handler
                // above rather than duplicating its save-to-car AJAX logic.
                $newItem.trigger('click');

                $('#mpcrbm_new_term_title').val('');
                if (typeof tinymce !== 'undefined' && tinymce.get('mpcrbm_new_term_answer_editor')) {
                    tinymce.get('mpcrbm_new_term_answer_editor').setContent('');
                } else {
                    $('#mpcrbm_new_term_answer_editor').val('');
                }
                $('#mpcrbm_new_term_panel').hide();
            });
        });

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
