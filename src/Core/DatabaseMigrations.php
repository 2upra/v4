<?php

/**
 * Migraciones de base de datos para el tema.
 * 
 * Crea las tablas necesarias para el funcionamiento del tema.
 * Solo se ejecuta en entorno LOCAL y una sola vez.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Core;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class DatabaseMigrations
{
    /**
     * Ejecutar todas las migraciones.
     */
    public static function ejecutar(): void
    {
        self::tablasMensajes();
        self::tablasPost();
        self::tablaFileHashes();
    }

    /**
     * Crear tablas de mensajes y conversaciones.
     */
    private static function tablasMensajes(): void
    {
        global $wpdb;

        if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
            update_option('tablasIniciales', '1');
            return;
        }

        if (get_option('tablasIniciales')) {
            return;
        }

        $tabla_mensajes = $wpdb->prefix . 'mensajes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql_mensajes = "CREATE TABLE IF NOT EXISTS $tabla_mensajes (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversacion BIGINT(20) UNSIGNED NOT NULL,
            emisor BIGINT(20) UNSIGNED NOT NULL,
            mensaje TEXT NOT NULL,
            fecha DATETIME NOT NULL,
            adjunto LONGTEXT,
            metadata LONGTEXT,
            iv BINARY(16) NOT NULL,
            leido TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY conversacion (conversacion),
            KEY emisor (emisor)
        ) $charset_collate;";

        $tabla_conversaciones = $wpdb->prefix . 'conversacion';

        $sql_conversaciones = "CREATE TABLE IF NOT EXISTS $tabla_conversaciones (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo TINYINT(1) NOT NULL,
            participantes LONGTEXT NOT NULL,
            fecha DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $wpdb->query($sql_mensajes);
        $wpdb->query($sql_conversaciones);

        update_option('tablasIniciales', true);
    }

    /**
     * Crear tablas de posts (intereses, likes).
     */
    private static function tablasPost(): void
    {
        global $wpdb;

        if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
            update_option('tablasPost', '1');
            return;
        }

        if (get_option('tablasPost')) {
            return;
        }

        $tabla_interes = $wpdb->prefix . 'interes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql_interes = "CREATE TABLE IF NOT EXISTS $tabla_interes (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            interest VARCHAR(255) NOT NULL,
            intensity INT NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY interest (interest)
        ) $charset_collate;";

        $tabla_post_likes = $wpdb->prefix . 'post_likes';

        $sql_post_likes = "CREATE TABLE IF NOT EXISTS $tabla_post_likes (
            like_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            like_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (like_id),
            KEY post_id (post_id),
            KEY like_date (like_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $wpdb->query($sql_interes);
        $wpdb->query($sql_post_likes);

        update_option('tablasPost', true);
    }

    /**
     * Crear tabla de hashes de archivos.
     */
    private static function tablaFileHashes(): void
    {
        global $wpdb;

        if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
            return;
        }

        if (get_option('tablaFileHashesCreada')) {
            return;
        }

        $tabla_file_hashes = $wpdb->prefix . 'file_hashes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql_file_hashes = "CREATE TABLE IF NOT EXISTS $tabla_file_hashes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            file_hash VARCHAR(64) NOT NULL,
            file_url TEXT NOT NULL,
            upload_date DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            user_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY file_hash (file_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_file_hashes);

        update_option('tablaFileHashesCreada', true);
    }
}

// Ejecutar migraciones al iniciar WordPress
add_action('init', [DatabaseMigrations::class, 'ejecutar']);
