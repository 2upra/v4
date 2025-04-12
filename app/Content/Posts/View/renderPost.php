<?php


// Refactor(Exec): Moved function sampleListHtml() to app/View/Renderers/PostRenderer.php



// Refactor(Exec): Función renderMusicContent() movida a app/View/Renderers/PostRenderer.php


// Refactor(Exec): Función renderNonMusicContent() movida a app/View/Renderers/PostRenderer.php


// Refactor(Exec): Función renderSubscriptionPrompt movida a app/View/Renderers/PostRenderer.php


// Refactor(Org): Función renderPostControls() movida a app/View/Helpers/PostHelper.php


// Refactor(Exec): Función renderContentAndMedia movida a app/View/Renderers/PostRenderer.php

// Refactor(Org): Función limpiarJSON movida a StringUtils.php

// Refactor(Org): Función nohayPost movida a app/View/Helpers/PostHelper.php

//Banear usuario desde el post
function handle_user_modification()
{
    if (current_user_can('administrator') && isset($_POST['author_id'])) {
        $author_id = intval($_POST['author_id']);
        if (!in_array('administrator', get_userdata($author_id)->roles)) {
            // Obtener todos los tipos de publicaciones
            $args = array(
                'author'         => $author_id,
                'posts_per_page' => -1,
                'post_type'      => 'any', // 'any' incluye todos los tipos de publicaciones
                'post_status'    => 'any'  // Incluye publicaciones en cualquier estado
            );

            $user_posts = get_posts($args);
            foreach ($user_posts as $post) {
                wp_delete_post($post->ID, true); // Borrado permanente
            }

            // Cambiar el rol del usuario a 'sin_acceso'
            $user = new WP_User($author_id);
            $user->set_role('sin_acceso');

            wp_send_json_success('Publicaciones eliminadas y usuario desactivado.');
        }
    }
    wp_send_json_error('No tienes permisos para realizar esta acción.');
}
add_action('wp_ajax_handle_user_modification', 'handle_user_modification');





// Refactor(Exec): Moved function update_post_content_callback() and its hook to app/Services/Post/PostContentService.php

function encolar_editar_post_script()
{
    global $post;
    wp_register_script('editar-post-js', get_template_directory_uri() . '/js/editarpost.js', array('jquery'), '1.0.16', true);
    wp_localize_script('editar-post-js', 'ajax_params', array(
        'ajax_url' => admin_url('admin-ajax.'),
    ));
    wp_enqueue_script('editar-post-js');
}

add_action('wp_enqueue_scripts', 'encolar_editar_post_script');
