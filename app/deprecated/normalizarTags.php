<?php

/**
 * Wrappers deprecados para funciones de normalizarTags.php
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Services\NormalizacionService en su lugar.
 * @package app\deprecated
 */

use Kamples\Services\Contenido\NormalizacionService;

/* Inicializar el servicio */

NormalizacionService::obtenerInstancia();

/**
 * @deprecated Usar NormalizacionService::normalizarNuevoPost()
 */
function normalizarNuevoPost($postId, $post, $update): void
{
    /* Manejado por NormalizacionService */
}

/**
 * @deprecated Usar NormalizacionService::normalizarPostActualizado()
 */
function normalizarPostActualizado($postId, $postAfter, $postBefore): void
{
    /* Manejado por NormalizacionService */
}

/**
 * @deprecated Usar NormalizacionService::verificarYRestaurarDatos()
 */
function verificarYRestaurarDatos($postId): void
{
    $service = NormalizacionService::obtenerInstancia();
    $service->verificarYRestaurarDatos((int)$postId);
}

/**
 * @deprecated Usar NormalizacionService::crearRespaldoYNormalizar()
 */
function crearRespaldoYNormalizar($batchSize = 100): int
{
    $service = NormalizacionService::obtenerInstancia();
    return $service->crearRespaldoYNormalizar($batchSize);
}

/**
 * @deprecated Usar NormalizacionService::revertirNormalizacion()
 */
function revertirNormalizacion($batchSize = 100): int
{
    $service = NormalizacionService::obtenerInstancia();
    return $service->revertirNormalizacion($batchSize);
}

/**
 * @deprecated Usar NormalizacionService::restaurarDatosAlgoritmo()
 */
function restaurar_datos_algoritmo(): void
{
    $service = NormalizacionService::obtenerInstancia();
    $service->restaurarDatosAlgoritmo();
}
