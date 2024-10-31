<?

/*

#VIEW

1) Un modal donde aparezca la lista de colecciones, por defecto todos los usuarios tienen una coleccion privada de "Favoritos", y "Usar mas tarde" por defecto aparece un boton de crear nueva coleccion, el modal se abre con un boton para crear una nueva coleccion. #Yo me encarog del js mas adelante

1.1) En el modal por defecto va aparecer "Favoritos", y "Usar mas tarde" pero no va a existir fisicamente en la base datos sino despues de que un usuario guarde algo alli

2) Hay que hacer el formularuio dinamico que crea una nueva coleccion dentro del mismo modal, tiene que ser sencillo, tiene que pedir una foto, un nombre, descripcion y tags. #Yo me encargo del js mas adelante

3) Tambien hay que hacer un view para las colecciones 
    Un view que tiene todas las colecciones propia
    Un view para las colecciones publicas 
    Un view dentro de la coleccion con los audios


*/
//en el header
function modalColeccion()
{
    // Obtener el ID del usuario actual
    $current_user_id = get_current_user_id();

    // Consultar las colecciones del usuario
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
                <li class="coleccion" id="favoritos">
                    <img src="<?php echo esc_url('https://2upra.com/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg'); ?>" alt=""><span>Favoritos</span>
                </li>
                <li class="coleccion borde" id="despues">
                    <img src="<?php echo esc_url('https://2upra.com/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg'); ?>" alt=""><span>Usar más tarde</span>
                </li>

                <?php if ($user_collections->have_posts()) : ?>
                    <?php while ($user_collections->have_posts()) : $user_collections->the_post(); ?>
                        <li class="coleccion borde" data-id="<?php the_ID(); ?>">
                            <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')); ?>" alt="">
                            <span><?php the_title(); ?></span>
                        </li>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>

                <?php endif; ?>
            </ul>

            <div class="XJAAHB">
                <button class="botonsecundario" id="btnEmpezarCreaColec">Nueva colección</button>
                <button class="botonprincipal" id="btnListo">Listo</button>
            </div>
        </div>
    </div>
<?php
}


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
            <input type="text" placeholder="Nombre de la colección">
            <input type="text" placeholder="Descripción de la colección (opcional)">
            <div class="XJAAHB">
                <button class="botonsecundario" id="btnVolverColec">Volver</button>
                <button class="botonprincipal" id="btnCrearColec">Crear</button>
            </div>
        </div>
    </div>
<?
}
