<?php

namespace Kamples\Services\Publicacion;

/**
 * Servicio central de gestion de publicaciones (Fachada).
 * 
 * Orquesta los servicios de query, ordenamiento y filtrado
 * para obtener y procesar publicaciones.
 *
 * @since 1.0.0
 */
class PublicacionService
{
    private PublicacionQueryService $queryService;
    private PublicacionOrdenamientoService $ordenamientoService;
    private static ?PublicacionService $instancia = null;

    public function __construct()
    {
        $this->queryService = PublicacionQueryService::obtenerInstancia();
        $this->ordenamientoService = PublicacionOrdenamientoService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene publicaciones con los argumentos proporcionados.
     *
     * @param array $args Argumentos de busqueda
     * @param bool $isAjax Si es peticion AJAX
     * @param int $paged Pagina actual
     * @return string|false HTML o false si falla
     */
    public function obtener(array $args = [], bool $isAjax = false, int $paged = 1): string|false
    {
        try {
            $usuarioActual = get_current_user_id();
            $defaults = $this->queryService->obtenerDefaults();

            if (!$isAjax && isset($_GET['busqueda'])) {
                $args['identifier'] = sanitize_text_field($_GET['busqueda']);
            }

            $userId = $args['user_id'] ?? '';
            $tipoUsuario = $this->queryService->obtenerTipoUsuario($args, $usuarioActual);
            $args = array_merge($defaults, $args);

            $queryArgs = $this->queryService->construirQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario);

            if (!$queryArgs) {
                return false;
            }

            $colecciones = $this->obtenerColeccionesParaMomento($args, $usuarioActual);
            $output = $this->procesarPublicaciones($queryArgs, $args, $isAjax);

            if ($args['filtro'] === 'momento') {
                $output = $colecciones . $output;
            }

            if ($isAjax) {
                echo $output;
                wp_die();
            }

            return $output;
        } catch (\Exception $e) {
            $this->log('error', "Error en obtener publicaciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene colecciones para el momento.
     */
    public function obtenerColeccionesParaMomento(array $args, int $usuarioActual): string
    {
        if ($args['filtro'] !== 'momento' || ($args['tipoUsuario'] ?? '') === 'Fan') {
            return '';
        }

        $queryArgsForOrdering = [
            'post_type' => 'colecciones',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        $orderedArgs = $this->ordenamientoService->ordenamientoColecciones($queryArgsForOrdering, 'momento', $usuarioActual);

        if (empty($orderedArgs['post__in'])) {
            return '';
        }

        $topIds = array_slice($orderedArgs['post__in'], 0, 6);

        if (empty($topIds)) {
            return '';
        }

        $coleccionesQueryArgs = [
            'post_type' => 'colecciones',
            'post__in' => $topIds,
            'orderby' => 'post__in',
            'order' => 'ASC',
            'post_status' => 'publish',
            'posts_per_page' => 6,
        ];

        return $this->procesarPublicaciones($coleccionesQueryArgs, $args, false);
    }

    /**
     * Procesa publicaciones y genera HTML.
     */
    public function procesarPublicaciones(array $queryArgs, array $args, bool $isAjax): string
    {
        ob_start();

        if (empty($queryArgs) || !is_array($queryArgs)) {
            return '';
        }

        try {
            $query = new \WP_Query($queryArgs);
            if (!is_a($query, 'WP_Query') || !method_exists($query, 'have_posts')) {
                return '';
            }
        } catch (\Exception $e) {
            return '';
        }

        $filtro = $args['filtro'] ?? '';
        $tipoPost = $args['post_type'];

        if (!wp_doing_ajax()) {
            $claseExtra = $this->obtenerClaseExtra($filtro);
            echo '<ul class="social-post-list ' . esc_attr($claseExtra) . '" 
                  data-filtro="' . esc_attr($filtro) . '" 
                  data-posttype="' . esc_attr($tipoPost) . '" 
                  data-tab-id="' . esc_attr($args['tab_id'] ?? '') . '">';
        }

        if ($filtro === 'notas' && function_exists('formNotas')) {
            echo formNotas();
        }

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo $this->renderizarPost($tipoPost, $filtro);
            }
        } else {
            if ($filtro !== 'notas') {
                echo (new \Kamples\Views\Components\PostComponents())->nohayPost($filtro, $isAjax);
            }
        }

        if (!wp_doing_ajax()) {
            echo '</ul>';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Obtiene clase CSS extra segun el filtro.
     */
    private function obtenerClaseExtra(string $filtro): string
    {
        $claseExtra = 'clase-' . esc_attr($filtro);

        if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
            $claseExtra = 'clase-rolastatus';
        }

        if ($filtro === 'notas') {
            $claseExtra .= ' masonary';
        }

        return $claseExtra;
    }

    /**
     * Renderiza un post segun su tipo.
     */
    private function renderizarPost(string $tipoPost, string $filtro): string
    {
        switch ($tipoPost) {
            case 'social_post':
                if ($filtro === 'rola' || $filtro === 'tiendaPerfil') {
                    return \Kamples\Views\Components\ColeccionComponents::renderHtmlColec($filtro);
                }
                return (new \Kamples\Views\Components\PostComponents())->htmlPost($filtro);

            case 'colab':
                return \Kamples\Views\Components\ColabComponents::renderHtmlColab($filtro);

            case 'colecciones':
                return \Kamples\Views\Components\ColeccionComponents::renderHtmlColec($filtro);

            case 'post':
                return (new \Kamples\Views\Components\PostContentComponents())->htmlArticulo($filtro);

            default:
                return '<p>Tipo de publicacion no reconocido.</p>';
        }
    }

    /* 
     * Metodos delegados para compatibilidad con codigo existente 
     */

    public function construirQueryArgs(array $args, int $paged, mixed $userId, int $usuarioActual, string $tipoUsuario): array|false
    {
        return $this->queryService->construirQueryArgs($args, $paged, $userId, $usuarioActual, $tipoUsuario);
    }

    public function obtenerUserId(bool $isAjax): mixed
    {
        return $this->queryService->obtenerUserId($isAjax);
    }

    public function obtenerDefaults(): array
    {
        return $this->queryService->obtenerDefaults();
    }

    private function log(string $nivel, string $mensaje): void
    {
        $logger = \Logger::obtenerInstancia();
        $logger->{$nivel}('ajaxPost', $mensaje);
    }
}
