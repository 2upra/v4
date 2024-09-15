function like() {
    const likeButtons = document.querySelectorAll('.post-like-button');

    likeButtons.forEach(button => {
        button.addEventListener('click', handleLike);
    });

    async function handleLike(event) {
        const button = event.currentTarget;
        const post_id = parseInt(button.dataset.post_id, 10);
        
        if (!post_id || button.dataset.requestRunning === 'true') {
            return;
        }
    
        button.dataset.requestRunning = 'true';
    
        const data = {
            post_id: post_id,
            like_state: !button.classList.contains('liked')
        };
    
        try {
            const response = await enviarAjax('handle_post_like', data);
    
            if (response === 'not_logged_in') {
                alert('Debes estar logueado para dar like.');
                return;
            } else if (response === 'invalid_nonce') {
                alert('Nonce inválido. Por favor, recarga la página e inténtalo de nuevo.');
                return;
            } else if (response === 'missing_post_id') {
                alert('Error: no se recibió el ID del post.');
                return;
            } else if (response === 'error') {
                alert('Hubo un error al procesar tu solicitud.');
                return;
            }
    
            const likes = parseInt(response, 10);
            if (!isNaN(likes)) {
                updateLikeUI(button, likes);
                showHeartAnimation(button.closest('.EDYQHV'));
            }
        } catch (error) {
            // Manejar errores si es necesario
        } finally {
            button.dataset.requestRunning = 'false';
        }
    }

    function updateLikeUI(button, likes) {
        const post = button.closest('.TJKQGJ');
        const likeCount = post.querySelector('.like-count');

        button.classList.toggle('liked');
        if (likeCount) {
            likeCount.textContent = likes;
        }
    }

    function showHeartAnimation(postContent) {
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