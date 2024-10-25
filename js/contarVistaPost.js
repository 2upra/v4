function registrarVistas() {
    // Obtener todos los posts que aún no han sido procesados
    var posts = document.querySelectorAll('.EDYQHV:not([data-registrado="true"])');

    // Recorrer cada post y enviar la información al servidor
    posts.forEach(function(post) {
        var postId = post.getAttribute('id-post');

        // Enviar los datos al servidor para actualizar las vistas globales y del usuario
        actualizarVistasServidor(postId);

        // Marcar este post como procesado para no volver a registrar su vista
        post.setAttribute('data-registrado', 'true');
    });
}

function actualizarVistasServidor(postId) {
    // Realizar una petición AJAX para enviar los datos al servidor
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=guardar_vistas&id_post=' + postId
    }).then(response => response.json()).then(data => {
        // Puedes gestionar la respuesta si es necesario
        console.log('Vistas actualizadas para el post ' + postId);
    });
}