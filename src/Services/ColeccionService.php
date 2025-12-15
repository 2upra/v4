<?php

/**
 * @deprecated Usar Kamples\Services\Coleccion\ColeccionService
 * 
 * Wrapper de compatibilidad para ColeccionService.
 * Este archivo redirige todas las llamadas al nuevo servicio modularizado.
 * 
 * El servicio ha sido dividido en:
 * - ColeccionService (fachada)
 * - ColeccionCrudService (crear, editar, eliminar)
 * - ColeccionSampleService (gestión de samples)
 * - ColeccionQueryService (consultas)
 * 
 * Ubicación nueva: src/Services/Coleccion/
 *
 * @package Kamples\Services
 * @since 1.0.0
 */

namespace Kamples\Services;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\Coleccion\ColeccionService as NuevoColeccionService;

class ColeccionService
{
    private NuevoColeccionService $servicio;

    public function __construct()
    {
        $this->servicio = new NuevoColeccionService();
    }

    public function crearColeccion(array $datos): array
    {
        return $this->servicio->crearColeccion($datos);
    }

    public function editarColeccion(array $datos): array
    {
        return $this->servicio->editarColeccion($datos);
    }

    public function eliminarColeccion(int $coleccionId, int $userId): array
    {
        return $this->servicio->eliminarColeccion($coleccionId, $userId);
    }

    public function guardarSample($colecId, int $sampleId, int $userId): array
    {
        return $this->servicio->guardarSample($colecId, $sampleId, $userId);
    }

    public function añadirSampleAColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        return $this->servicio->añadirSampleAColeccion($coleccionId, $sampleId, $userId);
    }

    public function eliminarSampleDeColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        return $this->servicio->eliminarSampleDeColeccion($coleccionId, $sampleId, $userId);
    }

    public function verificarSampleEnColecciones(int $sampleId, int $userId): array
    {
        return $this->servicio->verificarSampleEnColecciones($sampleId, $userId);
    }

    public function obtenerColeccionesUsuario(int $userId): array
    {
        return $this->servicio->obtenerColeccionesUsuario($userId);
    }

    public function obtenerVariablesColec(?int $postId = null): array
    {
        return $this->servicio->obtenerVariablesColec($postId);
    }

    public function renderizarBotonColeccion(int $postId): string
    {
        return $this->servicio->renderizarBotonColeccion($postId);
    }

    public function obtenerSamplesDeColeccion(int $coleccionId): array
    {
        return $this->servicio->obtenerSamplesDeColeccion($coleccionId);
    }

    public function obtenerOCrearColeccionEspecial(string $tipo, int $userId): ?int
    {
        return $this->servicio->obtenerOCrearColeccionEspecial($tipo, $userId);
    }

    public function puedeCrearColeccion(int $userId): bool
    {
        return $this->servicio->puedeCrearColeccion($userId);
    }
}
