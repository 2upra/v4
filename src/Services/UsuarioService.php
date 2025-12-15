<?php

namespace Kamples\Services;

/**
 * Servicio de gestión de usuarios.
 * 
 * Maneja tipos de usuario, bloqueos, pinkys y otras operaciones
 * relacionadas con la gestión de usuarios.
 *
 * @since 1.0.0
 */
class UsuarioService
{
    private static ?UsuarioService $instancia = null;
    private \wpdb $wpdb;
    private ?\Logger $logger = null;
    private string $tablaBloqueo;

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tablaBloqueo = $wpdb->prefix . 'bloqueo';
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia singleton del servicio.
     *
     * @return self
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /* 
    *  TIPO DE USUARIO
    */

    /**
     * Cambia el tipo de usuario (Fan/Artista).
     *
     * @param int $userId ID del usuario
     * @param string $tipo Tipo a cambiar ('fan')
     * @return bool Nuevo estado
     */
    public function cambiarTipoUsuario(int $userId, string $tipo): bool
    {
        if ($tipo === 'fan') {
            $estadoActual = (bool)get_user_meta($userId, 'fan', true);
            $nuevoEstado = !$estadoActual;
            update_user_meta($userId, 'fan', $nuevoEstado);
            return $nuevoEstado;
        }
        return false;
    }

    /* 
    *  BLOQUEOS
    */

