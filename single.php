<?
if (! defined('ABSPATH')) {
    exit;
}
$postId = get_the_ID();
//  <p class="post-authorSingle">By: <? the_author(); </p>
?>

<head>
    <meta charset="<? bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <? wp_head(); ?>
</head>

<body <? body_class(); ?>>

    <? get_header(); ?>

    <main id="main">
        <div id="content" class="<? echo esc_attr(! is_user_logged_in() ? 'nologin' : ''); ?>">
            <? if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <article class="singlePostArticulo">
                        <h1 class="post-titleSingle"><? the_title(); ?></h1>

                        <div class="post-contentSingle">
                            <? the_content(); ?>
                        </div>
                        <div class="botonesPost">
                            <? echo like($postId); ?>
                            <button class="btnSinglePost"><a href="https://2upra.com/inversion/">Apoyar el proyecto</a></button>
                            <? echo botonComentar($postId, $colab); ?>
                        </div>
                    </article>
            <? endwhile;
            endif; ?>
        </div>
    </main>

    <? get_footer(); ?>

</body>

</html>