<?php

/**
 * Servicio de edición de posts
 * 
 * Gestiona cambios de título, descripción y regeneración de JSON via IA
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

class PostEdicionService
{
    private static ?PostEdicionService $instancia = null;
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
     * Cambia la descripción de un post y regenera el JSON con IA
     *
     * @param int $userId ID del usuario
     * @param int $postId ID del post
     * @param string $descripcion Nueva descripción
     * @return array Resultado de la operación
     */
    public function cambiarDescripcion(int $userId, int $postId, string $descripcion): array
    {
        if (!$userId) {
            return ['success' => false, 'message' => 'No estás autorizado'];
        }

        if ($postId <= 0) {
            return ['success' => false, 'message' => 'ID de post no válido'];
        }

        $post = get_post($postId);
        if (!$post) {
            return ['success' => false, 'message' => 'El post no existe'];
        }

        if (!$this->tienePermisos($userId, $post)) {
            return ['success' => false, 'message' => 'No tienes permisos para editar este post'];
        }

        /* Actualizar descripción */
        $post->post_content = wp_kses_post($descripcion);
        wp_update_post($post);

        /* Regenerar JSON con IA */
        $this->rehacerDescripcionConIA($postId);

        return ['success' => true];
    }

    /**
     * Cambia solo el título de un post (sin IA)
     *
     * @param int $userId ID del usuario
     * @param int $postId ID del post
     * @param string $titulo Nuevo título
     * @return array Resultado de la operación
     */
    public function cambiarTitulo(int $userId, int $postId, string $titulo): array
    {
        if (!$userId) {
            return ['success' => false, 'message' => 'No estás autorizado'];
        }

        if ($postId <= 0) {
            return ['success' => false, 'message' => 'ID de post no válido'];
        }

        $post = get_post($postId);
        if (!$post) {
            return ['success' => false, 'message' => 'El post no existe'];
        }

        if (!$this->tienePermisos($userId, $post)) {
            return ['success' => false, 'message' => 'No tienes permisos para editar este post'];
        }

        $post->post_title = sanitize_text_field($titulo);
        wp_update_post($post);

        return ['success' => true];
    }

    /**
     * Corrige tags de un post usando IA
     *
     * @param int $userId ID del usuario
     * @param int $postId ID del post
     * @param string $descripcion Instrucciones de corrección
     * @return array Resultado de la operación
     */
    public function corregirTags(int $userId, int $postId, string $descripcion): array
    {
        if (!$userId) {
            return ['success' => false, 'message' => 'No estás autorizado'];
        }

        if ($postId <= 0) {
            return ['success' => false, 'message' => 'ID de post no válido'];
        }

        $post = get_post($postId);
        if (!$post) {
            return ['success' => false, 'message' => 'El post no existe'];
        }

        if (!$this->tienePermisos($userId, $post)) {
            return ['success' => false, 'message' => 'No tienes permisos para editar este post'];
        }

        $this->rehacerJsonConIA($postId, $descripcion);

        return ['success' => true];
    }

    /**
     * Verifica permisos del usuario sobre un post
     */
    private function tienePermisos(int $userId, \WP_Post $post): bool
    {
        return $post->post_author == $userId || current_user_can('administrator');
    }

    /**
     * Rehace la descripción de un post usando IA
     */
    private function rehacerDescripcionConIA(int $postId): void
    {
        $audioLiteId = get_post_meta($postId, 'post_audio_lite', true);

        if (!$audioLiteId) {
            return;
        }

        $archivoAudio = get_attached_file($audioLiteId);
        if (!$archivoAudio) {
            return;
        }

        $postContent = get_post_field('post_content', $postId);
        if (!$postContent) {
            return;
        }

        $datosAlgoritmo = get_post_meta($postId, 'datosAlgoritmo', true);
        if (!$datosAlgoritmo) {
            return;
        }

        $datosActuales = json_decode($datosAlgoritmo, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción porque hay un dato incorrecto o un fallo en el json por ejemplo, no es un sample sino un one shot y viceversa, corrije cualquier cosa."
            . " Ten muy en cuenta la descripción nueva, es para corregir el JSON: \"{$postContent}\". "
            . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva descripción del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
            . json_encode($datosActuales, JSON_UNESCAPED_UNICODE)
            . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.";

        $this->procesarRespuestaIA($postId, $archivoAudio, $prompt);
    }

    /**
     * Rehace el JSON de un post usando IA con instrucciones del usuario
     */
    private function rehacerJsonConIA(int $postId, string $descripcion): void
    {
        $audioLiteId = get_post_meta($postId, 'post_audio_lite', true);

        if (!$audioLiteId) {
            return;
        }

        $archivoAudio = get_attached_file($audioLiteId);
        if (!$archivoAudio) {
            return;
        }

        $datosAlgoritmo = get_post_meta($postId, 'datosAlgoritmo', true);
        if (!$datosAlgoritmo) {
            return;
        }

        if (is_array($datosAlgoritmo)) {
            $datosAlgoritmo = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);
        }

        $datosActuales = json_decode($datosAlgoritmo, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        $prompt = "El usuario ya subió este audio, pero esta pidiendo corregir los tags o informacion del siguiente json"
            . " Este es el mensaje, usa la informacion para corregir el JSON: \"{$descripcion}\". "
            . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva indicacion del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
            . json_encode($datosActuales, JSON_UNESCAPED_UNICODE)
            . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. No cambies las cosas si el usuario no lo pidio, sigue sus instrucciones. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra.";

        $this->procesarRespuestaIA($postId, $archivoAudio, $prompt, true);
    }

    /**
     * Procesa la respuesta de la IA y actualiza el post
     */
    private function procesarRespuestaIA(int $postId, string $archivoAudio, string $prompt, bool $actualizarTitulo = false): void
    {
        if (!function_exists('generarDescripcionIA')) {
            $this->logger->warning('post', "Función generarDescripcionIA no disponible");
            return;
        }

        $descripcionMejorada = generarDescripcionIA($archivoAudio, $prompt);

        if (!$descripcionMejorada) {
            return;
        }

        /* Limpiar respuesta de la IA */
        $descripcionLimpia = preg_replace('/```(?:json)?\n/', '', $descripcionMejorada);
        $descripcionLimpia = preg_replace('/\n```/', '', $descripcionLimpia);

        $datosActualizados = json_decode($descripcionLimpia, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('post', "Error al decodificar respuesta de IA para post $postId");
            return;
        }

        update_post_meta($postId, 'datosAlgoritmo', json_encode($datosActualizados, JSON_UNESCAPED_UNICODE));
        update_post_meta($postId, 'ultimoEdit', current_time('mysql'));
        update_post_meta($postId, 'proIA', false);

        if ($actualizarTitulo) {
            $this->actualizarTituloDesdeJSON($postId, $datosActualizados);
        }
    }

    /**
     * Actualiza el título del post desde el JSON de la IA
     */
    private function actualizarTituloDesdeJSON(int $postId, array $datos): void
    {
        $nombreCorto = "";

        if (isset($datos['nombre_corto']['en']) && is_string($datos['nombre_corto']['en'])) {
            $nombreCorto = $datos['nombre_corto']['en'];
        } elseif (isset($datos['nombre_corto']['en']) && is_array($datos['nombre_corto']['en'])) {
            $nombreCorto = reset($datos['nombre_corto']['en']);
        }

        if (!empty($nombreCorto)) {
            wp_update_post([
                'ID' => $postId,
                'post_title' => $nombreCorto,
                'post_name' => sanitize_title($nombreCorto),
                'post_content' => $nombreCorto,
            ]);
        }
    }
}
