<?php
// Refactor(Org): Function save_waveform_image() and its AJAX hooks moved to app/Services/AudioProcessingService.php

function reset_waveform_metas()
{
    guardarLog("Iniciando la función reset_waveform_metas.");

    $args = array(
        'post_type' => 'social_post',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'waveCargada',
                'value' => '1',
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);
    guardarLog("WP_Query ejecutado. Número de posts encontrados: " . $query->found_posts);

    if ($query->have_posts()) {
        guardarLog("Entrando en el bucle de posts.");
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            guardarLog("Procesando el post ID $post_id.");

            // Resetear waveCargada a false.
            update_post_meta($post_id, 'waveCargada', false);

            // Eliminar la imagen de waveform existente.
            $existing_attachment_id = get_post_meta($post_id, 'waveform_image_id', true);
            if ($existing_attachment_id) {
                wp_delete_attachment($existing_attachment_id, true);
            }

            // Eliminar los metadatos relacionados con la waveform.
            delete_post_meta($post_id, 'waveform_image_id');
            delete_post_meta($post_id, 'waveform_image_url');
        }
    } else {
        guardarLog("No se encontraron posts con el metadato 'waveCargada' igual a true.");
    }

    wp_reset_postdata();
    guardarLog("Finalizando la función reset_waveform_metas.");
}

// Registrar las acciones AJAX.
// add_action('wp_ajax_save_waveform_image', 'save_waveform_image'); // Moved
// add_action('wp_ajax_nopriv_save_waveform_image', 'save_waveform_image'); // Moved
