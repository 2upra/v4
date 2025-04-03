<?php
// Refactor(Org): Función buscar_posts movida desde app/Content/Logic/busqueda.php

/**
 * Busca posts de un tipo específico que coincidan con un texto.
 *
 * @param string $post_type El tipo de post a buscar (ej. 'social_post', 'colecciones').
 * @param string $texto El texto de búsqueda.
 * @return array Un array de resultados con 'titulo', 'url', 'tipo', 'imagen'.
 */
function buscar_posts($post_type, $texto)
{
    $args = [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        's'              => $texto,
        'posts_per_page' => 3,
    ];
    $query = new WP_Query($args);
    $resultados = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $resultados[] = [
                'titulo' => get_the_title(),
                'url'    => get_permalink(),
                'tipo'   => ucfirst(str_replace('_', ' ', $post_type)),
                'imagen' => obtenerImagenPost(get_the_ID()), // Asume que obtenerImagenPost está disponible globalmente
            ];
        }
    }
    wp_reset_postdata();
    return $resultados;
}

// Nota: Esta función depende de funciones globales de WordPress y de la función obtenerImagenPost.
// Asegúrate de que este archivo sea incluido correctamente (ej. en functions.php)
// y que la función obtenerImagenPost esté definida y accesible globalmente.

// Refactor(Org): Función realizar_busqueda movida desde app/Content/Logic/busqueda.php
function realizar_busqueda($texto)
{
    $resultados = [
        'social_post' => [],
        'colecciones' => [],
        'perfiles'    => [],
    ];

    // Refactor(Org): La función buscar_posts fue movida a app/Services/SearchService.php
    $resultados['social_post'] = buscar_posts('social_post', $texto);
    $resultados['colecciones'] = buscar_posts('colecciones', $texto);
    // Nota: buscar_usuarios y balancear_resultados deben estar disponibles globalmente
    // (actualmente están en app/Content/Logic/busqueda.php)
    $resultados['perfiles'] = buscar_usuarios($texto);

    return balancear_resultados($resultados);
}
