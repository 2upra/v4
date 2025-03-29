<?

function formRs()
{
    ob_start();
    $user = wp_get_current_user();
    $nombreUsuario = $user->display_name;
    $urlImagenperfil = imagenPerfil($user->ID);

?>
    <style>
        div#multiplesAudios label {
            width: 100%;
            place-content: center;
            padding: 6px;
        }

        div#multiplesAudios {
            width: 100%;
            gap: 10px;
            place-content: center;
        }
    </style>
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



        <div class="previewsForm NGEESM RS ppp3" id="ppp3" style="display: none;">
            <div class="previewAreaArchivos" id="previewImagen" style="display: none;">
                <label></label>
            </div>
            <div class="previewAreaArchivos" id="previewAudio" style="display: none;">
                <label></label>
                <div class="flew-row" id="multiplesAudios" style="display: none;">
                    <label class="custom-checkbox">
                        <input type="checkbox" id="individualPost" name="individualPost" value="1">
                        <span class="checkmark"></span>
                        Individual post
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" id="multiplePost" name="multiplePost" value="1">
                        <span class="checkmark"></span>
                        Multiples post
                    </label>
                </div>
            </div>
            <div class="previewAreaArchivos" id="previewArchivo" style="display: none;">
                <label>Archivo adicional para colab (flp, zip, rar, midi, etc)</label>
            </div>
        </div>

        <div class="DRHMDE" id="fanartistchecks">
            <label class="custom-checkbox">
                <input type="checkbox" id="fancheck" name="fancheck" value="1">
                <span class="checkmark">Area de fans</span>

            </label>
            <label class="custom-checkbox">
                <input type="checkbox" id="artistacheck" name="artistacheck" value="1">
                <span class="checkmark">Area de artistas</span>

            </label>
        </div>

        <input type="text" id="nombreLanzamiento" class="nombreLanzamiento" placeholder="Titulo de lanzamiento" style="background: none;
    border: var(--borde); display: none;">

        <div class="bloque flex-row" id="opciones" style="display: none">
            <p>Opciones de post</p>
            <div class="flex flex-row gap-2">
                <label class="custom-checkbox tooltip-element" data-tooltip="Permite las descargas en la publicación">
                    <input type="checkbox" id="descargacheck" name="descargacheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['descargaicono']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Exclusividad: solo los usuarios suscritos verán el contenido de la publicación">
                    <input type="checkbox" id="exclusivocheck" name="exclusivocheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['estrella']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Permite recibir solicitudes de colaboración">
                    <input type="checkbox" id="colabcheck" name="colabcheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['iconocolab']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Publicar en formato stream y lanzar a tiendas musicales">
                    <input type="checkbox" id="musiccheck" name="musiccheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['iconomusic']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Vender el contenido, beat o sample en la tienda de 2upra">
                    <input type="checkbox" id="tiendacheck" name="tiendacheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['dolar']; ?>
                </label>
                <label class="custom-checkbox tooltip-element" data-tooltip="Publicación Efimera">
                    <input type="checkbox" id="momentocheck" name="momentocheck" value="1">
                    <span class="checkmark"></span>
                    <? echo $GLOBALS['momentoIcon']; ?>
                </label>
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
