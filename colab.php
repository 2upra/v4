<?php
/*
Template Name: colab
*/
//PRUEBA
get_header();
?>

<div id="main">
    <div id="content">
        <?php if (!is_user_logged_in()): ?>
            <?php echo do_shortcode('[inicio]'); ?>
        <?php else:?>

            <div id="menuData" style="display:none;">
                <div data-tab="inicio"></div>
            </div>
            <div class="ZPJXRG">
                <div class="GEIMKY">
                    <?php echo do_shortcode('[colab]'); ?>
                </div>
                <div class="FPIAHK">
                    <?php // echo do_shortcode('[lateral]'); ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>
<?php
get_footer();
?>