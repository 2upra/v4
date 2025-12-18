<?php

/**
 * Migraciones de base de datos para el tema.
 * 
 * Crea las tablas personalizadas necesarias para el funcionamiento del tema.
 * Verifica la existencia real de las tablas antes de crearlas.
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
     * Instancia singleton.
     * 
     * @var DatabaseMigrations|null
     */
    private static ?DatabaseMigrations $instancia = null;

    /**
     * Referencia global a wpdb.
     * 
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Charset y collate de la base de datos.
     * 
     * @var string
     */
    private string $charsetCollate;

    /**
     * Constructor privado para singleton.
     */
    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charsetCollate = $wpdb->get_charset_collate();
    }

    /**
     * Obtener instancia singleton.
     * 
     * @return DatabaseMigrations
     */
    public static function obtenerInstancia(): DatabaseMigrations
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Verificar si una tabla existe en la base de datos.
     * 
     * @param string $nombreTabla Nombre de la tabla (sin prefijo).
     * @return bool
     */
    public function tablaExiste(string $nombreTabla): bool
    {
        $tablaCompleta = $this->wpdb->prefix . $nombreTabla;
        $resultado = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $tablaCompleta)
        );
        return $resultado === $tablaCompleta;
    }

    /**
     * Ejecutar todas las migraciones.
     * Solo crea tablas que no existan.
     */
    public function ejecutar(): void
    {
        $this->crearTablaConversacion();
        $this->crearTablaMensajes();
        $this->crearTablaInteres();
        $this->crearTablaPostLikes();
        $this->crearTablaFileHashes();
    }

    /**
     * Crear tabla de conversaciones.
     */
    private function crearTablaConversacion(): void
    {
        if ($this->tablaExiste('conversacion')) {
            return;
        }

        $tabla = $this->wpdb->prefix . 'conversacion';

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo TINYINT(1) NOT NULL,
            participantes LONGTEXT NOT NULL,
            fecha DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$this->charsetCollate};";

        $this->ejecutarSQL($sql);
    }

    /**
     * Crear tabla de mensajes.
     */
    private function crearTablaMensajes(): void
    {
        if ($this->tablaExiste('mensajes')) {
            return;
        }

        $tabla = $this->wpdb->prefix . 'mensajes';

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
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
        ) {$this->charsetCollate};";

        $this->ejecutarSQL($sql);
    }

    /**
     * Crear tabla de intereses de usuario.
     */
    private function crearTablaInteres(): void
    {
        if ($this->tablaExiste('interes')) {
            return;
        }

        $tabla = $this->wpdb->prefix . 'interes';

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            interest VARCHAR(255) NOT NULL,
            intensity INT NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY interest (interest)
        ) {$this->charsetCollate};";

        $this->ejecutarSQL($sql);
    }

    /**
     * Crear tabla de likes de posts.
     */
    private function crearTablaPostLikes(): void
    {
        if ($this->tablaExiste('post_likes')) {
            return;
        }

        $tabla = $this->wpdb->prefix . 'post_likes';

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            like_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            like_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (like_id),
            KEY post_id (post_id),
            KEY like_date (like_date)
        ) {$this->charsetCollate};";

        $this->ejecutarSQL($sql);
    }

    /**
     * Crear tabla de hashes de archivos.
     */
    private function crearTablaFileHashes(): void
    {
        if ($this->tablaExiste('file_hashes')) {
            return;
        }

        $tabla = $this->wpdb->prefix . 'file_hashes';

        $sql = "CREATE TABLE IF NOT EXISTS $tabla (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            file_hash VARCHAR(64) NOT NULL,
            file_url TEXT NOT NULL,
            upload_date DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            user_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY file_hash (file_hash)
        ) {$this->charsetCollate};";

        $this->ejecutarSQL($sql);
    }

    /**
     * Ejecutar una consulta SQL de creación de tabla.
     * 
     * @param string $sql Consulta SQL.
     */
    private function ejecutarSQL(string $sql): void
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Método estático para ejecutar migraciones.
     * Para usar con add_action.
     */
    public static function inicializar(): void
    {
        self::obtenerInstancia()->ejecutar();
    }
}

/* 
 * Ejecutar migraciones al iniciar WordPress.
 * Se ejecuta en 'init' con prioridad baja para asegurar que 
 * $wpdb esté disponible.
 */
add_action('init', [DatabaseMigrations::class, 'inicializar'], 1);
