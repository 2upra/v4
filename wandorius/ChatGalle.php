<?php 


/*
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

$sql_messages = "CREATE TABLE `wpsg_messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `message_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

dbDelta( $sql_messages );



require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

$sql = "CREATE TABLE `wpsg_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `participant_1` bigint(20) UNSIGNED NOT NULL,
  `participant_2` bigint(20) UNSIGNED NOT NULL,
  `last_message_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `participant_1` (`participant_1`),
  KEY `participant_2` (`participant_2`),
  CONSTRAINT `wpsg_conversations_ibfk_1` FOREIGN KEY (`participant_1`) REFERENCES `wpsg_users` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `wpsg_conversations_ibfk_2` FOREIGN KEY (`participant_2`) REFERENCES `wpsg_users` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

*/



function save_message($sender_id, $receiver_id, $message_text) {
    global $wpdb;
    $conversation_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM wpsg_conversations WHERE (participant_1 = %d AND participant_2 = %d) OR (participant_1 = %d AND participant_2 = %d) LIMIT 1",
        $sender_id, $receiver_id, $receiver_id, $sender_id
    ));

    if (!$conversation_id) {
        $wpdb->insert('wpsg_conversations', ['participant_1' => $sender_id, 'participant_2' => $receiver_id]);
        $conversation_id = $wpdb->insert_id;
    }

    $wpdb->insert('wpsg_messages', [
        'conversation_id' => $conversation_id,
        'sender_id' => $sender_id,
        'message_text' => $message_text,
        'created_at' => current_time('mysql', 1)
    ]);

    if ($wpdb->insert_id) {
        $wpdb->update('wpsg_conversations', ['last_message_id' => $wpdb->insert_id], ['id' => $conversation_id]);
    }

    return $conversation_id;
}

function guardar_mensaje_handler($request) {
    $sender_id = get_current_user_id(); 
    $params = $request->get_json_params();
    $receiver_login = $params['receiver_id']; 
    $message_text = $params['message_text'];

    global $wpdb;
    $receiver_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM wpsg_users WHERE user_login = %s", $receiver_login));

    if (!$sender_id || !$receiver_id || !$message_text) {
        return new WP_Error('missing_info', 'Falta información para guardar el mensaje', ['status' => 400]);
    }
    save_message($sender_id, $receiver_id, $message_text);
    return new WP_REST_Response('Mensaje guardado con éxito', 200);
}

function get_user_conversations($user_id, $page = 1, $per_page = 20) {
    global $wpdb;

    $offset = ($page - 1) * $per_page;
    $conversations_data = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT c.id, c.participant_1, c.participant_2, m.message_text, m.sender_id, 
            IF(c.participant_1 = %d, u2.user_login, u1.user_login) AS with_user,
            IF(c.participant_1 = %d, c.participant_2, c.participant_1) AS other_user_id, 
            u3.user_login AS sender_user
        FROM wpsg_conversations c
        INNER JOIN wpsg_messages m ON c.last_message_id = m.id
        INNER JOIN wpsg_users u1 ON c.participant_1 = u1.ID
        INNER JOIN wpsg_users u2 ON c.participant_2 = u2.ID
        INNER JOIN wpsg_users u3 ON m.sender_id = u3.ID
        WHERE %d IN (c.participant_1, c.participant_2)
        ORDER BY m.created_at DESC
        LIMIT %d OFFSET %d", $user_id, $user_id, $user_id, $per_page, $offset), ARRAY_A);

    $conversations = [];
    foreach ($conversations_data as $conversation) {
        if (!isset($conversations[$conversation['id']])) {
            $conversations[$conversation['id']] = [
                'with_user' => $conversation['with_user'],
                'other_user_id' => $conversation['other_user_id'], // Incluir el ID del otro usuario
                'messages' => []
            ];
        }
        
        $conversations[$conversation['id']]['messages'][] = [
            'message_text' => $conversation['message_text'],
            'sender_user' => $conversation['sender_user']
        ];
    }

    return $conversations;
}


