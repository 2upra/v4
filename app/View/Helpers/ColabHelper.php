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
