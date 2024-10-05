<?
/*
Template Name: dev
*/

get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content" class="<? echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="2upra"></div>
            <? if (current_user_can('administrator')) : ?>
            <? endif; ?>
        </div>

        <? if (is_user_logged_in()) : ?>
            <? echo devlogin(); ?>
        <? else : ?>
            <? echo dev(); ?>
        <? endif; ?>

    </div>
</div>

<?
get_footer();
?>