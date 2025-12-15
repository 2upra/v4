<?php

/**
 * Wrappers de compatibilidad para tabs de paginas.
 * 
 * NOTA: Este archivo contiene funciones legacy que deben
 * ser reemplazadas gradualmente por las clases en src/.
 * 
 * @deprecated Use las clases en Kamples\Views\Components\Tabs
 */

use Kamples\Views\Components\Tabs\SocialTabs;
use Kamples\Views\Components\Tabs\PerfilTabs;
use Kamples\Views\Components\Tabs\BibliotecaTabs;
use Kamples\Views\Components\Tabs\BusquedaTabs;
use Kamples\Views\Components\Tabs\MusicTabs;
use Kamples\Views\Components\Tabs\ColabTabs;
use Kamples\Views\Components\Tabs\ColeccionTabs;
use Kamples\Views\Components\Tabs\TaskTabs;
use Kamples\Views\Components\Tabs\InicioTabs;
use Kamples\Views\Components\Tabs\InversorTabs;

/**
 * @deprecated Use SocialTabs::render()
 */
if (!function_exists('socialTabs')) {
    function socialTabs(): string
    {
        return SocialTabs::render();
    }
}

/**
 * @deprecated Use SocialTabs::renderFeed()
 */
if (!function_exists('socialTabsFEED')) {
    function socialTabsFEED(): string
    {
        return SocialTabs::renderFeed();
    }
}

/**
 * @deprecated Use SocialTabs::renderSamples()
 */
if (!function_exists('socialTabsSAMPLE')) {
    function socialTabsSAMPLE(): string
    {
        return SocialTabs::renderSamples();
    }
}

/**
 * @deprecated Use SocialTabs::renderMomentosFijos()
 */
if (!function_exists('momentosfijos')) {
    function momentosfijos(): string
    {
        return SocialTabs::renderMomentosFijos();
    }
}

/**
 * @deprecated Use PerfilTabs::render()
 */
if (!function_exists('perfilTabs')) {
    function perfilTabs(): string
    {
        return PerfilTabs::render();
    }
}

/**
 * @deprecated Use BibliotecaTabs::render()
 */
if (!function_exists('bibliotecaTabs')) {
    function bibliotecaTabs(): string
    {
        return BibliotecaTabs::render();
    }
}

/**
 * @deprecated Use BusquedaTabs::render()
 */
if (!function_exists('busquedaTabs')) {
    function busquedaTabs(): string
    {
        return BusquedaTabs::render();
    }
}

/**
 * @deprecated Use MusicTabs::render()
 */
if (!function_exists('musica')) {
    function musica(): string
    {
        return MusicTabs::render();
    }
}

/**
 * @deprecated Use ColabTabs::render()
 */
if (!function_exists('colabTabs')) {
    function colabTabs(): string
    {
        return ColabTabs::render();
    }
}

/**
 * @deprecated Use ColeccionTabs::render()
 */
if (!function_exists('colecTabs')) {
    function colecTabs(): string
    {
        return ColeccionTabs::render();
    }
}

/**
 * @deprecated Use TaskTabs::render()
 */
if (!function_exists('taskTabs')) {
    function taskTabs(): string
    {
        return TaskTabs::render();
    }
}

/**
 * @deprecated Use InicioTabs::render()
 */
if (!function_exists('inicio')) {
    function inicio(): string
    {
        return InicioTabs::render();
    }
}

/**
 * @deprecated Use InversorTabs::render()
 */
if (!function_exists('inversorTab')) {
    function inversorTab(): string
    {
        return InversorTabs::render();
    }
}

/* 
 * Las siguientes funciones aun no han sido completamente
 * migradas porque dependen de otras funciones legacy
 * (calc_ing, botonSponsor, graficoHistorialAcciones, etc.)
 * 
 * Se incluyen directamente desde sus archivos originales:
 * - inversorSector() -> app/Pages/inversorSector.php
 * - panel() -> app/Pages/Sello.php  
 * - asleyTab() -> app/Pages/asleyTabs.php
 * - portafolio() -> app/Pages/asleyTabs.php
 * 
 * Estos archivos se mantienen en app/Pages/ hasta que
 * todas sus dependencias sean refactorizadas.
 */
