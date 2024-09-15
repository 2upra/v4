function like() {
    document.addEventListener('DOMContentLoaded', function() {
        const likeButtons = document.querySelectorAll('.post-like-button, .like-count, .TJKQGJ');
        const posts = document.querySelectorAll('.EDYQHV');

        likeButtons.forEach(button => {
            button.addEventListener('click', handleLike);
        });

        posts.forEach(post => {
            post.addEventListener('dblclick', handleLike);
        });

        async function handleLike(event) {
            let button;
            if (event.type === 'click') {
                button = event.target.classList.contains('post-like-button') 
                    ? event.target 
                    : event.target.closest('.post-like-button');
            } else if (event.type === 'dblclick') {
                button = event.currentTarget.querySelector('.post-like-button');
            }

            if (!button) return;

            const post_id = parseInt(button.getAttribute('data-post_id'), 10);
            if (!post_id) {
                console.error("Post ID not found in button data");
                return;
            }

            if (button.dataset.requestRunning === "true") return;
            button.dataset.requestRunning = "true";

            const data = {
                post_id: post_id,
                nonce: ajax_var_likes.nonce,
                like_state: !button.classList.contains('liked')
            };

            try {
                const response = await enviarAjax("handle_post_like", data);
                const likes = parseInt(response, 10);

                if (!isNaN(likes)) {
                    document.querySelectorAll(`.post-like-button[data-post_id="${post_id}"]`).forEach(button => {
                        const likeCount = button.closest('.social-post').querySelector('.like-count');
                        if (likeCount) {
                            likeCount.textContent = likes;
                        }
                        button.classList.toggle('liked');
                        const audioContainer = button.closest('.social-post').querySelector('.audio-container');
                        if (audioContainer) {
                            audioContainer.setAttribute('data-liked', button.classList.contains('liked'));
                        }
                    });
                    showHeartAnimation(button.closest('.EDYQHV'));
                } else {
                    console.error('Unexpected response from server:', response);
                }
            } catch (error) {
                console.error("AJAX Error:", error);
            } finally {
                button.dataset.requestRunning = "false";
                button.dataset.canToggleLike = !button.classList.contains('liked');
                if (button.classList.contains('liked')) {
                    setTimeout(() => {
                        button.dataset.canToggleLike = "true";
                    }, 500);
                }
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

            heart.animate([
                { opacity: 1, fontSize: '6rem' },
                { opacity: 0, fontSize: '4rem' }
            ], {
                duration: animationDuration,
                easing: 'ease-out',
                fill: 'forwards'
            }).onfinish = function() {
                heart.remove();
            };
        }
    });
}