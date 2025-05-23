<?php
/*
Template Name: Inicio
*/

// Configura metadatos y cookies basados en el idioma
configurarMetadatosPaginaIdioma();

?>

<head>
    <meta name="robots" content="index, follow">
    <?php wp_head(); ?>
</head>

<?php
get_header();
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">
        <?php if (!is_user_logged_in()): ?>
            <?php echo dev(); ?>
        <?php else: ?>
            <?php echo socialTabs(); ?>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
?>