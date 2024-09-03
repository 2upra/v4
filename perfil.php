<?php
/*
Template Name: Perfil
*/

get_header();

?>

<div id="main">
    <div id="content">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="perfil"></div>
            <?php if (current_user_can('administrator')) : ?>
            <?php endif; ?>
        </div>

        <?php echo perfil() ?>
    </div>
</div>

<?php
get_footer();
?>