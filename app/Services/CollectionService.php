<?php
// Función movida desde app/Content/Logic/manejarColeccion.php
function manejarColeccion($args, $paged)
{
    $cacheKey = 'coleccion_' . $args['colec'] . '_paged_' . $paged;

    $cachedData = obtenerCache($cacheKey);
    if ($cachedData !== false) {
        guardarLog("manejarColeccion: Cargando desde caché: {$args['colec']}");
        return $cachedData;
    }

    guardarLog("manejarColeccion: Cargando desde DB: {$args['colec']}");
    $samplesMeta = get_post_meta($args['colec'], 'samples', true);
    if (!is_array($samplesMeta)) {
        $samplesMeta = maybe_unserialize($samplesMeta);
    }

    if (is_array($samplesMeta)) {
        $queryArgs = [
            'post_type'      => $args['post_type'],
            'post__in'       => array_values($samplesMeta),
            'orderby'        => 'rand', // Cambiamos a orden aleatorio
            'posts_per_page' => 12,
            'paged'          => $paged,
        ];

        $cacheMasterKey = 'cache_colec_' . $args['colec'];
        $cacheKeys = obtenerCache($cacheMasterKey) ?: [];
        $cacheKeys[] = $cacheKey;
        guardarCache($cacheMasterKey, $cacheKeys, 86400);
        guardarCache($cacheKey, $queryArgs, 86400);

        return $queryArgs;
    } else {
        return false;
    }
}

