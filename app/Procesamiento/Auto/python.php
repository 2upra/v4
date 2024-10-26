<?

function procesarArchivoAudioPython($rutaArchivo) {
    // Comando para ejecutar el script de Python
    $python_command = escapeshellcmd("python3 /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/audio.py \"{$rutaArchivo}\"");
    
    // Log de la ejecución
    iaLog("Ejecutando comando de Python: {$python_command}");
    
    // Ejecutar el comando
    exec($python_command, $output, $return_var);
    
    // Verificar si hubo un error al ejecutar el comando
    if ($return_var !== 0) {
        iaLog("Error al ejecutar el script de Python. Código de retorno: {$return_var}. Salida: " . implode("\n", $output));
        return null;
    }
    
    // Ruta del archivo de resultados
    $resultados_path = "{$rutaArchivo}_resultados.json";
    $campos_esperados = ['bpm', 'pitch', 'emotion', 'key', 'scale', 'strength'];
    $resultados_data = [];
    
    // Verificar si el archivo de resultados existe
    if (file_exists($resultados_path)) {
        $resultados = json_decode(file_get_contents($resultados_path), true);

        // Validar que el contenido sea un array válido
        if ($resultados && is_array($resultados)) {
            foreach ($campos_esperados as $campo) {
                if (isset($resultados[$campo])) {
                    $resultados_data[$campo] = $resultados[$campo]; 
                } else {
                    iaLog("Campo '{$campo}' no encontrado en JSON.");
                }
            }
        } else {
            iaLog("El archivo de resultados JSON no contiene datos válidos.");
        }
    } else {
        iaLog("No se encontró el archivo de resultados en {$resultados_path}");
    }

    // Retornar los resultados procesados
    return [
        'bpm' => $resultados_data['bpm'] ?? null,
        'pitch' => $resultados_data['pitch'] ?? null,
        'emotion' => $resultados_data['emotion'] ?? null,
        'key' => $resultados_data['key'] ?? null,
        'scale' => $resultados_data['scale'] ?? null,
        'strength' => $resultados_data['strength'] ?? null
    ];
}