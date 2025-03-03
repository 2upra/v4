async function procesarDescarga(postId, usuarioId, Coleccion = false, costo = 1, Sync = false) {
    console.log('Iniciando procesarDescarga', postId, usuarioId, Coleccion, costo);
    const confirmed = await new Promise(resolve => {
        const confirmBox = confirm(`Esta descarga costará ${costo} Pinky${costo > 1 ? 's' : ''}. ¿Deseas continuar?`);
        resolve(confirmBox);
    });

    if (!confirmed) {
        console.log('Descarga cancelada por el usuario.');
        return false;
    }

    try {
        const data = {
            post_id: postId,
            coleccion: Coleccion,
            sync: Sync
        };

        // Enviar la solicitud AJAX
        const responseData = await enviarAjax('procesarDescarga', data);
        console.log('Datos de respuesta:', responseData);

        // Verificar si la respuesta fue exitosa
        if (responseData.success) {
            // Acceder a la propiedad download_url dentro de responseData.data
            if (responseData.data && responseData.data.download_url) {
                console.log('Descarga autorizada, iniciando descarga');

                // Redirige a la URL de descarga
                window.location.href = responseData.data.download_url;

                // Actualizar el botón visualmente para indicar que ya se descargó
                const button = document.querySelector(`button[data-post-id="${postId}"]`) || document.getElementById(`download-button-${postId}`);
                if (button) {
                    button.classList.add('yaDescargado');
                    console.log('Clase yaDescargado añadida al botón');
                } else {
                    console.error('No se encontró el botón de descarga.');
                }
            } else {
                alert('Sincronizado.');
            }
        } else {
            console.log('No hay suficientes pinkys o error en la descarga.');
            alert(responseData.message || `No tienes suficientes pinkys, necesitas al menos ${costo}`);
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
        alert('Ocurrió un error al procesar la descarga. Por favor, intenta de nuevo.');
    }

    return false;
}

function mantenerSesionViva() {
    setInterval(async () => {
        try {
            const rta = await enviarAjax('mantener_sesion_viva');
            if (rta.success) {
                console.log("Sesión mantenida viva.");
            } else {
                console.error("Error al mantener la sesión viva.", rta.data);
            }
        } catch (error) {
            console.error("Error al mantener la sesión viva.", error);
        }
    }, 600000); // 600000 milisegundos = 10 minutos
}

document.addEventListener('DOMContentLoaded', mantenerSesionViva);