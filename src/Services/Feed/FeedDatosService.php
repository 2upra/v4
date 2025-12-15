<?php

namespace Kamples\Services\Feed;

use Kamples\Services\CacheService;
use Kamples\Services\AlgoritmoService;

/**
 * Servicio de consulta de datos para el feed.
 * 
 * Responsabilidad unica: Consultas a base de datos para obtener datos del feed.
 *
 * @since 3.0.0
 */
class FeedDatosService
{
    private static ?FeedDatosService $instancia = null;
    private CacheService $cache;
    private \wpdb $wpdb;

    private function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->cache = CacheService::obtenerInstancia('feed');
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene los usuarios seguidos por un usuario.
     *
     * @param int $userId ID del usuario
     * @return array IDs de usuarios seguidos
     */
    public function obtenerUsuariosSeguidos(int $userId): array
    {
        $siguiendo = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT meta_value 
                 FROM {$this->wpdb->usermeta} 
                 WHERE user_id = %d AND meta_key = 'siguiendo'",
                $userId
            )
        );

        if (empty($siguiendo)) {
            return [];
        }

        $resultado = maybe_unserialize($siguiendo[0]);
        return is_array($resultado) ? $resultado : [];
    }

    /**
     * Obtiene los intereses del usuario.
     *
     * @param int $userId ID del usuario
     * @return array|object Intereses del usuario
     */
    public function obtenerInteresesUsuario(int $userId)
    {
        if (!defined('INTERES_TABLE')) {
            return [];
        }

        $tablaIntereses = INTERES_TABLE;
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT interest, intensity FROM {$tablaIntereses} WHERE user_id = %d",
                $userId
            ),
            OBJECT_K
        );
    }

    /**
     * Obtiene las vistas del usuario.
     *
     * @param int $userId ID del usuario
     * @return mixed Datos de vistas
     */
    public function vistasDatos(int $userId): mixed
    {
        return get_user_meta($userId, 'vistas_posts', true);
    }

    /**
     * Obtiene IDs de posts recientes.
     *
     * @return array IDs de posts
     */
    public function obtenerIdsPostsRecientes(): array
    {
        $args = [
            'post_type' => 'social_post',
            'posts_per_page' => 50000,
            'date_query' => [
                'after' => date('Y-m-d', strtotime('-365 days'))
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        return get_posts($args);
    }

    /**
     * Obtiene metadatos de los posts.
     *
     * @param array $postsIds IDs de posts
     * @return array Metadatos indexados por post_id
     */
    public function obtenerMetadatosPosts(array $postsIds): array
    {
        if (empty($postsIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));
        $metaKeys = ['datosAlgoritmo', 'Verificado', 'postAut', 'artista', 'fan', 'nombreOriginal'];
        $metaKeysPlaceholders = implode(',', array_fill(0, count($metaKeys), '%s'));

        $sql = "
            SELECT post_id, meta_key, meta_value
            FROM {$this->wpdb->postmeta}
            WHERE meta_key IN ({$metaKeysPlaceholders}) AND post_id IN ({$placeholders})
        ";

        $preparedSql = $this->wpdb->prepare($sql, array_merge($metaKeys, $postsIds));
        $resultados = $this->wpdb->get_results($preparedSql);

        $metaData = [];
        foreach ($resultados as $meta) {
            $metaData[$meta->post_id][$meta->meta_key] = $meta->meta_value;
        }

        return $metaData;
    }

    /**
     * Procesa los metadatos de roles.
     *
     * @param array $metaData Metadatos de posts
     * @return array Roles procesados
     */
    public function procesarMetadatosRoles(array $metaData): array
    {
        $metaRoles = [];
        foreach ($metaData as $postId => $meta) {
            $metaRoles[$postId] = [
                'artista' => isset($meta['artista']) ? filter_var($meta['artista'], FILTER_VALIDATE_BOOLEAN) : false,
                'fan' => isset($meta['fan']) ? filter_var($meta['fan'], FILTER_VALIDATE_BOOLEAN) : false,
            ];
        }
        return $metaRoles;
    }

    /**
     * Obtiene likes por post.
     *
     * @param array $postsIds IDs de posts
     * @return array Likes indexados por post_id
     */
    public function obtenerLikesPorPost(array $postsIds): array
    {
        if (empty($postsIds)) {
            return [];
        }

        $tablaLikes = "{$this->wpdb->prefix}post_likes";
        $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

        $sql = "
            SELECT post_id, like_type, COUNT(*) as cantidad
            FROM {$tablaLikes}
            WHERE post_id IN ({$placeholders})
            GROUP BY post_id, like_type
        ";

        $preparedSql = $this->wpdb->prepare($sql, $postsIds);
        $resultados = $this->wpdb->get_results($preparedSql);

        $likesPorPost = [];
        foreach ($resultados as $like) {
            if (!isset($likesPorPost[$like->post_id])) {
                $likesPorPost[$like->post_id] = [
                    'like' => 0,
                    'favorito' => 0,
                    'no_me_gusta' => 0
                ];
            }
            $likesPorPost[$like->post_id][$like->like_type] = (int)$like->cantidad;
        }

        return $likesPorPost;
    }

    /**
     * Obtiene datos basicos de posts.
     *
     * @param array $postsIds IDs de posts
     * @return array|object Datos de posts
     */
    public function obtenerDatosBasicosPosts(array $postsIds)
    {
        if (empty($postsIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($postsIds), '%d'));

        $sql = "
            SELECT ID, post_author, post_date, post_content
            FROM {$this->wpdb->posts}
            WHERE ID IN ({$placeholders})
        ";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $postsIds),
            OBJECT_K
        );
    }

    /**
     * Procesa el contenido de los posts.
     *
     * @param array|object $postsResultados Resultados de posts
     * @return array Contenido indexado por ID
     */
    public function procesarContenidoPosts($postsResultados): array
    {
        $postContenido = [];
        foreach ($postsResultados as $post) {
            $postContenido[$post->ID] = $post->post_content;
        }
        return $postContenido;
    }

    /**
     * Comprueba la conexion a la base de datos.
     *
     * @return bool
     */
    public function comprobarConexionBD(): bool
    {
        return $this->wpdb !== null;
    }
}
