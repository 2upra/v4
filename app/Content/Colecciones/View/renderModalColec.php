<?

/*

*/

// Refactor(Org): Funcion verificarSampleEnColec movida a app/Services/CollectionService.php

// aqui puedes hacer que se ordene por ulima modificacion update_post_meta($collection_id, 'ultimaModificacion', current_time('mysql'));

function obtenerListaColec()
{
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        error_log("Error: No se pudo obtener el ID del usuario actual.");
        wp_send_json_error("Error: No se pudo obtener el ID del usuario actual."); // Devuelve un error JSON
        wp_die(); // Termina la ejecución
    }

    $args = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $current_user_id,
        'meta_key'       => 'ultimaModificacion',
        'orderby'        => 'meta_value',
        'order'          => 'DESC'
    );

    error_log("Args de WP_Query: " . print_r($args, true));

    $user_collections = new WP_Query($args);

    if ($user_collections->have_posts()) {
        error_log("Se encontraron colecciones para el usuario ID: $current_user_id");
    } else {
        error_log("No se encontraron colecciones para el usuario ID: $current_user_id");
    }

    $default_image = 'https://2upra.com/wp-content/uploads/2024/10/699bc48ebc970652670ff977acc0fd92.jpg';

    ob_start();
?>
    <?
    if ($user_collections->have_posts()) {
        while ($user_collections->have_posts()) {
            $user_collections->the_post();
            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
    ?>
            <li class="coleccion borde" data-post_id="<? echo get_the_ID(); ?>">
                <img src="<? echo esc_url($thumbnail_url ? $thumbnail_url : $default_image); ?>" alt="">
                <span><? the_title(); ?></span>
                <button class="borrarColec" data-post_id="<? echo get_the_ID(); ?>">
                    <? echo $GLOBALS['iconPapelera']; ?>
                </button>
            </li>
    <?
        }
        wp_reset_postdata();
    } else {
        echo "<li>No se encontraron colecciones.</li>";
    }
    ?>
<?
    $html = ob_get_clean();
    error_log("HTML generado: " . substr($html, 0, 500));

    wp_send_json_success($html); // Devuelve el HTML como parte de una respuesta JSON exitosa
    wp_die(); // Termina la ejecución
}



add_action('wp_ajax_obtenerListaColec', 'obtenerListaColec');

function modalCreacionColeccion()
{

    ob_start();
?>
    <div class="modalColec crearColec modalCrearColec modal" id="modalCrearColec" style="display: none;">
        <div class="colecciones formColec">
            <h3>Crear colección</h3>
            <div class="previewAreaArchivos previewColec" id="previewImagenColec">
                <label>Agregar imagen (opcional)</label>
            </div>
            <input type="text" placeholder="Nombre de la colección" id="tituloColec">
            <input type="text" placeholder="Descripción de la colección (opcional)" id="descripColec">

            <div class="bloque flex-row"" id=" opcionesColec" style="display: flex">
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
