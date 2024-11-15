function registro() {
    var usernameInput = document.getElementById('nombre_usuario');
    if (usernameInput) {
        usernameInput.addEventListener('input', function () {
            this.value = this.value.replace(/\s+/g, '');
        });
    } else {
    }

}

function initializeModalregistro() {
    const elements = {
        modalRegistro: document.getElementById('modalregistro'),
        modalSesion: document.getElementById('modalsesion'),
        fondoNegro: document.getElementById('fondonegro'),
        fondograno: document.getElementById('fondograno')
    };

    const options = {
        animate: true,
        patternWidth: 100,
        patternHeight: 100,
        grainOpacity: 0.05,
        grainDensity: 1,
        grainWidth: 1,
        grainHeight: 1
    };

    if (elements.fondograno) {
        grained('fondograno', options);
    }

    if (elements.modalRegistro && elements.modalSesion) {
        const toggleModal = modalType => {
            // Cierra todos los modales antes de abrir el nuevo
            closeModals();

            const modal = elements[`modal${modalType}`];
            modal.style.display = 'flex';
            elements.fondoNegro.style.display = 'block';
        };

        const closeModals = () => {
            elements.modalRegistro.style.display = 'none';
            elements.modalSesion.style.display = 'none';
            elements.fondoNegro.style.display = 'none';
        };

        document.querySelectorAll('.boton-registro').forEach(btn => btn.addEventListener('click', () => toggleModal('Registro')));
        document.querySelectorAll('.boton-sesion').forEach(btn => btn.addEventListener('click', () => toggleModal('Sesion')));
        elements.fondoNegro.addEventListener('click', closeModals);
        document.querySelectorAll('.boton-cerrar').forEach(btn =>
            btn.addEventListener('click', e => {
                e.preventDefault();
                closeModals();
            })
        );
    }
}

