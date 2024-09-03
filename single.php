<?php
/**
 * The template for displaying all single posts
 *
 * @package WordPress
 * @subpackage 2upra_records
 * @since 1.0
 */

get_header();
?>

<main id="main" class="site-main">

    <?php
    while ( have_posts() ) :
        the_post();
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

            <div class="entry-meta">
                <?php
                // You can add your own meta information here
                // For example: posted_on();
                ?>
            </div>
        </header>

        <?php
        if ( has_post_thumbnail() ) :
            the_post_thumbnail();
        endif;
        ?>

        <div class="entry-content">
            <?php
            the_content();

            wp_link_pages(
                array(
                    'before' => '<div class="page-links">' . __( 'Pages:', 'your-theme-textdomain' ),
                    'after'  => '</div>',
                )
            );
            ?>
        </div>

        <footer class="entry-footer">
            <?php
            // You can add tags, categories, etc. here
            // For example: entry_footer();
            ?>
        </footer>
    </article>

    <?php
    // If comments are open or we have at least one comment, load up the comment template.
    if ( comments_open() || get_comments_number() ) :
        comments_template();
    endif;

    endwhile; // End of the loop.
    ?>

</main>

<?php
get_sidebar();
get_footer();