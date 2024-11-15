<?php
/*
Template Name: dev
ya hay un h1 adentro de dev
*/

// Asegúrate de que estas meta etiquetas estén en el header
add_action('wp_head', 'add_meta_tags');
function add_meta_tags() {
    ?>
    <meta name="description" content="<?php echo esc_attr(get_the_excerpt()); ?>">
    <meta name="keywords" content="<?php echo esc_attr(get_post_meta(get_the_ID(), '_yoast_wpseo_focuskw', true)); ?>">
    <meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?>">
    <meta property="og:description" content="<?php echo esc_attr(get_the_excerpt()); ?>">
    <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url()); ?>">
    <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
    <?php
}

get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<main id="main" role="main">
    <article id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" data-pestana-actual="">
            <div data-tab="2upra"></div>
            <?php if (current_user_can('administrator')) : ?>
            <?php endif; ?>
        </div>

        <?php if (is_user_logged_in()) : ?>
            <?php echo devlogin(); ?>
        <?php else : ?>
            <?php echo dev(); ?>
        <?php endif; ?>

    </article>
</main>

<?php
get_footer();
?>