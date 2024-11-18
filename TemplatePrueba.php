<?
/*
Template Name: Inicio Prueba
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<head>
    <meta name="robots" content="noindex, no follow">
    <? wp_head(); ?>
</head>


<style>
    .modal {
        position: unset;
        transform: unset;
    }
</style>

<div id="main">
    <div id="content" class="<? echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Preguntas"> </div>

        </div>

        <div class="tabs">
            <div class="tab-content">

                <div class="tab INICIO S4K7I3" id="Preguntas">

                </div>

            </div>
        </div>
    </div>
</div>

<?
get_footer();
?>