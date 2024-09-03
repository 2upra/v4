<?php
/*
Template Name: dev
*/

get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="2upra"></div>
            <?php if (current_user_can('administrator')) : ?>
            <?php endif; ?>
        </div>

        <?php if (is_user_logged_in()) : ?>
            <?php echo devlogin(); ?>
        <?php else : ?>
            <?php echo dev(); ?>
        <?php endif; ?>
        
    </div>
</div>

<?php
get_footer();
?>