function show_user_conversations() {
    $current_user_id = get_current_user_id();

    $conversations = get_user_conversations($current_user_id);


    $output = "<div class='icono-notificacion' id='conversations-icon' style='cursor: pointer;'>" .
    "<svg data-testid='geist-icon' height='16' stroke-linejoin='round' viewBox='0 0 16 16' width='16' style='color: currentcolor;'>" .
    "<path fill-rule='evenodd' clip-rule='evenodd' d='M2.8914 10.4028L2.98327 10.6318C3.22909 11.2445 3.5 12.1045 3.5 13C3.5 13.3588 3.4564 13.7131 3.38773 14.0495C3.69637 13.9446 4.01409 13.8159 4.32918 13.6584C4.87888 13.3835 5.33961 13.0611 5.70994 12.7521L6.22471 12.3226L6.88809 12.4196C7.24851 12.4724 7.61994 12.5 8 12.5C11.7843 12.5 14.5 9.85569 14.5 7C14.5 4.14431 11.7843 1.5 8 1.5C4.21574 1.5 1.5 4.14431 1.5 7C1.5 8.18175 1.94229 9.29322 2.73103 10.2153L2.8914 10.4028ZM2.8135 15.7653C1.76096 16 1 16 1 16C1 16 1.43322 15.3097 1.72937 14.4367C1.88317 13.9834 2 13.4808 2 13C2 12.3826 1.80733 11.7292 1.59114 11.1903C0.591845 10.0221 0 8.57152 0 7C0 3.13401 3.58172 0 8 0C12.4183 0 16 3.13401 16 7C16 10.866 12.4183 14 8 14C7.54721 14 7.10321 13.9671 6.67094 13.9038C6.22579 14.2753 5.66881 14.6656 5 15C4.23366 15.3832 3.46733 15.6195 2.8135 15.7653Z' fill='white'></path>" .
    "</svg>" .
    "</div>";

    $output .= "<div class='user-conversations' style='display:none;'>";
    foreach ($conversations as $conversation_id => $conversation) {
        $with_user = $conversation['with_user'];
        $imagen_url = obtener_url_imagen_perfil_o_defecto($conversation['other_user_id']);
        $output .= "<div class='conversation-item' data-conversation-id='" . esc_attr($conversation_id) . "' data-chat-user-login='" . esc_attr($conversation['with_user']) . "'>";
        $output .= "<img src='" . esc_url($imagen_url) . "' alt='Imagen de perfil' class='perfil-conversacion'/>";
        $output .= "<div class='mensajes-conversacion'>";
        $output .= "<p class='mensajeuser'>" . esc_html($with_user) . "</p>";
        foreach ($conversation['messages'] as $message) {
            $senderClass = ($message['sender_user'] === wp_get_current_user()->user_login) ? "currentUser" : $message['sender_user'];
            if ($message['sender_user'] === wp_get_current_user()->user_login) {
                $message_content = "<div class='mensaje' data-sender='currentUser'><p class='nombre'>Tú:</p> <p class='texto'>" . esc_html($message['message_text']) . "</p></div>";
            } else {
                $message_content = "<div class='mensaje' data-sender='" . esc_attr($senderClass) . "'><p class='texto'>" . esc_html($message['message_text']) . "</p></div>";
            }
            $output .= $message_content;
        }

        $output .= "</div>";
        $output .= "</div>"; 

    }
    $output .= "</div>"; 
    $output .= "<script>
        document.getElementById('conversations-icon').addEventListener('click', function() {
            var conversations = document.querySelector('.user-conversations');
            if (conversations.style.display === 'none' || conversations.style.display === '') {
                conversations.style.display = 'block';
            } else {
                conversations.style.display = 'none';
            }
        });

        // Asegúrate de que la referencia al contenedor de conversaciones esté disponible
        var conversations = document.querySelector('.user-conversations');

        document.querySelectorAll('.conversation-item').forEach(function(item) {
            item.addEventListener('click', function() {
                // Oculta el contenedor de todas las conversaciones
                conversations.style.display = 'none';
            });
        });
    </script>";

    return $output;
}
add_shortcode('user_conversations', 'show_user_conversations');



/*https://2upra.com/wp-content/uploads/2024/03/gallelogo.svg */


