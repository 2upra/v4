async function procesarDescarga(postId, usuarioId) {
    console.log('Iniciando procesarDescarga', postId, usuarioId);

    const confirmed = await new Promise(resolve => {
        const confirmBox = confirm('Esta descarga costará 1 Pinky. ¿Deseas continuar?');
        resolve(confirmBox);
    });

    if (!confirmed) {
        return false;
    }

    try {
        const data = {
            post_id: postId  // Cambiar 'postId' a 'post_id'
        };

        const responseData = await enviarAjax('procesarDescarga', data);
        console.log('Datos de respuesta:', responseData);

        if (responseData.success) {
            console.log('Descarga autorizada, iniciando descarga');
            window.location.href = responseData.download_url; 
        } else {
            console.log('No hay suficientes pinkys');
            alert('No tienes suficientes pinkys');
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
    }

    return false;
}