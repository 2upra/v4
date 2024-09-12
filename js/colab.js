function empezarcolab() {
    const buttons = document.querySelectorAll('.ZYSVVV');

    if (buttons.length === 0) {
        console.log('No se encontraron botones con la clase .ZYSVVV');
        return;
    }

    buttons.forEach(button => {
        button.addEventListener('click', async event => {
            console.log('Button clicked:', event.currentTarget);
            const postId = event.currentTarget?.dataset.postId;

            if (!postId) {
                console.error('El post ID no se encontró en el botón.');
                return;
            }

            console.log('Post ID:', postId);

            // Asegúrate de usar 'await' para esperar el resultado de 'confirm'
            const userConfirmed = await confirm('¿Estás seguro de que quieres empezar la colaboración?');

            if (userConfirmed) {
                const data = await enviarAjax('empezarColab', postId);

                // Manejo de la respuesta
                if (typeof data === 'object' && data !== null) {
                    // Si la respuesta es un objeto y tiene la propiedad "success"
                    if (data.success) {
                        alert('Colaboración iniciada con éxito');
                    } else {
                        alert(`Error al iniciar la colaboración: ${data.message || 'Desconocido'}`);
                    }
                } else if (typeof data === 'string') {
                    // Si la respuesta es una cadena de texto
                    if (data.includes('Colaboración iniciada correctamente')) {
                        alert('Colaboración iniciada con éxito');
                    } else {
                        alert(`Error al iniciar la colaboración: ${data}`);
                    }
                } else {
                    alert('Error inesperado al iniciar la colaboración.');
                }
            } else {
                alert('Inicio de colaboración cancelado');
            }
        });
    });
}