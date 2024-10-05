<?
/*
Template Name: Perfil
*/

get_header();

?>

<div id="main">
    <div id="content">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="perfil"></div>
            <? if (current_user_can('administrator')) : ?>
            <? endif; ?>
        </div>

        <? echo perfil() ?>
    </div>
</div>

<?
get_footer();
?>