<?php

function postRolaForm()
{
    ob_start() ?>

    <div class="FLXVTQ">
        <a href="https://2upra.com/">
            <p>La funcionalidad de subir rola aún no esta disponible</p>
            <button class="borde">Volver</button>
        </a>
    </div>

<?php
}

function postRolaFormTest()
{
    $nonce = wp_create_nonce('postRs');

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
