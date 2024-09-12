function empezarcolab() {
    document.querySelectorAll('.ZYSVVV').forEach(button => {
        button.addEventListener('click', async event => {
            if (confirm('¿Estás seguro de que quieres empezar la colaboración?')) {
                const postId = event.target.dataset.postId;
                const data = await enviarAjax('empezarColab', postId);
                alert(data.success ? 'Colaboración iniciada con éxito' : 'Error al iniciar la colaboración');
            } else {
                alert('Inicio de colaboración cancelado');
            }
        });
    });
}
