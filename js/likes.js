function like() {
    let lastClickTime = 0;
    const clickDelay = 500; // 500 ms de retraso

    // Usar delegación de eventos para manejar clics en los botones de like
    document.addEventListener('click', function(event) {
        const button = event.target.closest('[data-like_type][data-post_id]');
        if (button) {
            handleLike(event, button);
        }
    });

    async function handleLike(event, button) {
        event.preventDefault();
        const now = Date.now();
        if (now - lastClickTime < clickDelay) {
            return;
        }
        lastClickTime = now;

        const postId = parseInt(button.dataset.post_id, 10);
        const likeType = button.dataset.like_type;

        if (!postId || !likeType || button.dataset.requestRunning === 'true') {
            return;
        }

        if (!navigator.onLine) {
            alert('No hay conexión a internet. Por favor, verifica tu conexión e inténtalo de nuevo.');
            return;
        }

        button.dataset.requestRunning = 'true';

        // Determinar si se está "dando like" o "quitando like"
        const addingLike = !button.classList.contains(likeType + '-active');

        // Actualizar la UI inmediatamente
        updateLikeUI(button, addingLike, likeType);

        const data = {
            post_id: postId,
            like_type: likeType,
            like_state: addingLike // true para "dar like", false para "quitar like"
        };

        try {
            const response = await enviarAjax('like', data);

            if (response === 'not_logged_in') {
                alert('Debes estar logueado para realizar esta acción.');
                revertLikeUI(button, !addingLike, likeType); // Revertir al estado anterior
            } else if (response === 'invalid_nonce') {
                alert('Nonce inválido. Por favor, recarga la página e inténtalo de nuevo.');
                revertLikeUI(button, !addingLike, likeType);
            } else if (response === 'error_like_type') {
                alert('Tipo de like inválido.');
                revertLikeUI(button, !addingLike, likeType);
            } else if (response === 'error' || response === 'missing_post_id') {
                alert('Hubo un error al procesar tu solicitud.');
                revertLikeUI(button, !addingLike, likeType);
            } else {
                // Si la respuesta del servidor es exitosa, no necesitamos hacer nada con el contador
                // ya que lo actualizamos inmediatamente.
                // Aquí podrías procesar alguna información adicional del servidor si fuera necesario.
            }
        } catch (error) {
            console.error("Error en la solicitud AJAX:", error);
            alert('Hubo un error al procesar tu solicitud. Por favor, inténtalo de nuevo.');
            revertLikeUI(button, !addingLike, likeType);
        } finally {
            button.dataset.requestRunning = 'false';
        }
    }

    function updateLikeUI(button, addingLike, likeType) {
        const container = button.closest('.botonlike-container');
        if (!container) return;

        const countSpan = container.querySelector(`.${likeType}-count`);
        if (!countSpan) return;

        const activeButtonClass = likeType + '-active';

        if (addingLike) {
            button.classList.add(activeButtonClass);
            countSpan.textContent = parseInt(countSpan.textContent || '0', 10) + 1;
        } else {
            button.classList.remove(activeButtonClass);
            countSpan.textContent = Math.max(0, parseInt(countSpan.textContent || '0', 10) - 1);
        }
    }

    function revertLikeUI(button, addingLike, likeType) {
        const container = button.closest('.botonlike-container');
        if (!container) return;

        const countSpan = container.querySelector(`.${likeType}-count`);
        if (!countSpan) return;

        const activeButtonClass = likeType + '-active';

        if (addingLike) {
            button.classList.add(activeButtonClass);
            countSpan.textContent = parseInt(countSpan.textContent || '0', 10) + 1;
        } else {
            button.classList.remove(activeButtonClass);
            countSpan.textContent = Math.max(0, parseInt(countSpan.textContent || '0', 10) - 1);
        }
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
