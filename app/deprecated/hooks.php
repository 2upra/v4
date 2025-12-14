<?php

/**
 * Wrappers deprecados para hooks de limpieza
 * 
 * @deprecated Usar Kamples\Core\CleanupService en su lugar.
 * @package app\deprecated
 */

/* Inicializar CleanupService */
\Kamples\Core\CleanupService::inicializar();

/**
 * @deprecated Usar CleanupService::eliminarPostsPendientes()
 */
if (!function_exists('eliminarPostPendientes')) {
    function eliminarPostPendientes(): void
    {
        \Kamples\Core\CleanupService::eliminarPostsPendientes();
    }
}

/**
 * @deprecated Uso interno de CleanupService
 */
if (!function_exists('agregar_intervalo_semanal')) {
    function agregar_intervalo_semanal(array $schedules): array
    {
        return \Kamples\Core\CleanupService::agregarIntervaloSemanal($schedules);
    }
}

/**
 * @deprecated Usar CleanupService::eliminarAdjuntosPost()
 */
if (!function_exists('eliminarAdjuntosPost')) {
    function eliminarAdjuntosPost(int $postId): void
    {
        \Kamples\Core\CleanupService::eliminarAdjuntosPost($postId);
    }
}
