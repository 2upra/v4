<?php

/**
 * Fachada para gestionar colecciones de samples.
 * 
 * Orquesta los servicios especializados:
 * - ColeccionCrudService: Crear, editar, eliminar colecciones
 * - ColeccionSampleService: Gestionar samples en colecciones
 * - ColeccionQueryService: Consultas y renderizado
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColeccionService
{
    private ColeccionCrudService $crudService;
    private ColeccionSampleService $sampleService;
    private ColeccionQueryService $queryService;

    public function __construct(
        ?ColeccionCrudService $crudService = null,
        ?ColeccionSampleService $sampleService = null,
        ?ColeccionQueryService $queryService = null
    ) {
        $this->sampleService = $sampleService ?? new ColeccionSampleService();
        $this->crudService = $crudService ?? new ColeccionCrudService($this->sampleService);
        $this->queryService = $queryService ?? new ColeccionQueryService($this->sampleService);
    }

    /* 
    *
    * CRUD de Colecciones - Delegado a ColeccionCrudService
    *
    */

    /**
     * Crear una nueva colección.
     * 
     * @param array $datos Datos de la colección.
     * @return array ['exito' => bool, 'coleccionId' => int|null, 'mensaje' => string]
     */
    public function crearColeccion(array $datos): array
    {
        return $this->crudService->crearColeccion($datos);
    }

    /**
     * Editar una colección existente.
     * 
     * @param array $datos Datos de la colección.
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function editarColeccion(array $datos): array
    {
        return $this->crudService->editarColeccion($datos);
    }

    /**
     * Eliminar una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function eliminarColeccion(int $coleccionId, int $userId): array
    {
        return $this->crudService->eliminarColeccion($coleccionId, $userId);
    }

    /**
     * Verificar si el usuario puede crear más colecciones.
     */
    public function puedeCrearColeccion(int $userId): bool
    {
        return $this->crudService->puedeCrearColeccion($userId);
    }

    /* 
    *
    * Gestión de Samples - Delegado a ColeccionSampleService
    *
    */

    /**
     * Guardar un sample en una colección.
     * 
     * @param mixed $colecId  ID de la colección o 'favoritos'/'despues'.
     * @param int   $sampleId ID del sample.
     * @param int   $userId   ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string, 'samples' => array]
     */
    public function guardarSample($colecId, int $sampleId, int $userId): array
    {
        return $this->sampleService->guardarSample($colecId, $sampleId, $userId);
    }

    /**
     * Añadir un sample a una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $sampleId    ID del sample.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string, 'samples' => array]
     */
    public function añadirSampleAColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        return $this->sampleService->añadirSampleAColeccion($coleccionId, $sampleId, $userId);
    }

    /**
     * Eliminar un sample de una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $sampleId    ID del sample.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function eliminarSampleDeColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        return $this->sampleService->eliminarSampleDeColeccion($coleccionId, $sampleId, $userId);
    }

    /**
     * Verificar en qué colecciones está un sample.
     * 
     * @param int $sampleId ID del sample.
     * @param int $userId   ID del usuario.
     * @return array IDs de las colecciones que contienen el sample.
     */
    public function verificarSampleEnColecciones(int $sampleId, int $userId): array
    {
        return $this->sampleService->verificarSampleEnColecciones($sampleId, $userId);
    }

    /**
     * Obtener samples de una colección.
     */
    public function obtenerSamplesDeColeccion(int $coleccionId): array
    {
        return $this->sampleService->obtenerSamplesDeColeccion($coleccionId);
    }

    /**
     * Obtener o crear colección especial (favoritos/despues).
     */
    public function obtenerOCrearColeccionEspecial(string $tipo, int $userId): ?int
    {
        return $this->sampleService->obtenerOCrearColeccionEspecial($tipo, $userId);
    }

    /* 
    *
    * Consultas - Delegado a ColeccionQueryService
    *
    */

    /**
     * Obtener lista de colecciones del usuario.
     * 
     * @param int $userId ID del usuario.
     * @return array Colecciones del usuario.
     */
    public function obtenerColeccionesUsuario(int $userId): array
    {
        return $this->queryService->obtenerColeccionesUsuario($userId);
    }

    /**
     * Obtener variables de una colección.
     * 
     * @param int|null $postId ID de la colección.
     * @return array Variables de la colección.
     */
    public function obtenerVariablesColec(?int $postId = null): array
    {
        return $this->queryService->obtenerVariablesColec($postId);
    }

    /**
     * Renderizar botón de colección para un post.
     * 
     * @param int $postId ID del post.
     * @return string HTML del botón.
     */
    public function renderizarBotonColeccion(int $postId): string
    {
        return $this->queryService->renderizarBotonColeccion($postId);
    }

    /* 
    *
    * Acceso a servicios especializados
    *
    */

    /**
     * Obtener el servicio de CRUD.
     */
    public function getCrudService(): ColeccionCrudService
    {
        return $this->crudService;
    }

    /**
     * Obtener el servicio de samples.
     */
    public function getSampleService(): ColeccionSampleService
    {
        return $this->sampleService;
    }

    /**
     * Obtener el servicio de consultas.
     */
    public function getQueryService(): ColeccionQueryService
    {
        return $this->queryService;
    }
}
