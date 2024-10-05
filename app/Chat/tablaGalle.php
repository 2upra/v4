<?

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


        $sql_conversacion = "CREATE TABLE $tablaConversacion (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo TINYINT(1) NOT NULL,  -- Tipo de conversación (unouno o grupo)
            participantes LONGTEXT NOT NULL,  -- Almacena los participantes en formato JSON
            fecha DATETIME NOT NULL,  -- Fecha de creación de la conversación
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_mensajes = "CREATE TABLE $tablaMensajes (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversacion BIGINT(20) UNSIGNED NOT NULL,  -- Relación con la tabla de conversaciones
            emisor BIGINT(20) UNSIGNED NOT NULL,  -- ID del usuario que envía el mensaje
            mensaje TEXT NOT NULL,  -- Contenido del mensaje
            fecha DATETIME NOT NULL,  -- Fecha de envío del mensaje
            adjunto LONGTEXT DEFAULT NULL,  -- Almacena múltiples ID de adjuntos en formato JSON
            metadata LONGTEXT DEFAULT NULL,  -- Metadatos adicionales
            iv BINARY(16) NOT NULL,  -- IV para cifrado (no se va a usar mientras tanto)
            PRIMARY KEY (id),
            KEY conversacion (conversacion),
            KEY emisor (emisor)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_conversacion);
        dbDelta($sql_mensajes);

        if (!$existeMensajes) {
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
