<?

/*

*/

//esto es estatico, pero necesito se reinicie para que muestre la ultima coleccion que se creo, no quiero agregarla visualmente, sino que esto se reinicie 
function modalColeccion()
{
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $current_user_id,
    );

    $user_collections = new WP_Query($args);
?>
    <div class="modalColec modal" style="display: none;">
        <div class="colecciones">
            <h3>Colecciones</h3>
            <input type="text" placeholder="Buscar colección" id="buscarColeccion">

            <ul class="listaColeccion borde">
                <li class="coleccion" id="favoritos" data-post_id="">
                    <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'); ?>" alt=""><span>Favoritos</span>
                </li>
                <li class="coleccion borde" id="despues" data-post_id="">
                    <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg'); ?>" alt=""><span>Usar más tarde</span>
                </li>

                <? if ($user_collections->have_posts()) : ?>
                    <? while ($user_collections->have_posts()) : $user_collections->the_post(); ?>
                        <li class="coleccion borde" data-id="<? the_ID(); ?>">
                            <img src="<? echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')); ?>" alt="">
                            <span><? the_title(); ?></span>
                        </li>
                    <? endwhile; ?>
                    <? wp_reset_postdata(); ?>
                <? else : ?>

                <? endif; ?>
            </ul>

            <div class="XJAAHB">
                <button class="botonsecundario" id="btnEmpezarCreaColec">Nueva colección</button>
                <button class="botonprincipal" id="btnListo">Listo</button>
            </div>
        </div>
    </div>
<?
}

function obtenerHtmlColec() {
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type'      => 'colecciones',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author'         => $current_user_id,
    );

    $user_collections = new WP_Query($args);

    ob_start(); // Empieza a capturar el contenido HTML

    ?>
    <ul class="listaColeccion borde">
        <li class="coleccion" id="favoritos" data-post_id="">
            <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'); ?>" alt=""><span>Favoritos</span>
        </li>
        <li class="coleccion borde" id="despues" data-post_id="">
            <img src="<? echo esc_url('https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg'); ?>" alt=""><span>Usar más tarde</span>
        </li>

        <? if ($user_collections->have_posts()) : ?>
            <? while ($user_collections->have_posts()) : $user_collections->the_post(); ?>
                <li class="coleccion borde" data-id="<? the_ID(); ?>">
                    <img src="<? echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')); ?>" alt="">
                    <span><? the_title(); ?></span>
                </li>
            <? endwhile; ?>
            <? wp_reset_postdata(); ?>
        <? endif; ?>
    </ul>
    <?

    return ob_get_clean(); 
}

function ajax_actualizar_colecciones() {
    // Verificamos la autenticidad o permisos del usuario si es necesario
    $html = obtenerHtmlColec();
    
    // Devolvemos una respuesta JSON con el HTML generado
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_actualizar_colecciones', 'ajax_actualizar_colecciones');

function botonColeccion($postId)
{
    ob_start();
?>
    <div class="ZAQIBB botonColeccion">
        <button class="botonColeccionBtn" data-post_id="<? echo esc_attr($postId) ?>" data-nonce="<? echo wp_create_nonce('colec_nonce') ?>">
            <? echo $GLOBALS['iconoGuardar']; ?>
        </button>
    </div>

<?
}


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
            <div class="XJAAHB">
                <button class="botonsecundario" id="btnVolverColec">Volver</button>
                <button class="botonprincipal" id="btnCrearColec">Crear</button>
            </div>
        </div>
    </div>
<?
}
