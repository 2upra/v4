async function reporte() {
    // Capturamos todos los botones reportar
    const reportButtons = document.querySelectorAll('.reporte');
    
    if (reportButtons.length === 0) {
        console.log('No se encontraron botones de reporte');
        return;
    }

    reportButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const tipoContenido = this.getAttribute('tipoContenido');
            abrirModal(postId, tipoContenido);
        });
    });

    // Función para abrir el modal
    function abrirModal(idContenido, tipoContenido) {
        const modal = document.getElementById('formularioError');
        if (!modal) {
            console.error('No se encontró el modal de formulario de error');
            return;
        }
        modal.style.display = 'block';

        // Configurar el botón de enviar usando accionClick
        accionClick(
            '#enviarError',
            'guardarReporte',
            '¿Estás seguro de que quieres enviar este reporte?',
            (statusElement, data) => {
                alert('Reporte enviado correctamente');
                modal.style.display = 'none';
                document.getElementById('mensajeError').value = ''; // Resetear formulario
            }
        );

        // Configurar los datos para el reporte
        const enviarErrorBtn = document.getElementById('enviarError');
        if (enviarErrorBtn) {
            enviarErrorBtn.dataset.postId = idContenido;
            enviarErrorBtn.dataset.tipoContenido = tipoContenido;
        }
    }
}