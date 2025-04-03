<?php

function buscar_resultados()
{
    $texto = sanitize_text_field($_POST['busqueda']);
    $cache_key = 'resultadoBusqueda_' . md5($texto);
    $resultados_cache = obtenerCache($cache_key);

    if ($resultados_cache !== false) {
        wp_send_json(['success' => true, 'data' => $resultados_cache]);
        return;
    }

    // Refactor(Org): La lógica de búsqueda ahora reside en SearchService
    $resultados = realizar_busqueda($texto); // Asume que SearchService.php está incluido
    $html = generar_html_resultados($resultados);

    guardarCache($cache_key, $html, 7200);
    wp_send_json(['success' => true, 'data' => $html]);
}

add_action('wp_ajax_buscarResultado', 'buscar_resultados');
add_action('wp_ajax_nopriv_buscarResultado', 'buscar_resultados');

// Refactor(Org): Funciones realizar_busqueda, buscar_posts, buscar_usuarios, balancear_resultados y obtenerImagenPost movidas a app/Services/SearchService.php


function generar_html_resultados($resultados)
{
    ob_start();
    $num_resultados = 0;
    foreach ($resultados as $grupo) {
        $num_resultados += count($grupo);
        foreach ($grupo as $resultado) {
?>
            <a href="<?php echo esc_url($resultado['url']); ?>">
                <div class="resultado-item">
                    <?php if (!empty($resultado['imagen'])):
 ?>
                        <img class="resultado-imagen" src="<?php echo esc_url($resultado['imagen']); ?>" alt="<?php echo esc_attr($resultado['titulo']); ?>">
                    <?php endif; ?>
                    <div class="resultado-info">
                        <h3><?php echo esc_html($resultado['titulo']); ?></h3>
                        <p>
                            <?php
                            if ($resultado['tipo'] === 'social post') {
                                echo 'Post';
                            } else {
                                echo esc_html($resultado['tipo']);
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </a>
        <?php
        }
    }

    if ($num_resultados === 0) {
        ?>
        <div class="resultado-item">No se encontraron resultados.</div>
    <?php
    }

    return ob_get_clean();
}


function busqueda()
{

    ob_start();
    ?>
    <div class="buscadorBL bloque">
        <input name="buscadorLocal" id="buscadorLocal" placeholder="Ingresa tu busqueda"></input>

        <div class="resultadosBL"></div>
    </div>
<?php
    return ob_get_clean();
}
