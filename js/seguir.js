function seguir() {
    // Manejar el clic en el botón "Seguir"
    function seguir_usuario(seguidor_id, seguido_id, button) {
        jQuery.ajax({
            type: "POST",
            url: ajax_params.ajax_url,
            data: {
                action: "seguir_usuario",
                seguidor_id: seguidor_id,
                seguido_id: seguido_id
            },
            success: function(response) {
                console.log(response);
                button.textContent = 'Siguiendo'; // Actualiza el texto del botón
            }
        });
    }

    function dejar_de_seguir_usuario(seguidor_id, seguido_id, button) {
        jQuery.ajax({
            type: "POST",
            url: ajax_params.ajax_url,
            data: {
                action: "dejar_de_seguir_usuario",
                seguidor_id: seguidor_id,
                seguido_id: seguido_id
            },
            success: function(response) {
                console.log(response);
                button.textContent = 'Seguir'; // Actualiza el texto del botón
            }
        });
    }

    document.querySelectorAll('.seguir').forEach(function(button) {
        button.addEventListener('click', function() {
            var seguidor_id = this.getAttribute('data-seguidor-id');
            var seguido_id = this.getAttribute('data-seguido-id');
            seguir_usuario(seguidor_id, seguido_id, this); // Pasa el botón como argumento
        });
    });

    document.querySelectorAll('.dejar-de-seguir').forEach(function(button) {
        button.addEventListener('click', function() {
            var seguidor_id = this.getAttribute('data-seguidor-id');
            var seguido_id = this.getAttribute('data-seguido-id');
            dejar_de_seguir_usuario(seguidor_id, seguido_id, this); // Pasa el botón como argumento
        });
    });
}
