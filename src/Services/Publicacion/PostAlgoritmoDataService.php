<?php

/**
 * Servicio de datos de algoritmo para posts.
 * 
 * Maneja la generación y asignación de datos para el algoritmo de recomendación.
 *
 * @package Kamples\Services\Publicacion
 * @since 1.0.0
 */

namespace Kamples\Services\Publicacion;

class PostAlgoritmoDataService
{
    private static ?PostAlgoritmoDataService $instancia = null;
    private \Logger $logger;

    private function __construct()
    {
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
     * Guarda datos del algoritmo para un post
     */
    public function datosParaAlgoritmo(int $postId): void
    {
        $textoNormal = isset($_POST['textoNormal']) ? trim($_POST['textoNormal']) : '';
        $textoNormal = htmlspecialchars_decode($textoNormal, ENT_QUOTES);
        $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];

        $autorId = get_post_field('post_author', $postId);
        $nombreUsuario = get_the_author_meta('user_login', $autorId);
        $nombreMostrar = get_the_author_meta('display_name', $autorId);

        $datosAlgoritmo = [
            'tags' => $tags,
            'texto' => $textoNormal,
            'autor' => [
                'id' => $autorId,
                'usuario' => $nombreUsuario,
                'nombre' => $nombreMostrar,
            ],
        ];

        $datosAlgoritmoJson = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);

        if ($datosAlgoritmoJson !== false) {
            update_post_meta($postId, 'datosAlgoritmo', $datosAlgoritmoJson);
        }
    }

    /**
     * Asigna tags a un post
     */
    public function asignarTags(int $postId): void
    {
        if (!empty($_POST['Tags'])) {
            $tags = sanitize_text_field($_POST['Tags']);
            $tagsArray = explode(',', $tags);
            wp_set_post_tags($postId, $tagsArray, false);
        }
    }
}
