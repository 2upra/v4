<?php

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
                                <input type="checkbox" id="allowDownload" name="allow_download" value="1">
                                <span class="checkmark"></span>
                                Permitir descargas
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="content-block" name="content-block" value="1">
                                <span class="checkmark"></span>
                                Privado para suscriptores
                            </label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="para_colab" name="para_colab" value="1">
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
            <input type="hidden" name="social_post_nonce" value="<?php echo $nonce; ?>" />


        </form>
        <div id="uploadProgressContainer" style="position: fixed; bottom: 10px; right: 10px; display: flex; flex-direction: column;"></div>
        <button id="reportarerror" class="reportarerror">Reportar un error</button>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('sample_form', 'sample_form');

function postRolaForm()
{

    $nonce = wp_create_nonce('social-post-nonce');

    ob_start(); ?>
    <div id="social-post-container">
        <form id="postFormRola" method="post" enctype="multipart/form-data">

            <div>
                <label for="postContent">Titulo de lanzamiento</label>
                <textarea id="postContent" name="post_content" rows="1" required></textarea>
            </div>

            <div>
                <label for="realName">Tu nombre real</label>
                <textarea id="realName" name="real_name" rows="1" required></textarea>
            </div>

            <div>
                <label for="artisticName">Nombre artístico</label>
                <textarea id="artisticName" name="artistic_name" rows="1" required></textarea>
            </div>

            <div>
                <label for="email">Tu correo</label>
                <textarea id="email" name="email" rows="1" required></textarea>
            </div>

            <div id="rolasContainer">

                <label id="artistrola"></label>
                <div class="rolaForm">


                    <span class="artistrola-span" id="artistrola1"></span>

                    <div class="previewsForm">

                        <div class="previewAreaArchivos" id="previewAreaImagen">Arrastra tu portada
                            <label></label>
                        </div>

                        <input type="file" id="postImage" name="post_image" accept="image/*" style="display:none;">

                        <div class="previewAreaArchivos" id="previewAreaRola1">Arrastra tu música
                            <label></label>
                        </div>

                        <input type="file" id="postAudio1" name="post_audio1" accept="audio/*" style="display:none;">

                    </div>

                    <div>
                        <label for="nameRola">Titulo de lanzamiento</label>
                        <textarea id="nameRola1" name="name_Rola1" rows="1" required></textarea>
                    </div>

                </div>
            </div>

            <div class="botonesForm">
                <button type="button" id="otrarola">Agregar otra rola</button>
                <!--<button type="button" id="W0512KN">Publicar</button>-->
                <button type="submit" id="submitBtn">Publicar</button>
            </div>

            <input type="hidden" name="action" value="submit_social_post">
            <input type="hidden" name="rola" value="1">
            <input type="hidden" name="social_post_nonce" value="<?php echo $nonce; ?>" />


        </form>
        <button id="reportarerror" class="reportarerror">Reportar un error</button>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('social_post_rolas', 'postRolaForm');











//TAGIFY
function tagify()
{
    enqueue_and_localize_scripts('tagify', '/js/tagify.js', [], '3.0.21', true, '', '');
}
add_action('wp_enqueue_scripts', 'tagify');

//SUBIDA EN SEGUNDO PLANO 
function subida()
{
    wp_enqueue_script('subida', get_template_directory_uri() . '/js/subida.js', array('jquery'), '1.1.21', true);

    wp_localize_script('my-upload-script', 'my_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'subida');

//FORM FRONT
function formScriptFront()
{
    enqueue_and_localize_scripts('formScriptFront', '/js/formSubirRola.js', ['jquery'], '4.1.53', true, '', '');
}
add_action('wp_enqueue_scripts', 'formScriptFront');

//git pollo
//AJAX PARA SUBIR EL POST
function enqueue_and_localize_social_post_scripts()
{
    wp_enqueue_script('social-post-script', get_template_directory_uri() . '/js/ajax-submit.js', array('jquery'), '2.1.37', true);
    wp_localize_script('social-post-script', 'my_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'social_post_nonce' => wp_create_nonce('social-post-nonce'),
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_and_localize_social_post_scripts');


//MINIMIZAR Y SELECTOR DE TIPO
function form_script()
{
    wp_register_script('form-script', get_template_directory_uri() . '/js/formscript.js', array('jquery'), '1.1.11', true);
    wp_enqueue_script('form-script');

    // Agrega la información de si el usuario es administrador
    $is_admin = current_user_can('administrator') ? true : false;
    wp_localize_script('form-script', 'wpData', array(
        'isAdmin' => $is_admin
    ));
}
add_action('wp_enqueue_scripts', 'form_script');
