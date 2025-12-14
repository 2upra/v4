<?php

/**
 * Wrappers deprecados para funciones de administración
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Core\AdminConfig y Kamples\Views\Components\AdminComponents en su lugar.
 * @package app\deprecated
 */

use Kamples\Views\Components\AdminComponents;

/* 
 * Inicializar AdminConfig automáticamente
 * Las configuraciones ahora se manejan desde src/Core/AdminConfig.php
 */

\Kamples\Core\AdminConfig::inicializar();

/**
 * @deprecated Usar AdminComponents::renderAdminReport()
 */
if (!function_exists('render_admin_report')) {
    function render_admin_report($buttons, $contents): string
    {
        return AdminComponents::renderAdminReport($buttons, $contents);
    }
}

/**
 * @deprecated Usar AdminComponents::renderReportesAdmin()
 */
if (!function_exists('reportesAdmin')) {
    function reportesAdmin(): string
    {
        return AdminComponents::renderReportesAdmin();
    }
}

/**
 * @deprecated Usar AdminComponents::renderLogsAdmin()
 */
if (!function_exists('logsAdmin')) {
    function logsAdmin(): string
    {
        return AdminComponents::renderLogsAdmin();
    }
}
