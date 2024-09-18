<?php

use GuzzleHttp\Client;

/*


2024-09-18 17:54:42 - Inicio de la función generarMetaIA
2024-09-18 17:54:42 - Post ID: 231868
2024-09-18 17:54:42 - Ruta del archivo: /var/www/wordpress/wp-content/uploads/2024/09/2upra_66eb13e1b7904_128k.mp3
2024-09-18 17:54:42 - Index: 1
2024-09-18 17:54:42 - Inicio de generarDescripcionIA con prompt: Genera una descripción detallada del audio.
2024-09-18 17:54:42 - Archivo de audio: /var/www/wordpress/wp-content/uploads/2024/09/2upra_66eb13e1b7904_128k.mp3
2024-09-18 17:54:42 - Archivo de audio cargado con éxito.
2024-09-18 17:54:43 - Error: Client error: `POST https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent` resulted in a `400 Bad Request` response:
{
  "error": {
    "code": 400,
    "message": "Invalid JSON payload received. Unknown name \"generateContentRequest\":  (truncated...)
2024-09-18 17:54:43 - Descripción detallada generada: Error: Client error: `POST https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent` resulted in a `400 Bad Request` response:
{
  "error": {
    "code": 400,
    "message": "Invalid JSON payload received. Unknown name \"generateContentRequest\":  (truncated...)
2024-09-18 17:54:43 - Inicio de generarDescripcionIA con prompt: Describe brevemente los instrumentos usados.
2024-09-18 17:54:43 - Archivo de audio: /var/www/wordpress/wp-content/uploads/2024/09/2upra_66eb13e1b7904_128k.mp3
2024-09-18 17:54:43 - Archivo de audio cargado con éxito.
2024-09-18 17:54:44 - Error: Client error: `POST https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent` resulted in a `400 Bad Request` response:
{
  "error": {
    "code": 400,
    "message": "Invalid JSON payload received. Unknown name \"generateContentRequest\":  (truncated...)
2024-09-18 17:54:44 - Descripción corta generada: Error: Client error: `POST https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent` resulted in a `400 Bad Request` response:
{
  "error": {
    "code": 400,
    "message": "Invalid JSON payload received. Unknown name \"generateContentRequest\":  (truncated...)
2024-09-18 17:54:44 - Inicio de generarDescripcionIA con prompt: Sugiere algunos tags relevantes.
2024-09-18 17:54:44 - Archivo de audio: /var/www/wordpress/wp-content/uploads/2024/09/2upra_66eb13e1b7904_128k.mp3
2024-09-18 17:54:44 - Archivo de audio cargado con éxito.
2024-09-18 17:54:45 - Error: Client error: `POST https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent` resulted in a `400 Bad Request` response:
{
  "error": {
    "code": 400,
    "message": "Invalid JSON payload received. Unknown name \"generateContentRequest\":  (truncated...)

*/



function generarMetaIA($post_id, $nuevo_archivo_path_lite, $index) {
    guardarLog("Inicio de la función generarMetaIA"); // Log inicial
    
    // Verificar que los parámetros están correctos
    guardarLog("Post ID: " . $post_id);
    guardarLog("Ruta del archivo: " . $nuevo_archivo_path_lite);
    guardarLog("Index: " . $index);
    
    // Generar las descripciones y tags usando la API de Google Gemini
    $descripcion_detallada = generarDescripcionIA($nuevo_archivo_path_lite, "Genera una descripción detallada del audio.");
    guardarLog("Descripción detallada generada: " . $descripcion_detallada);
    
    $descripcion_corta = generarDescripcionIA($nuevo_archivo_path_lite, "Describe brevemente los instrumentos usados.");
    guardarLog("Descripción corta generada: " . $descripcion_corta);
    
    $tags_sugeridos = generarDescripcionIA($nuevo_archivo_path_lite, "Sugiere algunos tags relevantes.");
    guardarLog("Tags sugeridos generados: " . $tags_sugeridos);

    // Actualizar los metadatos del post con los resultados generados
    update_post_meta($post_id, "descripcionIA", $descripcion_detallada);
    update_post_meta($post_id, "descripcionCortaIA", $descripcion_corta);
    update_post_meta($post_id, "TagsSugeridosIA", $tags_sugeridos);
    
    guardarLog("Metadatos actualizados en el post ID: " . $post_id);
    guardarLog("Fin de la función generarMetaIA");
}

function generarDescripcionIA($archivo_path, $prompt) {
    guardarLog("Inicio de generarDescripcionIA con prompt: " . $prompt);
    guardarLog("Archivo de audio: " . $archivo_path);

    $client = new Client();
    $apiKey = $_ENV['API_KEY'];
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    try {
        $audio_data = file_get_contents($archivo_path);
        guardarLog("Archivo de audio cargado con éxito.");

        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey
            ],
            'json' => [
                'prompt' => [ // Cambiar a 'prompt' directamente en lugar de 'generateContentRequest'
                    'text' => $prompt
                ],
                'audio' => [ // Cambiar a 'audio' en lugar de 'audioInput'
                    'mimeType' => 'audio/mp3', 
                    'data' => base64_encode($audio_data)
                ]
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        guardarLog("Respuesta de la API obtenida correctamente.");

        return $body['candidates'][0]['content']['parts'][0]['text'];
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



