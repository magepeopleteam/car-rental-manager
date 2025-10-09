jQuery(document).ready(function($) {

    // Load default tab content
    let currentType = 'mpcrbm_car_type';
    loadTaxonomyData(currentType);

    // Switch tab
    $('.mpcrbm_taxonomies_tab').on('click', function() {
        $('.mpcrbm_taxonomies_tab').removeClass('active');
        $(this).addClass('active');
        currentType = $(this).data('target');
        loadTaxonomyData(currentType);
    });

    // Add new button
    $('.mpcrbm_taxonomies_add_btn').on('click', function() {
        $('.mpcrbm_taxonomies_popup_overlay').fadeIn();
    });

    // Cancel button
    $('.mpcrbm_taxonomies_cancel_btn').on('click', function() {
        $('.mpcrbm_taxonomies_popup_overlay').fadeOut();
    });

    // Save new taxonomy (AJAX)
    $('.mpcrbm_taxonomies_save_btn').on('click', function() {
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
            success: function(response) {
                alert(response.data.message);
                $('.mpcrbm_taxonomies_popup_overlay').fadeOut();
                loadTaxonomyData( currentType );
            }
        });
    });

    // Search filter
    $('.mpcrbm_taxonomies_search').on('input', function() {
        let query = $(this).val().toLowerCase();
        $('#mpcrbm_taxonomies_holder .mpcrbm_item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(query) > -1);
        });
    });

    // Load taxonomy data
    function loadTaxonomyData(type) {
        $('#mpcrbm_taxonomies_holder').html('<p>Loading...</p>');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mpcrbm_load_taxonomies',
                taxonomy_type: type
            },
            success: function(response) {
                $('#mpcrbm_taxonomies_holder').html(response.data.html);
            }
        });
    }


    // const ajaxurl = mpcrbm_admin.ajax_url;
    // const nonce = mpcrbm_admin.nonce;
    const nonce = '';

    // Hover effect to show buttons
    $(document).on('mouseenter', '.mpcrbm_taxonomy_item', function(){
        $(this).find('.mpcrbm_taxonomy_actions').fadeIn(150);
    }).on('mouseleave', '.mpcrbm_taxonomy_item', function(){
        $(this).find('.mpcrbm_taxonomy_actions').fadeOut(150);
    });

    // Edit button click
    $(document).on('click', '.mpcrbm_edit_taxonomy', function(){
        let item = $(this).closest('.mpcrbm_taxonomy_item');
        let id = item.data('term-id');
        let type = item.data('type');
        let name = item.find('strong').text();
        let desc = item.find('small').text();

        // Simple popup (you can style it)
        let popup1 = `
            <div class="mpcrbm_popup">
                <div class="mpcrbm_popup_inner">
                    <h3>Edit Taxonomy</h3>
                    <input type="text" id="edit_name" value="${name}" />
                    <textarea id="edit_description">${desc}</textarea>
                    <button class="button button-primary mpcrbm_update_taxonomy" data-id="${id}" data-type="${type}">Update</button>
                    <button class="button mpcrbm_close_popup">Cancel</button>
                </div>
            </div>`;
        let popup = `
            <div class="mpcrbm_popup">
                <div class="mpcrbm_popup_inner">
                    <h3>Edit Taxonomy</h3>
                    <label>Name:</label>
                    <input type="text" id="edit_name" placeholder="Enter name" value="${name}">
                    <label>Slug:</label>
                    <input type="text" id="mpcrbm_taxonomies_slug" placeholder="Optional slug">
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
    $(document).on('click', '.mpcrbm_update_taxonomy', function(){
        let term_id = $(this).data('id');
        let type = $(this).data('type');
        let name = $('#edit_name').val();
        let desc = $('#edit_description').val();

        $.post(ajaxurl, {
            action: 'mpcrbm_update_taxonomy',
            security: nonce,
            term_id,
            taxonomy_type: type,
            name,
            description: desc
        }, function(res){
            alert(res.data.message);
            $('.mpcrbm_popup').remove();
            // Reload taxonomy list
        });
    });

    // Delete button click
    $(document).on('click', '.mpcrbm_delete_taxonomy', function(){
        let item = $(this).closest('.mpcrbm_taxonomy_item');
        let id = item.data('term-id');
        let type = item.data('type');

        if(confirm('Are you sure you want to delete this taxonomy?')) {
            $.post(ajaxurl, {
                action: 'mpcrbm_delete_taxonomy',
                security: nonce,
                term_id: id,
                taxonomy_type: type
            }, function(res){
                alert(res.data.message);
                if(res.success) item.remove();
            });
        }
    });

    // Close popup
    $(document).on('click', '.mpcrbm_close_popup', function(){
        $('.mpcrbm_popup').remove();
    });


});
