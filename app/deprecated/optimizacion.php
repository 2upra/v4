<?php

/**
 * Wrappers deprecados para funciones de optimizacion.php
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Core\OptimizacionWP en su lugar.
 * @package app\deprecated
 */

use Kamples\Core\OptimizacionWP;

/* Inicializar la optimización automáticamente */

OptimizacionWP::obtenerInstancia();

/* Las siguientes funciones ya no son necesarias individualmente */

/**
 * @deprecated Manejado por OptimizacionWP
 */
function desactivar_todos_soportes_bloques($settings, $name)
{
    return $settings;
}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function desactivar_emojis(): void {}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function eliminar_scripts_y_estilos(): void {}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function desactivar_embeds(): void {}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function eliminar_version_wp(): string
{
    return '';
}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function desactivar_feeds(): void
{
    wp_die(__('Las feeds RSS están deshabilitadas.'));
}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function desactivar_autosave(): void {}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function eliminar_widgets_innecesarios(): void {}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function limpiar_footer_wordpress(): void {}

/**
 * @deprecated Manejado por OptimizacionWP
 */
function eliminar_ajustes_discusion(): void {}
