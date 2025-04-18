<?php

/**
 * Configuración inicial del tema.
 * Crea páginas esenciales si no existen en entornos locales.
 */
function paginasIniciales1()
{
    // Verificar si las paginas ya fueron creadas
    if (get_option('paginasIniciales1') == '1') return;


    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
        update_option('paginasIniciales1', '1');
        return;
    }


    $paginas = array(
        'Inicio' => array(
            'plantilla' => 'TemplateInicio.php',
            'contenido' => 'Este es el contenido de la pagina de inicio.'
        ),
        'Colab' => array(
            'plantilla' => 'TemplateColab.php',
            'contenido' => ''
        ),
        'Dev' => array(
            'plantilla' => 'TemplateDev.php',
            'contenido' => ''
        ),
        'Colec' => array(
            'plantilla' => 'TemplateColec.php',
            'contenido' => ''
        ),
        'Feed' => array(
            'plantilla' => 'TemplateFeed.php',
            'contenido' => ''
        ),
        'FeedSample' => array(
            'plantilla' => 'TemplateFeedSample.php',
            'contenido' => ''
        ),
        'Inversor' => array(
            'plantilla' => 'TemplateInversor.php',
            'contenido' => ''
        ),
        'Music' => array(
            'plantilla' => 'TemplateMusic.php',
            'contenido' => ''
        ),
        'Prueba' => array(
            'plantilla' => 'TemplatePrueba.php',
            'contenido' => ''
        ),
        'Sample' => array(
            'plantilla' => 'TemplateSample.php',
            'contenido' => ''
        ),
        'Sello' => array(
            'plantilla' => 'TemplateSello.php',
            'contenido' => ''
        ),
        'T&Q' => array(
            'plantilla' => 'TemplateT&Q.php',
            'contenido' => ''
        ),
        'Biblioteca' => array(
            'plantilla' => 'TemplateBiblioteca.php',
            'contenido' => ''
        )
    );

    // Recorrer el array y crear las paginas
    $inicio_id = 0; // Variable para guardar el ID de la pagina de inicio
    foreach ($paginas as $titulo => $datos) {
        // Usar WP_Query en lugar de get_page_by_title
        $pagina_query = new \WP_Query(array(
            'post_type' => 'page',
            'title'     => $titulo,
            'post_status' => 'any'
        ));

        if (!$pagina_query->have_posts()) {
            $nueva_pagina = array(
                'post_title'    => $titulo,
                'post_content'  => $datos['contenido'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'page_template' => $datos['plantilla']
            );

            $nueva_pagina_id = wp_insert_post($nueva_pagina);

            // Si la pagina creada es la de inicio, guardar su ID
            if ($titulo == 'Inicio') {
                $inicio_id = $nueva_pagina_id;
            }
        }

        // Liberar memoria
        wp_reset_postdata();
    }

    // Definir la pagina de inicio
    if ($inicio_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $inicio_id);
    }

    // Marcar que las paginas ya fueron creadas
    update_option('paginasIniciales1', '1');
}


/**
 * Añade meta tags genéricos, favicons, etc., al <head> HTML.
 * Se ejecuta en el hook 'wp_head'.
 */
function headGeneric()
{
    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === true)) {
        // No hacer nada en local
        return;
    }
?>

    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="https://2upra.com/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="57x57" href="https://2upra.com/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="https://2upra.com/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://2upra.com/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="https://2upra.com/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="https://2upra.com/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="https://2upra.com/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://2upra.com/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://2upra.com/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://2upra.com/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="https://2upra.com/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://2upra.com/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="https://2upra.com/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://2upra.com/favicon-16x16.png">
    <link rel="manifest" href="https://2upra.com/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="https://2upra.com/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

    <!-- Etiquetas Open Graph para Facebook y otras plataformas -->
    <meta property="og:title" content="<?php echo get_the_title(); ?>" />
    <meta property="og:description" content="Social Media para artistas" />
    <meta property="og:image" content="https://i0.wp.com/2upra.com/wp-content/uploads/2024/11/Pinterest_Download-47-28-818x1024.jpg?quality=60&strip=all" />
    <meta property="og:url" content="https://2upra.com" />
    <meta property="og:type" content="website" />

    <!-- Etiquetas de Twitter Cards -->
    <meta property="og:title" content="<?php echo get_the_title(); ?>" />
    <meta name="twitter:title" content="Social Media para artistas">
    <meta name="twitter:description" content="Descripcion de tu pagina que aparecera al compartir.">
    <meta name="twitter:image" content="https://i0.wp.com/2upra.com/wp-content/uploads/2024/11/Pinterest_Download-47-28-818x1024.jpg?quality=60&strip=all">
    <meta name="twitter:site" content="@wandorius" />

