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
                // Cambiar ícono en lugar de texto
                button.innerHTML = '<i class="icon-restar"></i>'; // Actualiza el icono
                button.classList.remove('seguir');
                button.classList.add('dejar-de-seguir'); // Cambia la clase a 'dejar-de-seguir'
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
                // Cambiar ícono en lugar de texto
                button.innerHTML = '<i class="icon-sumar"></i>'; // Actualiza el icono
                button.classList.remove('dejar-de-seguir');
                button.classList.add('seguir'); // Cambia la clase a 'seguir'
            }
        });
    }

    document.querySelectorAll('.seguir, .dejar-de-seguir').forEach(function(button) {
        button.addEventListener('click', function() {
            var seguidor_id = this.getAttribute('data-seguidor-id');
            var seguido_id = this.getAttribute('data-seguido-id');

            if (this.classList.contains('seguir')) {
                seguir_usuario(seguidor_id, seguido_id, this); // Llama a seguir
            } else {
                dejar_de_seguir_usuario(seguidor_id, seguido_id, this); // Llama a dejar de seguir
            }
        });
    });
}
