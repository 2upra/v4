function seguir() {
    async function manejarSeguimiento(seguidor_id, seguido_id, esDejarDeSeguir) {
        // Confirmar antes de dejar de seguir
        if (esDejarDeSeguir) {
            if (!confirm('¿Estás seguro de que quieres dejar de seguir a este usuario?')) {
                return;
            }
        }

        const action = esDejarDeSeguir ? 'dejar_de_seguir_usuario' : 'seguir_usuario';
        const response = await enviarAjax(action, {
            seguidor_id: seguidor_id,
            seguido_id: seguido_id
        });

        if (response.success) {
            // Actualizar todos los botones del mismo usuario
            const botones = document.querySelectorAll(`.seguir[data-seguido-id="${seguido_id}"], .dejar-de-seguir[data-seguido-id="${seguido_id}"]`);
            
            botones.forEach(boton => {
                if (!esDejarDeSeguir) { // Si estábamos siguiendo, ahora mostrar botón de dejar de seguir
                    // Cambiar a botón de dejar de seguir
                    boton.innerHTML = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM5 7.25H4.25V8.75H5H11H11.75V7.25H11H5Z" fill="currentColor"></path></svg>`;
                    boton.classList.remove('seguir');
                    boton.classList.add('dejar-de-seguir');
                } else { // Si estábamos dejando de seguir, ahora mostrar botón de seguir
                    // Cambiar a botón de seguir
                    boton.innerHTML = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM8.75 4.25V5V7.25H11H11.75V8.75H11H8.75V11V11.75L7.25 11.75V11V8.75H5H4.25V7.25H5H7.25V5V4.25H8.75Z" fill="currentColor"></path></svg>`;
                    boton.classList.remove('dejar-de-seguir');
                    boton.classList.add('seguir');
                }
            });
        }
    }

    // Agregar event listeners a todos los botones
    document.querySelectorAll('.seguir, .dejar-de-seguir').forEach(function(button) {
        button.addEventListener('click', async function() {
            const seguidor_id = this.getAttribute('data-seguidor-id');
            const seguido_id = this.getAttribute('data-seguido-id');
            const esDejarDeSeguir = this.classList.contains('dejar-de-seguir');

            await manejarSeguimiento(seguidor_id, seguido_id, esDejarDeSeguir);
        });
    });
}