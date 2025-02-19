// Función para manejar logs detallados con iconos
function logHash(message, detail = '') {
    const icons = {
        info: 'ℹ️',
        success: '✅',
        error: '❌',
        file: '📁',
        server: '🌐',
        hash: '#️⃣'
    };

    // Detectar el tipo de mensaje por palabras clave y asignar un icono
    let icon = icons.info;
    if (message.includes('Error')) {
        icon = icons.error;
    } else if (message.includes('Hash de audio recibido')) {
        icon = icons.hash;
    } else if (message.includes('Solicitando hash de audio al servidor')) {
        icon = icons.server;
    } else if (message.includes('archivo')) {
        icon = icons.file;
    } else if (message.includes('Iniciando')) {
        icon = icons.info;
    } else if (message.includes('exitoso') || message.includes('completado')) {
        icon = icons.success;
    }

    console.log(`${icon} ${message} ${detail}`);
}

window.generateFileHash = async function(file, chat = null) {
    logHash("Iniciando generación de hash para el archivo:", file.name);

    // Si el archivo es de tipo audio y `chat` no es true, usa el hash del servidor
    if (file.type.startsWith('audio/') && !chat) {
        return await generateServerAudioHash(file);
    }

    // En cualquier otro caso (audio con chat=true o no-audio), usa el hash local
    try {
        logHash("Generando hash localmente para archivo:", file.name);
        const buffer = await file.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        logHash("Hash local generado exitosamente:", hash);
        return hash;
    } catch (error) {
        logHash("Error generando hash local:", error);
        throw error;
    }
};

async function generateServerAudioHash(file) {
    logHash("Solicitando hash de audio al servidor:", file.name);

    const formData = new FormData();
    formData.append('action', 'recalcularHash');
    formData.append('audio_file', file);

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const result = await response.json();
        
        if (result.success && result.data.hash) {
            logHash("Hash de audio recibido del servidor exitosamente:", result.data.hash);
            return result.data.hash;
        } else {
            throw new Error(result.data.message || 'Error generando hash de audio');
        }
    } catch (error) {
        logHash("Error generando hash de audio en el servidor, usando hash local:", error.message);

        // En caso de error, usar el hash normal como fallback
        try {
            logHash("Generando hash local como fallback para archivo de audio:", file.name);
            const buffer = await file.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            logHash("Hash local generado exitosamente como fallback:", hash);
            return hash;
        } catch (localError) {
            logHash("Error generando hash local como fallback:", localError.message);
            throw localError;
        }
    }
}