<?php

/**
 * Servicio fachada para colaboraciones.
 * 
 * Orquesta los servicios especializados de colaboraciones
 * y mantiene compatibilidad con la API existente.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Services\Social;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColabService
{
    private ColabCrudService $crudService;
    private ColabQueryService $queryService;

    public function __construct()
    {
        $this->crudService  = new ColabCrudService();
        $this->queryService = new ColabQueryService();
    }

    /* 
     * ─────────────────────────────────────────────────────────────
     * Métodos delegados a ColabCrudService
     * ─────────────────────────────────────────────────────────────
     */

    /**
     * Validar si un usuario puede iniciar una colaboración.
     */
    public function validarPuedeColaborar(int $userId, int $postId, \WP_Post $originalPost): array
    {
        return $this->crudService->validarPuedeColaborar($userId, $postId, $originalPost);
    }

    /**
     * Crear una nueva colaboración.
     */
    public function crearColaboracion(array $datos): array
    {
        return $this->crudService->crearColaboracion($datos);
    }

    /**
     * Crear conversación para la colaboración.
     */
    public function crearConversacionColab(int $autorId, int $colaboradorId): ?int
    {
        return $this->crudService->crearConversacionColab($autorId, $colaboradorId);
    }

    /**
     * Actualizar metadatos del post original.
     */
    public function actualizarMetasPostOrigen(int $postId, int $userId): void
    {
        $this->crudService->actualizarMetasPostOrigen($postId, $userId);
    }

    /**
     * Adjuntar archivo a la colaboración.
     */
    public function adjuntarArchivoColab(int $colabId, string $fileUrl): bool
    {
        return $this->crudService->adjuntarArchivoColab($colabId, $fileUrl);
    }

    /**
     * Manejar cambio de estado de colaboración.
     */
    public function manejarCambioEstado(int $postId, \WP_Post $postAfter, \WP_Post $postBefore): void
    {
        $this->crudService->manejarCambioEstado($postId, $postAfter, $postBefore);
    }

    /* 
     * ─────────────────────────────────────────────────────────────
     * Métodos delegados a ColabQueryService
     * ─────────────────────────────────────────────────────────────
     */

    /**
     * Obtener el botón de colaboración para un post.
     */
    public function obtenerBotonColab(int $postId, bool $colab): string
    {
        return $this->queryService->obtenerBotonColab($postId, $colab);
    }

    /**
     * Obtener variables de una colaboración.
     */
    public function obtenerVariablesColab(?int $postId = null): array
    {
        return $this->queryService->obtenerVariablesColab($postId);
    }

    /**
     * Obtener resumen de colaboraciones del usuario actual.
     */
    public function obtenerColabsResumen(): array
    {
        return $this->queryService->obtenerColabsResumen();
    }
}
