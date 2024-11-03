<?

function normalizar_tags_personalizados($post_id) {
    // Definir las normalizaciones manuales
    $normalizaciones = array(
        'one-shot' => 'one shot',
        'oneshot' => 'one shot',
        'percusion' => 'percusión',
        'hiphop' => 'Hip Hop',
        'soul' => 'Soul',
        // Agrega aquí más normalizaciones según sea necesario
    );
    
    // Obtener los metadatos del post
    $meta_datos = get_post_meta($post_id, 'datosAlgoritmo', true);
    
    if (!$meta_datos) {
        return; // No hay datos que normalizar
    }
    
    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];
    
    foreach ($campos as $campo) {
        if (isset($meta_datos[$campo]['es'])) {
            foreach ($meta_datos[$campo]['es'] as &$tag) {
                // Normalizar el tag si existe en las normalizaciones
                $tag = isset($normalizaciones[strtolower($tag)]) ? $normalizaciones[strtolower($tag)] : $tag;
            }
        }
        
        if (isset($meta_datos[$campo]['en'])) {
            foreach ($meta_datos[$campo]['en'] as &$tag) {
                // Normalizar el tag si existe en las normalizaciones
                $tag = isset($normalizaciones[strtolower($tag)]) ? $normalizaciones[strtolower($tag)] : $tag;
            }
        }
    }
    
    // Actualizar los metadatos normalizados en el post
    update_post_meta($post_id, 'datosAlgoritmo', $meta_datos);
}

add_action('save_post', 'normalizar_tags_personalizados');

