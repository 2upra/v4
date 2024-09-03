// Esta función se encargará de agregar los eventos a todos los botones de edición.
function botoneditarpost() {
    var editButtons = document.querySelectorAll('.edit-post-btn');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var postId = this.getAttribute('data-post-id');
            var contentElement = document.querySelector('.texto-posts[data-post-id="' + postId + '"]');
            var isEditable = contentElement.isContentEditable;

            contentElement.contentEditable = !isEditable;
            this.textContent = isEditable ? 'Editar Contenido' : 'Guardar Cambios';

            if (isEditable) {
                return;
            }

            this.addEventListener('click', function saveChanges() {
                guardarContenidoPost(postId, contentElement.innerHTML);
                this.removeEventListener('click', saveChanges);
                contentElement.contentEditable = false;
                this.textContent = 'Editar Contenido';
            });
        });
    });
}

// Esta función ahora recibe postId y content como argumentos.
function guardarContenidoPost(postId, content) {
    console.log('Iniciando la función guardarContenidoPost para el post ID:', postId);

    var tagsInput = document.querySelector('.tags-posts[data-post-id="' + postId + '"]');
    var tags = tagsInput ? tagsInput.value : '';
    console.log('Etiquetas a actualizar: ', tags);

    var data = new FormData();
    data.append('action', 'update_post_content');

    data.append('post_id', postId);
    data.append('tags', tags);
    data.append('content', content);

    // Nuevo log para verificar todos los datos del formulario antes de enviar
    for (var pair of data.entries()) {
        console.log(pair[0]+ ': ' + pair[1]);
    }

    console.log('Enviando solicitud a: ', ajax_params.ajax_url);
    fetch(ajax_params.ajax_url, {
        method: 'POST',
        credentials: 'same-origin',
        body: data
    })
    .then(response => {
        console.log('Respuesta recibida: ', response);
        if (!response.ok) {
            throw new Error('La respuesta no fue exitosa: ' + response.statusText);
        }
        return response.json();
    })
    .then(responseData => {
        console.log('Datos de respuesta: ', responseData);
        if (responseData.success) {
            console.log('Contenido actualizado con éxito: ', responseData.message);
        } else {
            console.error('Error al actualizar: ', responseData.message);
        }
    })
    .catch((error) => {
        console.error('Error en la solicitud fetch: ', error);
    });
}

