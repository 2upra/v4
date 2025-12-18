<?php

namespace Kamples\Services\Publicacion;

use Kamples\Services\Contenido\IdeaService;
use Kamples\Services\Coleccion\ColeccionQueryService;

/**
 * Servicio de construccion de queries para publicaciones.
 * 
 * Maneja la construccion de argumentos de WP_Query,
 * configuracion basica y obtencion de defaults.
 *
 * @since 1.0.0
 */
class PublicacionQueryService
{
    private const FALLBACK_USER_ID = 44;

    private IdeaService $ideaService;
    private PublicacionOrdenamientoService $ordenamientoService;
    private PublicacionFiltroService $filtroService;
    private ColeccionQueryService $coleccionQueryService;
    private static ?PublicacionQueryService $instancia = null;

    public function __construct()
    {
        $this->ideaService = IdeaService::obtenerInstancia();
        $this->ordenamientoService = PublicacionOrdenamientoService::obtenerInstancia();
        $this->filtroService = PublicacionFiltroService::obtenerInstancia();
        $this->coleccionQueryService = new ColeccionQueryService();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Construye los argumentos de la query segun el contexto.
     *
     * @param array $args Argumentos
     * @param int $paged Pagina
     * @param mixed $userId ID usuario del perfil
     * @param int $usuarioActual ID usuario actual
     * @param string $tipoUsuario Tipo de usuario
     * @return array|false Query args
     */
    public function construirQueryArgs(
        array $args,
        int $paged,
        mixed $userId,
        int $usuarioActual,
        string $tipoUsuario
    ): array|false {
        /* Query para post especifico por ID */
        if (!empty($args['id'])) {
            return [
                'post_type' => $args['post_type'],
                'p' => intval($args['id']),
            ];
        }

        /* Query para ideas (posts similares a coleccion) */
        if (filter_var($args['idea'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $this->ideaService->manejar($args, $paged);
        }

        /* Query para coleccion especifica */
        if (!empty($args['colec']) && is_numeric($args['colec'])) {
            return $this->coleccionQueryService->manejarColeccionArgs(
                (int)$args['colec'],
                $paged,
                $args['post_type']
            );
        }

        /* Query estandar: aplicar configuracion y ordenamiento */
        return $this->configuracionQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario);
    }

    /**
     * Configura los argumentos de la query estandar.
     */
    public function configuracionQueryArgs(
        array $args,
        int $paged,
        mixed $userId,
        int $usuarioActual,
        string $tipoUsuario
    ): array|false {
        try {
            $isAuthenticated = $usuarioActual && $usuarioActual != 0;
            $isAdmin = current_user_can('administrator');
            $identifier = $args['identifier'] ?? '';

            if (!$isAuthenticated) {
                $usuarioActual = self::FALLBACK_USER_ID;
            }

            /* Query para perfil de usuario especifico */
            if (!empty($userId)) {
                $queryArgs = [
                    'post_type' => $args['post_type'],
                    'posts_per_page' => $args['posts'],
                    'paged' => $paged,
                    'ignore_sticky_posts' => true,
                    'suppress_filters' => false,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'author' => $userId,
                ];

                return $this->filtroService->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId);
            }

            $posts = $args['posts'];
            $similarTo = $args['similar_to'] ?? null;
            $filtroTiempo = (int)get_user_meta($usuarioActual, 'filtroTiempo', true);

            $queryArgs = $this->preOrdenamiento(
                $args,
                $paged,
                $usuarioActual,
                $identifier,
                $isAdmin,
                $posts,
                $filtroTiempo,
                $similarTo,
                $tipoUsuario
            );

            /* Aplicar filtros de usuario para social_post */
            if ($args['post_type'] === 'social_post' && in_array($args['filtro'], ['sampleList', 'sample'])) {
                if ($tipoUsuario !== 'Fan') {
                    $queryArgs = $this->filtroService->aplicarFiltrosUsuario($queryArgs, $usuarioActual);
                }
            }

            $queryArgs = $this->filtroService->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario);

            return $queryArgs;
        } catch (\Exception $e) {
            $this->log('error', "Error en configuracionQueryArgs: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aplica pre-ordenamiento segun el tipo de post.
     */
    public function preOrdenamiento(
        array $args,
        int $paged,
        int $usu,
        string $identifier,
        bool $isAdmin,
        int $posts,
        int $filtroTiempo,
        ?int $similarTo,
        ?string $tipoUsuario = null
    ): array|false {
        try {
            global $wpdb;
            if (!$wpdb) {
                return false;
            }

            $queryArgs = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $posts,
                'paged' => $paged,
                'ignore_sticky_posts' => true,
                'suppress_filters' => false,
            ];

            if (!empty($identifier)) {
                $queryArgs = $this->filtroService->prefiltrarIdentifier($identifier, $queryArgs);
            }

            /* Ordenamiento para social_post */
            if ($args['post_type'] === 'social_post') {
                $filtrosExcluidos = ['rola', 'momento', 'tiendaPerfil', 'rolaListLike'];
                if (!isset($args['filtro']) || !in_array($args['filtro'], $filtrosExcluidos)) {
                    $queryArgs = $this->ordenamientoService->ordenamiento(
                        $queryArgs,
                        $filtroTiempo,
                        $usu,
                        $identifier,
                        $similarTo,
                        $paged,
                        $isAdmin,
                        $posts,
                        $tipoUsuario
                    );
                }
            }

            /* Ordenamiento para colecciones */
            if ($args['post_type'] === 'colecciones') {
                $queryArgs = $this->ordenamientoService->ordenamientoColecciones($queryArgs, $filtroTiempo, $usu);
            }

            /* Ordenamiento para tareas */
            if ($args['post_type'] === 'tarea') {
                $prioridad = ($args['filtro'] ?? '') === 'tareaPrioridad';
                $queryArgs = $this->ordenamientoService->ordenamientoTareas($queryArgs, $usu, $args, $prioridad);
            }

            return $queryArgs;
        } catch (\Exception $e) {
            $this->log('error', "Error en preOrdenamiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el tipo de usuario.
     */
    public function obtenerTipoUsuario(array $args, int $usuarioActual): string
    {
        if (isset($args['tipoUsuario']) && !empty($args['tipoUsuario'])) {
            return $args['tipoUsuario'];
        }

        return get_user_meta($usuarioActual, 'tipoUsuario', true) ?: '';
    }

    /**
     * Obtiene el userId segun contexto.
     */
    public function obtenerUserId(bool $isAjax): mixed
    {
        if ($isAjax && isset($_POST['user_id'])) {
            return sanitize_text_field($_POST['user_id']);
        }

        $urlSegments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
        $indices = ['perfil', 'music', 'author', 'sello'];

        foreach ($indices as $index) {
            $pos = array_search($index, $urlSegments);
            if ($pos !== false) {
                if ($index === 'sello') {
                    return get_current_user_id();
                } elseif (isset($urlSegments[$pos + 1])) {
                    $usuario = get_user_by('slug', $urlSegments[$pos + 1]);
                    if ($usuario) {
                        return $usuario->ID;
                    }
                }
                break;
            }
        }

        return null;
    }

    /**
     * Obtiene valores por defecto para argumentos.
     */
    public function obtenerDefaults(): array
    {
        return [
            'filtro' => '',
            'tab_id' => '',
            'posts' => 12,
            'exclude' => [],
            'post_type' => 'social_post',
            'similar_to' => null,
            'colec' => null,
            'idea' => null,
            'user_id' => null,
            'identifier' => '',
            'tipoUsuario' => '',
            'id' => '',
        ];
    }

    private function log(string $nivel, string $mensaje): void
    {
        $logger = \Logger::obtenerInstancia();
        $logger->{$nivel}('ajaxPost', $mensaje);
    }
}