<?php
}
add_action('wp_head', 'headGeneric');

// Refactor(Org): Función obtenerIdiomaDelNavegador() movida a app/Utils/BrowserUtils.php

/**
 * Configura los metadatos de la página (título, descripción) y las cookies
 * basándose en el idioma detectado del navegador.
 */
function configurarMetadatosPaginaIdioma() {
    // Asegúrate de que la función obtenerIdiomaDelNavegador() esté disponible
    // (puede requerir incluir el archivo app/Utils/BrowserUtils.php si no se carga automáticamente)
    if (!function_exists('obtenerIdiomaDelNavegador')) {
        // Opcional: Registrar un error o manejar la ausencia de la función
        // error_log('Error: La función obtenerIdiomaDelNavegador() no está definida.');
        return; // Salir si la función no existe
    }

    $idioma = obtenerIdiomaDelNavegador();

    if ($idioma === 'es') {
        $titulo = "Social Media para Artistas | Samples y VST Gratis";
        $descripcion = "Unete a una red de creadores donde puedes conectar con artistas, colaborar en proyectos, y encontrar una amplia variedad de samples y plugins VST gratuitos para potenciar tus producciones musicales.";
    } else {
        $titulo = "Social Media for Artists | Free Samples & VSTs";
        $descripcion = "Join a network of creators where you can connect with artists, collaborate on projects, and access a wide range of free samples and VST plugins to enhance your music productions.";
    }

    // Añadir el título y la descripción al <head>
    add_action('wp_head', function () use ($titulo, $descripcion) {
        echo '<title>' . esc_html($titulo) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($descripcion) . '">' . "\n";
    }, 1); // Prioridad baja para que se ejecute temprano en wp_head

    // Configurar cookies para el título y descripción (si aún son necesarias)
    if (!headers_sent()) {
        setcookie("page_title", $titulo, time() + 3600, "/");
        setcookie("page_description", $descripcion, time() + 3600, "/");
    } else {
        // Opcional: Registrar un error si las cabeceras ya se enviaron
        // error_log("Advertencia: No se pudieron establecer las cookies page_title/page_description porque las cabeceras ya se enviaron.");
    }
}

/**
 * Precarga las fuentes principales del tema.
 * Solo se ejecuta en entornos no locales.
 */
function preload_fonts()
{
    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === true)) {
        return;
    }
    echo '<link rel="preload" href="https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/SourceSans3-Regular.woff2" as="font" type="font/woff2" crossorigin>';
    echo '<link rel="preload" href="https://2upra.com/wp-content/themes/2upra3v/assets/Fonts/SourceSans3-Bold.woff2" as="font" type="font/woff2" crossorigin>';
}
add_action('wp_head', 'preload_fonts', 1);

/**
 * Personaliza los meta tags del icono del sitio (favicon).
 *
 * Añade un enlace específico para el favicon.
 *
 * @param array $meta_tags Array de meta tags existentes.
 * @return array Array de meta tags modificado.
 */
function custom_site_icon($meta_tags)
{
    $meta_tags[] = sprintf('<link rel="icon" href="%s">', 'https://2upra.com/wp-content/themes/2upra3v/assets/icons/favicon-96x96.png');
    return $meta_tags;
}
add_filter('site_icon_meta_tags', 'custom_site_icon');

// Refactor(Org): Moved innerHeight function and hook to app/Setup/ScriptSetup.php

// Refactor(Org): Moved MIME type filters to app/Setup/MimeTypesSetup.php

// Refactor(Org): Moved CPT and status registration to PostTypesSetup.php

