<?php


//ESTAS SON MIS FUNCIONES GENERICAS PARA PROCESAR FORMULARIO
function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
{
    $contenido = sanitize_textarea_field($_POST['textoNormal'] ?? '');
    $titulo = wp_trim_words($contenido, 15, '...');
    $autor = get_current_user_id();

    return wp_insert_post([
        'post_title'   => $titulo,
        'post_content' => $contenido,
        'post_status'  => $estadoPost,
        'post_author'  => $autor,
        'post_type'    => $tipoPost,
    ]);
}

function actualizarMetaDatos($postId)
{
    $meta_fields = [
        'paraColab'   => 'colab',
        'esExclusivo' => 'exclusivo',
        'paraDescarga' => 'descarga'
    ];

    foreach ($meta_fields as $meta_key => $post_key) {
        update_post_meta($postId, $meta_key, isset($_POST[$post_key]) ? 1 : 0);
    }
}

function confirmarArchivos($postId)
{
    foreach (['archivoId', 'audioId', 'imagenId'] as $campo) {
        if (!empty($_POST[$campo])) {
            confirmarArchivo(intval($_POST[$campo]));
        }
    }
}

function procesarURLs($postId)
{
    $procesarURLs = [
        'imagenUrl'  => 'procesarArchivo',
        'audioUrl'   => ['procesarArchivo', true],
        'archivoUrl' => 'procesarArchivo',
    ];

    foreach ($procesarURLs as $field => $callback) {
        if (!empty($_POST[$field])) {
            is_array($callback)
                ? $callback[0]($postId, $field, $callback[1])
                : $callback($postId, $field);
        }
    }
}

function asignarTags($postId)
{
    if (!empty($_POST['Tags'])) {
        wp_set_post_tags($postId, explode(',', sanitize_text_field($_POST['Tags'])), false);
    }
}

function procesarArchivo($postId, $campo, $renombrar = false)
{
    $url = esc_url_raw($_POST[$campo]);
    $archivoId = obtenerArchivoId($url, $postId);

    if ($archivoId && !is_wp_error($archivoId)) {
        actualizarMetaConArchivo($postId, $campo, $archivoId);

        if ($renombrar) {
            renombrarArchivoAdjunto($postId, $archivoId);
        }

        return true;
    }

    return false;
}

function obtenerArchivoId($url, $postId)
{
    $archivoId = attachment_url_to_postid($url);
    if (!$archivoId) {
        $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $url);
        $archivoId = media_handle_sideload([
            'name'     => basename($file_path),
            'tmp_name' => $file_path
        ], $postId);
    }

    return $archivoId;
}

function actualizarMetaConArchivo($postId, $campo, $archivoId)
{
    update_post_meta($postId, $campo, $archivoId);
    if ($campo === 'imagenUrl') {
        set_post_thumbnail($postId, $archivoId);
    }
}

function renombrarArchivoAdjunto($postId, $archivoId)
{
    $post = get_post($postId);
    $author = get_userdata($post->post_author);
    $info = pathinfo($file_path = get_attached_file($archivoId));

    $new_filename = sprintf(
        '2upra_%s_%s.%s',
        sanitize_file_name(mb_substr($author->user_login, 0, 20)),
        sanitize_file_name(mb_substr($post->post_content, 0, 40)),
        $info['extension']
    );

    if (rename($file_path, $info['dirname'] . DIRECTORY_SEPARATOR . $new_filename)) {
        update_attached_file($archivoId, $new_filename);
        update_post_meta($postId, 'sample', true);
        procesarAudioLigero($postId, $archivoId, 1);
    }
}