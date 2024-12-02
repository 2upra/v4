<?
/*
Template Name: Config
*/

get_header();

?>

<div id="main">
    <div id="content">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">
        <? if (!is_user_logged_in()): ?>
            <? echo do_shortcode('[inicio]'); ?>
        <? else: ?>

            <div id="menuData" style="display:none;" pestanaActual="">
                <div data-tab="Configuración"></div>
                <? if (current_user_can('administrator')) : ?>
                <? endif; ?>
            </div>

        <? endif; ?>
    </div>
</div>
<?
get_footer();
?>