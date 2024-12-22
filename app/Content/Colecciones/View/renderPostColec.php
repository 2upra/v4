<?

function htmlColec($filtro)
{
    ob_start();
    $postId = get_the_ID();
    $vars = variablesColec($postId);
    extract($vars);
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV no-refresh"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo esc_attr($postId); ?>"
        autor="<? echo esc_attr($autorId); ?>">

        <div class="post-content">
            <? echo imagenColeccion($postId); ?>
            <div class="KLYJBY">
                <? echo audioPost($postId); ?>
            </div>
            <?
            $post_type = get_post_type($postId);
            if ($post_type !== 'social_post') {
            ?>
                <h2 class="post-title" data-post-id="<? echo esc_attr($postId); ?>">
                    <? echo get_the_title($postId); ?>
                </h2>
            <?
            } else {
            ?>
                <div class="LRKHLC">
                    <div class="XOKALG">
                        <?
                        $rola_meta = get_post_meta($postId, 'rola', true);
                        $tienda_meta = get_post_meta($postId, 'tienda', true);
                        $nombre_rola_html = '';

                        if ($rola_meta === '1' || $tienda_meta === '1') {
                            $nombre_rola = get_post_meta($postId, 'nombreRola', true);
                            if (empty($nombre_rola)) {
                                $nombre_rola = get_post_meta($postId, 'nombreRola1', true);
                            }
                            if (empty($nombre_rola)) {
                                $nombre_rola =  get_the_title($postId);
                            }
                            if (!empty($nombre_rola)) {
                                $nombre_rola_html = '<p class="nameRola">' . esc_html($nombre_rola) . '</p>';
                            }
                        }

                        $output .= $nombre_rola_html;

                        echo $output;
                        ?>
                    </div>
                </div>
            <?
            }
            ?>
            <div class="CPQBEN" style="display: none;">

                <? echo like($postId);?>
                <? echo botonCompra($postId); ?>
                <div class="CPQBAU"><? echo get_the_author_meta('display_name', $autorId); ?></div>
                <div class="CPQBCO">
                    <?

                    if ($rola_meta === '1' || $tienda_meta === '1') {
                        $nombre_rola = get_post_meta($postId, 'nombreRola', true);
                        if (empty($nombre_rola)) {
                            $nombre_rola = get_post_meta($postId, 'nombreRola1', true);
                        }
                        if (!empty($nombre_rola)) {
                            echo "<p>" . esc_html($nombre_rola) . "</p>";
                        } else {
                        }
                    } else {
                    }
                    ?>
                </div>
            </div>
            <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
            <? echo botonCompra($postId); ?>
        </div>
    </li>
<?
    return ob_get_clean();
}

function aplanarArray($input)
{
    $result = [];
    if (is_array($input)) {
        foreach ($input as $element) {
            if (is_array($element)) {
                $result = array_merge($result, aplanarArray($element));
            } else {
                $result[] = $element;
            }
        }
    } else {
        $result[] = $input;
    }
    return $result;
}

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

function maybe_unserialize_dos($data)
{
    if (empty($data)) {
        return $data;
    }

    // Si el dato ya es un array, devolverlo tal cual
    if (is_array($data)) {
        return $data;
    }

    // Intentar decodificar JSON si es un string
    if (is_string($data)) {
        $json = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
    }

    // Intentar deserializar si es un string
    $unserialized = @unserialize($data);
    if ($unserialized !== false || $data === 'b:0;') {
        return $unserialized;
    }

    // Devolver el original si no se pudo deserializar ni decodificar
    return $data;
}



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


function imagenColeccion($postId)
{
    $imagenSize = 'large';
    $quality = 60;
    $imagenUrl = imagenPost($postId, $imagenSize, $quality, 'all', false, true);
    $imagenProcesada = img($imagenUrl, $quality, 'all');
    $postType = get_post_type($postId);

    ob_start();
?>
    <div class="post-image-container">
        <? if ($postType !== 'social_post') : ?>
            <a href="<? echo esc_url(get_permalink($postId)); ?>" data-post-id="<? echo $postId; ?>" class="imagenColecS">
            <? endif; ?>
            <img class="imagenMusic" src="<? echo esc_url($imagenProcesada); ?>" alt="Post Image" data-post-id="<? echo $postId; ?>" />
            <div class="KLYJBY">
                <? echo audioPost($postId); ?>
            </div>
            <? if ($postType !== 'social_post') : ?>
            </a>
        <? endif; ?>
    </div>
<?

    $output = ob_get_clean();

    return $output;
}


