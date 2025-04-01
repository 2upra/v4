<?php

/**
 * Asigna tags a un post específico basado en los datos de $_POST.
 *
 * @param int $postId El ID del post al que se asignarán los tags.
 * @return void
 */
function asignarTags($postId)
{
    // Usar 'tagsUsuario' consistentemente como en crearPost y datosParaAlgoritmo
    // Note: Direct use of $_POST here might violate SRP if this service is used outside a direct web request context.
    // Consider passing tags as an argument instead.
    if (!empty($_POST['tags'])) {
        $tags_string = sanitize_text_field($_POST['tags']);
        // Convertir string separado por comas a un array de nombres de tag
        $tags_array = array_map('trim', explode(',', $tags_string));
        // Filtrar tags vacíos que podrían resultar de comas extra
        $tags_array = array_filter($tags_array);

        if (!empty($tags_array)) {
            // wp_set_post_tags asigna etiquetas de la taxonomía 'post_tag'
            // Asegúrate de que tu Custom Post Type 'social_post' soporta 'post_tag'
            // o usa wp_set_object_terms para una taxonomía personalizada.
            $result = wp_set_post_tags($postId, $tags_array, false); // false = reemplazar tags existentes

            if (is_wp_error($result)) {
                 $error_message = str_replace("\n", " | ", $result->get_error_message());
                 // Consider using a dedicated logging service or PSR-3 logger
                 error_log("Error en App\\Services\\asignarTags: Fallo al asignar tags para Post ID {$postId}. Error: " . $error_message);
            } elseif (empty($result)) {
                 // A veces retorna un array vacío si no se añadieron nuevos términos pero no es error.
                 error_log("Advertencia en App\\Services\\asignarTags: wp_set_post_tags retornó vacío para Post ID {$postId}. Tags: " . implode(', ', $tags_array) . ". Podría ser normal si los tags no cambiaron o no existen.");
            } else {
                 error_log("Tags asignados correctamente por App\\Services\\asignarTags para Post ID {$postId}: " . implode(', ', $tags_array));
            }
        } else {
             // Si después de limpiar, el array está vacío, opcionalmente eliminar todos los tags
             // wp_set_post_tags($postId, [], false);
             error_log("Info en App\\Services\\asignarTags: No se proporcionaron tags válidos para Post ID {$postId} en el campo 'tags'.");
        }
    } else {
         // No se proporcionó el campo 'tags'
         // Opcionalmente, podrías querer eliminar todos los tags existentes si el campo está vacío:
         // wp_set_post_tags($postId, [], false);
         error_log("Info en App\\Services\\asignarTags: Campo 'tags' no presente o vacío para Post ID {$postId}. No se asignaron tags.");
    }
}

// Add other post-related service functions here...

?>