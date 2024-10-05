<?

// FORMULARIO PARA SUBIR PUBLICACIÓN
function sample_form()
{
    $nonce = wp_create_nonce('social-post-nonce');

    ob_start(); ?>

    <div id="nRvORm">
        <form id="postFormSample" method="post" enctype="multipart/form-data">

            <div class="HXQTwf">

                <div class="Ivndig">

                    <div class="yIhIvn">

                        <div class="previewAreaArchivos" id="previewAreaImagen">Arrastra tu portada
                            <label></label>
                        </div>
                        <input type="file" id="postImage" name="post_image" accept="image/*" style="display:none;">
                    </div>


                    <div class="EfvrEn">

                        <div>
                            <label for="nameRola">Titulo</label>
                            <textarea id="nameRola1" name="name_Rola1" rows="1" required></textarea>
                        </div>

                        <div class="tags">
                            <label for="nameRola">Tags</label>
                            <div class="postTags" id="postTags1" contenteditable="true"></div>
                            <input type="hidden" id="postTagsHidden" name="post_tags">
                        </div>

                        <div class="opcionesform2 ">
                            <label class="custom-checkbox">
                                <input type="checkbox" id="allowDownload" name="paraDescarga" value="1">
                                <span class="checkmark"></span>
                                Permitir descargas
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="esExclusivo" name="esExclusivo" value="1">
                                <span class="checkmark"></span>
                                Privado para suscriptores
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="paraColab" name="paraColab" value="1">
                                <span class="checkmark"></span>
                                Permitir colabs
                            </label>
                        </div>

                    </div>
                </div>

                <div class="previewsForm">
                    <div class="previewAreaArchivos" id="previewAreaRola1">Arrastra tu sample
                        <label></label>
                    </div>
                    <input type="file" id="postAudio1" name="post_audio1" accept="audio/*" style="display:none;">
                </div>

                <div class="previewAreaArchivos" id="previewAreaflpSample">

                    <label>Archivo adicional para colab (flp, zip, rar, midi, etc)</label>

                    <input type="file" id="flp" name="flp" style="display: none;" accept=".flp,.zip,.rar,.cubase,.proj,.aiff,.midi,.ptx,.sng,.aup,.omg,.rpp,.xpm,.tst">

                </div>


                <div class="exQtjg" id="rolasContainer">

                </div>

            </div>

            <div class="botonesForm">
                <!-- Hay que terminar la funcionalidad de subir varios samples-->
                <button type="button" id="otrarola" style="display: none;">Agregar otro sample</button>
                <!-- <button type="button" id="W0512KN">Publicar</button> -->
                <button type="submit" id="submitBtnSample">Publicar</button>
            </div>

            <div id="validationMessage" class="hidden"></div>
            <input type="hidden" name="action" value="submit_social_post">
            <input type="hidden" name="sample" value="1">
            <input type="hidden" name="social_post_nonce" value="<? echo $nonce; ?>" />


        </form>
        <div id="uploadProgressContainer" style="position: fixed; bottom: 10px; right: 10px; display: flex; flex-direction: column;"></div>
        <button id="reportarerror" class="reportarerror">Reportar un error</button>
    </div>
<?
    return ob_get_clean();
}
add_shortcode('sample_form', 'sample_form');












