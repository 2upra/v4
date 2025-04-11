<?php

// Refactor(Exec): Mover función botonColab() desde UIHelper.php
function botonColab($postId, $colab)
{
    return $colab ? "<div class='XFFPOX'><button class='ZYSVVV' data-post-id='$postId'>{$GLOBALS['iconocolab']}</button></div>" : '';
}

// Refactor(Exec): Mover función opcionesColab() desde app/Content/Colab/partColab.php
function opcionesColab($var)
{
    $post_id = $var['post_id'];
    $colabColaborador = $var['colabColaborador'];
    $colabColaboradorAvatar = $var['colabColaboradorAvatar'];
    $colabColaboradorName = $var['colabColaboradorName'];
    $colabFecha = $var['colabFecha'];
    ob_start();
?>
    <div class="GFOPNU">

        <div class="CBZNGK">
            <a href="<? echo esc_url(get_author_posts_url($colabColaborador)); ?>"></a>
            <img src="<? echo esc_url($colabColaboradorAvatar); ?>">
        </div>

        <div class="ZVJVZA">
            <div class="JHVSFW">
                <a href="<? echo esc_url(get_author_posts_url($colabColaborador)); ?>" class="profile-link">
                    <? echo esc_html($colabColaboradorName); ?></a>
            </div>
            <div class="HQLXWD">
                <a href="<? echo esc_url(get_permalink()); ?>" class="post-link">
                    <? echo esc_html($colabFecha); ?>
                </a>
            </div>
        </div>

        <div class="flex gap-3 justify-end ml-auto">

            <button data-post-id="<? echo $post_id; ?>" class="botonsecundario rechazarcolab">Rechazar</button>
            <button data-post-id="<? echo $post_id; ?>" class="botonprincipal aceptarcolab">Aceptar</button>
            <button data-post-id="<? echo $post_id; ?>" class="botonsecundario submenucolab"><? echo $GLOBALS['iconotrespuntos']; ?></button>
        </div>

        <div class="A1806241" id="opcionescolab-<? echo $post_id; ?>">
            <div class="A1806242">

                <button class="reporte" data-post-id="<? echo $post_id; ?>" tipoContenido="colab">Reportar</button>
                <button class="bloquear" data-post-id="<? echo $post_id; ?>">Bloquear</button>
                <button class="mensajeBoton" data-receptor="<? echo $colabColaborador; ?>">Enviar mensaje</button>

            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

// Refactor(Exec): Mover función opcionesColabActivo() desde app/Content/Colab/partColab.php
function opcionesColabActivo($var)
{
    $post_id = $var['post_id'];
    $colabColaborador = $var['colabColaborador'];

    ob_start();
?>
    <button data-post-id="<? echo $post_id; ?>" class="botonsecundario submenucolab"><? echo $GLOBALS['iconotrespuntos']; ?></button>

    <div class="A1806241" id="opcionescolab-<? echo $post_id; ?>">
        <div class="A1806242">

            <button class="reporte" data-post-id="<? echo $post_id; ?>" tipoContenido="colab">Reportar</button>

        </div>
    </div>
<?php
    return ob_get_clean();
}

// Refactor(Exec): Mover función colabsResumen() desde app/Content/Colab/renderColab.php
function colabsResumen() {
    // Obtener el ID del usuario actual
    $current_user_id = get_current_user_id();

    // Preparar los argumentos para la consulta de WP_Query
    $args = array(
        'post_type'      => 'colab',           // Tipo de post
        'post_status'    => 'publish',         // Solo posts publicados
        'posts_per_page' => -1,                // Obtener todos los posts
        'meta_query'     => array(             // Añadir meta_query para filtrar por colabColaborador o colabAutor
            'relation' => 'OR',                // Relación OR para que se cumpla cualquiera de las condiciones
            array(
                'key'     => 'colabColaborador', // Meta key para el colaborador
                'value'   => $current_user_id,   // Valor a comparar (ID del usuario actual)
                'compare' => '=',                // Comparación exacta
                'type'    => 'NUMERIC',          // Tipo de dato para asegurar la comparación correcta
            ),
            array(
                'key'     => 'colabAutor',       // Meta key para el autor
                'value'   => $current_user_id,   // Valor a comparar (ID del usuario actual)
                'compare' => '=',                // Comparación exacta
                'type'    => 'NUMERIC',          // Tipo de dato para asegurar la comparación correcta
            ),
        ),
    );

    // Ejecutar la consulta
    $query = new WP_Query( $args );

    // Inicializar la variable de salida
    $output = '';

    if ( $query->have_posts() ) {
        // Iniciar la lista ordenada
        $output .= '<ol class="listaDeColabresumen">';

        // Loop a través de los posts
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();

            // Obtener la URL de la imagen destacada o la imagen alternativa
            $imagenPost = get_the_post_thumbnail_url( $post_id, 'full' );
            if ( ! $imagenPost ) {
                $imagenPost = 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/09/1ndoryu_1725478496.webp?quality=40&strip=all';
            }

            // Obtener la meta conversacion_id
            $conversacion_id = get_post_meta( $post_id, 'conversacion_id', true );

            // Construir el elemento de la lista
            $output .= '<li class="colabResumen" data-conversacion_id="' . esc_attr( $conversacion_id ) . '" data-post_id="' . esc_attr( $post_id ) . '">';
            $output .= '<img src="' . esc_url( $imagenPost ) . '" class="colabResumenImagen" alt="' . esc_attr( get_the_title() ) . '" width="40" />';
            $output .= '</li>';
        }

        // Cerrar la lista ordenada
        $output .= '</ol>';

        // Restaurar datos originales del post
        wp_reset_postdata();
    } else {

    }

    return $output;
}
