<?php

namespace Kamples\Services;

/**
 * Servicio de procesamiento de álbumes.
 * 
 * Maneja la creación y procesamiento de álbumes musicales
 * y sus tracks individuales (rolas).
 *
 * @since 2.0.0
 */
class AlbumService
{
    private static ?AlbumService $instancia = null;
    private ?\Logger $logger = null;

    /** @var array Claves de meta a copiar del post original al álbum */
    private array $metaKeysToCopy = [
        '_post_puntuacion_final',
        'paraDescarga',
        'esExclusivo',
        'paraColab',
        'real_name',
        'artistic_name',
        'email',
        'public',
        'genre_tags',
        'instrument_tags'
    ];

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
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

    /**
     * Procesa un post para convertirlo en álbum con tracks individuales.
     * 
     * @param int $postId ID del post original
     * @return int|null ID del álbum creado o null si no aplica
     */
    public function procesarAlbumPost(int $postId): ?int
    {
        if ($this->esRola($postId)) {
            return null;
        }

        if (!$this->esAlbum($postId)) {
            return null;
        }

        $originalPost = get_post($postId);
        if (!$originalPost) {
            $this->logger->error('album', "Post original no encontrado: $postId");
            return null;
        }

        $albumPostId = $this->crearPostAlbum($originalPost);
        if (!$albumPostId) {
            return null;
        }

        $this->copiarMetaDatos($postId, $albumPostId);
        $this->copiarThumbnail($postId, $albumPostId);

        $rolaPosts = $this->crearRolas($postId, $albumPostId, $originalPost->post_author);

        if (!empty($rolaPosts)) {
            update_post_meta($albumPostId, 'album_rolas', $rolaPosts);
            $this->logger->info('album', "Album creado: ID = $albumPostId con " . count($rolaPosts) . " rolas");
        }

        return $albumPostId;
    }

    /**
     * Verifica si el post es una rola individual.
     */
    private function esRola(int $postId): bool
    {
        return get_post_meta($postId, 'rola', true) === '1';
    }

    /**
     * Verifica si el post es un álbum.
     */
    private function esAlbum(int $postId): bool
    {
        return get_post_meta($postId, 'albumRolas', true) === '1';
    }

    /**
     * Crea el post de tipo álbum.
     * 
     * @param \WP_Post $originalPost Post original
     * @return int|null ID del álbum o null si falla
     */
    private function crearPostAlbum(\WP_Post $originalPost): ?int
    {
        $albumPostId = wp_insert_post([
            'post_type'    => 'albums',
            'post_title'   => $originalPost->post_title,
            'post_content' => $originalPost->post_content,
            'post_author'  => $originalPost->post_author,
            'post_status'  => 'publish',
        ]);

        if (is_wp_error($albumPostId)) {
            $this->logger->error('album', "Error creando album: " . $albumPostId->get_error_message());
            return null;
        }

        return $albumPostId;
    }

    /**
     * Copia los metadatos del post original al álbum.
     */
    private function copiarMetaDatos(int $postIdOrigen, int $albumPostId): void
    {
        foreach ($this->metaKeysToCopy as $key) {
            $value = get_post_meta($postIdOrigen, $key, true);
            if ($value) {
                update_post_meta($albumPostId, $key, $value);
            }
        }
    }

    /**
     * Copia la imagen destacada al álbum.
     */
    private function copiarThumbnail(int $postIdOrigen, int $albumPostId): void
    {
        $thumbnailId = get_post_thumbnail_id($postIdOrigen);
        if ($thumbnailId) {
            set_post_thumbnail($albumPostId, $thumbnailId);
        }
    }

