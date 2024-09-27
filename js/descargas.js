async function procesarDescarga(audioUrl, usuarioId) {
    console.log("Iniciando procesarDescarga", audioUrl, usuarioId);

    // Confirmar con el usuario
    const confirmed = await new Promise((resolve) => {
        const confirmBox = confirm("Esta descarga costará 1 Pinky. ¿Deseas continuar?");
        resolve(confirmBox);
    });

    if (!confirmed) {
        return false;
    }

    try {
        const data = {
            audio_id: audioId,
        };

        const responseData = await enviarAjax("procesar_descarga", data);
        console.log("Datos de respuesta:", responseData);

        if (responseData.success) {
            console.log("Descarga autorizada, iniciando descarga");
            window.location.href = audioUrl; 
        } else {
            console.log("No hay suficientes pinkys");
            alert("No tienes suficientes pinkys");
        }
    } catch (error) {
        console.error("Error en la solicitud:", error);
    }

    return false; 
}

