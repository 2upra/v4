<?php
/*
Template Name: Inicio Prueba
*/
get_header();
$user_id = get_current_user_id();
$nologin_class = !is_user_logged_in() ? ' nologin' : '';
?>

<head>
    <meta name="robots" content="noindex, nofollow">
    <?php wp_head(); ?>
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
    <div id="content" class="<?php echo esc_attr($nologin_class); ?>">
        <input type="hidden" id="pagina_actual" name="pagina_actual" value="<?php echo esc_attr(get_the_title()); ?>">

        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Comentario"> </div>

        </div>

        <div class="tabs">
            <div class="tab-content">

                <div class="tab INICIO S4K7I3" id="Comentario">
                    <div class="bloque">
                        <?php
                        // Verifica si las funciones de la extensión están disponibles
                        if (function_exists('calcular_suma') && function_exists('calcular_multiplicacion') && function_exists('operaciones_combinadas')) {
                            // Llama a las funciones y muestra los resultados
                            $suma = calcular_suma(5, 3);
                            $multiplicacion = calcular_multiplicacion(4, 2);
                            $combinadas = operaciones_combinadas(10);

                            echo "<p>El resultado de la suma es: " . $suma . "</p>";
                            echo "<p>El resultado de la multiplicación es: " . $multiplicacion . "</p>";
                            echo "<p>" . $combinadas . "</p>";
                        } else {
                            echo "<p>Las funciones de la extensión suprarust no están disponibles.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php get_footer(); ?>