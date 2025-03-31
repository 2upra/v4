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
 * Configura los metadatos de la página (título, descripción) y las cookies
 * basándose en el idioma detectado del navegador.
 */
function configurarMetadatosPaginaIdioma() {
    // Utiliza la función namespaced de BrowserUtils
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