function imagenPost($postId, $size = 'medium', $quality = 50, $strip = 'all', $pixelated = false, $use_temp = false)
{
    $post_thumbnail_id = get_post_thumbnail_id($postId);
    if ($post_thumbnail_id) {
        $url = wp_get_attachment_image_url($post_thumbnail_id, $size);
    } elseif ($use_temp) {
        $temp_image_id = get_post_meta($postId, 'imagenTemporal', true);

        // Si existe una imagen temporal, úsala
        if ($temp_image_id && wp_attachment_is_image($temp_image_id)) {
            $url = wp_get_attachment_image_url($temp_image_id, $size);
        } else {
            // Si no existe imagen temporal, sube una nueva
            $random_image_path = obtenerImagenAleatoria('/home/asley01/MEGA/Waw/random');
            if (!$random_image_path) {
                ejecutarScriptPermisos();
                error_log('imagenPost: No se pudo obtener imagen aleatoria para el post ID ' . $postId);
                return false;
            }
            $temp_image_id = subirImagenALibreria($random_image_path, $postId);
            if (!$temp_image_id) {
                ejecutarScriptPermisos();
                error_log('imagenPost: No se pudo subir imagen temporal para el post ID ' . $postId);
                return false;
            }
            update_post_meta($postId, 'imagenTemporal', $temp_image_id);
            $url = wp_get_attachment_image_url($temp_image_id, $size);
        }
    } else {
        return false;
    }

    if (function_exists('jetpack_photon_url') && $url) {
        $args = array('quality' => $quality, 'strip' => $strip);
        if ($pixelated) {
            $args['w'] = 50;
            $args['h'] = 50;
            $args['zoom'] = 2;
        }
        return jetpack_photon_url($url, $args);
    }
    return $url;
}

function singleColec($postId)
{
    $vars = variablesColec($postId);
    extract($vars);
    ob_start()
?>
    <div class="AMORP">
        <? echo imagenColeccion($postId); ?>
        <div class="ORGDE">

            <div class="AGDEORF">
                <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
                <h2 class="tituloColec" data-post-id="<? echo $postId; ?>"><? echo get_the_title($postId); ?></h2>
                <div class="DSEDBE">
                    <? echo $samples ?>
                </div>
                <div class="BOTONESCOLEC">
                    <? echo botonDescargaColec($postId, $sampleCount); ?>
                    <? echo botonSincronizarColec($postId, $sampleCount); ?>
                    <? echo like($postId); ?>
                    <? echo opcionesColec($postId, $autorId); ?>
                </div>
            </div>

            <div class="INFEIS">
                <? echo datosColeccion($postId); ?>
                <div class="tags-container-colec" id="tags-<? echo get_the_ID(); ?>"></div>

                <p id="dataColec" id-post-algoritmo="<? echo get_the_ID(); ?>" style="display:none;">
                    <? echo esc_html(limpiarJSON($datosColeccion)); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="LISTCOLECSIN">
        <? echo publicaciones(['post_type' => 'social_post', 'filtro' => 'sampleList', 'posts' => 12, 'colec' => $postId]); ?>
    </div>

<?
    return ob_get_clean();
}

function masIdeasColeb($postId)
{
    ob_start()
?>

    <div class="LISTCOLECSIN">
        <? echo publicaciones(['post_type' => 'social_post', 'filtro' => 'sampleList', 'posts' => 12, 'colec' => $postId, 'idea' => true]);  ?>
    </div>

<?
    return ob_get_clean();
}

function opcionesColec($postId, $autorId)
{
    $usuarioActual = get_current_user_id();
    $post_verificado = get_post_meta($postId, 'Verificado', true);
    ob_start();
?>
    <button class="HR695R8" data-post-id="<? echo $postId; ?>"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionespost-<? echo $postId; ?>">
        <div class="A1806242">
            <? if (current_user_can('administrator')) : ?>
                <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
                <button class="cambiarTitulo" data-post-id="<? echo $postId; ?>">Cambiar titulo</button>
                <button class="cambiarImagen" data-post-id="<? echo $postId; ?>">Cambiar imagen</button>
                <? if (!$post_verificado) : ?>
                    <button class="verificarPost" data-post-id="<? echo $postId; ?>">Verificar</button>
                <? endif; ?>
                <button class="editarWordPress" data-post-id="<? echo $postId; ?>">Editar en WordPress</button>
                <button class="banearUsuario" data-post-id="<? echo $postId; ?>">Banear</button>
            <? elseif ($usuarioActual == $autorId) : ?>
                <button class="eliminarPost" data-post-id="<? echo $postId; ?>">Eliminar</button>
                <button class="cambiarImagen" data-post-id="<? echo $postId; ?>">Cambiar Imagen</button>
            <? else : ?>
                <button class="reporte" data-post-id="<? echo $postId; ?>" tipoContenido="social_post">Reportar</button>
                <button class="bloquear" data-post-id="<? echo $postId; ?>">Bloquear</button>
            <? endif; ?>
        </div>
    </div>

    <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
<?
    return ob_get_clean();
}
