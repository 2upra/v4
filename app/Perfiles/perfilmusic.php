<?php

function custom_user_profile_shortcode_music() {
    $url_path = trim(parse_url(add_query_arg([]), PHP_URL_PATH), '/');
    $url_segments = explode('/', $url_path);
    $user_slug = end($url_segments);
    $user = get_user_by('slug', $user_slug);
    

    if ($user !== false) {
        $user_id = $user->ID;
        $current_user = wp_get_current_user();

        $suscripciones_a = get_user_meta($current_user->ID, 'offering_user_ids', true);
        $esta_suscrito = in_array($user_id, (array) $suscripciones_a);

        $subscription_price_id = 'price_1OqGjlCdHJpmDkrryMzL0BCK';
        $profile_description = get_user_meta($user_id, 'profile_description', true);

        $imagenPerfilId = get_user_meta($user_id, 'imagen_perfil_id', true);
        if ($imagenPerfilId) {
            $image_attributes = wp_get_attachment_image_src($imagenPerfilId, 'medium'); 
            if ($image_attributes) {
                $imagen_perfil_url = $image_attributes[0]; 
                $imagen_html = '<img src="' . esc_url($imagen_perfil_url) . '" alt="Imagen de perfil" class="gravatar avatar avatar-96 um-avatar um-avatar-default" width="' . $image_attributes[1] . '" height="' . $image_attributes[2] . '" onerror="if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}" loading="lazy">';
            }
        } else {
            $imagen_html = '<img src="https://2upra.com/wp-content/plugins/ultimate-member/assets/img/default_avatar.jpg" alt="Imagen de perfil" class="gravatar avatar avatar-96 um-avatar um-avatar-default lazyloaded" width="96" height="96" data-default="https://2upra.com/wp-content/plugins/ultimate-member/assets/img/default_avatar.jpg" onerror="if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}" loading="lazy">';
        }

        $insignia_urls = get_insignia_urls();
        $insignia_html = '';

        $user_roles = $user->roles;
        $es_admin = in_array('administrator', $user_roles);
        $es_pro = get_user_meta($user_id, 'user_pro', true);
        $es_member = get_user_meta($user_id, 'member', true);
        $oyentes_unicos = contar_oyentes_unicos($user_id);

        if ($es_admin) {
            $insignia_html .= '<img src="' . esc_url($insignia_urls['admin']) . '" alt="Insignia de Administrador" title="2UPRA TEAM" class="custom-user-insignia">';
        }

        if ($es_pro && $user_id != 1) {
            $insignia_html .= '<img src="' . esc_url($insignia_urls['pro']) . '" alt="Insignia de Usuario Pro" title="PARTNER" class="custom-user-insignia">';
        }

        if ($es_member && $user_id != 1) {
            $insignia_html .= '<img src="' . esc_url($insignia_urls['member']) . '" alt="Insignia de Usuario Pro" title="PARTNER" class="custom-user-insignia">';
        }

        $output = '<div class="music custom-uprofile-container" data-author-id="' . $user_id . '">';
        $output .= '<div class="music custom-uprofile-image">' . $imagen_html . '</div>';
        $output .= '<div class="music custom-uprofile-info">';
        $output .= '<p class="music custom-uprofile-username">' . esc_html($user->display_name) . $insignia_html . '</p>';
        $output .= '<p class="music custom-uprofile-listeners">' . $oyentes_unicos . ' Oyentes</p>';

        if (in_array('administrator', $user->roles)) {
            $output .= '<p class="music custom-uprofile-type">Artista</p>';
        } else {
            $output .= '<p class="music custom-uprofile-type">' . esc_html(ucfirst($user->roles[0])) . '</p>';
        }
        if ($user_id === $current_user->ID) {
            $output .= '<div contenteditable="true" id="editable-profile-description" data-user-id="' . esc_attr($user_id) . '" style="border: none; outline: none; max-width: 100%; overflow-wrap: break-word;">' . esc_html($profile_description) . '</div>';
        } else {
            $output .= '<p class="music custom-uprofile-description">' . esc_html($profile_description) . '</p>';
        }
        $output .= '<div class="music button-container">';
        $output .= '<button '; // Note: This line seems incomplete in the original code

        if ($user_id !== $current_user->ID) {
            $output .= '<button class="music custom-subscribe-btn';
            $output .= $esta_suscrito ? ' custom-subscribe-btn-suscrito"' : '"';
            $output .= ' data-offering-user-id="' . esc_attr($user_id) . '" ';
            $output .= 'data-offering-user-login="' . esc_attr($user->user_login) . '" ';
            $output .= 'data-offering-user-email="' . esc_attr($user->user_email) . '" ';
            $output .= 'data-subscriber-user-id="' . esc_attr($current_user->ID) . '" ';
            $output .= 'data-subscriber-user-login="' . esc_attr($current_user->user_login) . '" ';
            $output .= 'data-subscriber-user-email="' . esc_attr($current_user->user_email) . '" ';
            $output .= 'data-price="' . esc_attr($subscription_price_id) . '" ';
            $output .= 'data-url="' . esc_url(get_permalink()) . '" ';
            if ($esta_suscrito) {
                $output .= '>Suscripto</button>';
            } else {
                $output .= '>Suscribirse</button>';
            }
        }

        if ($user_id === $current_user->ID) { 
            $output .= '<button class="music custom-edit-profile-btn" onclick="abrirModalEditarPerfil()">Editar Perfil</button>';
        }

        $output .= '<button class="custom-start-chat-btn" data-chat-user-login="' . esc_attr($user->user_login) . '">Mensaje</button>';
        $output .= '</div>';
        $output .= '</div></div>';

        return $output;
    } else {
        return '<p>Perfil de usuario no encontrado.</p>';
    }
}
add_shortcode('custom_user_profile_music', 'custom_user_profile_shortcode_music');

// Refactor(Org): Moved function enqueue_scripts42 and its hook to app/Setup/ScriptSetup.php


// Refactor(Org): Moved function presentacion_shortcode() and its hook to app/View/Components/Profile/PresentationEditor.php


// Refactor(Exec): Moved function ajax_update_presentacion() and its hooks to app/View/Components/Profile/PresentationEditor.php


// Refactor(Org): Moved function postrolaresumen() to app/View/Renderers/PostRenderer.php


// Refactor(Exec): Moved function postcover() to app/View/Renderers/PostRenderer.php
