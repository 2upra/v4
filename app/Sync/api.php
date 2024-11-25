<?php

add_action( 'rest_api_init', function () {
    register_rest_route( '1/v1', '/user_audio_downloads/(?P<user_id>\d+)', array(
      'methods'  => 'GET',
      'callback' => 'get_user_audio_downloads',
      'permission_callback' => 'check_electron_app_header' // Función para verificar el encabezado
      ) );
  });
  
  
  function get_user_audio_downloads( WP_REST_Request $request ) {
      $user_id = $request->get_param( 'user_id' );
      $descargas = get_user_meta( $user_id, 'descargas', true );
     
     $downloads = [];
  
          if (is_array($descargas) ) {
            foreach ($descargas as $post_id => $count) {
                $attachment_id = get_post_meta($post_id, 'post_audio', true);
                if ($attachment_id) {
                    $audio_url = wp_get_attachment_url($attachment_id);   
              $audio_filename = basename($audio_url);
                    $downloads[] = [
                        'post_id' => $post_id,
                        'audio_url' => $audio_url,
                        'audio_filename' => $audio_filename
                    ];
                }
            }
          }
  
  
      return rest_ensure_response( $downloads );
  }
  
  // Función para verificar el encabezado X-Electron-App
  function check_electron_app_header() {
    $headers = apache_request_headers(); // Obtener todos los encabezados de la solicitud
    if (isset($headers['X-Electron-App']) && $headers['X-Electron-App'] === 'true') {
      return true; // Permitir acceso si el encabezado está presente y es correcto
    } else {
      return new WP_Error( 'forbidden', 'Acceso no autorizado', array( 'status' => 403 ) ); // Denegar acceso
    }
  }
?>