function registrarVistas() {
    // Seleccionar todos los posts que aún no se han registrado
    const posts = document.querySelectorAll('.EDYQHV:not([data-registrado="true"])');

    // Configuración de IntersectionObserver
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const post = entry.target;
                const postId = post.getAttribute('id-post');

                // Comienza un temporizador de 5 segundos
                const timer = setTimeout(() => {
                    // Enviar datos al servidor
                    actualizarVistasServidor(postId);
                    
                    // Marcar el post como registrado y detener la observación
                    post.setAttribute('data-registrado', 'true');
                    observer.unobserve(post);
                }, 5000); // 5 segundos en milisegundos

                // Almacenar el ID del temporizador en el post para cancelarlo si es necesario
                post.timerId = timer;
            } else {
                // Si el post sale de la vista antes de los 5 segundos, cancela el temporizador
                clearTimeout(entry.target.timerId);
            }
        });
    }, {
        threshold: 0.7
    });

    // Añadir un event listener para registrar la vista si el usuario hace clic en el post
    posts.forEach((post) => {
        // Observar el post para detectar si está visible
        observer.observe(post);

        // Agregar el event listener para el clic
        post.addEventListener('click', () => {
            // Si ya está registrado, no hacer nada
            if (post.getAttribute('data-registrado') === 'true') return;

            const postId = post.getAttribute('id-post');

            // Enviar datos al servidor inmediatamente
            actualizarVistasServidor(postId);

            // Marcar el post como registrado y detener la observación
            post.setAttribute('data-registrado', 'true');
            observer.unobserve(post);

            // Cancelar cualquier temporizador pendiente
            clearTimeout(post.timerId);
        });
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