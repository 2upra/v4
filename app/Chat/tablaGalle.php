<?

//necesito una funcion como esta
function tablas()
{
  global $wpdb;

  if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
    update_option('tablasIniciales', '1');
    return;
  }

  if (get_option('tablasIniciales')) {
    return;
  }

  // Crear tabla mensajes
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

  // Crear tabla conversaciones
  $tabla_conversaciones = $wpdb->prefix . 'conversacion';

  $sql_conversaciones = "CREATE TABLE IF NOT EXISTS $tabla_conversaciones (
      id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      tipo TINYINT(1) NOT NULL,
      participantes LONGTEXT NOT NULL,
      fecha DATETIME NOT NULL,
      PRIMARY KEY (id)
  ) $charset_collate;";

  // Incluir funciones de WordPress para ejecutar SQL
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  // Ejecutar las consultas
  $wpdb->query($sql_mensajes);
  $wpdb->query($sql_conversaciones);

  // Guardar opción para no volver a ejecutar esta función
  update_option('tablasIniciales', true);
}

// Hook para verificar y crear las tablas al cargar WordPress
add_action('init', 'tablas');

function tablasPost()
{
  global $wpdb;

  if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === false)) {
    update_option('tablasPost', '1');
    return;
  }

  if (get_option('tablasPost')) {
    return;
  }

  // Crear tabla interes
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

  // Crear tabla post_likes
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

  // Incluir funciones de WordPress para ejecutar SQL
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  // Ejecutar las consultas
  $wpdb->query($sql_interes);
  $wpdb->query($sql_post_likes);

  // Guardar opción para no volver a ejecutar esta función
  update_option('tablasPost', true);
}

// Hook para verificar y crear las tablas al cargar WordPress
add_action('init', 'tablasPost');

function fileHashTable() {
  global $wpdb;

  // Evitar ejecutar en producción o si ya se ejecutó.
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
  dbDelta($sql_file_hashes); // Usamos dbDelta para una mejor gestión de actualizaciones de la tabla

  update_option('tablaFileHashesCreada', true);
}

add_action('init', 'fileHashTable');