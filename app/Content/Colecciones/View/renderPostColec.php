<?

function htmlColec($filtro)
{
    ob_start();
    $postId = get_the_ID();
    $vars = variablesColec($postId);
    extract($vars);
?>
    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo esc_attr($postId); ?>"
        autor="<? echo esc_attr($autorId); ?>">

        <div class="post-content">
            <? echo imagenColeccion($postId); ?>
            <h2 class="post-title"><? echo get_the_title($postId); ?></h2>
            <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
        </div>
    </li>
<?
    return ob_get_clean();
}

/*
[17-Nov-2024 00:35:34 UTC] PHP Fatal error:  Cannot redeclare maybe_unserialize() (previously declared in /var/www/wordpress/wp-includes/functions.php:648) in /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/View/renderPostColec.php on line 158
[17-Nov-2024 00:35:45 UTC] PHP Fatal error:  Uncaught TypeError: json_decode(): Argument #1 ($json) must be of type string, array given in /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/View/renderPostColec.php:148
Stack trace:
#0 /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/View/renderPostColec.php(148): json_decode()
#1 /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/View/renderPostColec.php(182): maybe_unserialize_dos()
#2 /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/View/renderPostColec.php(286): variablesColec()
#3 /var/www/wordpress/wp-content/themes/2upra3v/single-colecciones.php(29): singleColec()
#4 /var/www/wordpress/wp-includes/template-loader.php(106): include('...')
#5 /var/www/wordpress/wp-blog-header.php(19): require_once('...')
#6 /var/www/wordpress/index.php(17): require('...')
#7 {main}
  thrown in /var/www/wordpress/wp-content/themes/2upra3v/app/Content/Colecciones/View/renderPostColec.php on line 148
*/


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
            'descripcion_corta'     => [],
            'estado_animo'          => [],
            'artista_posible'       => [],
            'genero_posible'        => [],
            'instrumentos_principal' => [],
            'tags_posibles'         => [],
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
                    $valores = $datos_algoritmo_array[$campo];
                    if (!is_array($valores)) {
                        // Asegurarse de que es un array
                        $valores = [$valores];
                    }
                    foreach ($valores as $valor) {
                        // Validar el tipo de dato antes de aplicar trim
                        if (is_array($valor)) {
                            error_log("Advertencia: Se encontró un array en lugar de un string al procesar el campo '$campo'. Sample ID: $sample_id");
                            continue; // Saltar este valor
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




function variablesColec($postId)
{
    // Si no se proporciona un postId, usa el ID del post global.
    if ($postId === null) {
        global $post;
        $postId = $post->ID;
    }

    // Obtiene el ID del usuario actual.
    $usuarioActual = get_current_user_id();

    // Obtiene el ID del autor del post.
    $autorId = get_post_field('post_author', $postId);

    // Obtiene los metadatos de 'samples' del post.
    $samplesMeta = get_post_meta($postId, 'samples', true);
    $sampleCount = 0;

    // Si los datos están serializados, cuenta los elementos.
    if (!empty($samplesMeta)) {
        $samplesArray = maybe_unserialize_dos($samplesMeta);
        if (is_array($samplesArray)) {
            $sampleCount = count($samplesArray);
        }
    }

    return [
        'fecha' => get_the_date('', $postId),
        'colecStatus' => get_post_status($postId),
        'autorId' => $autorId,
        'samples' => $sampleCount . ' samples',
    ];
}


function imagenColeccion($postId)
{
    $imagenSize = 'large';
    $quality = 60;
    $imagenUrl = imagenPost($postId, $imagenSize, $quality, 'all', false, true);
    $imagenProcesada = img($imagenUrl, $quality, 'all');

    ob_start();
?>
    <div class="post-image-container">
        <a href="<? echo esc_url(get_permalink($postId)); ?>">
            <img src="<? echo esc_url($imagenProcesada); ?>" alt="Post Image" />
        </a>
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

function img($url, $quality = 40, $strip = 'all')
{
    if ($url === null || $url === '') {
        return '';
    }
    $parsed_url = parse_url($url);
    if (strpos($url, 'https://i0.wp.com/') === 0) {
        $cdn_url = $url;
    } else {
        $path = isset($parsed_url['host']) ? $parsed_url['host'] . $parsed_url['path'] : ltrim($parsed_url['path'], '/');
        $cdn_url = 'https://i0.wp.com/' . $path;
    }

    $query = [
        'quality' => $quality,
        'strip' => $strip,
    ];

    $final_url = add_query_arg($query, $cdn_url);
    return $final_url;
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
                <h2 class="post-title"><? echo get_the_title($postId); ?></h2>
                <div class="DSEDBE">
                    <? echo $samples ?>
                </div>
                <div class="BOTONESCOLEC">
                    <? echo botonDescargaColec($postId); ?>
                    <? echo like($postId); ?>
                </div>
            </div>

            <div class="INFEIS">
                <? echo datosColeccion($postId); ?>
            </div>
        </div>
    </div>

    <div class="LISTCOLECSIN">
        <? echo publicaciones(['post_type' => 'social_post', 'filtro' => 'sampleList', 'posts' => 12, 'colec' => $postId]); ?>
    </div>

    <?
    return ob_get_clean();
}

function botonDescargaColec($postId)
{
    ob_start();

    $userID = get_current_user_id();

    if ($userID) {
        $descargas_anteriores = get_user_meta($userID, 'descargas', true);
        $yaDescargado = isset($descargas_anteriores[$postId]);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';

    ?>
        <div class="ZAQIBB">
            <button class="icon-arrow-down botonprincipal <? echo esc_attr($claseExtra); ?>"
                data-post-id="<? echo esc_attr($postId); ?>"
                aria-label="Boton Descarga"
                id="download-button-<? echo esc_attr($postId); ?>"
                onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($userID); ?>')">
                <? echo $GLOBALS['descargaicono']; ?> Descargar
            </button>
        </div>
    <?
    } else {
    ?>
        <div class="ZAQIBB">
            <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                <? echo $GLOBALS['descargaicono']; ?>
            </button>
        </div>
<?
    }


    return ob_get_clean();
}
