window.originalAlert = window.alert;
window.originalConfirm = window.confirm;

window.alert = (message) => showCustomNotification(message, 'alert');
window.confirm = (message) => showCustomNotification(message, 'confirm');

function showCustomNotification(message, type) {
    return new Promise((resolve) => {
        const notificationDiv = document.createElement('div');
        notificationDiv.className = `custom-notification${type === 'confirm' ? ' alertop' : ''}`;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'notification-content';
        contentDiv.textContent = message;
        notificationDiv.appendChild(contentDiv);

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
            setTimeout(() => {
                document.body.removeChild(notificationDiv);
                resolve();
            }, 3000);
        }

        document.body.appendChild(notificationDiv);
    });
}