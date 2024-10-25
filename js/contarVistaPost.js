function registrarVistas() {
    // Obtener todos los elementos de los posts
    var posts = document.querySelectorAll('.EDYQHV');

    // Crear un array para almacenar las visitas del usuario
    var vistasUsuario = JSON.parse(localStorage.getItem('vistasUsuario')) || {};

    // Recorrer cada post y verificar si se ha visto antes
    posts.forEach(function(post) {
        var postId = post.getAttribute('id-post');

        // Incrementar la vista del post para el usuario actual
        if (!vistasUsuario[postId]) {
            vistasUsuario[postId] = 1; // Primera vista
        } else {
            vistasUsuario[postId] += 1; // Ya ha visto antes, incrementar el contador
        }

        // Enviar los datos al servidor para actualizar las vistas globales
        actualizarVistasServidor(postId, vistasUsuario[postId]);
    });

    // Guardar las vistas del usuario en localStorage
    localStorage.setItem('vistasUsuario', JSON.stringify(vistasUsuario));
}

function actualizarVistasServidor(postId, vistasUsuario) {
    // Realizar una petición AJAX para enviar los datos al servidor
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=guardar_vistas&id_post=' + postId + '&vistas_usuario=' + vistasUsuario
    }).then(response => response.json()).then(data => {
        // Puedes gestionar la respuesta si es necesario
        console.log('Vistas actualizadas para el post ' + postId);
    });
}