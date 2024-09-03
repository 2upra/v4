function empezarcolab() {
    const colabButtons = document.querySelectorAll('.ZYSVVV');

    colabButtons.forEach(button => {
        button.addEventListener('click', empezarColab);
    });

    async function empezarColab(event) {
        const postId = event.target.dataset.postId;
        const socialPost = event.target.closest('.social-post');

        const confirmMessage = '¿Estás seguro de que quieres empezar la colaboración?';
        const confirmed = await confirm(confirmMessage);

        if (confirmed) {
            const data = await enviarAjax('empezar_colab', postId);
            if (data.success) {
                alert('Colaboración iniciada con éxito');
            } else {
                alert('Error al iniciar la colaboración');
            }
        } else {
            alert('Inicio de colaboración cancelado');
        }
    }
}