// Acción de refactorización: La función configurarMetadatosPaginaIdioma() ya se encontraba en este archivo. No se realizaron cambios.

// Refactor(Org): Mover lógica de normalización de posts y hooks desde TagUtils.php
// Funciones movidas desde app/Functions/normalizarTags.php (originalmente en app/Utils/TagUtils.php)
function normalizarNuevoPost($post_id, $post, $update) {
    // Verificar si es el tipo de post correcto
    if ('social_post' !== get_post_type($post_id)) {
        return;
    }

    // Obtener los datos del algoritmo
    $meta_datos = get_post_meta($post_id, 'datosAlgoritmo', true);
    
    if (!is_array($meta_datos)) {
        return;
    }

    // Normalizaciones
    $normalizaciones = array(
        'one-shot' => 'one shot',
        'oneshot' => 'one shot',
        'percusión' => 'percusión',
        'hiphop' => 'hip hop',
        'hip-hop' => 'hip hop',
        'rnb' => 'r&b',
        'vocal' => 'vocals',
        'r&b' => 'r&b',
        'randb' => 'r&b',
        'rock&roll' => 'rock and roll',
        'rockandroll' => 'rock and roll',
        'rock-and-roll' => 'rock and roll',
        'campana de vaca' => 'cowbell',
        'cowbells' => 'cowbell',
        'drums' => 'drum',
    );

    // Verificar si ya existe un respaldo antes de crear uno nuevo
    $respaldo_existente = get_post_meta($post_id, 'datosAlgoritmo_respaldo', true);
    if (empty($respaldo_existente)) {
        // Se usa add_post_meta con $meta_datos que es un array, WP lo serializará
        add_post_meta($post_id, 'datosAlgoritmo_respaldo', $meta_datos, true);
    }

    // Normalizar tags
    $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];
    $fue_modificado = false;

    foreach ($campos as $campo) {
        foreach (['es', 'en'] as $idioma) {
            if (!empty($meta_datos[$campo][$idioma]) && is_array($meta_datos[$campo][$idioma])) {
                foreach ($meta_datos[$campo][$idioma] as &$tag) {
                    $tag_lower = strtolower(trim($tag));
                    if (isset($normalizaciones[$tag_lower])) {
                        $tag = $normalizaciones[$tag_lower];
                        $fue_modificado = true;
                    }
                }
                // Liberar la referencia
                unset($tag);
            }
        }
    }

    if ($fue_modificado) {
        // Se usa update_post_meta con $meta_datos que es un array, WP lo serializará
        update_post_meta($post_id, 'datosAlgoritmo', $meta_datos);
    }

    // Verificar y restaurar después de la normalización
    verificarYRestaurarDatos($post_id);
}

function normalizarPostActualizado($post_id, $post_after, $post_before) {
    if ('social_post' !== get_post_type($post_id)) {
        return;
    }
    // Solo ejecutar si el contenido del post ha cambiado
    if ($post_before->post_content === $post_after->post_content) {
        // Podríamos querer normalizar incluso si el contenido no cambia, 
        // por si los metadatos se actualizaron por otra vía, pero la lógica original lo evita.
        // Considerar si esta condición es realmente necesaria o si siempre debe normalizar.
        return;
    }
    normalizarNuevoPost($post_id, $post_after, true);
    // La llamada a verificarYRestaurarDatos ya está dentro de normalizarNuevoPost
    // llamarla aquí de nuevo es redundante.
    // verificarYRestaurarDatos($post_id); 
}

// Función para verificar y restaurar datos si 'datosAlgoritmo' está vacío
function verificarYRestaurarDatos($post_id) {
    // Verificar si datosAlgoritmo existe y no está vacío
    $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    
    if (empty($datos_algoritmo)) {
        // Intentar restaurar desde el respaldo
        $respaldo = get_post_meta($post_id, 'datosAlgoritmo_respaldo', true);
        
        if (!empty($respaldo)) {
            // Restaurar los datos desde el respaldo
            // $respaldo ya debería ser un array si se guardó correctamente
            update_post_meta($post_id, 'datosAlgoritmo', $respaldo);
            
            // Opcional: Registrar la restauración
            //error_log("Datos 'datosAlgoritmo' restaurados desde respaldo para el post ID: " . $post_id);
        }
    }
}

