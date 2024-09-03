function comentarios() {
    jQuery('#commentform').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'mytheme_handle_comment'); // Añade esto

        console.log('Datos del formulario serializados: ', formData);

        jQuery.ajax({
            type: 'POST',
            url: ajax_var.url,
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function() {
                jQuery('#comment-status').text('Enviando comentario...');
            },
            success: function(response) {
                console.log('Respuesta del servidor: ', response);
                if(response.success) {
                    jQuery('#comments .comment-list').append(response.data.comment_html);
                    jQuery('#comment-status').text('¡Comentario publicado!');
                } else {
                    jQuery('#comment-status').text(response.data.error);
                }

            jQuery('#commentform').get(0).reset();

            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error en la solicitud AJAX:', textStatus, errorThrown);
                jQuery('#comment-status').text('Ocurrió un error al enviar el comentario. Error: ' + textStatus);
            }
        }).fail(function(jqXHR, textStatus, error) {
            console.error("Error en la petición: ", textStatus, error, "Detalles: ", jqXHR.responseText);
        });
    });
}
