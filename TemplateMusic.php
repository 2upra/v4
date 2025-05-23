<head>
    <meta name="robots" content="index, follow">
    <?php wp_head(); ?>
</head>

<?php
/*
Template Name: Music
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';

// Refactor(Org): Incluir el componente de pestañas de música movido
require_once 'app/View/Components/Tabs/MusicTabs.php';
?>

<div id="main">
    <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">
        <?php if (!is_user_logged_in()):
            // Aqui hace falta una pagina
        ?>
        <?php else: ?>

            <?php echo musica() ?>

        <?php endif; ?>
    </div>
</div>
<?php
get_footer();
?>