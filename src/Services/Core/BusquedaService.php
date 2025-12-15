<?php

namespace Kamples\Services\Core;

use Kamples\Services\CacheService;

/**
 * Servicio de búsqueda de contenido.
 * 
 * Proporciona funcionalidad para buscar posts, colecciones
 * y usuarios con balanceo de resultados y cache.
 *
 * @since 1.0.0
 */
class BusquedaService
{
    private CacheService $cache;
    private static ?BusquedaService $instancia = null;

    /**
     * Constructor del servicio.
     */
    public function __construct()
    {
        $this->cache = CacheService::obtenerInstancia('feed');
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

    /**
     * Realiza una búsqueda completa.
     *
     * @param string $texto Texto a buscar
     * @return array Resultados balanceados
     */
    public function realizarBusqueda(string $texto): array
    {
        $resultados = [
            'social_post' => [],
            'colecciones' => [],
            'perfiles' => [],
        ];

        $resultados['social_post'] = $this->buscarPosts('social_post', $texto);
        $resultados['colecciones'] = $this->buscarPosts('colecciones', $texto);
        $resultados['perfiles'] = $this->buscarUsuarios($texto);

        return $this->balancearResultados($resultados);
    }

    /**
     * Busca posts de un tipo específico.
     *
     * @param string $postType Tipo de post
     * @param string $texto Texto a buscar
     * @return array Resultados
     */
    public function buscarPosts(string $postType, string $texto): array
    {
        $args = [
            'post_type' => $postType,
            'post_status' => 'publish',
            's' => $texto,
            'posts_per_page' => 3,
        ];

        $query = new \WP_Query($args);
        $resultados = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $resultados[] = [
                    'titulo' => get_the_title(),
                    'url' => get_permalink(),
                    'tipo' => ucfirst(str_replace('_', ' ', $postType)),
                    'imagen' => $this->obtenerImagenPost(get_the_ID()),
                ];
            }
        }
        wp_reset_postdata();

        return $resultados;
    }

    /**
     * Busca usuarios.
     *
     * @param string $texto Texto a buscar
     * @return array Resultados
     */
    public function buscarUsuarios(string $texto): array
    {
        $userQuery = new \WP_User_Query([
            'search' => '*' . esc_attr($texto) . '*',
            'search_columns' => ['user_login', 'display_name'],
            'number' => 3,
        ]);

        $resultados = [];

        if (!empty($userQuery->get_results())) {
            foreach ($userQuery->get_results() as $user) {
                $resultados[] = [
                    'titulo' => $user->display_name,
                    'url' => get_author_posts_url($user->ID),
                    'tipo' => 'Perfil',
                    'imagen' => function_exists('imagenPerfil') ? imagenPerfil($user->ID) : '',
                ];
            }
        }

        return $resultados;
    }

    /**
     * Balancea los resultados para distribución uniforme.
     *
     * @param array $resultados Resultados sin balancear
     * @return array Resultados balanceados
     */
    public function balancearResultados(array $resultados): array
    {
        $numResultados = count($resultados['social_post'])
            + count($resultados['colecciones'])
            + count($resultados['perfiles']);

        if ($numResultados > 6) {
            $socialPostCount = count($resultados['social_post']);
            $coleccionesCount = count($resultados['colecciones']);
            $perfilesCount = count($resultados['perfiles']);

            $maxEach = 2;

            if ($socialPostCount < $maxEach) {
                $diff = $maxEach - $socialPostCount;
                if ($coleccionesCount >= $maxEach + $diff) {
                    $maxEach += $diff;
                } elseif ($perfilesCount >= $maxEach + $diff) {
                    $maxEach += $diff;
                }
            }

            if ($coleccionesCount < $maxEach) {
                $diff = $maxEach - $coleccionesCount;
                if ($socialPostCount >= $maxEach + $diff) {
                    $maxEach += $diff;
                } elseif ($perfilesCount >= $maxEach + $diff) {
                    $maxEach += $diff;
                }
            }

            if ($perfilesCount < $maxEach) {
                $diff = $maxEach - $perfilesCount;
                if ($socialPostCount >= $maxEach + $diff) {
                    $maxEach += $diff;
                } elseif ($coleccionesCount >= $maxEach + $diff) {
                    $maxEach += $diff;
                }
            }

            $resultados['social_post'] = array_slice($resultados['social_post'], 0, $maxEach);
            $resultados['colecciones'] = array_slice($resultados['colecciones'], 0, $maxEach);
            $resultados['perfiles'] = array_slice($resultados['perfiles'], 0, $maxEach);
        }

        return $resultados;
    }

    /**
     * Obtiene la imagen de un post.
     *
     * @param int $postId ID del post
     * @return string|false URL de la imagen o false
     */
    public function obtenerImagenPost(int $postId)
    {
        if (has_post_thumbnail($postId)) {
            $url = get_the_post_thumbnail_url($postId, 'thumbnail');
            return function_exists('img') ? img($url) : $url;
        }

        $imagenTemporalId = get_post_meta($postId, 'imagenTemporal', true);
        if ($imagenTemporalId) {
            $url = wp_get_attachment_image_url($imagenTemporalId, 'thumbnail');
            return function_exists('img') ? img($url) : $url;
        }

        return false;
    }

    /**
     * Busca con cache.
     *
     * @param string $texto Texto a buscar
     * @return string HTML de resultados
     */
    public function buscarConCache(string $texto): string
    {
        $cacheKey = 'resultadoBusqueda_' . md5($texto);
        $resultadosCache = $this->cache->obtener($cacheKey);

        if ($resultadosCache !== false) {
            return $resultadosCache;
        }

        $resultados = $this->realizarBusqueda($texto);
        $html = $this->generarHtmlResultados($resultados);

        $this->cache->guardar($cacheKey, $html, 7200);

        return $html;
    }

    /**
     * Genera el HTML de los resultados.
     *
     * @param array $resultados Resultados de búsqueda
     * @return string HTML
     */
    public function generarHtmlResultados(array $resultados): string
    {
        ob_start();
        $numResultados = 0;

        foreach ($resultados as $grupo) {
            $numResultados += count($grupo);
            foreach ($grupo as $resultado) {
?>
                <a href="<?php echo esc_url($resultado['url']); ?>">
                    <div class="resultado-item">
                        <?php if (!empty($resultado['imagen'])): ?>
                            <img class="resultado-imagen" src="<?php echo esc_url($resultado['imagen']); ?>" alt="<?php echo esc_attr($resultado['titulo']); ?>">
                        <?php endif; ?>
                        <div class="resultado-info">
                            <h3><?php echo esc_html($resultado['titulo']); ?></h3>
                            <p>
                                <?php
                                if ($resultado['tipo'] === 'social post') {
                                    echo 'Post';
                                } else {
                                    echo esc_html($resultado['tipo']);
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </a>
            <?php
            }
        }

        if ($numResultados === 0) {
            ?>
            <div class="resultado-item">No se encontraron resultados.</div>
<?php
        }

        return ob_get_clean();
    }
}
