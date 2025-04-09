<?
/*
Template Name: dev
ya hay un h1 adentro de dev
*/

// Asegúrate de que estas meta etiquetas estén en el header
add_action('wp_head', 'add_meta_tags');
function add_meta_tags() {
    ?>
    <meta name="description" content="<? echo esc_attr(get_the_excerpt()); ?>">
    <meta name="keywords" content="<? echo esc_attr(get_post_meta(get_the_ID(), '_yoast_wpseo_focuskw', true)); ?>">
    <meta property="og:title" content="<? echo esc_attr(get_the_title()); ?>">
    <meta property="og:description" content="<? echo esc_attr(get_the_excerpt()); ?>">
    <meta property="og:image" content="<? echo esc_url(get_the_post_thumbnail_url()); ?>">
    <meta property="og:url" content="<? echo esc_url(get_permalink()); ?>">
    <?
}

get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<main id="main" role="main">
    <article id="content" class="<? echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" data-pestana-actual="">
            <div data-tab="2upra"></div>
            <? if (current_user_can('administrator')) : ?>
            <? endif; ?>
        </div>

        <? if (is_user_logged_in()) : ?>
            <? echo devlogin(); ?>
        <? else : ?>
            <? echo dev(); ?>
        <? endif; ?>

    </article>
</main>

<?
get_footer();
?>