<?php

/**
 * Wrappers deprecados para Momentos
 * 
 * Este archivo contiene funciones de compatibilidad que redirigen
 * a las nuevas clases en src/. NO USAR EN CÓDIGO NUEVO.
 *
 * @deprecated Usar Kamples\Views\Components\MomentoComponents
 * @package Theme\V4\Deprecated
 */

use Kamples\Views\Components\MomentoComponents;

if (!function_exists('momentos')) {
    /**
     * @deprecated Usar MomentoComponents::momentos()
     */
    function momentos()
    {
        $component = new MomentoComponents();
        return $component->momentos();
    }
}

if (!function_exists('publicarMomento')) {
    /**
     * @deprecated Usar MomentoComponents::publicarMomento()
     */
    function publicarMomento()
    {
        $component = new MomentoComponents();
        return $component->publicarMomento();
    }
}
