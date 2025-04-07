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

/**
 * Obtiene el idioma preferido del navegador del usuario.
 *
 * Analiza la cabecera HTTP_ACCEPT_LANGUAGE para determinar el idioma preferido.
 * Prioriza 'es' (español) o 'en' (inglés).
 *
 * @return string Retorna 'es' o 'en', o 'en' por defecto si no se puede determinar o no está en la lista priorizada.
 */
function obtenerIdiomaDelNavegador()
{
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) || empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return 'en'; // Retorna inglés por defecto si la cabecera no está presente o está vacía
    }

    $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($accepted_languages as $language) {
        // Extrae el código de idioma principal (ej. 'es' de 'es-ES,es;q=0.9')
        $lang = substr(trim(explode(';', $language)[0]), 0, 2);

        // Verifica si el idioma extraído es español o inglés
        if (in_array($lang, ['es', 'en'])) {
            return $lang; // Retorna el primer idioma coincidente ('es' o 'en')
        }
    }

    return 'en'; // Retorna inglés si ninguno de los idiomas preferidos ('es', 'en') se encuentra
}

/**
 * Configura los metadatos de la página (título, descripción) y las cookies
 * basándose en el idioma detectado del navegador.
 */
function configurarMetadatosPaginaIdioma() {
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

//CALCULAR ALTURA CORRECTA CON SCRIPT
function innerHeight()
{
    wp_register_script('script-base', '');
    wp_enqueue_script('script-base');
    $script_inline = <<<'EOD'
    function setVHVariable() {
        var vh;
        if (window.visualViewport) {
            vh = window.visualViewport.height * 0.01;
        } else {
            vh = window.innerHeight * 0.01;
        }
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }

    document.addEventListener('DOMContentLoaded', function() {
        setVHVariable();

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', setVHVariable);
        } else {
            window.addEventListener('resize', setVHVariable);
        }
    });
EOD;
    wp_add_inline_script('script-base', $script_inline);
}

add_action('wp_enqueue_scripts', 'innerHeight');

// Refactor(Org): Mover hooks agregar_soporte_jfif y extender_wp_check_filetype aquí
function agregar_soporte_jfif($mimes)
{
    $mimes['jfif'] = 'image/jpeg';
    return $mimes;
}
add_filter('upload_mimes', 'agregar_soporte_jfif');

// Extiende wp_check_filetype para reconocer .jfif
function extender_wp_check_filetype($types, $filename, $mimes)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'jfif') {
        return ['ext' => 'jpeg', 'type' => 'image/jpeg'];
    }
    return $types;
}
add_filter('wp_check_filetype_and_ext', 'extender_wp_check_filetype', 10, 3);

// Refactor(Org): Función mimesPermitidos movida desde app/Admin/Ajustes.php
function mimesPermitidos($mimes)
{
    $mimes['flp'] = 'application/octet-stream';
    $mimes['zip'] = 'application/zip';
    $mimes['rar'] = 'application/x-rar-compressed';
    $mimes['cubase'] = 'application/octet-stream';
    $mimes['proj'] = 'application/octet-stream';
    $mimes['aiff'] = 'audio/aiff';
    $mimes['midi'] = 'audio/midi';
    $mimes['ptx'] = 'application/octet-stream';
    $mimes['sng'] = 'application/octet-stream';
    $mimes['aup'] = 'application/octet-stream';
    $mimes['omg'] = 'application/octet-stream';
    $mimes['rpp'] = 'application/octet-stream';
    $mimes['xpm'] = 'image/x-xpixmap';
    $mimes['tst'] = 'application/octet-stream';

    return $mimes;
}
add_filter('upload_mimes', 'mimesPermitidos');

// Refactor(Org): Mover registro de CPTs y estados desde typesPosts.php
// Registrar estado de publicación: Rechazado y Pendiente de Eliminación
function register_custom_post_statuses()
{
    register_post_status('rejected', [
        'label' => _x('Rejected', 'post status'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>')
    ]);

    register_post_status('pending_deletion', [
        'label' => _x('Pending Deletion', 'post status'),
        'public' => false,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Pending Deletion <span class="count">(%s)</span>', 'Pending Deletion <span class="count">(%s)</span>')
    ]);
}
add_action('init', 'register_custom_post_statuses');

// Registrar tipos de post: Samples, Álbums, Momentos y Colaboraciones
function register_custom_post_types()
{
    $post_types = [
        'social_post' => ['Samples', 'Sample', 'sample', 'dashicons-images-alt2'],
        'albums' => ['Albums', 'Album', 'album', 'dashicons-format-audio'],
        'stories' => ['Momentos', 'Momento', 'momentos', 'dashicons-camera'],
        'colab' => ['Colaboraciones', 'Colaboración', 'colab', 'dashicons-share-alt2'],
        'colecciones' =>  ['Colecciones', 'Colección', 'colecciones', 'dashicons-book'],
        'notificaciones' => ['Notificaciones', 'Notificación', 'notificacion', 'dashicons-bell'],
        'comentarios' => ['Comentarios', 'Comentario', 'comentario', 'dashicons-admin-comments'],
        'reporte' => ['Reportes', 'Reporte', 'reporte', 'dashicons-flag'],
        'tarea' => ['Tareas', 'Tarea', 'tarea', 'dashicons-list-check'],
        'notas' => ['Notas', 'Nota', 'notas', 'dashicons-admin-notes'],

    ];


    foreach ($post_types as $key => $type) {
        $name = $type[0];
        $singular = $type[1];
        $slug = $type[2];
        $icon = isset($type[3]) ? $type[3] : null;

        $args = [
            'labels' => [
                'name' => __($name),
                'singular_name' => __($singular)
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => $slug],
            'show_in_rest' => true,
            'menu_icon' => $icon
        ];

        register_post_type($key, $args);
    }
}
add_action('init', 'register_custom_post_types');

// Acción de refactorización: La función configurarMetadatosPaginaIdioma() ya se encontraba en este archivo. No se realizaron cambios.

?>
