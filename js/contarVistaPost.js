function registrarVistas() {
    const posts = document.querySelectorAll('.EDYQHV:not([data-registrado="true"])');
    
    // Configuración de IntersectionObserver
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const post = entry.target;
                const postId = post.getAttribute('id-post');

                // Comienza un temporizador de 10 segundos
                const timer = setTimeout(() => {
                    // Enviar datos al servidor
                    actualizarVistasServidor(postId);
                    
                    // Marcar el post como registrado y detener la observación
                    post.setAttribute('data-registrado', 'true');
                    observer.unobserve(post);
                }, 5000); // 5 segundos en milisegundos

                // Cancela el temporizador si el post sale de la vista antes de tiempo
                post.timerId = timer;
            } else {
                // Si el post sale de la vista antes de los 5 segundos, cancela el temporizador
                clearTimeout(entry.target.timerId);
            }
        });
    }, {
        threshold: 1.0 // 1.0 asegura que el post esté completamente visible en pantalla
    });

    // Observar cada post
    posts.forEach((post) => observer.observe(post));
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