<?

function comentariosForm()
{
    ob_start();
    $user = wp_get_current_user();
    $nombreUsuario = $user->display_name;
    $urlImagenperfil = imagenPerfil($user->ID);
?>
    <div class="bloque modal anadircomentario" id="rsComentario">

        <div class="W8DK25">
            <img id="perfil-imagen" src="<? echo esc_url($urlImagenperfil); ?>" alt="Perfil"
                style="max-width: 35px; max-height: 35px; border-radius: 50%;">
            <p><? echo $nombreUsuario ?></p>
        </div>

        <div>
            <textarea id="comentContent" name="comentContent" rows="1" required placeholder="Escribe tu comentario"></textarea>
        </div>

        <div class="previevsComent" id="previevsComent" style="display: none;">
            <div class="previewAreaArchivos pimagen" id="pcomentImagen" style="display: none;">
                <label></label>
            </div>
            <div class="previewAreaArchivos paudio" id="pcomentAudio" style="display: none;">
                <label></label>
            </div>
        </div>

        <div class="botonesForm R0A915">
            <button class="botonicono borde" id="audioComent"><? echo $GLOBALS['subiraudio']; ?></button>

            <button class="botonicono borde" id="imagenComent"><? echo $GLOBALS['subirimagen']; ?></button>

            <button class="botonicono borde" id="ArchivoComent" style="display: none;"><? echo $GLOBALS['subirarchivo']; ?></button>

            <button class="borde" id="enviarComent">Publicar</button>
        </div>

    </div>
<?
    return ob_get_clean();
}
?>