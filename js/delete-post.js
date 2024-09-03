function deletepost() {
    jQuery('.delete-post-button').on('click', function() {
        var post_id = jQuery(this).data('post_id');
        var nonce = ajax_var_delete.nonce; // Cambiado a ajax_var_delete para coincidir con la clave definida en PHP

        if (confirm("¿Estás seguro de que quieres borrar esta publicación?")) {
            jQuery.ajax({
                type: "POST",
                url: ajax_var_delete.url, // Correcto, usando ajax_var_delete.url
                data: {
                    action: "delete_post_by_user",
                    post_id: post_id,
                    nonce: nonce // Ahora correctamente usando ajax_var_delete.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Publicación eliminada.');
                        //ELIMINIACION VISUAL
                        jQuery('button.delete-post-button[data-post_id="' + post_id + '"]').closest('li.social-post').next('li').remove(); // Elimina el formulario de respuesta
                        jQuery('button.delete-post-button[data-post_id="' + post_id + '"]').closest('li.social-post').remove(); // Elimina la publicación y sus comentarios
                    } else {
                        alert('No se pudo eliminar la publicación.');
                    }
                },
                error: function(error) {
                    console.error("Error al eliminar la publicación:", error);
                }
            });
        }
    });
}

