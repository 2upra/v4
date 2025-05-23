<?php

// Refactor(Exec): Funcion modalColeccion() movida desde app/View/Helpers/UIHelper.php
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
                            <?
                            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                            ?>
                            <img src="<? echo esc_url($thumbnail_url ? $thumbnail_url : $default_image); ?>" alt="">
                            <span><? the_title(); ?></span>
                            <button class="borrarColec" data-post_id="<? echo get_the_ID(); ?>">
                                <? echo $GLOBALS['iconPapelera'] ?>
                            </button>
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
<?
}

// Refactor(Exec): Funcion modalCreacionColeccion() movida desde app/Content/Colecciones/View/renderModalColec.php
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