    /**
     * Crea la tabla de bloqueos si no existe.
     *
     * @return void
     */
    public function crearTablaBloqueo(): void
    {
        $charsetCollate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->tablaBloqueo} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            idUser bigint(20) unsigned NOT NULL,
            idBloqueado bigint(20) unsigned NOT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY idUser (idUser),
            KEY idBloqueado (idBloqueado)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Bloquea o desbloquea al autor de un post.
     *
     * @param int $usuarioActual ID del usuario que bloquea
     * @param int $postId ID del post cuyo autor se bloqueará
     * @return array ['success' => bool, 'message' => string, 'action' => 'blocked'|'unblocked']
     */
    public function toggleBloqueo(int $usuarioActual, int $postId): array
    {
        $post = get_post($postId);

        if (!$post) {
            return ['success' => false, 'message' => 'Post no encontrado.'];
        }

        $autorId = (int)$post->post_author;

        /* No permitir bloquear administradores */
        if (user_can($autorId, 'administrator')) {
            return ['success' => false, 'message' => 'No se puede bloquear a este usuario.'];
        }

        $bloqueoExistente = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->tablaBloqueo} WHERE idUser = %d AND idBloqueado = %d",
            $usuarioActual,
            $autorId
        ));

        if ($bloqueoExistente) {
            $this->wpdb->delete($this->tablaBloqueo, [
                'idUser' => $usuarioActual,
                'idBloqueado' => $autorId
            ]);
            return ['success' => true, 'message' => 'Usuario desbloqueado.', 'action' => 'unblocked'];
        } else {
            $this->wpdb->insert($this->tablaBloqueo, [
                'idUser' => $usuarioActual,
                'idBloqueado' => $autorId
            ]);
            return ['success' => true, 'message' => 'Usuario bloqueado.', 'action' => 'blocked'];
        }
    }

    /**
     * Quita el bloqueo de un usuario.
     *
     * @param int $usuarioActual ID del usuario que desbloquea
     * @param int $postId ID del post cuyo autor se desbloqueará
     * @return array ['success' => bool, 'message' => string]
     */
    public function quitarBloqueo(int $usuarioActual, int $postId): array
    {
        $post = get_post($postId);

        if (!$post) {
            return ['success' => false, 'message' => 'Post no encontrado.'];
        }

        $autorId = (int)$post->post_author;
        $resultado = $this->wpdb->delete($this->tablaBloqueo, [
            'idUser' => $usuarioActual,
            'idBloqueado' => $autorId
        ]);

        if ($resultado !== false) {
            return ['success' => true, 'message' => 'Bloqueo eliminado.'];
        } else {
            return ['success' => false, 'message' => 'No se pudo eliminar el bloqueo.'];
        }
    }

    /**
     * Verifica si un usuario está bloqueado por otro.
     *
     * @param int $userId ID del usuario que bloquea
     * @param int $bloqueadoId ID del usuario potencialmente bloqueado
     * @return bool
     */
    public function estaBloqueado(int $userId, int $bloqueadoId): bool
    {
        $resultado = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->tablaBloqueo} WHERE idUser = %d AND idBloqueado = %d",
            $userId,
            $bloqueadoId
        ));

        return $resultado !== null;
    }

    /**
     * Obtiene la lista de usuarios bloqueados.
     *
     * @param int $userId ID del usuario
     * @return array IDs de usuarios bloqueados
     */
    public function obtenerBloqueados(int $userId): array
    {
        $resultados = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT idBloqueado FROM {$this->tablaBloqueo} WHERE idUser = %d",
            $userId
        ));

        return $resultados ? array_map('intval', $resultados) : [];
    }

    /* 
    *  PINKYS (MONEDA VIRTUAL)
    */

    /**
     * Agrega pinkys a un usuario.
     *
     * @param int $userId ID del usuario
     * @param int $cantidad Cantidad a agregar
     * @return int Nuevo balance
     */
    public function agregarPinkys(int $userId, int $cantidad): int
    {
        $monedasActuales = (int)get_user_meta($userId, 'pinky', true);
        $nuevasMonedas = $monedasActuales + $cantidad;
        update_user_meta($userId, 'pinky', $nuevasMonedas);
        return $nuevasMonedas;
    }

    /**
     * Resta pinkys a un usuario.
     *
     * @param int $userId ID del usuario
     * @param int $cantidad Cantidad a restar
     * @return int Nuevo balance
     */
    public function restarPinkys(int $userId, int $cantidad): int
    {
        $monedasActuales = (int)get_user_meta($userId, 'pinky', true);
        $nuevasMonedas = max(0, $monedasActuales - $cantidad);
        update_user_meta($userId, 'pinky', $nuevasMonedas);
        return $nuevasMonedas;
    }

    /**
     * Resta pinkys al autor de un post (usado en eliminación).
     *
     * @param int $postId ID del post
     * @return bool True si se restaron
     */
    public function restarPinkysPorEliminacion(int $postId): bool
    {
        $post = get_post($postId);
        if (!$post) {
            return false;
        }

        $userId = (int)$post->post_author;
        if ($userId) {
            $this->restarPinkys($userId, 1);
            return true;
        }
        return false;
    }

    /**
     * Asigna pinkys iniciales a un nuevo usuario.
     *
     * @param int $userId ID del usuario
     * @param int $cantidad Cantidad inicial (default: 10)
     * @return void
     */
    public function asignarPinkysRegistro(int $userId, int $cantidad = 10): void
    {
        update_user_meta($userId, 'pinky', $cantidad);
    }

    /**
     * Restablece los pinkys de usuarios que tienen menos del mínimo.
     *
     * @param int $minimo Cantidad mínima (default: 10)
     * @return int Cantidad de usuarios actualizados
     */
    public function restablecerPinkys(int $minimo = 10): int
    {
        $usuariosQuery = new \WP_User_Query([
            'fields' => 'ID',
        ]);

        $actualizados = 0;
        if (!empty($usuariosQuery->results)) {
            foreach ($usuariosQuery->results as $userId) {
                $monedasActuales = (int)get_user_meta($userId, 'pinky', true);
                if ($monedasActuales < $minimo) {
                    update_user_meta($userId, 'pinky', $minimo);
                    $actualizados++;
                }
            }
        }

        return $actualizados;
    }

    /**
     * Obtiene el balance de pinkys de un usuario.
     *
     * @param int $userId ID del usuario
     * @return int Balance actual
     */
    public function obtenerPinkys(int $userId): int
    {
        return (int)get_user_meta($userId, 'pinky', true);
    }

    /* 
     * PREFERENCIAS DE USUARIO
     */

    /**
     * Verifica si al usuario le gusta al menos una rola.
     * Actualiza el meta 'leGustaAlMenosUnaRola' con el resultado.
     *
     * @param int $userId ID del usuario
     * @return bool True si le gusta al menos una rola
     */
    public function saberSi(int $userId): bool
    {
        $lastRun = get_user_meta($userId, 'ultima_ejecucion_saber', true);
        $currentTime = current_time('timestamp');

        if ($lastRun && ($currentTime - $lastRun < 1)) {
            return (bool) get_user_meta($userId, 'leGustaAlMenosUnaRola', true);
        }

        update_user_meta($userId, 'ultima_ejecucion_saber', $currentTime);

        $tableName = $this->wpdb->prefix . 'post_likes';
        $likedPosts = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT post_id FROM $tableName WHERE user_id = %d",
            $userId
        ));

        if (empty($likedPosts)) {
            update_user_meta($userId, 'leGustaAlMenosUnaRola', false);
            return false;
        }

        $rolaPosts = get_posts([
            'post__in' => $likedPosts,
            'meta_query' => [
                [
                    'key' => 'rola',
                    'value' => 'true',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);

        $leGustaRola = !empty($rolaPosts);
        update_user_meta($userId, 'leGustaAlMenosUnaRola', $leGustaRola);

        return $leGustaRola;
    }

    /**
     *
     * @param string $nivel Nivel del log
     * @param string $mensaje Mensaje
     * @return void
     */
    private function log(string $nivel, string $mensaje): void
    {
        if ($this->logger) {
            $this->logger->$nivel('auth', $mensaje);
        }
    }
}