// Refactor(Org): Lógica de colecciones movida desde app/Content/Colecciones/Logic/logicColecciones.php
# Ajusta editar coleccion en consecuencia, esta desactualizada
function editarColeccion()
{
    if (!is_user_logged_in()) {
        return json_encode(['error' => 'Usuario no autenticado']);
    }

    $coleccionId = isset($_POST['coleccionId']) ? intval($_POST['coleccionId']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    if ($coleccion && $coleccion->post_author == $userId) {
        // Sanear los datos recibidos
        $nameColec = isset($_POST['nameColec']) ? sanitize_text_field($_POST['nameColec']) : '';
        $descriptionColec = isset($_POST['descriptionColec']) ? sanitize_textarea_field($_POST['descriptionColec']) : '';
        $tagsColec = isset($_POST['tagsColec']) ? array_map('sanitize_text_field', $_POST['tagsColec']) : [];
        $imageURL = isset($_POST['image']) ? esc_url_raw($_POST['image']) : '';

        // Actualizar el título y la descripción
        wp_update_post([
            'ID'           => $coleccionId,
            'post_title'   => $nameColec,
            'post_content' => $descriptionColec,
        ]);

        // Actualizar los tags en la meta 'tagsColec'
        if (!empty($tagsColec)) {
            update_post_meta($coleccionId, 'tagsColec', $tagsColec);
        } else {
            delete_post_meta($coleccionId, 'tagsColec');
        }

        // Actualizar la imagen destacada si se proporcionó una nueva URL
        if ($imageURL) {
            $image_id = subirImagenDesdeURL($imageURL, $coleccionId);
            if ($image_id) {
                set_post_thumbnail($coleccionId, $image_id);
            }
        }

        return json_encode(['success' => true]);
    } else {
        return json_encode(['error' => 'No tienes permisos para editar esta colección']);
    }
}

//para mejorar la logica, se puede simplificar crearColeccion para que guarde el sample usando guardarSampleEnColec, hay que mantener la capacidad ajax de ambas funciones
function crearColeccion()
{
    if (!is_user_logged_in()) {
        guardarLog("Error: Usuario no autenticado");
        wp_send_json_error(['error' => 'Usuario no autenticado']);
    }

    // Verificar y sanear los datos recibidos
    $colecSampleId = isset($_POST['colecSampleId']) ? intval($_POST['colecSampleId']) : 0;
    $imgColec = isset($_POST['imgColec']) ? $_POST['imgColec'] : '';
    // Si la imagen es http://null o null, establecerla como cadena vacía
    $imgColec = ($imgColec === 'http://null' || $imgColec === 'null') ? '' : esc_url_raw($imgColec);
    $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
    $imgColecId = isset($_POST['imgColecId']) ? sanitize_text_field($_POST['imgColecId']) : '';
    $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
    $privado = isset($_POST['privado']) ? intval($_POST['privado']) : 0;

    guardarLog("Datos recibidos: colecSampleId=$colecSampleId, imgColec=$imgColec, titulo=$titulo, imgColecId=$imgColecId, descripcion=$descripcion");

    // Validar título obligatorio
    if (empty($titulo)) {
        guardarLog("Error: El título de la colección es obligatorio");
        wp_send_json_error(['error' => 'El título de la colección es obligatorio']);
    }

    // Comprobar cuántas colecciones tiene el usuario actualmente
    $user_id = get_current_user_id();
    $user_collections_count = new WP_Query([
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    guardarLog("Colecciones actuales del usuario $user_id: " . $user_collections_count->found_posts);

    // Verificar si el usuario ya tiene 50 colecciones
    if ($user_collections_count->found_posts >= 50) {
        guardarLog("Error: Límite de colecciones alcanzado para el usuario $user_id");
        wp_send_json_error(['error' => 'Has alcanzado el límite de 50 colecciones']);
    }

    // Crear la colección ya que no existe ninguna limitación
    $coleccionId = wp_insert_post([
        'post_title'    => $titulo,
        'post_content'  => $descripcion,
        'post_type'     => 'colecciones',
        'post_status'   => 'publish',
        'post_author'   => $user_id,
    ]);

    if (!$coleccionId) {
        guardarLog("Error: Error al crear la colección");
        wp_send_json_error(['message' => 'Error al crear la colección']);
    }

    guardarLog("Colección creada exitosamente: ID $coleccionId");

    // Establecer la imagen destacada solo si hay una URL válida
    if (!empty($imgColec)) {
        $image_id = subirImagenDesdeURL($imgColec, $coleccionId);
        if ($image_id) {
            set_post_thumbnail($coleccionId, $image_id);
            guardarLog("Imagen destacada establecida con ID $image_id para la colección $coleccionId");
        }
    }

    if ($privado === 1) {
        update_post_meta($coleccionId, 'privado', 1);
    } else {
        //delete_post_meta($coleccionId, 'privado'); // Opcional: elimina la meta si no es privada
    }

    // Guardar el imgColecId en la meta si existe y no es 'null'
    if (!empty($imgColecId) && $imgColecId !== 'null') {
        update_post_meta($coleccionId, 'imgColecId', $imgColecId);
        guardarLog("Meta imgColecId guardada con valor $imgColecId para la colección $coleccionId");
    }

    // Inicializar la meta 'samples' con el postId proporcionado usando la función auxiliar
    $resultado = añadirSampleEnColab($coleccionId, $colecSampleId, $user_id);

    if (!$resultado['success']) {
        guardarLog("Error al agregar el sample inicial: " . $resultado['message']);
        wp_delete_post($coleccionId, true); // Opcional: elimina la colección si no se puede agregar el sample
        wp_send_json_error(['message' => $resultado['message']]);
    }

    guardarLog("Meta 'samples' inicializada con colecSampleId $colecSampleId para la colección $coleccionId");

    wp_send_json_success(['message' => 'Colección creada exitosamente']);
    wp_die();
}

/*
[03-Jan-2025 17:01:05 UTC] PHP Fatal error:  Uncaught Error: Undefined constant "coleccionEspecial" in /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/Logic/logicColecciones.php:181
Stack trace:
#0 /var/www/wordpress/wp-includes/class-wp-hook.php(324): guardarSampleEnColec()
#1 /var/www/wordpress/wp-includes/class-wp-hook.php(348): WP_Hook->apply_filters()
#2 /var/www/wordpress/wp-includes/plugin.php(517): WP_Hook->do_action()
#3 /var/www/wordpress/wp-admin/admin-ajax.php(192): do_action()
#4 {main}
  thrown in /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/Logic/logicColecciones.php on line 181
*/

function guardarSampleEnColec()
{
    $log = "guardarSampleEnColec(): \\n ";
    if (!is_user_logged_in()) {
        $log .= "Usuario no autorizado, \\n ";
        //guardarLog($log);
        wp_send_json_error(['message' => 'Usuario no autorizado']);
        return;
    }

    $sampleId = isset($_POST['colecSampleId']) ? intval($_POST['colecSampleId']) : 0;
    $colecId = isset($_POST['colecSelecionado']) ? $_POST['colecSelecionado'] : '';
    $usu = get_current_user_id();

    if (!$sampleId || !$colecId) {
        $log .= "Datos inválidos, \\n ";
        //guardarLog($log);
        wp_send_json_error(['message' => 'Datos inválidos']);
        return;
    }

    if ($colecId === 'favoritos' || $colecId === 'despues') {
        $colecEspId = get_user_meta($usu, $colecId . '_coleccion_id', true);

        if (!$colecEspId) {
            $tit = ($colecId === 'favoritos') ? 'Favoritos' : 'Usar más tarde';
            $imgUrl = ($colecId === 'favoritos')
                ? 'https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'
                : 'https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg';

            $colecEspId = wp_insert_post([
                'post_title'    => $tit,
                'post_type'     => 'colecciones',
                'post_status'   => 'publish',
                'post_author'   => $usu,
            ]);

            if (!is_wp_error($colecEspId)) {
                update_user_meta($usu, $colecId . '_coleccion_id', $colecEspId);
                update_post_meta($colecEspId, 'coleccion_especial', $tit);
                $imgId = subirImagenDesdeURL($imgUrl, $colecEspId);
                if ($imgId) {
                    set_post_thumbnail($colecEspId, $imgId);
                }
                $log .= "Se creo la coleccion especial $colecEspId, \\n ";
            } else {
                $log .= "Error al crear la colección especial, \\n ";
                //guardarLog($log);
                wp_send_json_error(['message' => 'Error al crear la colección especial']);
                return;
            }
        }
        $colecId = $colecEspId;
    }

    $res = añadirSampleEnColab($colecId, $sampleId, $usu);

    if ($res['success']) {
        $log .= "Se agrego el sample $sampleId a la coleccion $colecId, \\n ";
        $log .= "samples " . print_r($res['samples'], true) . " \\n ";
        //guardarLog($log);
        wp_send_json_success([
            'message' => $res['message'],
            'samples' => $res['samples']
        ]);
    } else {
        $log .= "Error al agregar el sample $sampleId a la coleccion $colecId, \\n ";
        //guardarLog($log);
        wp_send_json_error(['message' => $res['message']]);
    }
}


function añadirSampleEnColab($collection_id, $sample_id, $user_id)
{
    $collection = get_post($collection_id);
    if (!$collection || $collection->post_author != $user_id) {
        return [
            'success' => false,
            'message' => 'No tienes permiso para modificar esta colección'
        ];
    }

    // Obtener los samples actuales en la colección
    $samples = get_post_meta($collection_id, 'samples', true);
    if (!is_array($samples)) {
        $samples = array();
    }

    // Verificar si el sample ya está en la colección
    if (in_array($sample_id, $samples)) {
        return [
            'success' => false,
            'message' => 'Este sample ya existe en la colección'
        ];
    }

    // Agregar el nuevo sample
    $samples[] = $sample_id;
    $updated = update_post_meta($collection_id, 'samples', $samples);

    if ($updated) {

        update_post_meta($collection_id, 'ultimaModificacion', current_time('mysql'));
        $samplesGuardados = get_user_meta($user_id, 'samplesGuardados', true);
        if (!is_array($samplesGuardados)) {
            $samplesGuardados = array();
        }
        if (!isset($samplesGuardados[$sample_id])) {
            $samplesGuardados[$sample_id] = [];
        }
        $samplesGuardados[$sample_id][] = $collection_id;
        update_user_meta($user_id, 'samplesGuardados', $samplesGuardados);
        borrarCacheColeccion($collection_id);
        actualizarTimestampSamplesGuardados($user_id);

        return [
            'success' => true,
            'message' => 'Sample agregado exitosamente',
            'samples' => $samples
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al guardar el sample en la colección'
        ];
    }
}

// Refactor(Org): Funcion botonColeccion movida a app/View/Helpers/UIHelper.php

function eliminarSampledeColec()
{
    // Verificar si el usuario está logueado
    if (!is_user_logged_in()) {
        wp_send_json_error(['error' => 'Usuario no autenticado']);
        return;
    }

    // Obtener los datos de la petición
    $coleccionId = isset($_POST['coleccion_id']) ? intval($_POST['coleccion_id']) : 0;
    $sample_id = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
    $userId = get_current_user_id();
    $coleccion = get_post($coleccionId);

    // Verificar si la colección existe
    if (!$coleccion) {
        wp_send_json_error(['error' => 'Colección no encontrada']);
        return;
    }

    // Verificar que el usuario sea el propietario de la colección
    if ($coleccion->post_author != $userId) {
        wp_send_json_error(['error' => 'No tienes permisos para modificar esta colección']);
        return;
    }

    // Obtener la meta 'samples' actual
    $samples = get_post_meta($coleccionId, 'samples', true);
    if (!is_array($samples)) {
        $samples = [];
    }

    // Buscar y remover el sample_id de la colección
    $key = array_search($sample_id, $samples);
    if ($key !== false) {
        unset($samples[$key]); // Remover el sample del array
        $samples = array_values($samples); // Reindexar el array
        update_post_meta($coleccionId, 'samples', $samples); // Actualizar el meta

        // Eliminar el registro del sample en los metadatos del usuario
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        if (isset($samplesGuardados[$sample_id])) {
            // Buscar y eliminar la colección específica del sample
            $index = array_search($coleccionId, $samplesGuardados[$sample_id]);
            if ($index !== false) {
                unset($samplesGuardados[$sample_id][$index]);
                $samplesGuardados[$sample_id] = array_values($samplesGuardados[$sample_id]); // Reindexar el array

                // Si no quedan colecciones para el sample, eliminar la entrada del sample en los metadatos
                if (empty($samplesGuardados[$sample_id])) {
                    unset($samplesGuardados[$sample_id]);
                }
            }
        }

        // Actualizar los metadatos del usuario
        update_user_meta($userId, 'samplesGuardados', $samplesGuardados);
        borrarCacheColeccion($coleccionId);
        wp_send_json_success(['message' => 'Sample eliminado de colección']);
    } else {
        wp_send_json_error(['message' => 'No se encontró el sample en la colección']);
    }
}

add_action('wp_ajax_eliminarSampledeColec', 'eliminarSampledeColec');

function borrarColec()
{
    // Verificar autenticación del usuario
    if (!is_user_logged_in()) {
        error_log('borrarColec: Usuario no autenticado intentó acceder.');
        wp_send_json_error(['message' => 'Usuario no autenticado']);
    }

    $coleccionId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$coleccionId) {
        error_log('borrarColec: El ID de la colección no se recibió o no es válido.');
        wp_send_json_error(['message' => 'ID de colección no válido']);
    }

    $userId = get_current_user_id();
    if (!$userId) {
        error_log('borrarColec: No se pudo obtener el ID del usuario actual.');
        wp_send_json_error(['message' => 'Error al obtener el usuario actual']);
    }

    $coleccion = get_post($coleccionId);
    if (!$coleccion) {
        error_log("borrarColec: La colección con ID {$coleccionId} no existe.");
        wp_send_json_error(['message' => 'La colección no existe']);
    }

    // Verificar si la colección pertenece al usuario actual
    if ($coleccion->post_author != $userId) {
        error_log("borrarColec: El usuario con ID {$userId} intentó eliminar una colección que no le pertenece (ID colección: {$coleccionId}).");
        wp_send_json_error(['message' => 'No tienes permisos para eliminar esta colección']);
    }

    // Obtener todos los samples de la colección antes de eliminarla
    $samples = get_post_meta($coleccionId, 'samples', true);
    if (!is_array($samples)) {
        $samples = [];
    }

    // Obtener los metadatos de samples guardados del usuario
    $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
    if (!is_array($samplesGuardados)) {
        $samplesGuardados = [];
    }

    // Recorrer cada sample y eliminar la referencia a la colección
    $samplesModificados = false; // Bandera para detectar si algo cambió
    foreach ($samples as $sample_id) {
        if (isset($samplesGuardados[$sample_id])) {
            // Buscar el índice de la colección en la lista de colecciones del sample
            $index = array_search($coleccionId, $samplesGuardados[$sample_id]);
            if ($index !== false) {
                unset($samplesGuardados[$sample_id][$index]);  // Eliminar la colección de la lista
                $samplesGuardados[$sample_id] = array_values($samplesGuardados[$sample_id]);  // Reindexar el array

                // Si no quedan colecciones asociadas al sample, eliminar el sample de 'samplesGuardados'
                if (empty($samplesGuardados[$sample_id])) {
                    unset($samplesGuardados[$sample_id]);
                }

                $samplesModificados = true;  // Algo ha cambiado
            }
        }
    }

    // Solo intentar actualizar los metadatos si hubo cambios
    if ($samplesModificados) {
        $updated = update_user_meta($userId, 'samplesGuardados', $samplesGuardados);
        if (!$updated) {
            error_log("borrarColec: Fallo al actualizar los metadatos de samples guardados para el usuario con ID {$userId}.");
            wp_send_json_error(['message' => 'Error al actualizar los metadatos del usuario']);
        }
    }

    // Si no quedan samples guardados, eliminar la entrada meta del usuario
    if (empty($samplesGuardados)) {
        delete_user_meta($userId, 'samplesGuardados');
    }

    // Eliminar la colección
    if (!wp_delete_post($coleccionId, true)) {
        error_log("borrarColec: Fallo al eliminar la colección con ID {$coleccionId}.");
        wp_send_json_error(['message' => 'Error al eliminar la colección']);
    }

    // Responder con éxito
    wp_send_json_success(['message' => 'Colección eliminada correctamente']);
}

// Refactor(Org): Funcion datosColeccion movida desde app/Content/Colecciones/View/renderPostColec.php
function datosColeccion($postId)
{
    // Registro de inicio de la función
    error_log("Inicio de la función datosColeccion para el post ID: " . $postId);

    try {
        // Obtener el metadato 'samples' y deserializarlo
        $samples_serialized = get_post_meta($postId, 'samples', true);
        if (empty($samples_serialized)) {
            error_log("Error en datosColeccion: Metadato 'samples' vacío para el post ID: " . $postId);
            return;
        }

        // Deserializar el array de muestras
        $samples = maybe_unserialize_dos($samples_serialized);

        if (!is_array($samples)) {
            preg_match_all('/i:\d+;i:(\d+);/', $samples_serialized, $matches);
            if (isset($matches[1])) {
                $samples = array_map('intval', $matches[1]);
            } else {
                error_log("Error en datosColeccion: No se pudo deserializar 'samples' para el post ID: " . $postId);
                return;
            }
        }

        // Inicializar el arreglo para 'datosColeccion'
        $datos_coleccion = [
            //'descripcion_corta'      => [],
            'estado_animo'           => [],
            'artista_posible'        => [],
            'genero_posible'         => [],
            'instrumentos_principal' => [],
            'tags_posibles'          => [],
        ];

        // Campos a procesar
        $campos = array_keys($datos_coleccion);

        foreach ($samples as $sample_id) {
            // Obtener 'datosAlgoritmo'
            $datos_algoritmo = get_post_meta($sample_id, 'datosAlgoritmo', true);
            if (empty($datos_algoritmo)) {
                $datos_algoritmo_respaldo = get_post_meta($sample_id, 'datosAlgoritmo_respaldo', true);
                if (!empty($datos_algoritmo_respaldo)) {
                    if (is_array($datos_algoritmo_respaldo) || is_object($datos_algoritmo_respaldo)) {
                        // Serializar o codificar según el caso
                        $datos_algoritmo = json_encode($datos_algoritmo_respaldo);
                    } else {
                        // Asumir que está serializado
                        $datos_algoritmo = maybe_unserialize_dos($datos_algoritmo_respaldo);
                        if (is_object($datos_algoritmo) || is_array($datos_algoritmo)) {
                            $datos_algoritmo = json_encode($datos_algoritmo);
                        }
                    }
                } else {
                    // No hay datos para este sample, continuar al siguiente
                    error_log("Advertencia en datosColeccion: No se encontraron datosAlgoritmo ni datosAlgoritmo_respaldo para el sample ID: " . $sample_id);
                    continue;
                }
            }

            // Validar que el dato sea un string antes de decodificar JSON
            if (is_array($datos_algoritmo)) {
                $datos_algoritmo = json_encode($datos_algoritmo);
            } elseif (!is_string($datos_algoritmo)) {
                error_log("Error: datosAlgoritmo no es string ni array. Sample ID: " . $sample_id);
                continue;
            }

            // Decodificar el JSON
            $datos_algoritmo_array = json_decode($datos_algoritmo, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // JSON inválido, intentar deserializar si es posible
                $datos_algoritmo_array = maybe_unserialize_dos($datos_algoritmo);
                if (!is_array($datos_algoritmo_array)) {
                    // No se puede procesar este sample, saltar
                    error_log("Error en datosColeccion: No se pudo decodificar o deserializar datosAlgoritmo para el sample ID: " . $sample_id);
                    continue;
                }
            }

            // Procesar cada campo
            foreach ($campos as $campo) {
                if (isset($datos_algoritmo_array[$campo])) {
                    // Obtener solo los datos en inglés
                    if (isset($datos_algoritmo_array[$campo]['en'])) {
                        $valores = $datos_algoritmo_array[$campo]['en'];
                    } else {
                        // Si no existe el campo 'en', saltar
                        continue;
                    }

                    // Aplanar el array en caso de que haya arrays anidados
                    $valores = aplanarArray($valores);

                    if (!is_array($valores)) {
                        // Asegurarse de que es un array
                        $valores = [$valores];
                    }

                    foreach ($valores as $valor) {
                        // Validar el tipo de dato antes de aplicar trim
                        if (is_array($valor)) {
                            error_log("Advertencia: Se encontró un array dentro de '$campo'. Sample ID: $sample_id");
                            // Aplanar de nuevo si es necesario
                            $subvalores = aplanarArray($valor);
                            foreach ($subvalores as $subvalor) {
                                $subvalor = trim((string) $subvalor);
                                if ($subvalor === '') {
                                    continue;
                                }
                                if (isset($datos_coleccion[$campo][$subvalor])) {
                                    $datos_coleccion[$campo][$subvalor]++;
                                } else {
                                    $datos_coleccion[$campo][$subvalor] = 1;
                                }
                            }
                            continue; // Ya procesamos los subvalores
                        }

                        // Normalizar el valor (trim, mayúsculas/minúsculas según necesidad)
                        $valor = trim((string) $valor); // Convertir a string por seguridad
                        if ($valor === '') {
                            continue;
                        }
                        if (isset($datos_coleccion[$campo][$valor])) {
                            $datos_coleccion[$campo][$valor]++;
                        } else {
                            $datos_coleccion[$campo][$valor] = 1;
                        }
                    }
                }
            }
        }

        // Opcional: Ordenar los resultados por frecuencia descendente
        foreach ($datos_coleccion as &$campo) {
            arsort($campo);
        }
        unset($campo); // Desreferenciar

        $datos_coleccion_json = json_encode($datos_coleccion, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        update_post_meta($postId, 'datosColeccion', $datos_coleccion_json);

        // Registro de finalización exitosa de la función
        error_log("Función datosColeccion completada con éxito para el post ID: " . $postId);
    } catch (Exception $e) {
        // Capturar cualquier excepción y registrar el error
        error_log("Error fatal en datosColeccion para el post ID: " . $postId . ". Mensaje de error: " . $e->getMessage());
    }
}

// Refactor(Org): Funcion variablesColec movida desde app/Content/Colecciones/View/renderPostColec.php
function variablesColec($postId = null)
{
    // Si no se proporciona un postId, usa el ID del post global.
    if ($postId === null) {
        global $post;
        $postId = $post->ID;
    }

    $usuarioActual = get_current_user_id();
    $autorId = get_post_field('post_author', $postId);
    $samplesMeta = get_post_meta($postId, 'samples', true);
    $datosColeccion = get_post_meta($postId, 'datosColeccion', true);
    $sampleCount = 0;
    $sampleCountReal = 0; // Inicializar la variable

    if (!empty($samplesMeta)) {
        $samplesArray = maybe_unserialize_dos($samplesMeta);

        if (is_array($samplesArray)) {
            $sampleCount = count($samplesArray);

            // Contar los samples no descargados
            if ($usuarioActual) {
                $descargas_anteriores = get_user_meta($usuarioActual, 'descargas', true);
                $sampleCountReal = 0;

                foreach ($samplesArray as $sampleId) {
                    // Verificar si el sample actual NO ha sido descargado
                    if (!isset($descargas_anteriores[$sampleId])) {
                        $sampleCountReal++;
                    }
                }
            } else {
                // Si no hay usuario actual (no ha iniciado sesión), el costo es el total de samples
                $sampleCountReal = $sampleCount;
            }
        }
    }

    return [
        'fecha' => get_the_date('', $postId),
        'colecStatus' => get_post_status($postId),
        'autorId' => $autorId,
        'samples' => $sampleCount . ' samples',
        'datosColeccion' => $datosColeccion,
        'sampleCount' => $sampleCountReal, // Usar el valor calculado
    ];
}

add_action('wp_ajax_crearColeccion', 'crearColeccion');
add_action('wp_ajax_editarColeccion', 'editarColeccion');
add_action('wp_ajax_borrarColec', 'borrarColec');
add_action('wp_ajax_guardarSampleEnColec', 'guardarSampleEnColec');

// Refactor(Org): Funcion verificarSampleEnColec movida desde app/Content/Colecciones/View/renderModalColec.php
add_action('wp_ajax_verificar_sample_en_colecciones', 'verificarSampleEnColec');

function verificarSampleEnColec()
{
    $sample_id = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
    $colecciones_con_sample = array();

    if ($sample_id) {
        // Obtener todas las colecciones del usuario actual
        $current_user_id = get_current_user_id();
        $args = array(
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'author'         => $current_user_id,
        );

        $colecciones = get_posts($args);

        // Verificar cada colección
        foreach ($colecciones as $coleccion) {
            $samples = get_post_meta($coleccion->ID, 'samples', true);
            if (is_array($samples) && in_array($sample_id, $samples)) {
                $colecciones_con_sample[] = $coleccion->ID;
            }
        }
    }

    wp_send_json_success(array(
        'colecciones' => $colecciones_con_sample
    ));
}
