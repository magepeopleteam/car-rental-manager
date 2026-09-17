jQuery(document).ready(function($){

    $(document).on('click', '.mpcrbm_car_list_grid_btn', function(){
        let wrap = $(this).closest('.mpcrbm_car_list_grid_wrapper');
        $(this).addClass('active');
        wrap.find('.mpcrbm_car_list_list_btn').removeClass('active');
        wrap.find('#mpcrbm_car_list_grid')
            .removeClass('mpcrbm_car_list_lists mpcrbm_car_list_list_view')
            .addClass('mpcrbm_car_list_grid mpcrbm_car_list_grid_view');
    });

    $(document).on('click', '.mpcrbm_car_list_list_btn', function(){
        let wrap = $(this).closest('.mpcrbm_car_list_grid_wrapper');
        $(this).addClass('active');
        wrap.find('.mpcrbm_car_list_grid_btn').removeClass('active');
        wrap.find('#mpcrbm_car_list_grid')
            .removeClass('mpcrbm_car_list_grid mpcrbm_car_list_grid_view')
            .addClass('mpcrbm_car_list_lists mpcrbm_car_list_list_view');
    });

    // AJAX "Load More" — clicking the button fetches the next page and
    // appends the cards to the grid in place, no page reload and no
    // replacing what's already shown. The wrapper's existing grid/list view
    // class is untouched, so whichever view mode is active stays active as
    // more cards load in.
    let mpcrbmCarListLoading = false;

    $(document).on('click', '.mpcrbm_car_list_loadmore_btn', function(){
        let $btn = $(this);
        if ( mpcrbmCarListLoading || $btn.is(':disabled') ) {
            return;
        }

        let wrap = $btn.closest('.mpcrbm_car_list_grid_wrapper');
        let grid = wrap.find('#mpcrbm_car_list_grid');
        let page = parseInt( $btn.data('page'), 10 ) || 1;

        mpcrbmCarListLoading = true;
        $btn.prop('disabled', true);
        $btn.find('.mpcrbm_car_list_loadmore_text').hide();
        $btn.find('.mpcrbm_car_list_loadmore_spinner').show();

        $.post( wrap.data('ajax'), {
            action:    'mpcrbm_car_list_page',
            nonce:     wrap.data('nonce'),
            page:      page,
            per_page:  wrap.data('per-page'),
            car_type:  wrap.data('car-type'),
            fuel_type: wrap.data('fuel-type'),
            brand:     wrap.data('brand')
        }, function( response ){
            if ( response && response.success ) {
                grid.append( response.data.html );
                if ( response.data.has_more ) {
                    $btn.data('page', response.data.next_page);
                } else {
                    $btn.closest('.mpcrbm_car_list_loadmore_wrap').remove();
                }
            }
        }).always(function(){
            mpcrbmCarListLoading = false;
            $btn.prop('disabled', false);
            $btn.find('.mpcrbm_car_list_loadmore_spinner').hide();
            $btn.find('.mpcrbm_car_list_loadmore_text').show();
        });
    });
});
