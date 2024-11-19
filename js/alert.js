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
                    modalBackground.style.zIndex = '998';
                }, 0);

                modalBackground.onclick = () => {
                    closeNotification(false);
                };
            }

            if (type === 'confirm') {
                const buttonsDiv = document.createElement('div');
                buttonsDiv.className = 'notification-buttons';

                const confirmButton = document.createElement('button');
                confirmButton.textContent = 'Confirmar';
                confirmButton.className = 'botonprincipal'; 
                confirmButton.onclick = () => {
                    closeNotification(true);
                };

                const cancelButton = document.createElement('button');
                cancelButton.textContent = 'Cancelar';
                cancelButton.className = 'botonsecundario'; 
                cancelButton.onclick = () => {
                    closeNotification(false);
                };

                buttonsDiv.appendChild(cancelButton); 
                buttonsDiv.appendChild(confirmButton);

                notificationDiv.appendChild(buttonsDiv);
            } else {
                setTimeout(() => {
                    closeNotification();
                }, 3000);
            }

            document.body.appendChild(notificationDiv);

            function closeNotification(result) {
                if (notificationDiv) {
                    document.body.removeChild(notificationDiv);
                }
                if (modalBackground && type === 'confirm') {
                    modalBackground.style.display = 'none';
                    modalBackground.onclick = null;
                }
                resolve(result);
            }
        });
    }
};