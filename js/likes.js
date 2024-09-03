function like() {
    jQuery(document).ready(function($) {
        $(document).on('click', '.post-like-button, .like-count, .TJKQGJ', handleLike);
        $(document).on('dblclick', '.EDYQHV', handleLike);

        function handleLike() {
            var button = $(this).hasClass('post-like-button') ? $(this) : $(this).find('.post-like-button');
            var post_id = parseInt(button.attr('data-post_id'), 10);
            if (!post_id) {
                console.error("Post ID not found in button data");
                return;
            }
            if (button.data('requestRunning')) return;
            button.data('requestRunning', true);

            $.ajax({
                type: "POST",
                url: ajax_var_likes.url,
                data: {
                    action: "handle_post_like",
                    post_id: post_id,
                    nonce: ajax_var_likes.nonce,
                    like_state: !button.hasClass('liked')
                },
                success: function(response) {
                    var likes = parseInt(response, 10);
                    if (!isNaN(likes)) {
                        $('.post-like-button[data-post_id="' + post_id + '"]').each(function() {
                            $(this).siblings('.like-count').text(likes);
                            $(this).toggleClass('liked');
                            $(this).closest('.social-post').find('.audio-container').attr('data-liked', $(this).hasClass('liked'));
                        });
                        showHeartAnimation(button.closest('.EDYQHV'));
                    } else {
                        console.error('Unexpected response from server:', response);
                    }
                },
                error: function(error) {
                    console.error("AJAX Error:", error.status, error.statusText);
                },
                complete: function() {
                    button.data('requestRunning', false);
                    button.data('canToggleLike', !button.hasClass('liked'));
                    if (button.hasClass('liked')) {
                        setTimeout(function() {
                            button.data('canToggleLike', true);
                        }, 500);
                    }
                }
            });
        }

        function showHeartAnimation(postContent) {
            var heart = $('<div class="heart-animation">❤</div>').css({
                position: 'absolute',
                zIndex: '999',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%) scale(1)',
                fontSize: '4rem',
                color: 'red',
                opacity: 0,
                pointerEvents: 'none'
            });
            postContent.css('position', 'relative').append(heart);
            heart.animate({opacity: 1, fontSize: '6rem'}, 500).animate({opacity: 0, fontSize: '4rem'}, 500, function() {
                $(this).remove();
            });
        }
    });
}