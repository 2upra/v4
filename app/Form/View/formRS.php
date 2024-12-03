<?

function formRs()
{
    ob_start();
    $user = wp_get_current_user();
    $nombreUsuario = $user->display_name;
    $urlImagenperfil = imagenPerfil($user->ID);

?>
    <div class="bloque modal" id="formRs" style="display: none;">

        <div class="W8DK25">
            <img id="perfil-imagen" src="<? echo esc_url($urlImagenperfil); ?>" alt="Perfil"
                style="max-width: 50px; max-height: 50px; border-radius: 50%;">
            <p><? echo $nombreUsuario ?></p>
        </div>

        <div>
            <div class="postTags DABVYT" id="textoRs" contenteditable="true" data-placeholder="Agrega tags usando #, puedes agregar varios audios a la vez"></div>

            <input type="hidden" id="postTagsHidden" name="post_tags">

            <textarea id="postContent" name="post_content" rows="2" required placeholder="Escribe aquí" style="display: none;"></textarea>
        </div>

        <div class="DRHMDE">
            <label class="custom-checkbox">
                <input type="checkbox" id="fancheck" name="fancheck" value="1">
                <span class="checkmark"></span>
                Area de fans
            </label>
            <label class="custom-checkbox">
                <input type="checkbox" id="artistacheck" name="artistacheck" value="1">
                <span class="checkmark"></span>
                Area de artistas
            </label>
        </div>

        <div class="previewsForm NGEESM RS ppp3" style="display: none;">
            <div class="previewAreaArchivos" id="previewImagen" style="display: none;">
                <label></label>
            </div>
            <div class="previewAreaArchivos" id="previewAudio" style="display: none;">
                <label></label>
            </div>
            <div class="previewAreaArchivos" id="previewArchivo" style="display: none;">
                <label>Archivo adicional para colab (flp, zip, rar, midi, etc)</label>
            </div>
        </div>

        <div class="bloque flex-row"" id=" opciones" style="display: none">
            <p>Opciones de post</p>
            <div class="flex flex-row gap-2">
                <label class="custom-checkbox">
                    <input type="checkbox" id="descargacheck" name="descargacheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['descargaicono']; ?>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="exclusivocheck" name="exclusivocheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['estrella']; ?>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="colabcheck" name="colabcheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['iconocolab']; ?>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="musiccheck" name="musiccheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['iconomusic']; ?>
                </label>
                <!--<label class="custom-checkbox">
                    <input type="checkbox" id="momento" name="momento" value="1">
                    <span class="checkmark"></span>
                    Momento
                </label> -->
            </div>
        </div>

        <div class="botonesForm R0A915">
            <button class="botonicono borde" id="botonAudio"><? echo $GLOBALS['subiraudio']; ?></button>

            <button class="botonicono borde" id="botonImagen"><? echo $GLOBALS['subirimagen']; ?></button>

            <button class="botonicono borde" id="botonArchivo"><? echo $GLOBALS['subirarchivo']; ?></button>

            <button class="borde" id="enviarRs">Publicar</button>
        </div>
    </div>

<?
    return ob_get_clean();
}
