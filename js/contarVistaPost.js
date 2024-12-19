function registrarVistas() {
    const feed = document.querySelector('.social-post-list[data-filtro="sample"][data-tab-id="Feed"]');
    const posts = document.querySelectorAll('.EDYQHV:not([data-registrado="true"])');

    if (feed) {
        const postsFeed = feed.querySelectorAll('.EDYQHV:not([data-registrado="true"])');
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    let timer = setTimeout(() => {
                        const post = entry.target;
                        const postId = post.getAttribute('id-post');
                        actualizarVistasServidor(postId);
                        post.setAttribute('data-registrado', 'true');
                        observer.unobserve(post); // Dejar de observar una vez registrado
                    }, 5000); // 5 segundos

                    // Limpiar el timeout si el elemento deja de ser observado antes de 5 segundos
                    entry.target.dataset.visibilityTimer = timer;
                } else {
                    if (entry.target.dataset.visibilityTimer) {
                        clearTimeout(entry.target.dataset.visibilityTimer);
                        delete entry.target.dataset.visibilityTimer;
                    }
                }
            });
        }, { threshold: 0.5 }); // Observar cuando al menos el 50% del elemento es visible

        postsFeed.forEach(post => {
            observer.observe(post);
        });
    }

    // Registrar vistas por clic para posts fuera del feed
    posts.forEach((post) => {
        // Verificar si el post no está dentro del feed para evitar doble registro
        if (!feed || !feed.contains(post)) {
            post.addEventListener('click', (event) => {
                if (post.getAttribute('data-registrado') === 'true') return;

                const postId = post.getAttribute('id-post');
                actualizarVistasServidor(postId);
                post.setAttribute('data-registrado', 'true');
            });
        }
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
        console.log('Vistas actualizadas para el post ' + postId);
    });
}

