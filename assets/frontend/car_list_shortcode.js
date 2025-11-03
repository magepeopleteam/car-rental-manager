jQuery(document).ready(function($){

    let parent = $('.mpcrbm_car_list_grid_wrapper');

    $('.mpcrbm_car_list_grid_btn').on('click', function(){
        $(this).addClass('active');
        $('.mpcrbm_car_list_list_btn').removeClass('active');
        $('.mpcrbm_car_list_grid').removeClass('mpcrbm_car_list_list_view').addClass('mpcrbm_car_list_grid_view');

        parent.find('.mpcrbm_car_list_list_btn').removeClass('active');
        parent.find('#mpcrbm_car_list_grid').removeClass('mpcrbm_car_list_lists').addClass('mpcrbm_car_list_grid');
        parent.find('#mpcrbm_car_list_grid').removeClass('mpcrbm_car_list_list_view').addClass('mpcrbm_car_list_grid_view');

    });
    $('.mpcrbm_car_list_list_btn').on('click', function(){
        $(this).addClass('active');
        parent.find('.mpcrbm_car_list_grid_btn').removeClass('active');
        parent.find('#mpcrbm_car_list_grid').removeClass('mpcrbm_car_list_grid').addClass('mpcrbm_car_list_lists');
        parent.find('#mpcrbm_car_list_grid').removeClass('mpcrbm_car_list_grid_view').addClass('mpcrbm_car_list_list_view');

    });
});