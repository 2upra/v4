<?php

/**
 * Servicio de comentarios (Fachada)
 * 
 * Orquesta los servicios especializados de comentarios:
 * - ComentarioCrudService: Crear y eliminar comentarios
 * - ComentarioQueryService: Consultas y formateo
 * - ComentarioUtilService: Rate limiting, validacion, metadatos
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ComentarioService
{
    private static ?ComentarioService $instancia = null;
    private ComentarioCrudService $crudService;
    private ComentarioQueryService $queryService;
    private ComentarioUtilService $utilService;

    private function __construct()
    {
        $this->crudService = ComentarioCrudService::obtenerInstancia();
        $this->queryService = ComentarioQueryService::obtenerInstancia();
        $this->utilService = ComentarioUtilService::obtenerInstancia();
    }

    /**
     * Obtiene la instancia unica del servicio
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Verificar si el usuario puede comentar (rate limiting)
     *
     * @param int $userId ID del usuario
     * @return bool True si puede comentar, false si ha alcanzado el limite
     */
    public function usuarioPuedeComentario(int $userId): bool
    {
        return $this->utilService->usuarioPuedeComentario($userId);
    }

    /**
     * Incrementar el contador de comentarios recientes del usuario
     *
     * @param int $userId ID del usuario
     */
    public function incrementarContadorComentarios(int $userId): void
    {
        $this->utilService->incrementarContadorComentarios($userId);
    }

    /**
     * Validar datos del comentario
     *
     * @param array $datos Datos del comentario
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarDatosComentario(array $datos): array
    {
        return $this->utilService->validarDatosComentario($datos);
    }

    /**
     * Crear un nuevo comentario
     *
     * @param array $datos Datos del comentario
     * @return array ['exito' => bool, 'comentarioId' => int|null, 'mensaje' => string]
     */
    public function crearComentario(array $datos): array
    {
        return $this->crudService->crearComentario($datos);
    }

    /**
     * Eliminar un comentario (solo por autor o admin)
     *
     * @param int $comentarioId ID del comentario
     * @param int $userId ID del usuario que intenta eliminar
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function eliminarComentario(int $comentarioId, int $userId): array
    {
        return $this->crudService->eliminarComentario($comentarioId, $userId);
    }

    /**
     * Obtener comentarios de un post con paginacion
     *
     * @param int $postId ID del post
     * @param int $pagina Numero de pagina (1-indexed)
     * @return array ['comentarios' => WP_Post[], 'hayMas' => bool, 'total' => int]
     */
    public function obtenerComentariosPost(int $postId, int $pagina = 1): array
    {
        return $this->queryService->obtenerComentariosPost($postId, $pagina);
    }

    /**
     * Obtener datos formateados de un comentario para renderizado
     *
     * @param \WP_Post $comentario Post de tipo comentario
     * @return array Datos del comentario formateados
     */
    public function formatearComentario(\WP_Post $comentario): array
    {
        return $this->queryService->formatearComentario($comentario);
    }

    /* 
     * Accesores para servicios especializados
     */

    public function obtenerCrudService(): ComentarioCrudService
    {
        return $this->crudService;
    }

    public function obtenerQueryService(): ComentarioQueryService
    {
        return $this->queryService;
    }

    public function obtenerUtilService(): ComentarioUtilService
    {
        return $this->utilService;
    }
}
