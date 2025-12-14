<?php

/**
 * Wrappers deprecados para funciones de renderFiltro.php
 * 
 * @deprecated Estas funciones serán eliminadas en futuras versiones.
 *             Usar Kamples\Views\Components\FiltroComponents en su lugar.
 * @package app\deprecated
 */

use Kamples\Views\Components\FiltroComponents;

/**
 * @deprecated Usar FiltroComponents::renderFiltroSampleList()
 */
function renderFiltroSampleList(): string
{
    return FiltroComponents::renderFiltroSampleList();
}
