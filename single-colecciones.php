<?

if (! defined('ABSPATH')) {
    exit;
}

$postId = get_the_ID();
$filtro = 'singleColec';

?>
<html <? language_attributes(); ?>>

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
                    <article <? post_class(); ?>>
                        <div class="SINGLECOLECSGER">
                            <? echo singleColec($postId) ?>
                        </div>
                    </article>
            <? endwhile;
            endif; ?>
        </div>
    </main>

    <? get_footer(); ?>

</body>

</html>