<?

use GuzzleHttp\Client;

// Refactor(Org): Función generarDescripcionIAConURI movida a app/Services/IAService.php



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

// Refactor(Org): Función generarDescripcionIA movida a app/Services/IAService.php


add_action('wp_ajax_ai_request', 'iaSend');
add_action('wp_ajax_nopriv_ai_request', 'iaSend');

// Refactor(Org): Función generarDescripcionIAPro movida a app/Services/IAService.php

add_action('wp_ajax_ai_request', 'iaSend');
add_action('wp_ajax_nopriv_ai_request', 'iaSend');


