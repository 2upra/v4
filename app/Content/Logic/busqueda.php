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

    // Refactor(Org): Función realizar_busqueda movida a app/Services/SearchService.php
    $resultados = realizar_busqueda($texto);
    $html = generar_html_resultados($resultados);

    guardarCache($cache_key, $html, 7200);
    wp_send_json(['success' => true, 'data' => $html]);
}

add_action('wp_ajax_buscarResultado', 'buscar_resultados');
add_action('wp_ajax_nopriv_buscarResultado', 'buscar_resultados');

// Refactor(Org): Función realizar_busqueda movida a app/Services/SearchService.php

// Refactor(Org): Función buscar_posts movida a app/Services/SearchService.php

// Refactor(Org): Función buscar_usuarios movida a app/Services/SearchService.php

function balancear_resultados($resultados)
{
    $num_resultados = count($resultados['social_post']) + count($resultados['colecciones']) + count($resultados['perfiles']);
    if ($num_resultados > 6) {
        $social_post_count = count($resultados['social_post']);
        $colecciones_count = count($resultados['colecciones']);
        $perfiles_count = count($resultados['perfiles']);

        $max_each = 2;

        if ($social_post_count < $max_each) {
            $diff = $max_each - $social_post_count;
            if ($colecciones_count >= $max_each + $diff) {
                $max_each += $diff;
            } elseif ($perfiles_count >= $max_each + $diff) {
                $max_each += $diff;
            }
        }
        if ($colecciones_count < $max_each) {
            $diff = $max_each - $colecciones_count;
            if ($social_post_count >= $max_each + $diff) {
                $max_each += $diff;
            } elseif ($perfiles_count >= $max_each + $diff) {
                $max_each += $diff;
            }
        }
        if ($perfiles_count < $max_each) {
            $diff = $max_each - $perfiles_count;
            if ($social_post_count >= $max_each + $diff) {
                $max_each += $diff;
            } elseif ($colecciones_count >= $max_each + $diff) {
                $max_each += $diff;
            }
        }

        $resultados['social_post'] = array_slice($resultados['social_post'], 0, $max_each);
        $resultados['colecciones'] = array_slice($resultados['colecciones'], 0, $max_each);
        $resultados['perfiles'] = array_slice($resultados['perfiles'], 0, $max_each);
    }
    return $resultados;
}

function obtenerImagenPost($post_id)
{
    if (has_post_thumbnail($post_id)) {
        return img(get_the_post_thumbnail_url($post_id, 'thumbnail'));
    }
    $imagen_temporal_id = get_post_meta($post_id, 'imagenTemporal', true);
    if ($imagen_temporal_id) {
        return img(wp_get_attachment_image_url($imagen_temporal_id, 'thumbnail'));
    }
    return false;
}

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
