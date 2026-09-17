jQuery(document).ready(function($){
    $('#mpcrbm_review_form').on('submit', function(e){
        e.preventDefault();

        let post_id = $('input[name="post_id"]').val();
        let author  = $('input[name="author"]').val();
        let email   = $('input[name="email"]').val();
        let comment = $('textarea[name="comment"]').val();
        let rating  = $('input[name="rating"]:checked').val();
        let nonce   = $('input[name="mpcrbm_review_nonce"]').val();

        $.ajax({
            type: 'POST',
            url: mpcrbm_ajax_url,
            data: {
                action  : 'mpcrbm_review_save',
                post_id : post_id,
                author  : author,
                email   : email,
                comment : comment,
                rating  : rating,
                mpcrbm_review_nonce: nonce
            },
            beforeSend: function(){
                $('#mpcrbm_review_message').html('Submitting...');
            },
            success: function(response){
                $('#mpcrbm_review_message').html(response);
                $('#mpcrbm_review_form')[0].reset();
                location.reload();
            },
            error: function(){
                $('#mpcrbm_review_message').html('Something went wrong.');
            }
        });
    });


    let reviewsPerClick = 3; // number of reviews to show per click
    let $reviews = $('#mpcrbm_review_list .mpcrbm_review_card');
    $reviews.hide();
    $reviews.slice(0, reviewsPerClick).show();

    if($reviews.length > reviewsPerClick){
        $('#mpcrbm_review_load_more').show();
    }
    $('#mpcrbm_review_load_more').on('click', function(){
        // Show next 5 hidden reviews
        let hiddenReviews = $reviews.filter(':hidden');
        hiddenReviews.slice(0, reviewsPerClick).fadeIn();

        // If no more hidden reviews, hide the button
        if(hiddenReviews.length <= reviewsPerClick){
            $(this).fadeOut();
        }
    });

    $(document).on('click', '.mpcrbm_review_delete_btn', function(){
        if(!confirm('Are you sure you want to delete this review?')) return;

        let card = $(this).closest('.mpcrbm_review_card');
        let comment_id = card.data('comment-id');

        $.ajax({
            type: 'POST',
            url: mpcrbm_ajax_url,
            data: {
                action: 'mpcrbm_review_delete',
                comment_id: comment_id,
                mpcrbm_review_nonce: $('input[name="mpcrbm_review_nonce"]').val()
            },
            success: function(response){
                if(response.success){
                    card.fadeOut(function(){ $(this).remove(); });
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Edit review
    $(document).on('click', '.mpcrbm_review_edit_btn', function(){
        let card = $(this).closest('.mpcrbm_review_card');
        let comment_id = card.data('comment-id');
        let current_content = card.find('.mpcrbm_review_content').text();

        let new_content = prompt("Edit your review:", current_content);
        if(new_content === null) return; // Cancel

        $.ajax({
            type: 'POST',
            url: mpcrbm_ajax_url,
            data: {
                action: 'mpcrbm_review_edit',
                comment_id: comment_id,
                comment_content: new_content,
                mpcrbm_review_nonce: $('input[name="mpcrbm_review_nonce"]').val()
            },
            success: function(response){
                if(response.success){
                    card.find('.mpcrbm_review_content').text(new_content);
                } else {
                    alert(response.data);
                }
            }
        });
    });

});