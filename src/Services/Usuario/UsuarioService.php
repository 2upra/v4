<?php

namespace Kamples\Services\Usuario;

/**
 * Servicio de gestión de usuarios (Fachada).
 * 
 * Orquesta operaciones de usuario delegando a servicios especializados.
 *
 * @since 2.0.0
 */
class UsuarioService
{
    private static ?UsuarioService $instancia = null;
    private UsuarioBloqueoService $bloqueoService;
    private UsuarioPinkyService $pinkyService;
    private ?\Logger $logger = null;
    private \wpdb $wpdb;

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = \Logger::obtenerInstancia();
        $this->bloqueoService = UsuarioBloqueoService::obtenerInstancia();
        $this->pinkyService = UsuarioPinkyService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia singleton del servicio.
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
    *  BLOQUEOS - Delegados a UsuarioBloqueoService
    */

    public function crearTablaBloqueo(): void
    {
        $this->bloqueoService->crearTablaBloqueo();
    }

    public function toggleBloqueo(int $usuarioActual, int $postId): array
    {
        return $this->bloqueoService->toggleBloqueo($usuarioActual, $postId);
    }

    public function quitarBloqueo(int $usuarioActual, int $postId): array
    {
        return $this->bloqueoService->quitarBloqueo($usuarioActual, $postId);
    }

    public function estaBloqueado(int $userId, int $bloqueadoId): bool
    {
        return $this->bloqueoService->estaBloqueado($userId, $bloqueadoId);
    }

    public function obtenerBloqueados(int $userId): array
    {
        return $this->bloqueoService->obtenerBloqueados($userId);
    }

    /* 
    *  PINKYS - Delegados a UsuarioPinkyService
    */

    public function agregarPinkys(int $userId, int $cantidad): int
    {
        return $this->pinkyService->agregarPinkys($userId, $cantidad);
    }

    public function restarPinkys(int $userId, int $cantidad): int
    {
        return $this->pinkyService->restarPinkys($userId, $cantidad);
    }

    public function restarPinkysPorEliminacion(int $postId): bool
    {
        return $this->pinkyService->restarPinkysPorEliminacion($postId);
    }

    public function asignarPinkysRegistro(int $userId, int $cantidad = 10): void
    {
        $this->pinkyService->asignarPinkysRegistro($userId, $cantidad);
    }

    public function restablecerPinkys(int $minimo = 10): int
    {
        return $this->pinkyService->restablecerPinkys($minimo);
    }

    public function obtenerPinkys(int $userId): int
    {
        return $this->pinkyService->obtenerPinkys($userId);
    }

    /* 
     * PREFERENCIAS DE USUARIO
     */

    /**
     * Verifica si al usuario le gusta al menos una rola.
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
}
