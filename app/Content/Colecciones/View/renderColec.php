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
        $samplesArray = maybe_unserialize($samplesMeta);
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
        error_log('imagenPost: No se encontró imagen para el post ID ' . $postId);
        return false;
    }

    error_log('imagenPost: URL generada - ' . $url);

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
    error_log('img: URL generada - ' . $final_url);

    return $final_url;
}

function singleColec($postId)
{
    $vars = variablesColec($postId);
    extract($vars);
    ob_start()
?>
    <div>
        <? echo imagenColeccion($postId); ?>
        <div>
            <p class="post-author"><? echo get_the_author_meta('display_name', $autorId); ?></p>
            <h2 class="post-title"><? echo get_the_title($postId); ?></h2>
            <div>
                <? echo $samples ?>
            </div>
            <? echo $botonDescargaColec ?>
        </div>
    </div>

    <div>
        
    </div>

    <?
    return ob_get_clean();
}

function botonDescargaColec($postID)
{
    ob_start();

    $userID = get_current_user_id();

    if ($userID) {
        $descargas_anteriores = get_user_meta($userID, 'descargas', true);
        $yaDescargado = isset($descargas_anteriores[$postID]);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';

    ?>
        <div class="ZAQIBB">
            <button class="icon-arrow-down <? echo esc_attr($claseExtra); ?>"
                data-post-id="<? echo esc_attr($postID); ?>"
                aria-label="Boton Descarga"
                id="download-button-<? echo esc_attr($postID); ?>"
                onclick="return procesarDescarga('<? echo esc_js($postID); ?>', '<? echo esc_js($userID); ?>')">
                <? echo $GLOBALS['descargaicono']; ?>
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