    /**
     * Crea los posts individuales de cada rola del álbum.
     * 
     * @param int $postIdOrigen ID del post original
     * @param int $albumPostId ID del álbum
     * @param int $authorId ID del autor
     * @return array IDs de las rolas creadas
     */
    private function crearRolas(int $postIdOrigen, int $albumPostId, int $authorId): array
    {
        $rolasMeta = get_post_meta($postIdOrigen, 'rolas_meta_key', true);
        $rolaNames = maybe_unserialize($rolasMeta);

        if (empty($rolaNames) || !is_array($rolaNames)) {
            return [];
        }

        $artisticName = get_post_meta($postIdOrigen, 'artistic_name', true);
        $realName = get_post_meta($postIdOrigen, 'real_name', true);
        $thumbnailId = get_post_thumbnail_id($postIdOrigen);

        $rolaPosts = [];

        foreach ($rolaNames as $index => $rolaTitle) {
            $rolaPostId = $this->crearRolaIndividual(
                $postIdOrigen,
                $albumPostId,
                $authorId,
                $rolaTitle,
                $index,
                $artisticName,
                $realName,
                $thumbnailId
            );

            if ($rolaPostId) {
                $rolaPosts[] = $rolaPostId;
            }
        }

        return $rolaPosts;
    }

    /**
     * Crea un post individual para una rola.
     * 
     * @return int|null ID de la rola creada o null si falla
     */
    private function crearRolaIndividual(
        int $postIdOrigen,
        int $albumPostId,
        int $authorId,
        string $rolaTitle,
        int $index,
        string $artisticName,
        string $realName,
        int $thumbnailId
    ): ?int {
        $indexOffset = $index + 1;

        $this->logger->debug('album', "Procesando rola $indexOffset: $rolaTitle");

        $audioId = get_post_meta($postIdOrigen, "post_audio$indexOffset", true);
        $audioLiteId = get_post_meta($postIdOrigen, "post_audio_lite_$indexOffset", true);
        $audioHdId = get_post_meta($postIdOrigen, "post_audio_hd_$indexOffset", true);
        $waveformImage = get_post_meta($postIdOrigen, "audio_waveform_image_$indexOffset", true);
        $duration = get_post_meta($postIdOrigen, "audio_duration_$indexOffset", true);

        if (!$audioId || !$rolaTitle) {
            $this->logger->warning('album', "Faltan datos criticos para la rola $indexOffset: $rolaTitle");
            return null;
        }

        $rolaPostId = wp_insert_post([
            'post_type'    => 'social_post',
            'post_title'   => $rolaTitle,
            'post_content' => $rolaTitle,
            'post_author'  => $authorId,
            'post_status'  => 'publish',
        ]);

        if (is_wp_error($rolaPostId)) {
            $this->logger->error('album', "Error creando rola: " . $rolaPostId->get_error_message());
            return null;
        }

        $this->asignarMetaRola($rolaPostId, [
            'post_audio'           => $audioId,
            'album_id'             => $albumPostId,
            'real_name'            => $realName,
            'post_audio_lite'      => $audioLiteId,
            'post_audio_hd'        => $audioHdId,
            'audio_waveform_image' => $waveformImage,
            'audio_duration'       => $duration,
            'rola'                 => true,
            'artistic_name'        => $artisticName,
            '_post_puntuacion_final' => 100,
        ]);

        $additionalSearchData = [
            'rola'          => true,
            'artistic_name' => $artisticName,
            'titulo'        => $rolaTitle
        ];
        update_post_meta($rolaPostId, 'additional_search_data', wp_json_encode($additionalSearchData));

        if ($thumbnailId) {
            set_post_thumbnail($rolaPostId, $thumbnailId);
        }

        $this->logger->info('album', "Rola creada: ID = $rolaPostId, Titulo = $rolaTitle");

        return $rolaPostId;
    }

    /**
     * Asigna múltiples metadatos a una rola.
     */
    private function asignarMetaRola(int $rolaPostId, array $metas): void
    {
        foreach ($metas as $key => $value) {
            if ($value !== '' && $value !== null) {
                update_post_meta($rolaPostId, $key, $value);
            }
        }
    }

    /**
     * Obtiene las rolas de un álbum.
     * 
     * @param int $albumId ID del álbum
     * @return array IDs de las rolas
     */
    public function obtenerRolasDelAlbum(int $albumId): array
    {
        $rolas = get_post_meta($albumId, 'album_rolas', true);
        return is_array($rolas) ? $rolas : [];
    }

    /**
     * Obtiene el álbum al que pertenece una rola.
     * 
     * @param int $rolaId ID de la rola
     * @return int|null ID del álbum o null
     */
    public function obtenerAlbumDeRola(int $rolaId): ?int
    {
        $albumId = get_post_meta($rolaId, 'album_id', true);
        return $albumId ? intval($albumId) : null;
    }
}
