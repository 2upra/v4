function borrarcomentario() {

    var allForms = document.querySelectorAll('form');
    allForms.forEach(function(form) {
        form.enctype = 'multipart/form-data';
    });

    var updateHiddenTabId = function() {
        var activeTab = document.querySelector('.tab.active');
        if (activeTab) {
            var postId = activeTab.getAttribute('data-post-id');
            document.querySelectorAll('input[name="tab_id"]').forEach(function(input) {
                input.value = postId;
            });
        }
    };

    document.querySelectorAll('.tab-links a').forEach(function(tabLink) {
        tabLink.addEventListener('click', function() {
            setTimeout(updateHiddenTabId, 100);
        });
    });

    document.querySelectorAll('.tab input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.closest('.tab').classList.contains('active')) {
                var label = document.querySelector('label[for="' + this.id + '"]');
                if (label) { // Verifica si el label existe
                    if (this.files.length > 0) {
                        label.style.color = 'green'; // Cambia el color a verde
                    } else {
                        label.style.color = ''; // Restablece el color original
                    }
                } else {
                    console.warn('Label for input with id "' + this.id + '" not found.');
                }
            }
        });
    });

    var commentForm = document.querySelector('#commentform');
    if (commentForm) {
        commentForm.addEventListener('submit', updateHiddenTabId);
    }

    // Esto se supone que manda el scroll para abajo
    var commentLists = document.querySelectorAll('.comment-list');
    commentLists.forEach(function(list) {
        list.scrollTop = list.scrollHeight;
    });

    document.querySelectorAll('.icon-bubble').forEach(function(icon) {
        icon.addEventListener('click', function() {
            var comentarios = icon.closest('.social-post').nextElementSibling;
            if (comentarios && comentarios.classList.contains('comentarios')) {
                // Establecer un valor predeterminado si display es vacío o no está definido
                if (!comentarios.style.display) {
                    comentarios.style.display = 'none';
                    console.log('Estableciendo valor predeterminado: none');
                }
                console.log('Comentarios estaban:', comentarios.style.display);
                comentarios.style.display = comentarios.style.display === 'none' ? 'block' : 'none';
                console.log('Comentarios ahora están:', comentarios.style.display);
                // Si los comentarios están visibles, hacer scroll al último comentario
                if (comentarios.style.display === 'block') {
                    comentarios.scrollTop = comentarios.scrollHeight;
                    console.log('Scroll al último comentario');
                }
            }
        });
    });

    jQuery('.delete-comment-button').on('click', function(e) {
        e.preventDefault();
        var comment_id = jQuery(this).data('comment_id');
        var nonce = ajax_var_borrar_comentario.nonce;

        if (confirm("¿Estás seguro de que quieres borrar este comentario?")) {
            jQuery.ajax({
                type: "POST",
                url: ajax_var_borrar_comentario.url,
                data: {
                    action: "delete_comment_by_user",
                    comment_id: comment_id,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Comentario eliminado.');
                        jQuery('#li-comment-' + comment_id).remove();
                    } else {
                        alert('No se pudo eliminar el comentario.');
                    }
                }
            });
        }
    });
}