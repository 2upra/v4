async function procesarDescarga(postId, usuarioId) {
    console.log('Iniciando procesarDescarga', postId, usuarioId);

    const confirmed = await new Promise(resolve => {
        const confirmBox = confirm('Esta descarga costará 1 Pinky. ¿Deseas continuar?');
        resolve(confirmBox);
    });

    if (!confirmed) {
        console.log('Descarga cancelada por el usuario.');
        return false;
    }

    try {
        const data = {
            post_id: postId 
        };

        // Enviar la solicitud AJAX
        const responseData = await enviarAjax('procesarDescarga', data);
        console.log('Datos de respuesta:', responseData);

        // Verificar si la respuesta fue exitosa
        if (responseData.success) {
            // Acceder a la propiedad download_url dentro de responseData.data
            if (responseData.data && responseData.data.download_url) {
                console.log('Descarga autorizada, iniciando descarga');
                window.location.href = responseData.data.download_url;  // Redirige a la URL de descarga
            } else {
                console.error('Error: download_url no está definido en la respuesta.');
                alert('Hubo un problema obteniendo el enlace de descarga.');
            }
        } else {
            console.log('No hay suficientes pinkys o error en la descarga.');
            alert(responseData.message || 'No tienes suficientes pinkys');
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
        alert('Ocurrió un error al procesar la descarga. Por favor, intenta de nuevo.');
    }

    return false;
}