// Función para crear respaldo y normalizar en lote
function crearRespaldoYNormalizar($batch_size = 100) {
    global $wpdb;
    
    // Normalizaciones (repetidas de normalizarNuevoPost, considerar centralizar)
    $normalizaciones = array(
        'one-shot' => 'one shot',
        'oneshot' => 'one shot',
        'percusión' => 'percusión',
        'hiphop' => 'hip hop',
        'hip-hop' => 'hip hop',
        'rnb' => 'r&b',
        'vocal' => 'vocals',
        'r&b' => 'r&b',
        'randb' => 'r&b',
        'rock&roll' => 'rock and roll',
        'rockandroll' => 'rock and roll',
        'rock-and-roll' => 'rock and roll',
        'campana de vaca' => 'cowbell',
        'cowbells' => 'cowbell',
        'drums' => 'drum',
    );

    $offset = 0;
    $total_procesados = 0;

    do {
        // Obtener posts que tienen 'datosAlgoritmo' pero no 'datosAlgoritmo_respaldo'
        // La consulta original tenía un LEFT JOIN incorrecto, corregido aquí:
        $query = $wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'datosAlgoritmo_respaldo'
            WHERE p.post_type = 'social_post'
            AND pm2.meta_id IS NULL
            LIMIT %d OFFSET %d
        ", $batch_size, $offset);

        $resultados = $wpdb->get_results($query);
        
        if (empty($resultados)) {
            break;
        }

        foreach ($resultados as $row) {
            // El valor meta ya viene serializado de la BD
            $meta_datos_serializados = $row->meta_value;
            // Deserializar para trabajar con él
            $meta_datos = maybe_unserialize($meta_datos_serializados);
            
            if (!is_array($meta_datos)) {
                //error_log("Error al deserializar datosAlgoritmo para post ID: {$row->ID}");
                continue;
            }

            // Crear respaldo usando el valor serializado original
            add_post_meta($row->ID, 'datosAlgoritmo_respaldo', $meta_datos_serializados, true);

            // Normalizar tags (usando el array deserializado)
            $campos = ['instrumentos_principal', 'tags_posibles', 'estado_animo', 'genero_posible', 'tipo_audio'];
            $fue_modificado = false;

            foreach ($campos as $campo) {
                foreach (['es', 'en'] as $idioma) {
                    if (!empty($meta_datos[$campo][$idioma]) && is_array($meta_datos[$campo][$idioma])) {
                        foreach ($meta_datos[$campo][$idioma] as &$tag) {
                            $tag_lower = strtolower(trim($tag));
                            if (isset($normalizaciones[$tag_lower])) {
                                $tag = $normalizaciones[$tag_lower];
                                $fue_modificado = true;
                            }
                        }
                        unset($tag); // Liberar referencia
                    }
                }
            }

            if ($fue_modificado) {
                // Guardar el array modificado, WP lo serializará
                update_post_meta($row->ID, 'datosAlgoritmo', $meta_datos);
            }

            $total_procesados++;
        }

        // El offset se incrementa por el tamaño del lote para la siguiente página
        $offset += $batch_size; 
        
        // Pequeña pausa para no sobrecargar el servidor
        if ($total_procesados > 0 && $total_procesados % 1000 === 0) {
            sleep(1);
        }

    } while (count($resultados) === $batch_size);

    //error_log("Proceso de respaldo y normalización completado. Total procesados: " . $total_procesados);
    return $total_procesados;
}

