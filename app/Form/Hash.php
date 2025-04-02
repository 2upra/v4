<?php
// Refactor(Org): Funciones movidas a app/Utils/HashUtils.php

// La función obtenerHash permanece aquí temporalmente si es necesaria por otras partes del código
// o será movida/eliminada en pasos posteriores.

/*
async function generateServerAudioHash(file) {
    logHash("Solicitando hash de audio al servidor:", file.name);

    const formData = new FormData();
    formData.append('action', 'recalcularHash');
    formData.append('audio_file', file);

    try {
        const response = await fetch(ajax_url, {
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
*/

/*
function antivirus($file_path, $file_id, $current_user_id)
{
    $command = escapeshellcmd("clamscan --infected --quiet " . $file_path);
    $output = shell_exec($command);

    if ($output) {
        unlink($file_path); // Elimina el archivo infectado
        ////guardarLog("Archivo infectado eliminado: $file_path");
        // Restringir al usuario que subió el archivo infectado
        restringir_usuario(array($current_user_id));
    } else {
        ////guardarLog("Archivo limpio confirmado: $file_path");
    }
}


// Programar la acción de WordPress
add_action('antivirus', 'antivirus', 10, 2);
*/

// Nota: La función obtenerHash() no fue movida según la instrucción y permanece aquí.
// Si es necesaria por las funciones movidas, requerirá ajustes posteriores.

?>