function registrarVistas() {
    const filtros = ['sample', 'samplelist', 'feed'];
    let logs = '';

    filtros.forEach(filtro => {
        const feed = document.querySelector(`.social-post-list[data-filtro="${filtro}"]`);
        
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
                            logs += `Post con ID ${postId} en el filtro ${filtro} registrado por observador. `;
                            observer.unobserve(post);
                        }, 5000);

                        entry.target.dataset.visibilityTimer = timer;
                    } else {
                        if (entry.target.dataset.visibilityTimer) {
                            clearTimeout(entry.target.dataset.visibilityTimer);
                            delete entry.target.dataset.visibilityTimer;
                        }
                    }
                });
            }, { threshold: 0.5 });

            postsFeed.forEach(post => {
                observer.observe(post);
            });
        }
    });

    // Registrar vistas por clic para posts en los filtros especificados
    const posts = document.querySelectorAll('.EDYQHV:not([data-registrado="true"])');
    posts.forEach(post => {
        const parentFeed = post.closest('.social-post-list');
        if (parentFeed && filtros.includes(parentFeed.getAttribute('data-filtro'))) {
            post.addEventListener('click', () => {
                if (post.getAttribute('data-registrado') === 'true') return;

                const postId = post.getAttribute('id-post');
                actualizarVistasServidor(postId);
                post.setAttribute('data-registrado', 'true');
                logs += `Post con ID ${postId} registrado por clic. `;
            });
        }
    });

    console.log(logs);
}

function actualizarVistasServidor(postId) {
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