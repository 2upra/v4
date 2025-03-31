<?php

namespace App\Setup;

/**
 * Configuración inicial del tema.
 * Crea páginas esenciales si no existen en entornos locales.
 */
function paginasIniciales1()
{
    // Verificar si las paginas ya fueron creadas
    if (get_option('paginasIniciales1') == '1') return;


    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
        update_option('paginasIniciales1', '1');
        return;
    }


    $paginas = array(
        'Inicio' => array(
            'plantilla' => 'TemplateInicio.php',
            'contenido' => 'Este es el contenido de la pagina de inicio.'
        ),
        'Colab' => array(
            'plantilla' => 'TemplateColab.php',
            'contenido' => ''
        ),
        'Dev' => array(
            'plantilla' => 'TemplateDev.php',
            'contenido' => ''
        ),
        'Colec' => array(
            'plantilla' => 'TemplateColec.php',
            'contenido' => ''
        ),
        'Feed' => array(
            'plantilla' => 'TemplateFeed.php',
            'contenido' => ''
        ),
        'FeedSample' => array(
            'plantilla' => 'TemplateFeedSample.php',
            'contenido' => ''
        ),
        'Inversor' => array(
            'plantilla' => 'TemplateInversor.php',
            'contenido' => ''
        ),
        'Music' => array(
            'plantilla' => 'TemplateMusic.php',
            'contenido' => ''
        ),
        'Prueba' => array(
            'plantilla' => 'TemplatePrueba.php',
            'contenido' => ''
        ),
        'Sample' => array(
            'plantilla' => 'TemplateSample.php',
            'contenido' => ''
        ),
        'Sello' => array(
            'plantilla' => 'TemplateSello.php',
            'contenido' => ''
        ),
        'T&Q' => array(
            'plantilla' => 'TemplateT&Q.php',
            'contenido' => ''
        ),
        'Biblioteca' => array(
            'plantilla' => 'TemplateBiblioteca.php',
            'contenido' => ''
        )
    );

    // Recorrer el array y crear las paginas
    $inicio_id = 0; // Variable para guardar el ID de la pagina de inicio
    foreach ($paginas as $titulo => $datos) {
        // Usar WP_Query en lugar de get_page_by_title
        $pagina_query = new \WP_Query(array(
            'post_type' => 'page',
            'title'     => $titulo,
            'post_status' => 'any'
        ));

        if (!$pagina_query->have_posts()) {
            $nueva_pagina = array(
                'post_title'    => $titulo,
                'post_content'  => $datos['contenido'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'page_template' => $datos['plantilla']
            );

            $nueva_pagina_id = wp_insert_post($nueva_pagina);

            // Si la pagina creada es la de inicio, guardar su ID
            if ($titulo == 'Inicio') {
                $inicio_id = $nueva_pagina_id;
            }
        }

        // Liberar memoria
        wp_reset_postdata();
    }

    // Definir la pagina de inicio
    if ($inicio_id > 0) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $inicio_id);
    }

    // Marcar que las paginas ya fueron creadas
    update_option('paginasIniciales1', '1');
}

add_action('init', __NAMESPACE__ . '\\paginasIniciales1');
