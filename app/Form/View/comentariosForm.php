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
                style="max-width: 50px; max-height: 50px; border-radius: 50%;">
            <p><? echo $nombreUsuario ?></p>
        </div>

        <div>
            <textarea id="comentContent" name="comentContent" rows="1" required placeholder="Escribe tu comentario"></textarea>
        </div>

        <div class="previevsComent" id="previevsComent">
            <div class="previewAreaArchivos" id="previewComentImagen" style="display: none;">
                <label></label>
            </div>
            <div class="previewAreaArchivos" id="previewComentAudio" style="display: none;">
                <label></label>
            </div>
        </div>

        <div class="botonesForm R0A915">
            <button class="botonicono borde" id="botonAudioComent"><? echo $GLOBALS['subiraudio']; ?></button>

            <button class="botonicono borde" id="botonImagenComent"><? echo $GLOBALS['subirimagen']; ?></button>

            <button class="botonicono borde" id="botonArchivoComent"><? echo $GLOBALS['subirarchivo']; ?></button>

            <button class="borde" id="enviarComent">Publicar</button>
        </div>

    </div>
<?
    return ob_get_clean();
}
?>