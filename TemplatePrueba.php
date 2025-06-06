<?
/*
Template Name: Inicio Prueba
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<head>
    <meta name="robots" content="noindex, nofollow">
    <? wp_head(); ?>
</head>

<style>
    .modal {
        position: unset;
        transform: unset;
    }

    .S4K7I3.active {
        margin: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 20px;
        padding: 20px;
    }
</style>

<div id="main">
    <div id="content" class="<? echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<? echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Comentario"> </div>

        </div>

        <div class="tabs">
            <div class="tab-content">

                <div class="tab INICIO S4K7I3" id="Comentario">
                    <div class="bloque">
                        <?
                        if (function_exists('conectar_bd_sin_lazy_static')) {
                            $resultado = conectar_bd_sin_lazy_static();
                            guardarLog("conectar_bd: " . $resultado);
                            echo "<p>Resultado de la conexión a la base de datos: " . $resultado . "</p>";
                        } else {
                            echo "<p>La función conectar_bd_sin_lazy_static de la extensión suprarust no está disponible.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <? get_footer(); ?>