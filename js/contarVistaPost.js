function registrarVistas() {
    // Seleccionar todos los posts que aún no se han registrado.
    const posts = document.querySelectorAll('.EDYQHV:not([data-registrado="true"])');

    // Añadir el listener de clic para registrar la vista solo cuando se hace clic en el post.
    posts.forEach((post) => {
        post.addEventListener('click', (event) => {
            // Evitar registrar la vista si ya se registró.
            if (post.getAttribute('data-registrado') === 'true') return;

            const postId = post.getAttribute('id-post');

            // Registrar la vista inmediatamente cuando se hace clic.
            actualizarVistasServidor(postId);

            // Marcar el post como registrado para evitar múltiples registros.
            post.setAttribute('data-registrado', 'true');
        });
    });
}

function actualizarVistasServidor(postId) {
    // Petición AJAX para enviar la información de la vista al servidor.
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=guardar_vistas&id_post=' + postId
    }).then(response => response.json()).then(data => {
        //console.log('Vistas actualizadas para el post ' + postId);
    });
}