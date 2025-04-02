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

function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
{
    $contenido = isset($_POST['textoNormal']) ? sanitize_textarea_field($_POST['textoNormal']) : '';
    $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';

    if (empty($contenido)) {
        error_log('Error en crearPost: El contenido no puede estar vacio.');
        return new WP_Error('empty_content', 'El contenido no puede estar vacio.');
    }

    $titulo = wp_trim_words($contenido, 15, '...');
    $autor = get_current_user_id();

    // Insertar el post
    $postId = wp_insert_post([
        'post_title'   => $titulo,
        'post_content' => $contenido,
        'post_status'  => $estadoPost,
        'post_author'  => $autor,
        'post_type'    => $tipoPost,
    ]);

    if (is_wp_error($postId)) {
        // Reemplazar saltos de línea en el mensaje de error para loguear en una sola línea
        $error_message = str_replace("\n", " | ", $postId->get_error_message());
        error_log('Error en crearPost: Error al insertar el post. Detalles: ' . $error_message);
        return $postId;
    }

    // El bloque de actualización de 'tagsUsuario' ha sido eliminado de aquí.
    // La asignación de tags ahora es manejada por la función asignarTags en PostService.php

    /*
    // Código de notificaciones comentado - los logs aquí no parecen tener variables con saltos de línea
    $seguidores = get_user_meta($autor, 'seguidores', true);
    if (!empty($seguidores) && is_array($seguidores)) {
        $autor_nombre = esc_html(get_the_author_meta('display_name', $autor));
        $contenido_corto = mb_strimwidth($contenido, 0, 100, "...");
        $post_url = get_permalink($postId);

        $notificaciones = get_option('notificaciones_pendientes', []);
        $notificaciones_unicas = [];

        foreach ($seguidores as $seguidor_id) {
            if (get_user_by('id', $seguidor_id) === false) {
                error_log("Error en crearPost: Seguidor ID {$seguidor_id} no es un usuario valido.");
                continue;
            }

            $clave_notificacion = "{$seguidor_id}_{$postId}";

            if (!isset($notificaciones_unicas[$clave_notificacion])) {
                $notificaciones[] = [
                    'seguidor_id' => $seguidor_id,
                    'mensaje' => "{$autor_nombre} ha publicado: \"{$contenido_corto}\"", // Corregido el escape de comillas
                    'post_id' => $postId,
                    'titulo' => 'Nueva publicacion',
                    'url'  => $post_url,
                    'autor_id' => $autor
                ];
                $notificaciones_unicas[$clave_notificacion] = true;
            }
        }

        update_option('notificaciones_pendientes', $notificaciones);

        if (!wp_next_scheduled('wp_enqueue_notifications')) {
            wp_schedule_event(time(), 'minute', 'wp_enqueue_notifications');
        }
    } else {
        error_log("El usuario $autor no tiene seguidores o la lista de seguidores no es valida.");
    }
    */
    return $postId;
}


#Paso 2
function actualizarMetaDatos($postId)
{
    $meta_fields = [
        'paraColab'         => 'colab',
        'esExclusivo'       => 'exclusivo',
        'paraDescarga'      => 'descarga',
        'rola'              => 'music',
        'fan'               => 'fan',
        'artista'           => 'artista',
        'individual'        => 'individual',
        'multiple'          => 'multiple',
        'tienda'            => 'tienda',
        'momento'           => 'momento'
    ];

    foreach ($meta_fields as $meta_key => $post_key) {
        // Asegúrate que el índice existe antes de accederlo
        $value = (isset($_POST[$post_key]) && $_POST[$post_key] == '1') ? 1 : 0;
        if (update_post_meta($postId, $meta_key, $value) === false) {
            // Los logs aquí están comentados, no se requiere acción inmediata
            //error_log("Error en actualizarMetaDatos: Fallo al actualizar el meta $meta_key para el post ID $postId.");
        }
    }

    // Manejo de nombreLanzamiento
    if (isset($_POST['nombreLanzamiento'])) {
        $nombreLanzamiento = sanitize_text_field($_POST['nombreLanzamiento']);
        if (update_post_meta($postId, 'nombreLanzamiento', $nombreLanzamiento) === false) {
            // Log comentado
            //error_log("Error en actualizarMetaDatos: Fallo al actualizar el meta nombreLanzamiento para el post ID $postId.");
        }
    }

    if (isset($_POST['music']) && $_POST['music'] == '1') {
        registrarNombreRolas($postId);
    }
    if (isset($_POST['tienda']) && $_POST['tienda'] == '1') {
        registrarPrecios($postId);
    }
}

