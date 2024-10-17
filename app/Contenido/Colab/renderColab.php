<?

/*
    TituloColab: 
    [ ] Que al dar click en la imagen se pueda elegir la imagen del proyecto
    [ ] Establecer imagenes de proyecto por defecto 
    [ ] Cambiar el titulo al dar click en el nombre 

    participantesColab:
    [ ] Que al dar click en el nombre se pueda ver la lista de participantes
    [ ] Que al dar click en el nombre de un participante se pueda ver el chat
    [ ] Poder añardir otros miembros
    [ ] El autor puede eliminar miembros
    
*/

function htmlColab($filtro)
{
    $post_id = get_the_ID();
    $var = variablesColab($post_id);
    extract($var);
    ob_start();

?>

    <li class="POST-<? echo esc_attr($filtro); ?> EDYQHV"
        filtro="<? echo esc_attr($filtro); ?>"
        id-post="<? echo get_the_ID(); ?>"
        autor="<? echo esc_attr($colabColaborador); ?>">

        <div class="colab-content">
            <? if ($filtro === 'colabPendiente'): ?>
                <? echo opcionesColab($var); ?>
                <? echo contenidoColab($var); ?>
            <? else: ?>
                <div class="UICMCG">
                    <? echo tituloColab($var); ?>
                    <? echo participantesColab($var) ?>
                    <? echo opcionesColabActivo($var); ?>
                </div>
                <div class="MXPLYN">
                    <? echo chatColab($var); ?>
                    <? //echo archivosColab($var); ?>
                    <? //echo historialColab($var); ?>
                    <? //echo comandosColab($var); ?>
                    <? //echo enviarColab($var);?>
                </div>
            <? endif; ?>

        </div>
    </li>

<?
    return ob_get_clean();
}

function colab()
{
    ob_start() ?>

    <div class="FLXVTQ">
        <a href="https://2upra.com/">
            <p>La funcionalidad de colaboración aún no esta disponible</p>
            <button class="borde">Volver</button>
        </a>
    </div>


<? return ob_get_clean();
}

function colabTest()
{
    ob_start();
?>
    <div class="IBPDFF">
        <div>
            <div>Colab pendientes</div>
            <? echo publicaciones(['post_type' => 'colab', 'filtro' => 'colabPendiente', 'posts' => 20]); ?>
        </div>
        <div>
            <? echo publicaciones(['post_type' => 'colab', 'filtro' => 'colab', 'posts' => 20]); ?>
        </div>
    </div>
<?
    return ob_get_clean();
}


function colabsResumen() {
    // Obtener el ID del usuario actual
    $current_user_id = get_current_user_id();

    // Preparar los argumentos para la consulta de WP_Query
    $args = array(
        'post_type'      => 'colab',           // Tipo de post
        'post_status'    => 'publish',         // Solo posts publicados
        'author'         => $current_user_id,  // Autor actual
        'posts_per_page' => -1,                // Obtener todos los posts
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