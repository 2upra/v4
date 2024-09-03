<?php
/*
Template Name: Sello
*/

get_header();
?>

<div id="main">
    <div id="content">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">
        <?php if (!is_user_logged_in()): ?>
            <?php echo do_shortcode('[inicio]'); ?>
        <?php else:
            $user = wp_get_current_user();
            $nombre_usuario = $user->display_name;
            $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID);
        ?>

            <div id="menuData" style="display:none;">
                <div data-tab="rolas"></div>
                <div data-tab="eliminadas"></div>
                <div data-tab="rechazadas"></div>
            </div>
            <div class="P0390VU">
                <?php echo do_shortcode('[panel]'); ?>
            </div>


        <?php endif; ?>
    </div>
</div>
<?php
get_footer();
?>