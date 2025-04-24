<?php

// Refactor(Org): Función generarDescripcionIA movida desde app/Logic/IA.php
function generarDescripcionIA($archivo_path, $prompt) {
    iaLog("Inicio de generarDescripcionIA con prompt: " . $prompt);
    iaLog("Archivo de audio: " . $archivo_path);

    try {
        // Leer el archivo de audio y convertirlo a base64
        $audio_data = file_get_contents($archivo_path);
        $audio_base64 = base64_encode($audio_data);
        iaLog("Archivo de audio cargado y convertido a base64 con éxito.");

        // Construir el cuerpo de la solicitud
        $data = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $prompt
                        ],
                        [
                            "inline_data" => [
                                "mime_type" => "audio/mp3",
                                "data" => $audio_base64
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Hacer la solicitud POST usando CURL
        $apiKey = $_ENV['API_KEY'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_message = "Error en CURL: " . curl_error($ch);
            iaLog($error_message);
            return $error_message;
        }

        curl_close($ch);
        $bodyGenerate = json_decode($response, true);

        // Registrar la respuesta completa para depuración
        iaLog("Respuesta completa de la API: " . json_encode($bodyGenerate));

        // Verificar si la respuesta contiene los datos esperados
        if (isset($bodyGenerate['candidates'][0]['content']['parts'][0]['text'])) {
            $generated_text = $bodyGenerate['candidates'][0]['content']['parts'][0]['text'];
            iaLog("Contenido generado: " . $generated_text);
            return $generated_text;
        } else {
            $error_message = "Error: Respuesta inesperada de la API. Detalles: " . json_encode($bodyGenerate);
            iaLog($error_message);
            return false; // retorna false en caso de error
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        iaLog($error_message);
        return false; // retorna false en caso de error
    }
}

// Refactor(Org): Función generarDescripcionIAConURI movida desde app/Logic/IA.php
function generarDescripcionIAConURI($audio_uri, $prompt) {
    iaLog("Generando descripción IA con prompt: " . $prompt . " y URI: " . $audio_uri);

    try {
        // Construir el cuerpo de la solicitud
        $data = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $prompt
                        ],
                        [
                            "inline_data" => [
                                "mime_type" => "audio/mp3",
                                "uri" => $audio_uri
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Hacer la solicitud POST usando CURL
        $apiKey = $_ENV['API_KEY'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_message = "Error en CURL: " . curl_error($ch);
            iaLog($error_message);
            return $error_message;
        }

        curl_close($ch);
        $bodyGenerate = json_decode($response, true);

        // Verificar si la respuesta contiene los datos esperados
        if (isset($bodyGenerate['contents'][0]['parts'][0]['text'])) {
            $generated_text = $bodyGenerate['contents'][0]['parts'][0]['text'];
            iaLog("Contenido generado: " . $generated_text);

            return $generated_text;
        } else {
            $error_message = "Error: Respuesta inesperada de la API.";
            iaLog($error_message);
            return $error_message;
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        iaLog($error_message);
        return $error_message;
    }
}

// Refactor(Org): Mueve función generarDescripcionIAPro() de app/Logic/IA.php
function generarDescripcionIAPro($archivo_path, $prompt) {
    iaLog("Inicio de generarDescripcionIAPRO con prompt: " . $prompt);
    iaLog("Archivo de audio: " . $archivo_path);

    try {
        // Leer el archivo de audio y convertirlo a base64
        $audio_data = file_get_contents($archivo_path);
        $audio_base64 = base64_encode($audio_data);
        iaLog("Archivo de audio cargado y convertido a base64 con éxito.");

        // Construir el cuerpo de la solicitud
        $data = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $prompt
                        ],
                        [
                            "inline_data" => [
                                "mime_type" => "audio/mp3",
                                "data" => $audio_base64
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Hacer la solicitud POST usando CURL
        $apiKey = $_ENV['API_KEY'];
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_message = "Error en CURL: " . curl_error($ch);
            iaLog($error_message);
            return $error_message;
        }

        curl_close($ch);
        $bodyGenerate = json_decode($response, true);

        // Registrar la respuesta completa para depuración
        iaLog("Respuesta completa de la API: " . json_encode($bodyGenerate));

        // Verificar si la respuesta contiene los datos esperados
        if (isset($bodyGenerate['candidates'][0]['content']['parts'][0]['text'])) {
            $generated_text = $bodyGenerate['candidates'][0]['content']['parts'][0]['text'];
            iaLog("Contenido generado: " . $generated_text);
            return $generated_text;
        } else {
            $error_message = "Error: Respuesta inesperada de la API. Detalles: " . json_encode($bodyGenerate);
            iaLog($error_message);
            return $error_message;
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        iaLog($error_message);
        return $error_message;
    }
}

// Refactor(Org): Mueve función subirArchivo() de app/Logic/IA.php
function subirArchivo($archivo_path) {
    iaLog("Subiendo archivo: " . $archivo_path);

    try {
        // Leer el archivo de audio y convertirlo a base64
        $audio_data = file_get_contents($archivo_path);
        iaLog("Archivo de audio cargado con éxito.");

        // Construir el cuerpo de la solicitud para subir el archivo
        $data = [
            "file" => [
                "mimeType" => "audio/mp3", 
                "data" => base64_encode($audio_data)
            ]
        ];

        // Hacer la solicitud POST usando CURL para subir el archivo
        $apiKey = $_ENV['API_KEY'];
        $urlUpload = "https://generativelanguage.googleapis.com/v1beta/media:upload?key=$apiKey";

        $ch = curl_init($urlUpload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_message = "Error en CURL: " . curl_error($ch);
            iaLog($error_message);
            return $error_message;
        }

        curl_close($ch);
        $bodyUpload = json_decode($response, true);

        // Verificar si la respuesta contiene el URI del archivo subido
        if (isset($bodyUpload['uri'])) {
            $audio_uri = $bodyUpload['uri'];
            iaLog("Archivo subido exitosamente. URI: " . $audio_uri);
            return $audio_uri;
        } else {
            $error_message = "Error: Respuesta inesperada durante la subida del archivo.";
            iaLog($error_message);
            return $error_message;
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        iaLog($error_message);
        return $error_message;
    }
}

// Refactor(Org): Mueve función procesarArchivoAudioPython() de AudioPythonService
function procesarArchivoAudioPython($rutaArchivo)
{
    // Comando para ejecutar el script de Python
    $python_command = escapeshellcmd("python3 /var/www/wordpress/wp-content/themes/2upra3v/app/python/audio.py \"{$rutaArchivo}\"");

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

?>
