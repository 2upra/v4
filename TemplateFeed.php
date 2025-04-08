<?
/*
Template Name: Feed
*/

// Configura metadatos (título, descripción) y cookies según el idioma
configurarMetadatosPaginaIdioma();
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
    <div id="content" class="<? echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">
        <? if (!is_user_logged_in()): ?>
            <?php // Refactor: Reemplaza dev() por inicio() para usuarios no logueados ?>
            <?php echo inicio(); ?>
        <? else: ?>
            <? echo socialTabsFEED(); ?>
        <? endif; ?>
    </div>
</div>

<?
get_footer();
?>

