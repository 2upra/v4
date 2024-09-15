<?php

function formRs()
{
    ob_start();
    $user = wp_get_current_user();
    $nombreUsuario = $user->display_name;
    $urlImagenperfil = imagenPerfil($user->ID);

?>
    <div class="bloque" id="formRs">

        <div class="W8DK25">
            <img id="perfil-imagen" src="<?php echo esc_url($urlImagenperfil); ?>" alt="Perfil"
                style="max-width: 50px; max-height: 50px; border-radius: 50%;">
            <p><?php echo $nombreUsuario ?></p>
        </div>

        <div>
            <div class="postTags DABVYT" id="textoRs" contenteditable="true" data-placeholder="Puedes agregar tags usando #"></div>

            <input type="hidden" id="postTagsHidden" name="post_tags">
            
            <textarea id="postContent" name="post_content" rows="2" required placeholder="Escribe aquí" style="display: none;"></textarea>
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

        <div class="bloque flex-row"" id="opciones" style="display: none">
            <p>Opciones de post</p>
            <div class="flex flex-row gap-2">
                <label class="custom-checkbox">
                    <input type="checkbox" id="descarga" name="descarga"" value="1">
                    <span class="checkmark"></span>
                    <?php echo $GLOBALS['descargaicono']; ?>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="exclusivo" name="exclusivo" value="1">
                    <span class="checkmark"></span>
                    <?php echo $GLOBALS['estrella']; ?>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="colab" name="colab" value="1">
                    <span class="checkmark"></span>
                    <?php echo $GLOBALS['iconocolab']; ?>
                </label>
                <!--<label class="custom-checkbox">
                    <input type="checkbox" id="momento" name="momento" value="1">
                    <span class="checkmark"></span>
                    Momento
                </label> -->
            </div>
        </div>

        <div class="botonesForm R0A915">
            <button class="botonicono" id="botonAudio"><?php echo $GLOBALS['subiraudio']; ?></button>

            <button class="botonicono" id="botonImagen"><?php echo $GLOBALS['subirimagen']; ?></button>

            <button class="botonicono" id="botonArchivo"><?php echo $GLOBALS['subirarchivo']; ?></button>

            <button id="enviarRs">Publicar</button>
        </div>
    </div>

<?php
    return ob_get_clean();
}
