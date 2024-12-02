<?php
/*
Template Name: Perfil
*/

// Función para obtener el idioma preferido del navegador
function obtenerIdiomaDelNavegador() {
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return 'en'; // Idioma por defecto
    }
    $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $idiomas_soportados = ['es', 'en'];

    foreach ($accepted_languages as $language) {
        $lang = substr($language, 0, 2); // Extraer idioma principal
        if (in_array($lang, $idiomas_soportados)) {
            return $lang;
        }
    }

    return 'en'; // Idioma por defecto
}

// Filtrar el título dinámico para la página de perfil
add_filter('pre_get_document_title', function ($title) {
    // Obtener el idioma del navegador
    $idioma = obtenerIdiomaDelNavegador();

    // Verificar si estamos en la página de perfil
    if (is_page_template('perfil.php')) {
        // Obtener el usuario actual
        $usuario = wp_get_current_user();

        // Si el usuario está logueado, personalizamos el título
        if ($usuario->exists()) {
            $nombre_usuario = !empty($usuario->display_name) ? $usuario->display_name : __('Usuario', 'tu-text-domain');
            $title = ($idioma === 'es') ? "$nombre_usuario | 2upra" : "$nombre_usuario | 2upra";
        } else {
            // Si no hay usuario logueado, usamos un título genérico
            $title = ($idioma === 'es') ? "Social Media para Artistas | 2upra" : "Social Media for Artists | 2upra";
        }
    }

    return $title;
});

// Añadir una descripción dinámica al <head>
add_action('wp_head', function () {
    // Obtener el idioma del navegador
    $idioma = obtenerIdiomaDelNavegador();

    // Verificar si estamos en la página de perfil
    if (is_page_template('perfil.php')) {
        // Obtener el usuario actual
        $usuario = wp_get_current_user();

        // Si el usuario está logueado, personalizamos la descripción
        if ($usuario->exists()) {
            $nombre_usuario = !empty($usuario->display_name) ? $usuario->display_name : __('Usuario', 'tu-text-domain');
            if ($idioma === 'es') {
                $descripcion = "Perfil de $nombre_usuario en 2upra. Descubre artistas, colabora en proyectos y encuentra samples y plugins VST gratuitos para tus producciones.";
            } else {
                $descripcion = "$nombre_usuario's profile on 2upra. Discover artists, collaborate on projects, and find free samples and VST plugins for your productions.";
            }
        } else {
            // Si no hay usuario logueado, usamos una descripción genérica
            if ($idioma === 'es') {
                $descripcion = "Únete a una red de creadores donde puedes conectar con artistas, colaborar en proyectos, y encontrar una amplia variedad de samples y plugins VST gratuitos para potenciar tus producciones musicales.";
            } else {
                $descripcion = "Join a network of creators where you can connect with artists, collaborate on projects, and access a wide range of free samples and VST plugins to enhance your music productions.";
            }
        }

        // Imprimir la meta descripción
        echo '<meta name="description" content="' . esc_attr($descripcion) . '">' . "\n";
    }
}, 1); // Prioridad baja para que se ejecute temprano en wp_head
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
    get_header();

    // Obtener el usuario actual
    $user_id = get_current_user_id();
    $acciones = get_user_meta($user_id, 'acciones', true);
    $nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="perfil"></div>
            <?php if (current_user_can('administrator')) : ?>
                <!-- Contenido exclusivo para administradores (si es necesario) -->
            <?php endif; ?>
        </div>

        <?php echo perfil(); // Mostrar contenido del perfil ?>
    </div>
</div>

<?php
    get_footer();
?>
</body>
</html>