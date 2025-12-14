<?php

/**
 * Wrappers deprecados para funciones de modales de la app
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Views\Components\AppModalComponents en su lugar.
 * @package app\deprecated
 */

use Kamples\Views\Components\AppModalComponents;

/**
 * @deprecated Usar AppModalComponents::renderModalDescargaApp()
 */
function modalApp(): string
{
    return AppModalComponents::renderModalDescargaApp();
}

/**
 * @deprecated Usar AppModalComponents (estilos incluidos en el componente)
 */
function estiloAppModal(): string
{
    return '';
}

/**
 * @deprecated Usar AppModalComponents::renderModalActualizacionApp()
 */
function mostrarModalActualizacionApp(): void
{
    echo AppModalComponents::renderModalActualizacionApp();
}

/**
 * @deprecated Usar AppModalComponents (estilos incluidos en el componente)
 */
function generarEstilosModalActualizacion(): string
{
    return '';
}
