function empezarcolab() {
    const buttons = document.querySelectorAll('.ZYSVVV');

    if (!buttons.length) {
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

            if (await confirm('¿Estás seguro de que quieres empezar la colaboración?')) {
                const data = await enviarAjax('empezarColab', postId);
                if (data?.success) {
                    alert('Colaboración iniciada con éxito');
                } else if (typeof data === 'string') {
                    alert(data.includes('Colaboración iniciada correctamente, te avisaremos cuando sea aceptada :)') ? 'Colaboración iniciada con éxito' : `Error al iniciar la colaboración: ${data}`);
                } else {
                    alert(`Error al iniciar la colaboración: ${data?.message || 'Desconocido'}`);
                }
            } else {
                alert('Inicio de colaboración cancelado');
            }
        });
    });
}