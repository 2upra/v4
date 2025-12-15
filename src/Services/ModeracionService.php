<?php

/**
 * Servicio de moderación y gestión de usuarios bloqueados.
 * 
 * Proporciona funcionalidades para bloquear, restringir y banear usuarios,
 * así como para gestionar IPs bloqueadas y registro de intentos fallidos.
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class ModeracionService
{
    private static ?ModeracionService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio (Singleton).
     */
    public static function obtenerInstancia(): ModeracionService
    {
        if (self::$instancia === null) {
            self::$instancia = new ModeracionService();
        }
        return self::$instancia;
    }

    /**
     * Bloquea y elimina usuarios completamente.
     * 
     * Elimina todos sus comentarios, posts y cambia su rol a 'blocked'.
     *
     * @param array $usuarios Lista de identificadores (ID, email, login o IP).
     * @return array Resultado de la operación por cada usuario.
     */
    public function bloquearYEliminarUsuarios(array $usuarios): array
    {
        $resultados = [];

        foreach ($usuarios as $usuario) {
            $user = $this->buscarUsuario($usuario);

            if ($user) {
                /* Eliminar comentarios */
                $comentarios = get_comments(['user_id' => $user->ID]);
                foreach ($comentarios as $comentario) {
                    wp_delete_comment($comentario->comment_ID, true);
                }

                /* Eliminar posts */
                $posts = get_posts([
                    'author' => $user->ID,
                    'post_type' => 'any',
                    'numberposts' => -1
                ]);
                foreach ($posts as $post) {
                    if ($post instanceof \WP_Post) {
                        wp_delete_post($post->ID, true);
                    }
                }

                /* Bloquear usuario */
                wp_update_user(['ID' => $user->ID, 'role' => 'blocked']);
                wp_update_user(['ID' => $user->ID, 'user_status' => 1]);

                /* Bloquear IP si es una IP válida */
                if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                    $this->bloquearIp($usuario);
                }

                $this->logger->info('moderacion', 'Usuario bloqueado y eliminado', [
                    'userId' => $user->ID,
                    'login' => $user->user_login
                ]);

                $resultados[$usuario] = ['success' => true, 'mensaje' => 'Usuario bloqueado'];
            } else {
                /* Si no se encontró usuario pero es IP, bloquearla */
                if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                    $this->bloquearIp($usuario);
                    $resultados[$usuario] = ['success' => true, 'mensaje' => 'IP bloqueada'];
                } else {
                    $this->logger->warning('moderacion', 'Usuario no encontrado', ['identificador' => $usuario]);
                    $resultados[$usuario] = ['success' => false, 'mensaje' => 'Usuario no encontrado'];
                }
            }
        }

        return $resultados;
    }

    /**
     * Restringe usuarios (rol limitado, no puede publicar).
     *
     * @param array $usuarios Lista de identificadores.
     * @return array Resultado de la operación.
     */
    public function restringirUsuarios(array $usuarios): array
    {
        $resultados = [];

        foreach ($usuarios as $usuario) {
            $user = $this->buscarUsuario($usuario);

            if ($user) {
                /* No restringir administradores ni al usuario ID 1 */
                if (in_array('administrator', $user->roles) || $user->ID === 1) {
                    $this->logger->warning('moderacion', 'Intento de restringir administrador', [
                        'userId' => $user->ID,
                        'login' => $user->user_login
                    ]);
                    $resultados[$usuario] = ['success' => false, 'mensaje' => 'No se puede restringir administrador'];
                    continue;
                }

                wp_update_user(['ID' => $user->ID, 'role' => 'restringido']);
                wp_update_user(['ID' => $user->ID, 'user_status' => 1]);

                if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                    $this->bloquearIp($usuario);
                }

                $this->logger->info('moderacion', 'Usuario restringido', [
                    'userId' => $user->ID,
                    'login' => $user->user_login
                ]);

                $resultados[$usuario] = ['success' => true, 'mensaje' => 'Usuario restringido'];
            } else {
                if (filter_var($usuario, FILTER_VALIDATE_IP)) {
                    $this->bloquearIp($usuario);
                    $resultados[$usuario] = ['success' => true, 'mensaje' => 'IP restringida'];
                } else {
                    $this->logger->warning('moderacion', 'Usuario no encontrado para restringir', ['identificador' => $usuario]);
                    $resultados[$usuario] = ['success' => false, 'mensaje' => 'Usuario no encontrado'];
                }
            }
        }

        return $resultados;
    }

    /**
     * Banea al autor de un post específico.
     *
     * @param int $postId ID del post.
     * @return array Resultado de la operación.
     */
    public function banearAutorDePost(int $postId): array
    {
        $post = get_post($postId);

        if (!$post) {
            return ['success' => false, 'mensaje' => 'El post no existe'];
        }

        $autorId = (int) $post->post_author;

        return $this->restringirUsuarios([$autorId]);
    }

    /**
     * Busca un usuario por diferentes tipos de identificador.
     *
     * @param mixed $identificador ID, email, login o IP.
     * @return \WP_User|null Usuario encontrado o null.
     */
    private function buscarUsuario($identificador): ?\WP_User
    {
        $user = null;

        if (is_numeric($identificador)) {
            $user = get_user_by('id', $identificador);
        } elseif (is_email($identificador)) {
            $user = get_user_by('email', $identificador);
        } elseif (!filter_var($identificador, FILTER_VALIDATE_IP)) {
            /* Es un login (no es IP) */
            $user = get_user_by('login', $identificador);
        }

        return $user ?: null;
    }

    /**
     * Bloquea una IP añadiéndola al .htaccess.
     *
     * @param string $ip Dirección IP a bloquear.
     * @return bool True si se bloqueó correctamente.
     */
    public function bloquearIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->logger->warning('moderacion', 'IP inválida para bloquear', ['ip' => $ip]);
            return false;
        }

        $htaccess = ABSPATH . '/.htaccess';
        $deny = "\n# Bloqueo de IP - " . date('Y-m-d H:i:s') . "\nDeny from $ip\n";

        if (file_exists($htaccess) && is_writable($htaccess)) {
            file_put_contents($htaccess, $deny, FILE_APPEND);
            $this->logger->info('moderacion', 'IP bloqueada en htaccess', ['ip' => $ip]);
            return true;
        }

        $this->logger->error('moderacion', 'No se pudo escribir en htaccess', ['ip' => $ip]);
        return false;
    }

    /**
     * Registra un intento de acceso fallido.
     *
     * @param string $username Nombre de usuario intentado.
     */
    public function registrarIntentoFallido(string $username): void
    {
        $logFile = ABSPATH . '/wp-content/uploads/access_logs.txt';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $time = date('Y-m-d H:i:s');
        $logEntry = "Intento fallido de acceso por usuario: $username, IP: $ip, Fecha: $time\n";

        file_put_contents($logFile, $logEntry, FILE_APPEND);

        $this->logger->warning('auth', 'Intento de acceso fallido', [
            'username' => $username,
            'ip' => $ip
        ]);
    }

    /**
     * Crea el rol 'restringido' si no existe.
     */
    public function crearRolRestringido(): void
    {
        if (!get_role('restringido')) {
            add_role('restringido', 'Usuario Restringido', [
                'read' => true,
                'edit_posts' => false,
                'upload_files' => false,
                'delete_posts' => false,
            ]);
            $this->logger->info('moderacion', 'Rol restringido creado');
        }
    }
}