function get_user_conversations_messages($user_id, $conversation_id, $page = 1, $per_page = 20) {
    global $wpdb;

    $offset = ($page - 1) * $per_page;

    $messages = $wpdb->get_results($wpdb->prepare("
        SELECT m.message_text, m.sender_id, u.user_login AS sender_user
        FROM wpsg_messages m
        JOIN wpsg_users u ON m.sender_id = u.ID
        WHERE m.conversation_id = %d
        ORDER BY m.created_at DESC
        LIMIT %d OFFSET %d", $conversation_id, $per_page, $offset), ARRAY_A);
    
    return $messages;
}

function handle_get_conversation_by_post_id() {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if ($post_id <= 0) {
        wp_send_json_error(['message' => 'ID de publicación inválido']);
        wp_die();
    }

    $artist_id = get_post_meta($post_id, 'artist_id', true);
    $collaborator_id = get_post_meta($post_id, 'collaborator_id', true);

    if (!$artist_id || !$collaborator_id) {
        wp_send_json_error(['message' => 'No se pueden encontrar los usuarios para esta colaboración.']);
        wp_die();
    }


    $artist_user = get_userdata($artist_id);
    $collaborator_user = get_userdata($collaborator_id);
    if (!$artist_user || !$collaborator_user) {
        wp_send_json_error(['message' => 'Uno o ambos usuarios no existen.']);
        wp_die();

}


$conversation = find_or_create_conversation($artist_user->user_login, $collaborator_user->user_login);
if (null === $conversation) {
    wp_send_json_error(['message' => 'No se pudo encontrar o crear la conversación.']);
    wp_die();
}

$messages = get_user_conversations_messages($artist_id, $conversation->id);
if ($messages === null) {
    wp_send_json_error(['message' => 'No se pudieron cargar los mensajes de la conversación.']);
    wp_die();
}

wp_send_json_success([
    'conversation' => [
        'id' => $conversation->id,
        'messages' => $messages,
    ]
]);
}

add_action('wp_ajax_get_conversation_by_post_id', 'handle_get_conversation_by_post_id');
add_action('wp_ajax_nopriv_get_conversation_by_post_id', 'handle_get_conversation_by_post_id');

add_action('wp_ajax_get_conversation_by_post_id', 'handle_get_conversation_by_post_id');
add_action('wp_ajax_nopriv_get_conversation_by_post_id', 'handle_get_conversation_by_post_id');

function handle_load_more_messages() {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;

    if (!$user_id || !$conversation_id) {
        wp_send_json_error(['message' => 'User ID y Conversation ID son requeridos']);
        return;
    }

    $messages = get_user_conversations_messages($user_id, $conversation_id, $page);
    if (empty($messages)) {
        wp_send_json_success(['messages' => []]); 
    } else {
        wp_send_json_success(['messages' => $messages]);
    }
}

add_action('wp_ajax_load_more_messages', 'handle_load_more_messages');
add_action('wp_ajax_nopriv_load_more_messages', 'handle_load_more_messages');

function find_or_create_conversation($user_one_username, $user_two_username) {
    global $wpdb;

    $user_one_id = username_exists($user_one_username);
    $user_two_id = username_exists($user_two_username);
    if (!$user_one_id || !$user_two_id) {
        return null; 
    }

    $conversation_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM wpsg_conversations WHERE (participant_1 = %d AND participant_2 = %d) OR (participant_1 = %d AND participant_2 = %d) LIMIT 1",
        $user_one_id, $user_two_id, $user_two_id, $user_one_id
    ));

    if (!$conversation_id) {
        $wpdb->insert('wpsg_conversations', [
            'participant_1' => $user_one_id,
            'participant_2' => $user_two_id
        ]);
        if ($wpdb->insert_id) {
            $conversation_id = $wpdb->insert_id;
        } else {
            return null;
        }
    }

    return (object) ['id' => $conversation_id];
}

function get_or_start_conversation() {
    if(isset($_POST['user_login'])) {
        $user_login = sanitize_text_field($_POST['user_login']);
        $current_user_login = wp_get_current_user()->user_login;
        $conversation = find_or_create_conversation($current_user_login, $user_login);
        if (null === $conversation) {
            wp_send_json_error(['message' => 'No se pudo encontrar o crear la conversación.']);
            wp_die();
        }
        $messages = get_user_conversations_messages(wp_get_current_user()->ID, $conversation->id);
        wp_send_json_success([
            'conversation' => [
                'id' => $conversation->id,
                'messages' => $messages,
            ]
        ]);
    } else {
        wp_send_json_error(['message' => 'User login is missing.']);
    }
    wp_die(); 
}


