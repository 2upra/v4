window.inicializarAlerta = function () {

    window.originalAlert = window.alert;
    window.originalConfirm = window.confirm;

    window.alert = (message) => {
        return showCustomNotification(message, 'alert');
    };

    window.confirm = (message) => {
        return showCustomNotification(message, 'confirm');
    };

    function showCustomNotification(message, type) {
        return new Promise((resolve) => {

            const notificationDiv = document.createElement('div');
            notificationDiv.className = `custom-notification${type === 'confirm' ? ' alertop' : ''}`;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'notification-content';
            contentDiv.textContent = message;
            notificationDiv.appendChild(contentDiv);

            const modalBackground = document.getElementById('modalBackground2');
            if (modalBackground && type === 'confirm') {
                setTimeout(() => {
                    modalBackground.style.display = 'block';
                    modalBackground.style.visibility = 'visible';
                    modalBackground.style.opacity = '1';
                    modalBackground.style.zIndex = '9999';
                }, 0);

                // Clic fuera de la alerta se considera como cancelar
                modalBackground.onclick = () => {
                    closeNotification(false);
                };
            }

            if (type === 'confirm') {
                const buttonsDiv = document.createElement('div');
                buttonsDiv.className = 'notification-buttons';

                const confirmButton = document.createElement('button');
                confirmButton.textContent = 'Confirmar';
                confirmButton.onclick = () => {
                    closeNotification(true);
                };

                const cancelButton = document.createElement('button');
                cancelButton.textContent = 'Cancelar';
                cancelButton.onclick = () => {
                    closeNotification(false);
                };

                buttonsDiv.appendChild(confirmButton);
                buttonsDiv.appendChild(cancelButton);
                notificationDiv.appendChild(buttonsDiv);
            } else {
                setTimeout(() => {
                    closeNotification();
                }, 3000);
            }

            document.body.appendChild(notificationDiv);
            document.body.classList.add('no-scroll');  // Bloquear scroll

            function closeNotification(result) {
                if (notificationDiv) {
                    document.body.removeChild(notificationDiv);
                }
                if (modalBackground && type === 'confirm') {
                    modalBackground.style.display = 'none';
                    modalBackground.onclick = null; // Desactivar el evento de clic fuera de la alerta
                }
                document.body.classList.remove('no-scroll');  // Habilitar scroll
                resolve(result);
            }
        });
    }
}