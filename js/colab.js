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
            
            const userConfirmed = confirm('¿Estás seguro de que quieres empezar la colaboración?');
            // Solo enviamos la solicitud AJAX si el usuario confirma
            if (userConfirmed) {
                const data = await enviarAjax('empezarColab', postId);
                alert(data.success ? 'Colaboración iniciada con éxito' : `Error al iniciar la colaboración: ${data.message || 'Desconocido'}`);
            } else {
                alert('Inicio de colaboración cancelado');
            }
        });
    });
}