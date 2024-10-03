<?php
/*
Template Name: Inicio Prueba
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<div id="main">
    <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Colab"></div>
        </div>

        <div class="tabs">
            <div class="tab-content">

                <div class="tab INICIO S4K7I3" id="Colab">
                    <div class="GSDKRA">
                        <div><?php echo colabTest(); ?></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
get_footer();
?>