add_action('wp_ajax_get_or_start_conversation', 'get_or_start_conversation');
add_action('wp_ajax_nopriv_get_or_start_conversation', 'get_or_start_conversation');


function g_chat_conversation_sc() {
    $user = wp_get_current_user();
    $user_id = get_current_user_id(); 
    $uname_js = esc_js($user->user_login ? $user->user_login : 'Anónimo');

    $userInfoDiv = "<div id='userInfo' data-username='{$uname_js}' data-user-id='{$user_id}' style='display:none;'></div>";


    $chat_html = <<<HTML
<div class="galle-chat-text" id="gc">
    <div id="selectedUserDisplay" style="padding: 9px;background: #080808;padding-left: 15px;font-size: 11px;">Usuario seleccionado: Ninguno</div>
    <div class="galle-chat-con" id="gc-conversation-content" style="padding: 0;"> 
        <div class="message-con" id="gcm" style="padding: 10px;max-height: 340px;height: 340px;"></div>
        
        <div class="galle-chat-text-lest">
            <textarea rows="1" required="" placeholder="Tu mensaje" id="gct"></textarea>
            <button id="gs" style="background: #eee8e800;border: none;padding: 9px;padding-bottom: 6px;margin-left: 5px;border-radius: 8px;cursor: pointer;">
            <svg data-testid="geist-icon" height="16" stroke-linejoin="round" viewBox="0 0 16 16" width="16" style="color: #b0b0b0;">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M14.7477 0.293701L0.747695 5.2937L0.730713 6.70002L6.81589 9.04047C6.88192 9.06586 6.93409 9.11804 6.95948 9.18406L9.29994 15.2692L10.7063 15.2523L15.7063 1.25226L14.7477 0.293701ZM7.31426 7.62503L3.15693 6.02605L12.1112 2.8281L7.31426 7.62503ZM8.37492 8.68569L9.9739 12.843L13.1719 3.88876L8.37492 8.68569Z" fill="currentColor"></path>
            </svg>
        </button>
        </div>
        <button id="close-chat" style="position: absolute; top: 0; right: 0; padding: 5px 10px; cursor: pointer;">
            <svg data-testid="geist-icon" height="14" stroke-linejoin="round" viewBox="0 0 16 16" width="16" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4697 13.5303L13 14.0607L14.0607 13L13.5303 12.4697L9.06065 7.99999L13.5303 3.53032L14.0607 2.99999L13 1.93933L12.4697 2.46966L7.99999 6.93933L3.53032 2.46966L2.99999 1.93933L1.93933 2.99999L2.46966 3.53032L6.93933 7.99999L2.46966 12.4697L1.93933 13L2.99999 14.0607L3.53032 13.5303L7.99999 9.06065L12.4697 13.5303Z" fill="currentColor"></path></svg>
        </button>
    </div>
    $userInfoDiv
</div>
HTML;

    return $chat_html;
}
add_shortcode('g_chat', 'g_chat_conversation_sc');

function add_global_chat_to_footer() {
    echo g_chat_conversation_sc(); 
}
add_action('wp_footer', 'add_global_chat_to_footer');


function galle_chat_scripts() {

    wp_enqueue_script('galle-chat-js', get_template_directory_uri() . '/js/galle.js', array(), '2.0.8', true);

    $nonce = wp_create_nonce('wp_rest');
    wp_localize_script('galle-chat-js', 'galleChat', array(
        'nonce' => $nonce,
        'apiUrl' => esc_url_raw(rest_url('mi-chat/v1/guardar-mensaje/')),
    ));
}

add_action('wp_enqueue_scripts', 'galle_chat_scripts');

add_action('rest_api_init', function () {
    register_rest_route('mi-chat/v1', '/guardar-mensaje/', array(
        'methods' => 'POST',
        'callback' => 'guardar_mensaje_handler',
        'permission_callback' => function () {
            return is_user_logged_in();
        }

    ));
});

