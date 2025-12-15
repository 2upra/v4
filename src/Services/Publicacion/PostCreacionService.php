<?php

/**
 * Servicio de creación de posts (Fachada).
 * 
 * Orquesta la creación de posts delegando a servicios especializados.
 *
 * @package Kamples\Services\Publicacion
 * @since 1.0.0
 */

namespace Kamples\Services\Publicacion;

class PostCreacionService
{
    private static ?PostCreacionService $instancia = null;
    private PostArchivosService $archivosService;
    private PostAlgoritmoDataService $algoritmoDataService;
    private \Logger $logger;

    private function __construct()
    {
        $this->archivosService = PostArchivosService::obtenerInstancia();
        $this->algoritmoDataService = PostAlgoritmoDataService::obtenerInstancia();
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia única del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Crea un nuevo post
     *
     * @param string $tipoPost Tipo de post
     * @param string $estadoPost Estado del post
     * @return int|\WP_Error ID del post o error
     */
    public function crearPost(string $tipoPost = 'social_post', string $estadoPost = 'publish')
    {
        $contenido = sanitize_textarea_field($_POST['textoNormal'] ?? '');
        $tags = sanitize_text_field($_POST['tags'] ?? '');

        if (empty($contenido)) {
            $this->logger->error('post', 'El contenido no puede estar vacío');
            return new \WP_Error('empty_content', 'El contenido no puede estar vacío.');
        }

        $titulo = wp_trim_words($contenido, 15, '...');
        $autor = get_current_user_id();

        $postId = wp_insert_post([
            'post_title' => $titulo,
            'post_content' => $contenido,
            'post_status' => $estadoPost,
            'post_author' => $autor,
            'post_type' => $tipoPost,
        ]);

        if (is_wp_error($postId)) {
            $this->logger->error('post', 'Error al insertar post', ['error' => $postId->get_error_message()]);
            return $postId;
        }

        if (!empty($tags)) {
            update_post_meta($postId, 'tagsUsuario', $tags);
        }

        return $postId;
    }

    /**
     * Actualiza los metadatos de un post
     *
     * @param int $postId ID del post
     */
    public function actualizarMetaDatos(int $postId): void
    {
        $metaFields = [
            'paraColab' => 'colab',
            'esExclusivo' => 'exclusivo',
            'paraDescarga' => 'descarga',
            'rola' => 'music',
            'fan' => 'fan',
            'artista' => 'artista',
            'individual' => 'individual',
            'multiple' => 'multiple',
            'tienda' => 'tienda',
            'momento' => 'momento'
        ];

        foreach ($metaFields as $metaKey => $postKey) {
            $value = isset($_POST[$postKey]) && $_POST[$postKey] == '1' ? 1 : 0;
            update_post_meta($postId, $metaKey, $value);
        }

        if (isset($_POST['nombreLanzamiento'])) {
            $nombreLanzamiento = sanitize_text_field($_POST['nombreLanzamiento']);
            update_post_meta($postId, 'nombreLanzamiento', $nombreLanzamiento);
        }

        if (isset($_POST['music']) && $_POST['music'] == '1') {
            $this->registrarNombreRolas($postId);
        }

        if (isset($_POST['tienda']) && $_POST['tienda'] == '1') {
            $this->registrarPrecios($postId);
        }
    }

    /**
     * Registra nombres de rolas para un post
     */
    private function registrarNombreRolas(int $postId): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $rolaKey = 'nombreRola' . $i;
            if (isset($_POST[$rolaKey])) {
                $nombreRola = sanitize_text_field($_POST[$rolaKey]);
                update_post_meta($postId, $rolaKey, $nombreRola);
            }
        }
    }

    /**
     * Registra precios de rolas
     */
    private function registrarPrecios(int $postId): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $precioKey = 'precioRola' . $i;
            if (isset($_POST[$precioKey])) {
                $precio = sanitize_text_field($_POST[$precioKey]);
                if (is_numeric($precio)) {
                    update_post_meta($postId, $precioKey, $precio);
                }
            }
        }
    }

    /**
     * Guarda datos del algoritmo para un post
     */
    public function datosParaAlgoritmo(int $postId): void
    {
        $this->algoritmoDataService->datosParaAlgoritmo($postId);
    }

    /**
     * Confirma archivos subidos asociados al post
     */
    public function confirmarArchivos(int $postId): void
    {
        $this->archivosService->confirmarArchivos($postId);
    }

    /**
     * Procesa las URLs de archivos
     */
    public function procesarURLs(int $postId): void
    {
        $this->archivosService->procesarURLs($postId);
    }

    /**
     * Asigna tags a un post
     */
    public function asignarTags(int $postId): void
    {
        $this->algoritmoDataService->asignarTags($postId);
    }
}
