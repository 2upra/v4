<?
/*
Template Name: Perfil
*/

// Función para obtener el idioma preferido del navegador
function obtenerIdiomaDelNavegador() {
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return 'en';
    }
    $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($accepted_languages as $language) {
        $lang = substr($language, 0, 2);
        if (in_array($lang, ['es', 'en'])) {
            return $lang;
        }
    }
    return 'en';
}

// Obtener el idioma del navegador
$idioma = obtenerIdiomaDelNavegador();

// Obtener el nombre del usuario actual
$user_id = get_current_user_id();
if ($user_id) {
    $user_info = get_userdata($user_id);
    $user_name = $user_info->display_name; // Puedes usar 'user_login' o cualquier otro campo
    $titulo = $user_name . " | 2upra";
} else {
    $titulo = $idioma === 'es' ? "Perfil de usuario | 2upra" : "User Profile | 2upra";
}

// Configurar descripción según el idioma
if ($idioma === 'es') {
    $descripcion = "Únete a una red de creadores donde puedes conectar con artistas, colaborar en proyectos, y encontrar una amplia variedad de samples y plugins VST gratuitos para potenciar tus producciones musicales.";
} else {
    $descripcion = "Join a network of creators where you can connect with artists, collaborate on projects, and access a wide range of free samples and VST plugins to enhance your music productions.";
}

// Añadir el título y la descripción al <head>
add_action('wp_head', function () use ($titulo, $descripcion) {
    echo '<title>' . esc_html($titulo) . '</title>' . "\n";
    echo '<meta name="description" content="' . esc_attr($descripcion) . '">' . "\n";
}, 1);
?>

<head>
    <meta name="robots" content="index, follow">
    <? wp_head(); ?>
</head>

<?
get_header();
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">
        <? echo perfilTabs() ?>
    </div>
</div>

<?
get_footer();
?>