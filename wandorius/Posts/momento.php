<?php 


function postMomento()
{
    global $post;
    $current_user_id = get_current_user_id();
    $author_id = get_the_author_meta('ID');
    $user = get_userdata($author_id);
    $author_name = get_the_author();
    $audio_id_lite = get_post_meta(get_the_ID(), 'post_audio_lite', true);
    $author_username = $user->user_login;
    $music_profile_url = esc_url(home_url('/music/' . $author_username));
    $url_imagen_perfil = obtener_url_imagen_perfil_o_defecto($user->ID);
    if (function_exists('jetpack_photon_url')) {
        $url_imagen_perfil = jetpack_photon_url($url_imagen_perfil, array('quality' => 40, 'strip' => 'all'));
    }
    // Obtener información de 'likes'
    $current_post_id = get_the_ID();
    $user_has_liked = check_user_liked_post($current_post_id, $current_user_id);

    ob_start();
    $post_content = get_the_content();
    $post_content = wp_strip_all_tags($post_content);
    $post_content = esc_attr($post_content);
    $post_thumbnail_id = get_post_thumbnail_id();
    $post_thumbnail_url = function_exists('jetpack_photon_url')
        ? jetpack_photon_url(wp_get_attachment_image_url($post_thumbnail_id, 'medium'), array('quality' => 50, 'strip' => 'all'))
        : wp_get_attachment_image_url($post_thumbnail_id, 'medium');

?>

    <li class="HEYCHL social-post cover" data-post-id="<?php echo get_the_ID(); ?>" style="background-image: url('<?php echo esc_url($post_thumbnail_url); ?>'); background-size: cover; background-position: center;">

        <input type="hidden" class="post-id" value="<?php echo get_the_ID(); ?>" />

        <div class="W8DK25">
            <img id="perfil-imagen" src="<?php echo esc_url($url_imagen_perfil); ?>" alt="Perfil"
                style="max-width: 50px; max-height: 50px; border-radius: 50%;">
        </div>

        <div class="IVAMXF cover social-post-content">
            <div id="audio-container-<?php echo get_the_ID(); ?>" class="audio-container"
                data-imagen="<?php echo esc_url($post_thumbnail_url); ?>"
                data-title="<?php echo $post_content; ?>"
                data-author="<?php echo esc_attr($author_name); ?>"
                data-post-id="<?php echo get_the_ID(); ?>"
                data-artist="<?php echo esc_attr($author_id); ?>"
                data-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
                style="width: 150px;height: 150px;aspect-ratio: 1 / 1;position: relative;">

                <div class="play-pause-sobre-imagen" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: pointer; display: none;">
                    <img src="https://2upra.com/wp-content/uploads/2024/03/1.svg" alt="Play" style="width: 50px; height: 50px;">
                </div>

                <audio id="audio-<?php echo get_the_ID(); ?>" src="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
            </div>

            <div class="contentrola"><?php the_content(); ?></div>
            <div class="music-profile-info">
                <a href="<?php echo $music_profile_url; ?>" class="profile-link inicio-music">
                    <?php echo esc_html($author_name); ?>
                </a>
            </div>

            <div class="social-post-like rola" style="display: none;">
                <?php
                $current_post_id = get_the_ID();
                like($current_post_id);
                ?>
            </div>
        </div>
    </li>
<?php
    return ob_get_clean();
}






function delete_expired_momentos() {
    $args = array(
        'post_type' => 'social_post',
        'meta_query' => array(
            array(
                'key' => 'momento',
                'value' => true,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    );

    $momentos = get_posts($args);

    foreach ($momentos as $momento) {
        // Verificar si el post lleva más de 24 horas publicado
        $post_date_timestamp = strtotime($momento->post_date);
        if (time() - $post_date_timestamp > 24 * 60 * 60) {
            // Obtener los archivos adjuntos
            $attachments = get_attached_media('', $momento->ID);
            
            // Borrar cada archivo adjunto
            foreach ($attachments as $attachment) {
                wp_delete_attachment($attachment->ID, true);
            }
            
            // Borrar el post
            wp_delete_post($momento->ID, true);
        }
    }
}

if (!wp_next_scheduled('delete_expired_momentos_hook')) {
    wp_schedule_event(time(), 'hourly', 'delete_expired_momentos_hook');
}
add_action('delete_expired_momentos_hook', 'delete_expired_momentos');
