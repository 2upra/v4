function like() {
    let lastClickTime = 0;
    const clickDelay = 500; // 500 ms de retraso

    // Usar delegación de eventos
    document.addEventListener('click', function(event) {
        // Verifica si el clic fue en el contenedor del like o en sus hijos
        const likeContainer = event.target.closest('.TJKQGJ.botonlike');
        if (likeContainer) {
            // Busca el botón dentro del contenedor
            const likeButton = likeContainer.querySelector('.post-like-button');
            if (likeButton) {
                handleLike(event, likeButton);
            }
        }
    });

    async function handleLike(event, button) {
        event.preventDefault();
        const now = Date.now();
        if (now - lastClickTime < clickDelay) {
            return;
        }
        lastClickTime = now;
        const post_id = parseInt(button.dataset.post_id, 10);
        if (!post_id || button.dataset.requestRunning === 'true') {
            return;
        }
        // Comprobación de conexión
        if (!navigator.onLine) {
            alert('No hay conexión a internet. Por favor, verifica tu conexión e inténtalo de nuevo.');
            return;
        }
    
        button.dataset.requestRunning = 'true';
    
        const data = {
            post_id: post_id,
            like_state: !button.classList.contains('liked')
        };

        button.classList.toggle('liked');
    
        try {
            const response = await enviarAjax('like', data);
    
            if (response === 'not_logged_in') {
                alert('Debes estar logueado para dar like.');
                button.classList.toggle('liked'); // Revertir cambio visual
                return;
            } else if (response === 'invalid_nonce') {
                alert('Nonce inválido. Por favor, recarga la página e inténtalo de nuevo.');
                button.classList.toggle('liked'); // Revertir cambio visual
                return;
            } else if (response === 'missing_post_id') {
                alert('Error: no se recibió el ID del post.');
                button.classList.toggle('liked'); // Revertir cambio visual
                return;
            } else if (response === 'error') {
                alert('Hubo un error al procesar tu solicitud.');
                button.classList.toggle('liked'); // Revertir cambio visual
                return;
            }
    
            const likes = parseInt(response, 10);
            if (!isNaN(likes)) {
                updateLikeUI(button, likes);
                showHeartAnimation(button.closest('.EDYQHV'));
            } else {
                button.classList.toggle('liked'); // Revertir cambio visual
            }
        } catch (error) {
            alert('Hubo un error al procesar tu solicitud. Por favor, inténtalo de nuevo.');
            button.classList.toggle('liked'); // Revertir cambio visual
        } finally {
            button.dataset.requestRunning = 'false';
        }
    }

    function updateLikeUI(button, likes) {
        const post = button.closest('.TJKQGJ');
        if (!post) {
            return;
        }
        const likeCount = post.querySelector('.like-count');
        if (!likeCount) {
            return;
        }

        likeCount.textContent = likes;
    }

    function showHeartAnimation(postContent) {
        if (!postContent) {
            return;
        }

        const heart = document.createElement('div');
        heart.className = 'heart-animation';
        heart.textContent = '❤';
        Object.assign(heart.style, {
            position: 'absolute',
            zIndex: '999',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%) scale(1)',
            fontSize: '4rem',
            color: 'red',
            opacity: 0,
            pointerEvents: 'none'
        });

        postContent.style.position = 'relative';
        postContent.appendChild(heart);

        const animationDuration = 500;

        heart.animate(
            [
                {opacity: 1, fontSize: '6rem'},
                {opacity: 0, fontSize: '4rem'}
            ],
            {
                duration: animationDuration,
                easing: 'ease-out',
                fill: 'forwards'
            }
        ).onfinish = function () {
            heart.remove();
        };
    }
}