// Para revertir los cambios si algo sale mal
function revertirNormalizacion($batch_size = 100) {
    global $wpdb;
    
    $offset = 0;
    $total_revertidos = 0;

    do {
        // Obtener posts que tienen respaldo
        $query = $wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'social_post'
            AND pm.meta_key = 'datosAlgoritmo_respaldo'
            LIMIT %d OFFSET %d
        ", $batch_size, $offset);

        $resultados = $wpdb->get_results($query);
        
        if (empty($resultados)) {
            break;
        }

        foreach ($resultados as $row) {
            // $row->meta_value contiene el valor serializado del respaldo
            // Restaurar 'datosAlgoritmo' con el valor del respaldo
            // Es importante pasar el valor tal cual (serializado) a update_post_meta
            // o deserializarlo si se espera un array.
            // Dado que add_post_meta guardó el valor serializado, lo pasamos tal cual.
            update_post_meta($row->ID, 'datosAlgoritmo', $row->meta_value);
            // Eliminar el respaldo
            delete_post_meta($row->ID, 'datosAlgoritmo_respaldo');
            $total_revertidos++;
        }

        $offset += $batch_size;
        
        if ($total_revertidos > 0 && $total_revertidos % 1000 === 0) {
            sleep(1);
        }

    } while (count($resultados) === $batch_size);

    //error_log("Proceso de reversión completado. Total revertidos: " . $total_revertidos);
    return $total_revertidos;
}

// Función para restaurar 'datosAlgoritmo' desde 'datosAlgoritmo_respaldo' si está vacío
function restaurar_datos_algoritmo() {
    // Parámetros para obtener todos los posts del tipo 'social_post'
    $args = array(
        'post_type'      => 'social_post',
        'posts_per_page' => -1, // Obtener todos los posts
        'post_status'    => 'any', // Incluir todos los estados (publicados, borradores, etc.)
        'fields'         => 'ids', // Solo necesitamos los IDs para optimizar
        'no_found_rows'  => true, // Optimización: no necesitamos paginación
        'update_post_meta_cache' => false, // Optimización: no necesitamos meta cache
        'update_post_term_cache' => false, // Optimización: no necesitamos term cache
    );

    // Obtener los IDs de los posts
    $post_ids = get_posts($args);

    $restaurados_count = 0;
    // Verificar cada post
    foreach ($post_ids as $post_id) {
        // Intentar obtener el meta dato 'datosAlgoritmo'
        $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);

        // Si 'datosAlgoritmo' no existe o está vacío
        if (empty($datos_algoritmo)) {
            // Verificar si existe 'datosAlgoritmo_respaldo'
            $datos_algoritmo_respaldo = get_post_meta($post_id, 'datosAlgoritmo_respaldo', true);

            if (!empty($datos_algoritmo_respaldo)) {
                // Restaurar el valor de 'datosAlgoritmo' desde 'datosAlgoritmo_respaldo'
                // Asumimos que $datos_algoritmo_respaldo es el valor correcto (puede ser serializado o no)
                update_post_meta($post_id, 'datosAlgoritmo', $datos_algoritmo_respaldo);
                //error_log("Restaurado 'datosAlgoritmo' para el post ID: $post_id");
                $restaurados_count++;
            }
        }
        // Liberar memoria
        unset($datos_algoritmo, $datos_algoritmo_respaldo);
    }

    // Agregar un log para saber que la función se ejecutó y cuántos se restauraron
    //error_log("Restauración de 'datosAlgoritmo' completada. Posts restaurados: " . $restaurados_count);
}

// Hooks movidos desde app/Utils/TagUtils.php
add_action('wp_insert_post', 'normalizarNuevoPost', 10, 3);
add_action('post_updated', 'normalizarPostActualizado', 10, 3);

// Ejecutar la función de restauración una sola vez al inicio
add_action('init', function() {
    // Usar una opción para asegurar que solo se ejecute una vez
    if (!get_option('datos_algoritmo_restaurado_v1')) {
        restaurar_datos_algoritmo();
        update_option('datos_algoritmo_restaurado_v1', 1); // Marcar que ya se ejecutó
    }
});

// Refactor(Org): Mover lógica de redirección/reescritura de perfiles desde reglas.php
/**
 * Maneja las redirecciones relacionadas con los perfiles de usuario.
 *
 * @action template_redirect
 */
