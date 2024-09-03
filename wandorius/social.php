<?php

function social()
{
    ob_start();

    $user_id = get_current_user_id();
    $acciones = get_user_meta($user_id, 'acciones', true);
    $active_tab = ($acciones > 1) ? 'bolsa' : 'inicio';
?>

    <div class="tabs">
        <div class="tab-content">

            <?php if ($acciones > 1) : ?>
                <div class="tab <?php echo ($active_tab === 'bolsa') ? 'active' : ''; ?> CRRWGL" id="bolsa" data-post-id="tab4-posts" data-id="unico4">
                    <?php echo inversor(); ?>
                </div>
            <?php endif; ?>

            <div class="tab <?php echo ($active_tab === 'inicio') ? 'active' : ''; ?> S4K7I3" id="inicio">
                <div class="OXMGLZ">
                    <div class="OAXRVB">
                        <div class="K51M22">
                            <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="momento" tab_id="tab1-posts"]'); ?>
                            <div class="PODOVV">
                                <?php echo momentosfijos() ?>
                            </div>
                        </div>
                        <div class="M0883I">
                            <?php echo do_shortcode('[formSocial]');
                            ?>
                        </div>
                        <div class="FEDAG5">
                            <?php echo do_shortcode('[mostrar_publicaciones_sociales filtro="no_bloqueado" tab_id="tab1-posts"]'); ?>
                        </div>
                    </div>
                    <div class="SUPMPQ">
                        <p>Sugerencia de seguimiento</p>
                        <?php echo do_shortcode('[RecomendarUsuarios]'); ?>
                    </div>
                </div>
            </div>

            <div class="tab S4K7I3" id="Logs">
                <?php echo logsAdmin() ?>
            </div>

            <div class="tab S4K7I3" id="Reportes">
                <?php echo reportesAdmin() ?>
            </div>

        </div>
    </div>

<?php
    return ob_get_clean();
}



function formSocial()
{
    ob_start();
    $nonce = wp_create_nonce('social-post-nonce');
    $user = wp_get_current_user();
    $nombre_usuario = $user->display_name;
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID);
    if (function_exists('jetpack_photon_url')) {
        $url_imagen_perfil = jetpack_photon_url($url_imagen_perfil, array('quality' => 40, 'strip' => 'all'));
    }

?>
    <div class="X522YA" id="FormSubidaRs">
        <form id="postFormRs" method="post" enctype="multipart/form-data">

            <div class="RE5840">
                <div class="W8DK25">
                    <img id="perfil-imagen" src="<?php echo esc_url($url_imagen_perfil); ?>" alt="Perfil"
                        style="max-width: 50px; max-height: 50px; border-radius: 50%;">
                    <p><?php echo $nombre_usuario ?></p>
                </div>
                <div>
                    <div class="postTags DABVYT" id="textoRs" contenteditable="true" data-placeholder="Puedes agregar tags usando #"></div>
                    <input type="hidden" id="postTagsHidden" name="post_tags">
                    <textarea id="postContent" name="post_content" rows="2" required placeholder="Escribe aquí" style="display: none;"></textarea>
                </div>
            </div>

            <div class="previewsForm NGEESM">

                <div class="previewAreaArchivos" id="previewAreaImagen" style="display: none;">
                    <label></label>
                </div>

                <input type="file" id="postImage" name="post_image" accept="image/*" style="display:none;">

                <div class="previewAreaArchivos" id="previewAreaRola1" style="display: none;">
                    <label></label>
                </div>

                <input type="file" id="postAudio1" name="post_audio1" accept="audio/*" style="display:none;">


                <div class="previewAreaArchivos" id="previewAreaflp" style="display: none;">
                    <label>Archivo adicional para colab (flp, zip, rar, midi, etc)</label>
                </div>

                <input type="file" id="flp" name="flp" style="display: none;" accept=".flp,.zip,.rar,.cubase,.proj,.aiff,.midi,.ptx,.sng,.aup,.omg,.rpp,.xpm,.tst">

            </div>

            <div class="opcionesform2" id="SABTJC" style="display: none;">
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
                <button type="button" id="U74C2P">Audio</button>
                <button type="button" id="41076K">Imagen</button>
                <button type="button" id="SGGDAS">Archivo</button>
                <button type="submit" id="submitBtnRs">Publicar</button>
            </div>

            <input type="hidden" name="action" value="submit_social_post">
            <input type="hidden" name="socialpost" value="1">
            <input type="hidden" name="social_post_nonce" value="<?php echo $nonce; ?>" />

        </form>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('formSocial', 'formSocial');

function momentosfijos()
{
    ob_start();

    $imagenUno = "https://images.ctfassets.net/kftzwdyauwt9/2CPrXUZS0yLGo894hU24zv/b9e1759c6f213a8888e17852266c515b/apple-art-2a-3x4.jpg?w=640&q=90&fm=webp";
    $imagenDos = "https://images.ctfassets.net/kftzwdyauwt9/1ZTOGp7opuUflFmI2CsATh/df5da4be74f62c70d35e2f5518bf2660/ChatGPT_Carousel1.png?w=640&q=90&fm=webp";
    $imagenTres = "https://images.ctfassets.net/kftzwdyauwt9/3XDJfuQZLCKWAIOleFIFZn/14b93d23652347ee7706eca921e3a716/enterprise.png?w=640&q=90&fm=webp";

?>
    <div class="ZCOPHT" style="background-image: url('<?php echo esc_url($imagenUno); ?>');" onclick="window.location.href='https://2upra.com/quehacer';">
        <p>Que hacer en 2upra</p>
    </div>
    <div class="ZCOPHT" style="background-image: url('<?php echo esc_url($imagenDos); ?>');" onclick="window.location.href='https://2upra.com/descubrir2upra';">
        <p>Descubre el proyecto</p>
    </div>
    <div class="ZCOPHT" style="background-image: url('<?php echo esc_url($imagenTres); ?>');" onclick="window.location.href='https://2upra.com/reglas';">
        <p>Normas y Políticas</p>
    </div>
<?php

    $contenido = ob_get_clean();
    return $contenido;
}