function combined_conversations_shortcode() {
    $current_user_id = get_current_user_id();

    $conversations = get_user_conversations($current_user_id);

    $user = wp_get_current_user();
    $user_id = $current_user_id; 
    $uname_js = esc_js($user->user_login ? $user->user_login : 'Anónimo');
    $userInfoDiv = "<div id='userInfo' data-username='{$uname_js}' data-user-id='{$user_id}' style='display:none;'></div>";

    $conversations_output = "<div class='user-conversations-block' style='display: flex;flex-direction: column;'>";
    foreach ($conversations as $conversation_id => $conversation) {
        $with_user = $conversation['with_user'];
        $imagen_url = obtener_url_imagen_perfil_o_defecto($conversation['other_user_id']);
        $conversations_output .= "<div class='conversation-item' data-conversation-id='" . esc_attr($conversation_id) . "' data-chat-user-login='" . esc_attr($conversation['with_user']) . "'>";
        $conversations_output .= "<img src='" . esc_url($imagen_url) . "' alt='Imagen de perfil' class='perfil-conversacion'/>";
        $conversations_output .= "<div class='mensajes-conversacion'>";
        $conversations_output .= "<p class='mensajeuser'>" . esc_html($with_user) . "</p>";
        foreach ($conversation['messages'] as $message) {
            $senderClass = ($message['sender_user'] === $user->user_login) ? "currentUser" : $message['sender_user'];
            $message_content = $message['sender_user'] === $user->user_login
                ? "<div class='mensaje' data-sender='currentUser'><p class='nombre'>Tú:</p> <p class='texto'>" . esc_html($message['message_text']) . "</p></div>"
                : "<div class='mensaje' data-sender='" . esc_attr($senderClass) . "'><p class='texto'>" . esc_html($message['message_text']) . "</p></div>";
            $conversations_output .= $message_content;
        }
        $conversations_output .= "</div></div>";
    }
    $conversations_output .= "</div>";

    $selected_conversation_output = <<<HTML
<div class="galle-chat-text-block" id="gc">
    <div class="header-chat" style="padding: 10px; background: #0f0f0f; padding-left: 15px; font-size: 11px;">
        <div id="selectedUserDisplay">Usuario seleccionado: Ninguno</div>
        <button id="backButton" class="back-to-conversations">Regresar</button>
    </div>
    <div class="galle-chat-con-block" id="gc-conversation-content"></div>
    <div class="message-con" id="gcm"></div>
    <div class="galle-chat-text-lest">
        <textarea rows="1" required="" placeholder="Tu mensaje" id="gct"></textarea>
        <button id="gs" style="background: #eee8e800;border: none;padding: 9px;padding-bottom: 6px;margin-left: 5px;border-radius: 8px;cursor: pointer;">
            <svg data-testid="geist-icon" height="16" stroke-linejoin="round" viewBox="0 0 16 16" width="16" style="color: #b0b0b0;">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M14.7477 0.293701L0.747695 5.2937L0.730713 6.70002L6.81589 9.04047C6.88192 9.06586 6.93409 9.11804 6.95948 9.18406L9.29994 15.2692L10.7063 15.2523L15.7063 1.25226L14.7477 0.293701ZM7.31426 7.62503L3.15693 6.02605L12.1112 2.8281L7.31426 7.62503ZM8.37492 8.68569L9.9739 12.843L13.1719 3.88876L8.37492 8.68569Z" fill="currentColor"></path>
            </svg>
        </button>
    </div>
        $userInfoDiv
</div>
HTML;

    return $conversations_output . $selected_conversation_output;
}

add_shortcode('combined_conversations', 'combined_conversations_shortcode');


/*

function g_chat_sc() {
    $user = wp_get_current_user();
    $uname = $user->user_login ? $user->user_login : 'Anónimo';
    $chat_html = <<<HTML
<div id="gc">
    <div id="gcm"></div>
    <textarea id="gct"></textarea>
    <button id="gs">Enviar</button>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var c = new WebSocket('wss://2upra.com:8080');
    var mC = document.getElementById('gcm');
    var mI = document.getElementById('gct');
    var sB = document.getElementById('gs');
    var u = '$uname';

    c.onmessage = function(e) {
        var m = document.createElement('div');
        m.textContent = e.data;
        mC.appendChild(m);
    };

    sB.addEventListener('click', function() {
        var m = mI.value;
        c.send(u + ': ' + m);
        mI.value = '';
    });
});
</script>
HTML;

    return $chat_html;
}
add_shortcode('g_chat', 'g_chat_sc');

*/
