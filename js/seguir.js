function seguir() {
    // SVGs para los botones
    const SVG_SUMAR = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM8.75 4.25V5V7.25H11H11.75V8.75H11H8.75V11V11.75L7.25 11.75V11V8.75H5H4.25V7.25H5H7.25V5V4.25H8.75Z" fill="currentColor"></path></svg>`;
    const SVG_RESTAR = `<svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="14" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 8C14.5 11.5899 11.5899 14.5 8 14.5C4.41015 14.5 1.5 11.5899 1.5 8C1.5 4.41015 4.41015 1.5 8 1.5C11.5899 1.5 14.5 4.41015 14.5 8ZM16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8ZM5 7.25H4.25V8.75H5H11H11.75V7.25H11H5Z" fill="currentColor"></path></svg>`;

    // Función para actualizar todos los botones del mismo usuario
    function actualizarTodosLosBotones(seguido_id, esSeguir) {
        const botones = document.querySelectorAll(`.seguir[data-seguido-id="${seguido_id}"], .dejar-de-seguir[data-seguido-id="${seguido_id}"]`);
        botones.forEach(boton => {
            if (esSeguir) {
                boton.innerHTML = SVG_RESTAR;
                boton.classList.remove('seguir');
                boton.classList.add('dejar-de-seguir');
            } else {
                boton.innerHTML = SVG_SUMAR;
                boton.classList.remove('dejar-de-seguir');
                boton.classList.add('seguir');
            }
        });
    }

    // Manejador para botones de seguir
    accionClick('.seguir', 'seguir_usuario', '', async (_, data, post_id) => {
        if (data.success) {
            const boton = document.querySelector(`.seguir[data-seguido-id="${post_id}"]`);
            const seguido_id = boton.getAttribute('data-seguido-id');
            actualizarTodosLosBotones(seguido_id, true);
        }
    });

    // Manejador para botones de dejar de seguir
    accionClick('.dejar-de-seguir', 'dejar_de_seguir_usuario', '¿Estás seguro de que quieres dejar de seguir a este usuario?', async (_, data, post_id) => {
        if (data.success) {
            const boton = document.querySelector(`.dejar-de-seguir[data-seguido-id="${post_id}"]`);
            const seguido_id = boton.getAttribute('data-seguido-id');
            actualizarTodosLosBotones(seguido_id, false);
        }
    });
}