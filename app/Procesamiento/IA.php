<?php

use GuzzleHttp\Client;



function generarDescripcionIAConURI($audio_uri, $prompt) {
    guardarLog("Generando descripción IA con prompt: " . $prompt . " y URI: " . $audio_uri);

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
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";

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
            guardarLog($error_message);
            return $error_message;
        }

        curl_close($ch);
        $bodyGenerate = json_decode($response, true);

        // Verificar si la respuesta contiene los datos esperados
        if (isset($bodyGenerate['contents'][0]['parts'][0]['text'])) {
            $generated_text = $bodyGenerate['contents'][0]['parts'][0]['text'];
            guardarLog("Contenido generado: " . $generated_text);

            return $generated_text;
        } else {
            $error_message = "Error: Respuesta inesperada de la API.";
            guardarLog($error_message);
            return $error_message;
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        guardarLog($error_message);
        return $error_message;
    }
}



function subirArchivo($archivo_path) {
    guardarLog("Subiendo archivo: " . $archivo_path);

    try {
        // Leer el archivo de audio y convertirlo a base64
        $audio_data = file_get_contents($archivo_path);
        guardarLog("Archivo de audio cargado con éxito.");

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
            guardarLog($error_message);
            return $error_message;
        }

        curl_close($ch);
        $bodyUpload = json_decode($response, true);

        // Verificar si la respuesta contiene el URI del archivo subido
        if (isset($bodyUpload['uri'])) {
            $audio_uri = $bodyUpload['uri'];
            guardarLog("Archivo subido exitosamente. URI: " . $audio_uri);
            return $audio_uri;
        } else {
            $error_message = "Error: Respuesta inesperada durante la subida del archivo.";
            guardarLog($error_message);
            return $error_message;
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        guardarLog($error_message);
        return $error_message;
    }
}

function generarDescripcionIA($archivo_path, $prompt) {
    guardarLog("Inicio de generarDescripcionIA con prompt: " . $prompt);
    guardarLog("Archivo de audio: " . $archivo_path);

    try {
        // Leer el archivo de audio y convertirlo a base64
        $audio_data = file_get_contents($archivo_path);
        $audio_base64 = base64_encode($audio_data);
        guardarLog("Archivo de audio cargado y convertido a base64 con éxito.");

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
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";

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
            guardarLog($error_message);
            return $error_message;
        }

        curl_close($ch);
        $bodyGenerate = json_decode($response, true);

        // Verificar si la respuesta contiene los datos esperados
        if (isset($bodyGenerate['contents'][0]['parts'][0]['text'])) {
            $generated_text = $bodyGenerate['contents'][0]['parts'][0]['text'];
            guardarLog("Contenido generado: " . $generated_text);

            return $generated_text;
        } else {
            $error_message = "Error: Respuesta inesperada de la API.";
            guardarLog($error_message);
            return $error_message;
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        guardarLog($error_message);
        return $error_message;
    }
}

// Función AJAX para manejar la solicitud desde el frontend
function iaSend() {
    if (isset($_POST['post_id']) && isset($_POST['audio_path'])) {
        $post_id = intval($_POST['post_id']);
        $audio_path = sanitize_text_field($_POST['audio_path']);
        generarMetaIA($post_id, $audio_path, 0);

        echo 'Metadatos IA generados y actualizados correctamente.';
    } else {
        echo 'Datos insuficientes.';
    }
    wp_die();
}

add_action('wp_ajax_ai_request', 'iaSend');
add_action('wp_ajax_nopriv_ai_request', 'iaSend');