function handle_profile_redirects() {
    $current_url = $_SERVER['REQUEST_URI'];

    // Caso 1: Redirección de /perfil/ al perfil del usuario actual
    if (rtrim($current_url, '/') === '/perfil' && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $clean_user_login = sanitize_title($current_user->user_login);
        wp_redirect(home_url("/perfil/{$clean_user_login}/"), 301);
        exit;
    }

    // Caso 2: Corregir URLs con espacios o caracteres especiales en los perfiles
    if (strpos($current_url, '/perfil/') !== false) {
        $path_parts = explode('/perfil/', $current_url);
        if (isset($path_parts[1])) {
            $username_with_spaces = trim(urldecode($path_parts[1]), '/');

            // Eliminar espacios y caracteres especiales del nombre de usuario
            $clean_username = str_replace([' ', '+', '%20'], '', $username_with_spaces);

            // Si el nombre de usuario original tenía espacios, redirigir a la URL limpia
            if ($username_with_spaces !== $clean_username) {
                wp_redirect(home_url("/perfil/{$clean_username}/"), 301);
                exit;
            }
        }
    }

    // Caso 3: Redirigir author page a perfil
    if (is_author()) {
        global $wp;
        $author_slug = $wp->query_vars['author_name'];
        $nuevo_url = home_url('/perfil/' . $author_slug);
        wp_redirect($nuevo_url, 301);
        exit();
    }

    // Caso 4: Manejar errores 404 relacionados con nombres de usuario
    if (is_404()) {
        $requested_url = $_SERVER['REQUEST_URI'];
        $url_segments = explode('/', trim($requested_url, '/'));
        if (count($url_segments) == 1) {
            $user_slug = $url_segments[0];
            $user = get_user_by('slug', $user_slug);
            if ($user) {
                wp_redirect(home_url('/perfil/' . $user_slug), 301);
                exit;
            }
        }
    }
}

/**
 * Añade reglas de reescritura personalizadas para los perfiles de usuario.
 *
 * @action init
 */
function custom_rewrite_rules() {
    add_rewrite_rule('^perfil/([^/]*)/?', 'index.php?profile_user=$matches[1]', 'top');
}

/**
 * Añade 'profile_user' al listado de query vars.
 *
 * @param array $vars Array de query vars existentes.
 * @return array Array de query vars actualizado.
 * @filter query_vars
 */
function custom_query_vars($vars) {
    $vars[] = 'profile_user';
    return $vars;
}

/**
 * Usa una plantilla personalizada para la URL de perfil.
 *
 * @param string $template Ruta actual de la plantilla.
 * @return string Ruta de la plantilla a usar.
 * @filter template_include
 */
function custom_template_include($template) {
    if (get_query_var('profile_user')) {
        $new_template = locate_template(array('perfil.php'));
        if ('' != $new_template) {
            return $new_template;
        }
    }
    return $template;
}

/**
 * Limpia la caché de reglas de reescritura una vez.
 *
 * @action init
 */
function flush_rewrite_rules_once() {
    if (get_option('rewrite_rules_flushed') != true) {
        flush_rewrite_rules();
        update_option('rewrite_rules_flushed', true);
    }
}

// Hooks para las funciones de perfil movidas
add_action('template_redirect', 'handle_profile_redirects');
add_action('init', 'custom_rewrite_rules');
add_filter('query_vars', 'custom_query_vars');
add_filter('template_include', 'custom_template_include');
add_action('init', 'flush_rewrite_rules_once');

// Refactor(Org): Mover lógica de redirección de búsqueda desde app/Perfiles/reglas.php
add_action('template_redirect', function () {
    // Verificar si estamos en la página de inicio y si el parámetro 'search' está en la URL
    if (!is_front_page() && isset($_GET['search'])) {
        $search_query = sanitize_text_field($_GET['search']); // Sanitiza el término de búsqueda

        // Evitar redirección infinita comprobando si ya estamos redirigiendo a esta URL
        if (!isset($_GET['redirected'])) {
            // Redirige a la página de inicio con el parámetro de búsqueda y una marca para evitar bucles
            wp_redirect(home_url('/?search=' . urlencode($search_query) . '&redirected=1'));
            exit;
        }
    }
});

// Refactor(Org): Moved function add_meta_tags() and hook to app/Services/SEOService.php

// Refactor(Org): Mover función ocultarBarraAdmin() y hook desde app/Admin/Ajustes.php
/**
 * Oculta la barra de administración para usuarios que no son administradores.
 */
function ocultarBarraAdmin()
{
    if (!current_user_can('administrator')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('after_setup_theme', 'ocultarBarraAdmin');

?>