// Refactor(Org): Funciones registrarNombreRolas y registrarPrecios movidas desde app/Form/Manejar.php
#Paso 2.1
function registrarNombreRolas($postId)
{
    for ($i = 1; $i <= 30; $i++) {
        $rola_key = 'nombreRola' . $i;
        if (isset($_POST[$rola_key])) {
            $nombre_rola = sanitize_text_field($_POST[$rola_key]);
            if (update_post_meta($postId, $rola_key, $nombre_rola) === false) {
                // Mensaje de log simple, sin variables complejas
                error_log("Error en registrarNombreRolas: Fallo al actualizar el meta $rola_key para el post ID $postId.");
            }
        }
    }
}

#Paso 2.2 (Renumerado para seguir la secuencia lógica)
function registrarPrecios($postId)
{
    for ($i = 1; $i <= 30; $i++) {
        $precio_key = 'precioRola' . $i;
        if (isset($_POST[$precio_key])) {
            $precio = sanitize_text_field($_POST[$precio_key]);

            if (is_numeric($precio)) {
                if (update_post_meta($postId, $precio_key, $precio) === false) {
                    // Mensaje de log simple
                    error_log("Error en registrarPrecios: Fallo al actualizar el meta $precio_key para el post ID $postId.");
                }
            } else {
                // Mensaje de log simple
                error_log("Error en registrarPrecios: El valor para $precio_key no es numerico. Post ID: $postId, valor ingresado: " . $precio);
            }
        }
    }
}


// Refactor(Org): Funcion datosParaAlgoritmo movida desde app/Form/Manejar.php
#Paso 3
function datosParaAlgoritmo($postId)
{
    $textoNormal = isset($_POST['textoNormal']) ? trim($_POST['textoNormal']) : '';
    // Decodificar entidades HTML podría ser necesario dependiendo de cómo se guardó el texto
    // $textoNormal = htmlspecialchars_decode($textoNormal, ENT_QUOTES); // Descomentar si es necesario
    $tags_string = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : '';
    $tags = !empty($tags_string) ? array_map('trim', explode(',', $tags_string)) : [];

    $autorId = get_post_field('post_author', $postId);
    $autorData = get_userdata($autorId); // Obtener datos del autor de forma segura

    $nombreUsuario = $autorData ? $autorData->user_login : 'desconocido';
    $nombreMostrar = $autorData ? $autorData->display_name : 'Desconocido';

    $datosAlgoritmo = [
        'tags' => $tags,
        'texto' => $textoNormal, // Usar el texto sanitizado
        'autor' => [
            'id' => $autorId,
            'usuario' => $nombreUsuario,
            'nombre' => $nombreMostrar,
        ],
    ];

    // Usar wp_json_encode para manejo de errores de WordPress si aplica, o json_encode estándar
    $datosAlgoritmoJson = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);

    if ($datosAlgoritmoJson === false) {
        // Reemplazar saltos de línea en el mensaje de error JSON si los hubiera (poco probable pero seguro)
        $json_error_message = str_replace("\n", " | ", json_last_error_msg());
        error_log("Error en datosParaAlgoritmo: Fallo al codificar JSON para el post ID: " . $postId . ". Error: " . $json_error_message);
    } else {
        if (update_post_meta($postId, 'datosAlgoritmo', $datosAlgoritmoJson) === false) {
            // Mensaje de log simple
            error_log("Error en datosParaAlgoritmo: Fallo al actualizar meta datosAlgoritmo para el post ID " . $postId);
        }
    }
}


?>
