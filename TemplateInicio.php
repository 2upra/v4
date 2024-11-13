<?
/*
Template Name: Inicio
*/
?>

<head>
    <meta name="robots" content="index, follow">
    <? wp_head(); ?>
    <?
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

    // Detectar idioma y definir título y descripción
    $idioma = obtenerIdiomaDelNavegador();
    if ($idioma === 'es') {
        $titulo = "Social Media para Artistas | Samples, Colaboración, Sonidos y VST gratis";
        $descripcion = "Conéctate con artistas, comparte samples y encuentra sonidos y plugins VST gratuitos para tus proyectos.";
    } else {
        $titulo = "Social Media for Artists | Samples, Collaboration, Sounds and Free VSTs";
        $descripcion = "Connect with artists, share samples, and find free sounds and VST plugins for your projects.";
    }

    // Configurar cookies para el título y descripción
    setcookie("page_title", $titulo, time() + 3600, "/");
    setcookie("page_description", $descripcion, time() + 3600, "/");
    ?>
    <title><? echo esc_html($titulo); ?></title>
    <meta name="description" content="<? echo esc_attr($descripcion); ?>">
</head>

<?
get_header();
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content" class="<? echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">
        <? if (!is_user_logged_in()): ?>
            <? echo dev(); ?>
        <? else: ?>
            <? echo socialTabs(); ?>
        <? endif; ?>
    </div>
</div>

<?
get_footer();
?>
