<?php

namespace Kamples\Services\Usuario;

/**
 * Servicio de gestión de bloqueos de usuarios.
 * 
 * Maneja bloqueos entre usuarios.
 *
 * @since 2.0.0
 */
class UsuarioBloqueoService
{
    private static ?UsuarioBloqueoService $instancia = null;
    private \wpdb $wpdb;
    private string $tablaBloqueo;

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tablaBloqueo = $wpdb->prefix . 'bloqueo';
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Crea la tabla de bloqueos si no existe.
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
     */
    public function obtenerBloqueados(int $userId): array
    {
        $resultados = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT idBloqueado FROM {$this->tablaBloqueo} WHERE idUser = %d",
            $userId
        ));

        return $resultados ? array_map('intval', $resultados) : [];
    }
}
