<?php


// Refactor(Exec): Moved function sampleListHtml() to app/View/Renderers/PostRenderer.php



// Refactor(Exec): Función renderMusicContent() movida a app/View/Renderers/PostRenderer.php


// Refactor(Exec): Función renderNonMusicContent() movida a app/View/Renderers/PostRenderer.php


// Refactor(Exec): Función renderSubscriptionPrompt movida a app/View/Renderers/PostRenderer.php


// Refactor(Org): Función renderPostControls() movida a app/View/Helpers/PostHelper.php


// Refactor(Exec): Función renderContentAndMedia movida a app/View/Renderers/PostRenderer.php

// Refactor(Org): Función limpiarJSON movida a StringUtils.php

// Refactor(Org): Función nohayPost movida a app/View/Helpers/PostHelper.php

// Refactor(Exec): Moved function handle_user_modification() and its hook to app/Services/UserService.php





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
