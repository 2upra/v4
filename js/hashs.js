let enablelogHash = true;
const logHash = enablelogHash ? console.log : function () {};

window.generateFileHash = async function(file) {
    logHash("Iniciando generación de hash para el archivo:", file.name);
    
    // Si es un archivo de audio, enviar al servidor para generar el hash
    if (file.type.startsWith('audio/')) {
        return await generateServerAudioHash(file);
    }
    
    // Para archivos no-audio, usar el hash normal
    const buffer = await file.arrayBuffer();
    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
};

async function generateServerAudioHash(file) {
    logHash("Solicitando hash de audio al servidor:", file.name);
    
    const formData = new FormData();
    formData.append('action', 'generate_audio_hash');
    formData.append('audio_file', file);

    try {
        const response = await fetch(my_ajax_object.ajax_url, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const result = await response.json();
        
        if (result.success && result.data.hash) {
            logHash("Hash de audio recibido del servidor:", result.data.hash);
            return result.data.hash;
        } else {
            throw new Error(result.data.message || 'Error generando hash de audio');
        }
    } catch (error) {
        logHash("Error generando hash de audio:", error);
        // En caso de error, usar el hash normal
        const buffer = await file.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
}