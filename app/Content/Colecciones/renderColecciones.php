<?

/*

*/

add_action('wp_ajax_verificar_sample_en_colecciones', 'verificarSampleEnColec');

function verificarSampleEnColec()
{
    $sample_id = isset($_POST['sample_id']) ? intval($_POST['sample_id']) : 0;
    $colecciones_con_sample = array();

    if ($sample_id) {
        // Obtener todas las colecciones del usuario actual
        $current_user_id = get_current_user_id();
        $args = array(
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'author'         => $current_user_id,
        );

        $colecciones = get_posts($args);

        // Verificar cada colección
        foreach ($colecciones as $coleccion) {
            $samples = get_post_meta($coleccion->ID, 'samples', true);
            if (is_array($samples) && in_array($sample_id, $samples)) {
                $colecciones_con_sample[] = $coleccion->ID;
            }
        }
    }

    wp_send_json_success(array(
        'colecciones' => $colecciones_con_sample
    ));
}

// aqui puedes hacer que se ordene por ulima modificacion update_post_meta($collection_id, 'ultimaModificacion', current_time('mysql'));
function modalColeccion()
{
    $current_user_id = get_current_user_id();

    // ID de favoritos y usar más tarde
    $favoritos_id = get_user_meta($current_user_id, 'favoritos_coleccion_id', true);
    $despues_id = get_user_meta($current_user_id, 'despues_coleccion_id', true);

    $args = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $current_user_id,
        'meta_key'       => 'ultimaModificacion',  // Campo de última modificación
        'orderby'        => 'meta_value',          // Ordenar por el valor del campo personalizado
        'order'          => 'DESC'                 // Orden descendente para mostrar las más recientes primero
    );

    $user_collections = new WP_Query($args);
    $default_image = 'https://2upra.com/wp-content/uploads/2024/10/699bc48ebc970652670ff977acc0fd92.jpg'; // Imagen predeterminada
?>
    <div class="modalColec modal" style="display: none;">
        <div class="colecciones">
            <h3>Colecciones</h3>
            <input type="text" placeholder="Buscar colección" id="buscarColeccion">
            <ul class="listaColeccion borde">
                <? if (!$favoritos_id) : ?>
                    <li class="coleccion" id="favoritos" data-post_id="favoritos">
                        <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'); ?>" alt="">
                        <span>Favoritos</span>
                    </li>
                <? endif; ?>

                <? if (!$despues_id) : ?>
                    <li class="coleccion borde" id="despues" data-post_id="despues">
                        <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg'); ?>" alt="">
                        <span>Usar más tarde</span>
                    </li>
                <? endif; ?>

                <? if ($user_collections->have_posts()) : ?>
                    <? while ($user_collections->have_posts()) : $user_collections->the_post(); ?>
                        <li class="coleccion borde" data-post_id="<? the_ID(); ?>">
                            <?php
                            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                            ?>
                            <img src="<? echo esc_url($thumbnail_url ? $thumbnail_url : $default_image); ?>" alt="">
                            <span><? the_title(); ?></span>
                        </li>
                    <? endwhile; ?>
                    <? wp_reset_postdata(); ?>
                <? endif; ?>
            </ul>

            <div class="XJAAHB">
                <button class="botonsecundario" id="btnEmpezarCreaColec">Nueva colección</button>
                <button class="botonprincipal" id="btnListo">Listo</button>
            </div>
        </div>
    </div>
<?php
}


function obtenerListaColec()
{
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $current_user_id,
        'meta_key'       => 'ultimaModificacion',  // Campo de última modificación
        'orderby'        => 'meta_value',          // Ordenar por el valor del campo personalizado
        'order'          => 'DESC'                 // Orden descendente para mostrar las más recientes primero
    );

    $user_collections = new WP_Query($args);
    $html = '';
    $default_image = 'https://2upra.com/wp-content/uploads/2024/10/699bc48ebc970652670ff977acc0fd92.jpg';

    if ($user_collections->have_posts()) {
        while ($user_collections->have_posts()) {
            $user_collections->the_post();
            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            $html .= '<li class="coleccion borde" data-post_id="' . get_the_ID() . '">';
            $html .= '<img src="' . esc_url($thumbnail_url ? $thumbnail_url : $default_image) . '" alt="">';
            $html .= '<span>' . get_the_title() . '</span>';
            $html .= '</li>';
        }
        wp_reset_postdata();
    }

    echo $html;
    wp_die();
}


add_action('wp_ajax_obtener_colecciones', 'obtenerListaColec');

function modalCreacionColeccion()
{

    ob_start();
?>
    <div class="modalColec crearColec modalCrearColec modal" style="display: none;">
        <div class="colecciones formColec">
            <h3>Crear colección</h3>
            <div class="previewAreaArchivos previewColec" id="previewImagenColec">
                <label>Agregar imagen (opcional)</label>
            </div>
            <input type="text" placeholder="Nombre de la colección" id="tituloColec">
            <input type="text" placeholder="Descripción de la colección (opcional)" id="descripColec">

            <div class="bloque flex-row"" id="opcionesColec" style="display: flex">
                <p>Opciones de post</p>
                <div class="flex flex-row gap-2">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="privadoColec" name="privadoColec" value="1">
                        <span class="checkmark"></span>
                        <? echo $GLOBALS['iconoPrivado']; ?>
                    </label>
                </div>
            </div>
            <div class="XJAAHB">
                <button class="botonsecundario" id="btnVolverColec">Volver</button>
                <button class="botonprincipal" id="btnCrearColec">Crear</button>
            </div>
        </div>
    </div>
<?
}