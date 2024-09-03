async function procesarDescarga(audioUrl, usuarioId) {
    console.log("Iniciando procesarDescarga", audioUrl, usuarioId);

    const confirmed = await new Promise((resolve) => {
        const confirmBox = confirm("Esta descarga costará 1 Pinky. ¿Deseas continuar?");
        resolve(confirmBox);
    });

    console.log("Confirmación del usuario:", confirmed);

    if (confirmed) {
        try {
            console.log("Preparando solicitud fetch");
            console.log("URL de AJAX:", pinkyCobro.ajaxurl);

            const response = await fetch(pinkyCobro.ajaxurl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "procesar_descarga",
                    usuario_id: usuarioId,
                    enlace_descarga: audioUrl,
                    nonce: pinkyCobro.nonce,
                }),
            });

            console.log("Respuesta recibida", response);

            const data = await response.json();
            console.log("Datos de respuesta:", data);

            if (data.success) {
                console.log("Descarga autorizada, iniciando descarga");
                window.location.href = audioUrl; // Cambia esto para iniciar la descarga directamente
            } else {
                console.log("No hay suficientes pinkys");
                alert("No tienes suficientes pinkys");
            }
        } catch (error) {
            console.error("Error en la solicitud:", error);
        }
    } else {
        console.log("Descarga cancelada por el usuario.");
    }

    // Prevenir la navegación por defecto
    return false;
}