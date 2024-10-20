<? 

function eliminarAdjuntosPost($post_id)
{
    $adjuntos = get_attached_media('', $post_id);

    foreach ($adjuntos as $adjunto) {
        wp_delete_attachment($adjunto->ID, true);
        $hash_keys = ['idHash_archivoId', 'idHash_audioId', 'idHash_imagenId'];
        foreach ($hash_keys as $key) {
            $id = get_post_meta($post_id, $key, true);
            if ($id) {
                eliminarHash($id);
            }
        }
    }
}

add_action('before_delete_post', 'eliminarAdjuntosPost');
