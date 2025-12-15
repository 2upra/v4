<?php

namespace Kamples\Views\Components;

use Kamples\Services\PerfilService;
use Kamples\Views\Components\LikeButtons;

class PerfilComponents
{
    /**
     * Renderiza el banner del perfil
     *
     * @param int $userId ID del usuario
     * @return string HTML
     */
    public static function renderPerfilBanner(int $userId): string
    {
        $current_user_id = get_current_user_id();
        $mismoAutor = ($userId === $current_user_id);
        $perfilService = PerfilService::obtenerInstancia();

        $seguidores = $perfilService->obtenerSeguidoresOSiguiendo($userId, 'seguidores');
        $seguidores_count = count($seguidores);

        $siguiendo = $perfilService->obtenerSeguidoresOSiguiendo($userId, 'siguiendo');
        $siguiendo_count = count($siguiendo);

        $subscription_price_id = 'price_1PBgGfCdHJpmDkrrHorFUNaV'; // Constante magic string original
        $imagen_perfil = $perfilService->obtenerImagenPerfil($userId);
        $user_info = get_userdata($userId);

        if (!$user_info) {
            return 'Usuario no encontrado';
        }

        $descripcion = get_user_meta($userId, 'profile_description', true);

        ob_start();
?>
        <div class="X522YA FRRVBB" data-iduser="<?php echo esc_attr($userId); ?>">
            <div class="JKBZKR">
                <img src="<?php echo esc_url($imagen_perfil); ?>" alt="">
                <div class="KFEVRT">
                    <p class="ZEKRWP"><?php echo esc_html($user_info->display_name); ?></p>
                    <p class="NZERUU">@<?php echo esc_html($user_info->user_login); ?></p>
                    <p class="ZBNIRW"><?php echo esc_html($descripcion); ?></p>
                </div>
            </div>

            <div class="KNIDBC">
                <p><?php echo esc_html($seguidores_count); ?> seguidores ·</p>
                <p><?php echo esc_html($siguiendo_count); ?> siguiendo</p>
            </div>

            <div class="R0A915">
                <?php if (!$mismoAutor): ?>
                    <?php
                    // Usamos el wrapper global deprecado o la nueva implementación si existiera
                    // Por ahora mantenemos la llamada a la función global si no ha sido migrada a componente
                    if (function_exists('botonSeguirPerfilBanner')) {
                        echo botonSeguirPerfilBanner($userId);
                    } else {
                        // Fallback o implementación directa
                        echo '<button class="follow-button" data-user-id="' . esc_attr($userId) . '">Seguir</button>';
                    }
                    ?>
                    <button class="borde PRJWWT mensajeBoton" data-receptor="<?php echo esc_attr($userId); ?>">Enviar mensaje</button>
                <?php endif; ?>
                <?php if ($mismoAutor): ?>
                    <button class="botonConfig borde">Configuración</button>
                    <button class="compartirPerfil borde" data-username="<?php echo esc_attr($user_info->user_login); ?>">Compartir perfil</button>
                <?php endif; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el modal de configuración de usuario
     */
    public static function renderConfigModal(): string
    {
        $current_user = wp_get_current_user();
        if (!$current_user->exists()) return '';

        $user_id = $current_user->ID;
        $user_name = $current_user->display_name;
        $descripcion = get_user_meta($user_id, 'profile_description', true);
        $linkUser = get_user_meta($user_id, 'user_link', true);
        $tipoUsuario = get_user_meta($user_id, 'tipoUsuario', true);

        ob_start();
    ?>

        <div class="LEDDCN modal" id="modalConfig" style="display: none;">
            <p class="ONDNYU">Configuración de Perfil</p>

            <form class="PVSHOT">

                <!-- Cambiar foto de perfil -->
                <div class="PTORKC">
                    <div class="previewAreaArchivos" id="previewAreaImagenPerfil">Arrastra tu foto de perfil
                        <label></label>
                    </div>
                    <input type="file" id="profilePicture" accept="image/*" style="display:none;">
                </div>

                <!-- Cambiar nombre de usuario -->
                <div class="PTORKC">
                    <label for="username">Nombre de Usuario:</label>
                    <input type="text" id="username" name="username" value="<?php echo esc_attr($user_name); ?>">
                </div>

                <!-- Cambiar descripción -->
                <div class="PTORKC">
                    <label for="description">Descripción:</label>
                    <textarea id="description" name="description" rows="2"><?php echo esc_attr($descripcion); ?></textarea>
                </div>

                <!-- Agregar un enlace -->
                <div class="PTORKC">
                    <label for="link">Enlace:</label>
                    <input type="url" id="link" name="link" placeholder="Ingresa un enlace (opcional)" value="<?php echo esc_attr($linkUser); ?>">
                </div>

                <!-- Tipo de usuario -->
                <div class="PTORKC ADGOR3">
                    <label for="typeUser">Tipo de usuario:</label>
                    <div class="DRHMDE">
                        <label class="custom-checkbox">
                            <input type="checkbox" id="fanTipoCheck" name="fanTipoCheck" value="1" <?php echo $tipoUsuario === 'Fan' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Fan
                        </label>
                        <label class="custom-checkbox">
                            <input type="checkbox" id="artistaTipoCheck" name="artistaTipoCheck" value="1" <?php echo $tipoUsuario === 'Artista' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Artista
                        </label>
                    </div>
                </div>

            </form>
            <button class="guardarConfig">Guardar cambios</button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el shortcode custom_user_profile_music
     */
    public static function renderMusicProfile(): string
    {
        $url_path = trim(parse_url(add_query_arg([]), PHP_URL_PATH), '/');
        $url_segments = explode('/', $url_path);

        // Asumiendo que el slug del usuario es el último segmento
        // O lógica específica para obtener el usuario de la URL
        // Original: $user_slug = end($url_segments);
        // Esto puede fallar si hay query params o estructura diferente, pero mantenemos lógica legacy
        $user_slug = end($url_segments);

        // Mejor intento: buscar usuario por slugs en la URL
        $user = get_user_by('slug', $user_slug);

        if ($user === false) {
            // Fallback si no funciona el último segmento, intentamos buscar 'perfil'
            if (($key = array_search('perfil', $url_segments)) !== false && isset($url_segments[$key + 1])) {
                $user = get_user_by('slug', $url_segments[$key + 1]);
            }
        }

        if ($user !== false) {
            $user_id = $user->ID;
            $current_user = wp_get_current_user();
            $perfilService = PerfilService::obtenerInstancia();

            $suscripciones_a = get_user_meta($current_user->ID, 'offering_user_ids', true);
            $esta_suscrito = is_array($suscripciones_a) && in_array($user_id, $suscripciones_a);

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
                $default_avatar = site_url('/wp-content/plugins/ultimate-member/assets/img/default_avatar.jpg');
                $imagen_html = '<img src="' . esc_url($default_avatar) . '" alt="Imagen de perfil" class="gravatar avatar avatar-96 um-avatar um-avatar-default lazyloaded" width="96" height="96" data-default="' . esc_url($default_avatar) . '" onerror="if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}" loading="lazy">';
            }

            // Insignias logic (simplificada o traída de global)
            // Asumimos que get_insignia_urls() es global o helper
            $insignia_urls = function_exists('get_insignia_urls') ? get_insignia_urls() : [];
            $insignia_html = '';

            $user_roles = $user->roles;
            $es_admin = in_array('administrator', $user_roles);
            $es_pro = get_user_meta($user_id, 'user_pro', true);
            $es_member = get_user_meta($user_id, 'member', true);

            // contar_oyentes_unicos global
            $oyentes_unicos = function_exists('contar_oyentes_unicos') ? contar_oyentes_unicos($user_id) : 0;

            if ($es_admin && isset($insignia_urls['admin'])) {
                $insignia_html .= '<img src="' . esc_url($insignia_urls['admin']) . '" alt="Insignia de Administrador" title="2UPRA TEAM" class="custom-user-insignia">';
            }

            if ($es_pro && $user_id != 1 && isset($insignia_urls['pro'])) {
                $insignia_html .= '<img src="' . esc_url($insignia_urls['pro']) . '" alt="Insignia de Usuario Pro" title="PARTNER" class="custom-user-insignia">';
            }

            if ($es_member && $user_id != 1 && isset($insignia_urls['member'])) {
                $insignia_html .= '<img src="' . esc_url($insignia_urls['member']) . '" alt="Insignia de Usuario Pro" title="PARTNER" class="custom-user-insignia">';
            }

            ob_start();
        ?>
            <div class="music custom-uprofile-container" data-author-id="<?php echo esc_attr($user_id); ?>">
                <div class="music custom-uprofile-image"><?php echo $imagen_html; ?></div>
                <div class="music custom-uprofile-info">
                    <p class="music custom-uprofile-username"><?php echo esc_html($user->display_name); ?><?php echo $insignia_html; ?></p>
                    <p class="music custom-uprofile-listeners"><?php echo $oyentes_unicos; ?> Oyentes</p>

                    <?php
                    if (in_array('administrator', $user->roles)) {
                        echo '<p class="music custom-uprofile-type">Artista</p>';
                    } else {
                        echo '<p class="music custom-uprofile-type">' . esc_html(ucfirst($user->roles[0] ?? '')) . '</p>';
                    }

                    if ($user_id === $current_user->ID) {
                        echo '<div contenteditable="true" id="editable-profile-description" data-user-id="' . esc_attr($user_id) . '" style="border: none; outline: none; max-width: 100%; overflow-wrap: break-word;">' . esc_html($profile_description) . '</div>';
                    } else {
                        echo '<p class="music custom-uprofile-description">' . esc_html($profile_description) . '</p>';
                    }
                    ?>
                    <div class="music button-container">
                        <?php if ($user_id !== $current_user->ID): ?>
                            <button class="music custom-subscribe-btn <?php echo $esta_suscrito ? 'custom-subscribe-btn-suscrito' : ''; ?>"
                                data-offering-user-id="<?php echo esc_attr($user_id); ?>"
                                data-offering-user-login="<?php echo esc_attr($user->user_login); ?>"
                                data-offering-user-email="<?php echo esc_attr($user->user_email); ?>"
                                data-subscriber-user-id="<?php echo esc_attr($current_user->ID); ?>"
                                data-subscriber-user-login="<?php echo esc_attr($current_user->user_login); ?>"
                                data-subscriber-user-email="<?php echo esc_attr($current_user->user_email); ?>"
                                data-price="<?php echo esc_attr($subscription_price_id); ?>"
                                data-url="<?php echo esc_url(get_permalink()); ?>">
                                <?php echo $esta_suscrito ? 'Suscripto' : 'Suscribirse'; ?>
                            </button>
                        <?php endif; ?>

                        <?php if ($user_id === $current_user->ID): ?>
                            <button class="music custom-edit-profile-btn" onclick="abrirModalEditarPerfil()">Editar Perfil</button>
                        <?php endif; ?>

                        <button class="custom-start-chat-btn" data-chat-user-login="<?php echo esc_attr($user->user_login); ?>">Mensaje</button>
                    </div>
                </div>
            </div>
        <?php
            return ob_get_clean();
        } else {
            return '<p>Perfil de usuario no encontrado.</p>';
        }
    }

    /**
     * Renderiza resumen de rola (post type music)
     */
    public static function renderPostRolaResumen(): string
    {
        $current_post_id = get_the_ID();
        $author_id = get_the_author_meta('ID');
        $author_name = get_the_author();

        $audio_id_lite = get_post_meta($current_post_id, 'post_audio_lite', true);

        // $audio_id = get_post_meta($current_post_id, 'post_audio', true); 
        // $audio_url = wp_get_attachment_url($audio_id);

        $duration = get_post_meta($current_post_id, 'audio_duration', true);

        $post_content = get_the_content();
        $post_content = wp_strip_all_tags($post_content);
        $post_content = esc_attr($post_content);

        $post_thumbnail_id = get_post_thumbnail_id();
        $post_thumbnail_url = function_exists('jetpack_photon_url')
            ? jetpack_photon_url(wp_get_attachment_image_url($post_thumbnail_id, 'medium'), array('quality' => 50, 'strip' => 'all'))
            : wp_get_attachment_image_url($post_thumbnail_id, 'medium');

        // Like logic
        $user_has_liked = false;
        // Logica simplificada, mejor usar LikeButtons component

        ob_start();
        ?>
        <li class="social-post rola" data-post-id="<?php echo $current_post_id; ?>">
            <input type="hidden" class="post-id" value="<?php echo $current_post_id; ?>" />
            <div class="rola social-post-content" style="font-size: 13px;">

                <div id="audio-container-<?php echo $current_post_id; ?>" class="audio-container"
                    data-imagen="<?php echo esc_url($post_thumbnail_url); ?>"
                    data-title="<?php echo $post_content; ?>"
                    data-author="<?php echo esc_attr($author_name); ?>"
                    data-post-id="<?php echo $current_post_id; ?>"
                    data-artist="<?php echo esc_attr($author_id); ?>"
                    data-liked="<?php echo $user_has_liked ? 'true' : 'false'; ?>"
                    style="width: 40px; height: 40px; aspect-ratio: 1 / 1; position: relative;">

                    <img class="imagen-post" src="<?php echo esc_url($post_thumbnail_url); ?>" alt="Imagen del post" style="position: absolute; border-radius: 3%; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                    <div class="play-pause-sobre-imagen" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: pointer; display: none;">
                        <img src="<?php echo esc_url(site_url('/wp-content/uploads/2024/03/1.svg')); ?>" alt="Play" style="width: 50px; height: 50px;">
                    </div>
                    <audio id="audio-<?php echo $current_post_id; ?>" src="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
                </div>

                <div class="contentrola"><?php the_content(); ?></div>
                <div class="duracionrola"><?php echo esc_html($duration); ?></div>
                <div class="social-post-like rola">
                    <?php
                    // Usar LikeButtons si existe
                    if (class_exists('Kamples\Views\Components\LikeButtons')) {
                        echo (new LikeButtons())->render($current_post_id);
                    }
                    ?>
                </div>

        </li>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza post cover
     */
    public static function renderPostCover(): string
    {
        $current_post_id = get_the_ID();
        $author_id = get_the_author_meta('ID');
        $author_name = get_the_author();

        $audio_id_lite = get_post_meta($current_post_id, 'post_audio_lite', true);

        $post_content = get_the_content();
        $post_content = wp_strip_all_tags($post_content);
        $post_content = esc_attr($post_content);

        $post_thumbnail_id = get_post_thumbnail_id();
        $post_thumbnail_url = function_exists('jetpack_photon_url')
            ? jetpack_photon_url(wp_get_attachment_image_url($post_thumbnail_id, 'medium'), array('quality' => 50, 'strip' => 'all'))
            : wp_get_attachment_image_url($post_thumbnail_id, 'medium');

        ob_start();
    ?>
        <li class="social-post cover" data-post-id="<?php echo $current_post_id; ?>">
            <input type="hidden" class="post-id" value="<?php echo $current_post_id; ?>" />
            <div class="cover social-post-content" style="font-size: 13px;">

                <div id="audio-container-<?php echo $current_post_id; ?>" class="audio-container"
                    data-imagen="<?php echo esc_url($post_thumbnail_url); ?>"
                    data-title="<?php echo $post_content; ?>"
                    data-author="<?php echo esc_attr($author_name); ?>"
                    data-post-id="<?php echo $current_post_id; ?>"
                    data-artist="<?php echo esc_attr($author_id); ?>"
                    data-liked="false"
                    style="width: 150px;height: 150px;aspect-ratio: 1 / 1;position: relative;">

                    <img class="imagen-post" src="<?php echo esc_url($post_thumbnail_url); ?>" alt="Imagen del post" style="position: absolute; border-radius: 3%; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                    <div class="play-pause-sobre-imagen" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: pointer; display: none;">
                        <img src="<?php echo esc_url(site_url('/wp-content/uploads/2024/03/1.svg')); ?>" alt="Play" style="width: 50px; height: 50px;">
                    </div>
                    <audio id="audio-<?php echo $current_post_id; ?>" src="<?php echo site_url('?custom-audio-stream=1&audio_id=' . $audio_id_lite); ?>"></audio>
                </div>

                <div class="contentrola"><?php the_content(); ?></div>
            </div>
        </li>
<?php
        return ob_get_clean();
    }

    /**
     * Renderiza la presentación (shortcode legacy)
     * 
     * @param array|string $atts Atributos del shortcode
     * @return string HTML
     */
    public static function renderPresentacionLegacy($atts): string
    {
        $current_user_id = get_current_user_id();
        $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $url_segments = explode('/', trim($url_path, '/'));
        $perfil_index = array_search('music', $url_segments);

        // Lógica de detección de usuario en URL
        $user_id = null;
        if ($perfil_index !== false && isset($url_segments[$perfil_index + 1])) {
            $user = get_user_by('slug', $url_segments[$perfil_index + 1]);
            if ($user) $user_id = $user->ID;
        }

        // Recupera valores
        $saved_text = get_user_meta($user_id, 'presentacion_texto', true);
        $saved_image = get_user_meta($user_id, 'presentacion_imagen', true);

        $atts = shortcode_atts(array(
            'texto' => $saved_text ?: 'Este es un texto de ejemplo blablabla, 1ndoryü tu patrona.',
            'imagen' => $saved_image ?: site_url('/wp-content/uploads/2024/03/GC1r9wVXgAA5e2T.jpg'),
        ), $atts);

        // Construye HTML
        $html = "<div class='presentacion-container' id='presentacion'>";
        $html .= "<div class='imagen-container'>";
        $html .= "<img src='" . esc_url($atts['imagen']) . "' alt='Imagen de Presentación' id='presentacion-imagen'>";
        $html .= "<p id='presentacion-texto'>" . esc_html($atts['texto']) . "</p>";

        if ($user_id && $current_user_id == $user_id) {
            $html .= "<button onclick='openModal()'>Editar</button>";
        }

        $html .= "</div></div>";

        // Modal (hardcoded en el original)
        $html .= "<div id='modal' class='modal'>";
        $html .= "<div class='modal-content'>";
        $html .= "<span class='close' onclick='closeModal()'>&times;</span>";
        $html .= "<form id='editForm' enctype='multipart/form-data'>";
        $html .= "<input type='file' id='newImage' name='newImage'>";
        $html .= "<textarea id='editedText' name='editedText' placeholder='Editar texto de presentación'></textarea>";
        $html .= "<input type='button' value='Guardar Cambios' onclick='updatePresentacion()'>";
        $html .= "</form></div></div>";

        return $html;
    }
}
