//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A05 = false; // Cambia a true para activar los logs
const log05 = A05 ? console.log : function () {};
//////////////////////////////////////////////

function reporteScript() {
    const botonReportar = document.querySelector('.reportarerror');
    const formularioError = document.querySelector('.formularioError');

    log05('botonReportar:', botonReportar);
    log05('formularioError:', formularioError);

    // Solo crear el modal si el formulario de error existe
    let modalBackground;
    if (formularioError) {
        modalBackground = document.createElement('div');
        modalBackground.id = 'modalBackground';
        Object.assign(modalBackground.style, {
            position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
            backgroundColor: 'rgba(0, 0, 0, 0.7)', zIndex: 999, display: 'none'
        });
        document.body.appendChild(modalBackground);

        const toggleModal = (show) => {
            log05('toggleModal called with show:', show);
            const display = show ? 'flex' : 'none';
            formularioError.style.display = display;
            modalBackground.style.display = display;
        };

        if (botonReportar) {
            botonReportar.addEventListener('click', () => {
                log05('botonReportar clicked');
                toggleModal(true);
            });
        }

        modalBackground.addEventListener('click', () => {
            log05('modalBackground clicked');
            toggleModal(false);
        });

        document.getElementById('enviarError')?.addEventListener('click', () => {
            log05('enviarError clicked');
            const mensaje = document.getElementById('mensajeError').value.trim();
            log05('mensaje:', mensaje);

            if (!mensaje) {
                alert('Por favor, describe el error.');
                return;
            }

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'enviar_reporte_error', mensaje })
            })
            .then(response => response.json())
            .then(response => {
                log05('response:', response);
                alert(response.data.message);
                if (response.success) {
                    toggleModal(false);
                    document.getElementById('mensajeError').value = '';
                }
            })
            .catch(error => {
                log05('fetch error:', error);
            });
        });
    } else {
        log05('formularioError no está presente, no se crea el modal.');
    }

    async function confirmarAccion(mensajeConfirmacion) {
        return new Promise((resolve) => {
            const confirmado = confirm(mensajeConfirmacion);
            resolve(confirmado);
        });
    }
    
    async function reportarPost(boton) {
        log05('reportarPost button clicked:', boton);
        const postId = boton.getAttribute('data-post-id');
        log05('postId:', postId);
    
        const mensaje = `El usuario ha reportado el post con ID ${postId}`;
        log05('mensaje:', mensaje);
    
        const confirmado = await confirmarAccion('¿Estás seguro de que quieres reportar este post?');
        if (!confirmado) {
            log05('Acción de reporte de post cancelada por el usuario.');
            return;
        }
    
        fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'enviar_reporte_error', mensaje })
        })
        .then(response => response.json())
        .then(response => {
            log05('response:', response);
            alert(response.data.message);
        })
        .catch(error => {
            log05('fetch error:', error);
            alert('Hubo un error al reportar el post. Por favor, inténtalo de nuevo.');
        });
    }
    
    document.querySelectorAll('.reportarPost').forEach(boton => {
        log05('reportarPost button found:', boton);
        boton.addEventListener('click', () => reportarPost(boton));
    });
    
    document.querySelectorAll('.delete-error-report').forEach(boton => {
        boton.addEventListener('click', async function() {
            const confirmado = await confirmarAccion('¿Estás seguro de que quieres borrar este reporte de error?');
            if (!confirmado) {
                log05('Acción de borrar reporte de error cancelada por el usuario.');
                return;
            }
    
            const reportId = this.dataset.reportId;
            const row = this.closest('tr');
    
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'delete_error_report', report_id: reportId })
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = 0;
                    setTimeout(() => {
                        row.remove();
                        if (document.querySelectorAll('.error-reports-table tbody tr').length === 0) {
                            document.querySelector('.error-reports-table').outerHTML = '<p>No hay reporte de errores</p>';
                        }
                    }, 300);
                } else {
                    alert('Hubo un error al borrar el reporte. Por favor, inténtalo de nuevo.');
                }
            })
            .catch(() => alert('Hubo un error al comunicarse con el servidor. Por favor, inténtalo de nuevo.'));
        });
    });
}