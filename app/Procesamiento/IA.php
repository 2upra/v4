<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

function generateContent($prompt) {
    $client = new Client();
    $apiKey = $_ENV['API_KEY']; // Corregido: eliminado los paréntesis extras
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    try {
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey
            ],
            'json' => [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['candidates'][0]['content']['parts'][0]['text'];
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Función para manejar la solicitud AJAX
function handle_ai_request() {
    if (isset($_POST['prompt'])) {
        $prompt = sanitize_text_field($_POST['prompt']);
        $response = generateContent($prompt);
        echo $response;
    }
    wp_die(); // Termina la ejecución de WordPress
}
add_action('wp_ajax_ai_request', 'handle_ai_request');
add_action('wp_ajax_nopriv_ai_request', 'handle_ai_request');

// Función para agregar el botón y el chat
function add_ai_chat_button() {
    ?>
    <div id="ai-chat-container">
        <textarea id="ai-prompt" rows="4" cols="50"></textarea>
        <button id="ai-submit">Enviar a AI</button>
        <div id="ai-response"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#ai-submit').click(function() {
            var prompt = $('#ai-prompt').val();
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'ai_request',
                    prompt: prompt
                },
                success: function(response) {
                    $('#ai-response').html(response);
                }
            });
        });
    });
    </script>
    <?php
}
