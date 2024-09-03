window.originalAlert = window.alert;
window.originalConfirm = window.confirm;

window.alert = function(message) {
    return showCustomNotification(message, 'alert');
};

window.confirm = function(message) {
    return showCustomNotification(message, 'confirm');
};

function showCustomNotification(message, type) {
    return new Promise((resolve) => {
        // Crea el contenedor principal
        const notificationDiv = document.createElement('div');
        notificationDiv.className = 'custom-notification';
        
        // Crea el contenido de la notificación
        const contentDiv = document.createElement('div');
        contentDiv.className = 'notification-content';
        contentDiv.textContent = message;
        
        notificationDiv.appendChild(contentDiv);

        // Si es una confirmación, añade botones
        if (type === 'confirm') {
            const buttonsDiv = document.createElement('div');
            buttonsDiv.className = 'notification-buttons';
            
            const confirmButton = document.createElement('button');
            confirmButton.textContent = 'Confirmar';
            confirmButton.onclick = () => {
                document.body.removeChild(notificationDiv);
                resolve(true);
            };
            
            const cancelButton = document.createElement('button');
            cancelButton.textContent = 'Cancelar';
            cancelButton.onclick = () => {
                document.body.removeChild(notificationDiv);
                resolve(false);
            };
            
            buttonsDiv.appendChild(confirmButton);
            buttonsDiv.appendChild(cancelButton);
            notificationDiv.appendChild(buttonsDiv);
        } else {
            // Si es una alerta simple, se cierra automáticamente
            setTimeout(() => {
                document.body.removeChild(notificationDiv);
                resolve();
            }, 3000);
        }

        // Añade la notificación al DOM
        document.body.appendChild(notificationDiv);
    });
}