<?php

if (!function_exists('tablasMensaje')) {
    function tablasMensaje()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablaMensajes = $wpdb->prefix . 'mensajes';
        $tablaConversacion = $wpdb->prefix . 'conversacion';

        // Verifica si las tablas ya existen correctamente
        $existeMensajes = $wpdb->get_var("SHOW TABLES LIKE '$tablaMensajes'") === $tablaMensajes;
        $existeConversacion = $wpdb->get_var("SHOW TABLES LIKE '$tablaConversacion'") === $tablaConversacion;

        if ($existeMensajes && $existeConversacion) {
            return; // Si ambas tablas existen, no hacemos nada
        }

        // SQL para crear la tabla de conversaciones
        $sql_conversacion = "CREATE TABLE $tablaConversacion (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo TINYINT(1) NOT NULL,  -- Tipo de conversación (unouno o grupo)
            participantes LONGTEXT NOT NULL,  -- Almacena los participantes en formato JSON
            fecha DATETIME NOT NULL,  -- Fecha de creación de la conversación
            PRIMARY KEY (id)
        ) $charset_collate;";

        // SQL para crear la tabla de mensajes
        $sql_mensajes = "CREATE TABLE $tablaMensajes (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversacion BIGINT(20) UNSIGNED NOT NULL,  -- Relación con la tabla de conversaciones
            emisor BIGINT(20) UNSIGNED NOT NULL,  -- ID del usuario que envía el mensaje
            mensaje TEXT NOT NULL,  -- Contenido del mensaje
            fecha DATETIME NOT NULL,  -- Fecha de envío del mensaje
            adjunto LONGTEXT DEFAULT NULL,  -- Almacena múltiples ID de adjuntos en formato JSON
            metadata LONGTEXT DEFAULT NULL,  -- Metadatos adicionales
            iv BINARY(16) NOT NULL,  -- IV para cifrado (posiblemente)
            PRIMARY KEY (id),
            KEY conversacion (conversacion),
            KEY emisor (emisor)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Ejecuta la creación de las tablas
        dbDelta($sql_conversacion);
        dbDelta($sql_mensajes);

        // Si la tabla de mensajes fue creada pero no tiene la clave foránea, la añadimos
        if (!$existeMensajes) {
            // Asegúrate de que la tabla 'conversacion' existe antes de añadir la clave foránea
            $wpdb->query("
                ALTER TABLE $tablaMensajes 
                ADD CONSTRAINT fk_mensajes_conversacion 
                FOREIGN KEY (conversacion) 
                REFERENCES $tablaConversacion(id) 
                ON DELETE CASCADE;
            ");
        }
    }
}

tablasMensaje();


add_action('rest_api_init', function () {
    register_rest_route('mi-chat/v1', '/procesarMensaje', array(
        'methods' => 'POST',
        'callback' => 'procesarMensaje',
        'permission_callback' => function () {
            return is_user_logged_in();
        }

    ));
});


define('CIPHER', 'AES-256-CBC');

function procesarMensaje($request)
{
    $emisor = get_current_user_id();
    $params = $request->get_json_params();
    chatLog($params);

    $receptor = $params['receptor'];
    $mensaje = $params['mensaje'];
    $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
    $metadata = isset($params['metadata']) ? $params['metadata'] : null;

    if (!$emisor || !$receptor || !$mensaje) {
        return;
    }
    guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata);
}

function cifrarMensaje($mensaje, $clave, $iv)
{
    $cifrado = openssl_encrypt($mensaje, CIPHER, $clave, 0, $iv);
    return base64_encode($cifrado);
}

function descifrarMensaje($mensajeCifrado, $clave, $iv)
{
    $mensajeCifrado = base64_decode($mensajeCifrado);
    return openssl_decrypt($mensajeCifrado, CIPHER, $clave, 0, $iv);
}

function guardarMensaje($emisor, $receptor, $mensaje, $adjunto = null, $metadata = null)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $clave = $_ENV['GALLEKEY'];
    $iv = openssl_random_pseudo_bytes(16);
    $mensajeCifrado = cifrarMensaje($mensaje, $clave, $iv);

    // Iniciar la transacción
    $wpdb->query('START TRANSACTION');

    try {
        // Intentar obtener la conversación
        $query = $wpdb->prepare("
            SELECT id FROM $tablaConversacion
            WHERE tipo = 1
            AND JSON_CONTAINS(participantes, %s)
            AND JSON_CONTAINS(participantes, %s)
        ", json_encode($emisor), json_encode($receptor));

        $conversacionID = $wpdb->get_var($query);

        if (!$conversacionID) {
            $participantes = json_encode([$emisor, $receptor]);
            $wpdb->insert($tablaConversacion, [
                'tipo' => 1,
                'participantes' => $participantes,
                'fecha' => current_time('mysql')
            ]);
            $conversacionID = $wpdb->insert_id;
            chatLog("Nueva conversación creada con ID: $conversacionID");
        } else {
            chatLog("Conversación existente encontrada con ID: $conversacionID");
        }

        $resultado = $wpdb->insert($tablaMensajes, [
            'conversacion' => $conversacionID,
            'emisor' => $emisor,
            'mensaje' => $mensajeCifrado,
            'fecha' => current_time('mysql'),
            'adjunto' => isset($adjunto) ? json_encode($adjunto) : null,
            'metadata' => isset($metadata) ? json_encode($metadata) : null,
            'iv' => base64_encode($iv)
        ]);

        if ($resultado === false) {
            throw new Exception("Error al insertar el mensaje: " . $wpdb->last_error);
        }

        $mensajeID = $wpdb->insert_id;

        // Confirmar la transacción
        $wpdb->query('COMMIT');
        chatLog("Mensaje cifrado guardado con ID: $mensajeID en la conversación: $conversacionID");

        return $mensajeID;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log($e->getMessage());
        chatLog("Error al guardar el mensaje: " . $e->getMessage());
        return false;
    }
}

// usa chatLog para depurar 
function conversacionesUsuario($usuarioId)
{
    global $wpdb;
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $query = $wpdb->prepare("
        SELECT id, participantes, fecha 
        FROM $tablaConversacion 
        WHERE JSON_CONTAINS(participantes, %s)
    ", json_encode($usuarioId));

    $conversaciones = $wpdb->get_results($query);
    return renderConversaciones($conversaciones, $usuarioId);
}

function renderConversaciones($conversaciones, $usuarioId)
{
    ob_start();

    if ($conversaciones) {
?>
        <div class="modal">
            <ul>
                <?php foreach ($conversaciones as $conversacion):
                    $participantes = json_decode($conversacion->participantes);
                    $otrosParticipantes = array_diff($participantes, [$usuarioId]);
                ?>
                    <li>
                        Conversación ID: <?= esc_html($conversacion->id); ?> -
                        Participantes: <?= esc_html(implode(', ', $otrosParticipantes)); ?> -
                        Fecha: <?= esc_html($conversacion->fecha); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php
    } else {
    ?>
        <p>No tienes conversaciones activas.</p>
<?php
    }
    return ob_get_clean();
}
