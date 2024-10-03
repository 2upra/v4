function notificacioncolab() {
    jQuery(document).ready(function($) {
        $('.send-notification').click(function() {
            var postId = $(this).data('post-id');
            var artistId = $(this).data('artist-id');
            var collaboratorId = $(this).data('collaborator-id');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'send_notification_to_artist_collaborator', 
                    postId: postId,
                    artistId: artistId,
                    collaboratorId: collaboratorId
                },
                success: function(response) {
                    alert('Notificación enviada');
                }
            });
        });
    });
}