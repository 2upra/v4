<?php

/**
 * Wrappers deprecados para funciones misceláneas.
 * 
 * @deprecated Usar Kamples\Services\UsuarioService
 */

use Kamples\Services\UsuarioService;

/**
 * Verifica si al usuario le gusta al menos una rola.
 * 
 * @deprecated Usar UsuarioService::obtenerInstancia()->saberSi()
 */
if (!function_exists('saberSi')) {
    function saberSi($user_id)
    {
        UsuarioService::obtenerInstancia()->saberSi($user_id);
    }
}

/* 
 * Nota: Los archivos recuperar_perdidos.php y tieneOnoTiene.php
 * contenían scripts de utilidad/administración que no se usan
 * en el funcionamiento normal del tema.
 * 
 * recuperar_perdidos.php - Script para recuperar archivos de medios 
 *                          perdidos (comentado/desactivado)
 * tieneOnoTiene.php      - Función saberSi() migrada a UsuarioService
 */
