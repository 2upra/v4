function like() {
    const likeButtons = document.querySelectorAll('.post-like-button');

    likeButtons.forEach(button => {
        button.addEventListener('click', handleLike);
    });

    async function handleLike(event) {
        const button = event.currentTarget;
        const post_id = parseInt(button.dataset.post_id, 10);
        const nonce = button.dataset.nonce;

        console.log('Button clicked for post ID:', post_id);

        if (!post_id || button.dataset.requestRunning === 'true') {
            console.error('Invalid Post ID or request already running');
            return;
        }

        button.dataset.requestRunning = 'true';

        const data = {
            post_id: post_id,
            nonce: nonce,
            like_state: !button.classList.contains('liked')
        };

        try {
            console.log('Sending AJAX request with data:', data);
            const response = await enviarAjax('handle_post_like', data);
            console.log('Response received:', response);

            const likes = parseInt(response, 10);
            if (!isNaN(likes)) {
                updateLikeUI(button, likes);
                showHeartAnimation(button.closest('.EDYQHV'));
            } else {
                console.error('Unexpected response:', response);
            }
        } catch (error) {
            console.error('AJAX Error:', error);
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

        console.log('Updated UI for post ID:', button.dataset.post_id, 'New likes count:', likes);
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
