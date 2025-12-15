<?php

/**
 * Servicio fachada para likes/reacciones.
 * 
 * Orquesta los servicios especializados de likes
 * y mantiene compatibilidad con la API existente.
 *
 * @package Kamples\Services\Social
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class LikeService
{
    private LikeCrudService $crudService;
    private LikeQueryService $queryService;

    public function __construct()
    {
        $this->crudService  = new LikeCrudService();
        $this->queryService = new LikeQueryService();
    }

    /* 
     * ─────────────────────────────────────────────────────────────
     * Métodos delegados a LikeCrudService
     * ─────────────────────────────────────────────────────────────
     */

    /**
     * Obtener tipos de reacciones permitidas.
     */
    public function obtenerTiposPermitidos(): array
    {
        return $this->crudService->obtenerTiposPermitidos();
    }

    /**
     * Verificar si un tipo de reacción es válido.
     */
    public function esTipoValido(string $tipo): bool
    {
        return $this->crudService->esTipoValido($tipo);
    }

    /**
     * Ejecutar una acción de like/unlike.
     */
    public function ejecutarAccion(int $postId, int $userId, string $accion, string $tipo = 'like'): bool
    {
        return $this->crudService->ejecutarAccion($postId, $userId, $accion, $tipo);
    }

    /* 
     * ─────────────────────────────────────────────────────────────
     * Métodos delegados a LikeQueryService
     * ─────────────────────────────────────────────────────────────
     */

    /**
     * Contar reacciones de un post.
     */
    public function contarReacciones(int $postId, ?string $tipo = null): int
    {
        return $this->queryService->contarReacciones($postId, $tipo);
    }

    /**
     * Verificar si un usuario tiene una reacción en un post.
     */
    public function usuarioTieneReaccion(int $postId, int $userId, string $tipo = 'like'): bool
    {
        return $this->queryService->usuarioTieneReaccion($postId, $userId, $tipo);
    }

    /**
     * Obtener todos los contadores de un post.
     */
    public function obtenerContadores(int $postId): array
    {
        return $this->queryService->obtenerContadores($postId);
    }

    /**
     * Obtener el estado de reacciones de un usuario en un post.
     */
    public function obtenerEstadoUsuario(int $postId, int $userId): array
    {
        return $this->queryService->obtenerEstadoUsuario($postId, $userId);
    }

    /**
     * Obtener IDs de posts que tienen like de un usuario.
     */
    public function obtenerLikesDelUsuario(int $userId, string $tipo = 'like'): array
    {
        return $this->queryService->obtenerLikesDelUsuario($userId, $tipo);
    }
}
