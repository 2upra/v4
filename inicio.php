<?php
/*
Template Name: Inicio
*/

get_header();
$user_id = get_current_user_id();
$acciones = get_user_meta($user_id, 'acciones', true);
$nologin_class = !is_user_logged_in() ? ' nologin' : ''; 
?>

<div id="main">
    <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">
        <?php if (!is_user_logged_in()): ?>
            <?php echo inicio(); ?>
        <?php else: ?>

            <div id="menuData" style="display:none;" pestanaActual="">
                <?php if ($acciones > 1) : ?>
                    <div data-tab="bolsa"></div>
                <?php endif; ?>
                <div data-tab="inicio"></div>
                <?php if (current_user_can('administrator')) : ?>
                    <div data-tab="Logs"></div>
                    <div data-tab="Reportes"></div>
                    <div data-tab="Sello admin"></div>
                <?php endif; ?>
            </div>

            <?php echo social(); ?>

        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
?>
