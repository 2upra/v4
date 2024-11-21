<?php
if (! defined('ABSPATH')) {
    exit;
}
$current_post_id = get_the_ID();
?>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <?php get_header(); ?>

    <main id="main">
        <div id="content" class="<?php echo esc_attr(! is_user_logged_in() ? 'nologin' : ''); ?>">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <article <?php post_class(); ?>>
                        <h1 class="post-titleSingle"><?php the_title(); ?></h1>
                        <p class="post-authorSingle">By: <?php the_author(); ?></p>
                        <div class="post-contentSingle">
                            <?php the_content(); ?>
                        </div>
                        <div class="botonesPost">
                            <? echo like($post_id); ?>
                            <? echo botonComentar($post_id, $colab); ?>
                        </div>
                    </article>
            <?php endwhile;
            endif; ?>
        </div>
    </main>

    <?php get_footer(); ?>

</body>

</html>