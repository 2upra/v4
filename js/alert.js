window.inicializarAlerta = function () {
    console.log('DOM completamente cargado y procesado');
    
    window.originalAlert = window.alert;
    window.originalConfirm = window.confirm;

    window.alert = (message) => {
        console.log('Se ha llamado a window.alert con el mensaje:', message);
        return showCustomNotification(message, 'alert');
    };
    
    window.confirm = (message) => {
        console.log('Se ha llamado a window.confirm con el mensaje:', message);
        return showCustomNotification(message, 'confirm');
    };

    function showCustomNotification(message, type) {
        console.log('Entrando en showCustomNotification:', message, type);

        return new Promise((resolve) => {
            console.log('Creando el div de notificación.');

            const notificationDiv = document.createElement('div');
            notificationDiv.className = `custom-notification${type === 'confirm' ? ' alertop' : ''}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'notification-content';
            contentDiv.textContent = message;
            notificationDiv.appendChild(contentDiv);

            console.log('Buscando el div con id modalBackground2.');

            // Mostrar el modalBackground2
            const modalBackground = document.getElementById('modalBackground2');
            if (modalBackground) {
                console.log('modalBackground2 encontrado. Mostrándolo.');
                setTimeout(() => {
                    modalBackground.style.display = 'block';
                    modalBackground.style.visibility = 'visible';
                    modalBackground.style.opacity = '1';
                    modalBackground.style.zIndex = '9999'; // Asegurarse de que esté por encima de otros elementos
                    console.log('Estado del modalBackground2 después de mostrarlo:', 
                                modalBackground.style.display, 
                                window.getComputedStyle(modalBackground).display);
                }, 0);
            } else {
                console.error('modalBackground2 no encontrado.');
            }

            if (type === 'confirm') {
                console.log('Generando botones para confirmación.');

                const buttonsDiv = document.createElement('div');
                buttonsDiv.className = 'notification-buttons';

                const confirmButton = document.createElement('button');
                confirmButton.textContent = 'Confirmar';
                confirmButton.onclick = () => {
                    console.log('Botón de confirmar clicado.');
                    document.body.removeChild(notificationDiv);
                    if (modalBackground) {
                        console.log('Ocultando modalBackground2.');
                        modalBackground.style.display = 'none';
                    }
                    resolve(true);
                };

                const cancelButton = document.createElement('button');
                cancelButton.textContent = 'Cancelar';
                cancelButton.onclick = () => {
                    console.log('Botón de cancelar clicado.');
                    document.body.removeChild(notificationDiv);
                    if (modalBackground) {
                        console.log('Ocultando modalBackground2.');
                        modalBackground.style.display = 'none';
                    }
                    resolve(false);
                };

                buttonsDiv.appendChild(confirmButton);
                buttonsDiv.appendChild(cancelButton);
                notificationDiv.appendChild(buttonsDiv);
            } else {
                console.log('Configurando para cerrar la notificación después de 3 segundos.');
                setTimeout(() => {
                    console.log('Cerrando notificación.');
                    document.body.removeChild(notificationDiv);
                    if (modalBackground) {
                        console.log('Ocultando modalBackground2.');
                        modalBackground.style.display = 'none';
                    }
                    resolve();
                }, 3000);
            }

            console.log('Agregando la notificación al cuerpo del documento.');
            document.body.appendChild(notificationDiv);

            // Verificar el estado del modalBackground2 después de un breve retraso
            setTimeout(() => {
                if (modalBackground) {
                    console.log('Estado final del modalBackground2:', 
                                modalBackground.style.display, 
                                window.getComputedStyle(modalBackground).display);
                }
            }, 100);
        });
    }
}