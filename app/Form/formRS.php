<?php

function formRs()
{
    ob_start();
    $user = wp_get_current_user();
    $nombreUsuario = $user->display_name;
    $urlImagenperfil = imagenPerfil($user->ID);

?>
    <div class="bloque flex gap-4" id="formRs">

            <div class="W8DK25">
                <img id="perfil-imagen" src="<?php echo esc_url($urlImagenperfil); ?>" alt="Perfil"
                    style="max-width: 50px; max-height: 50px; border-radius: 50%;">
                <p><?php echo $nombreUsuario ?></p>
            </div>

            <div>
                <div class="postTags DABVYT" id="textoRs" contenteditable="true" data-placeholder="Puedes agregar tags usando #"></div>
            </div>

            <div class="previewsForm NGEESM">
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

            <div class="bloque" id="opciones" style="display: none;">
                <label class="custom-checkbox">
                    <input type="checkbox" id="allowDownload" name="allow_download" value="1">
                    <span class="checkmark"></span>
                    Permitir descargas
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="content-block" name="content-block" value="1">
                    <span class="checkmark"></span>
                    Para suscriptores
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="para_colab" name="para_colab" value="1">
                    <span class="checkmark"></span>
                    Permitir colabs
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="momento" name="momento" value="1">
                    <span class="checkmark"></span>
                    Momento
                </label>
            </div>

            <div class="botonesForm R0A915">
                <button id="botonAudio">Audio</button>
                <button id="botonImagen">Imagen</button>
                <button id="botonArchivo">Archivo</button>
                <button id="enviarRs">Publicar</button>
            </div>
    </div>

<?php
    return ob_get_clean();
}
