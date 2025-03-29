<?
/*
Template Name: Inicio
*/


?>

<? headGeneric(); ?>

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