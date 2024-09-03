function editarcomentario() {

        const editButtons = document.querySelectorAll('.edit-comment-button'); 

        editButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const commentId = this.dataset.commentId;
                const commentBody = document.querySelector('#comment-' + commentId + ' .comment-text');

                const textArea = document.createElement('textarea');
                textArea.value = commentBody.innerText;

                const saveButton = document.createElement('button');
                saveButton.innerText = 'Guardar';
                saveButton.addEventListener('click', function() {
                    const editedContent = textArea.value;

                    fetch(editar_comentario.ajax_url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=save_edited_comment&comment_ID=${commentId}&comment_content=${encodeURIComponent(editedContent)}&nonce=${editar_comentario.nonce}`
                    })

                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                        commentBody.innerHTML = editedContent; // Actualiza el comentario en la página
                    } else {
                        alert('Error al guardar el comentario.');
                    }
                })
                    .catch(error => console.error('Error:', error));
                });

                commentBody.innerHTML = ''; // Limpia el contenido actual
                commentBody.appendChild(textArea);
                commentBody.appendChild(saveButton); // Añade el botón de guardar
            });
        });
    }

