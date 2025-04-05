<?php

// Refactor(Org): Función AJAX buscar_resultados() y sus hooks movidos a app/Services/SearchService.php

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
