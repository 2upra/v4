<?php

/**
 * Wrapper deprecado para funciones de moderación y emergencias.
 * 
 * Mantiene compatibilidad con código legacy que usaba las funciones
 * originales de app/Misc/Emergencias.php.
 * 
 * @deprecated Usar Kamples\Services\ModeracionService
 * @package Kamples\Deprecated
 */

use Kamples\Services\ModeracionService;

/**
 * Bloquea y elimina usuarios completamente.
 * 
 * @deprecated Usar ModeracionService::bloquearYEliminarUsuarios()
 * @param array $usuarios Lista de identificadores.
 * @return array Resultado de la operación.
 */
if (!function_exists('bloquear_y_eliminar_usuarios')) {
    function bloquear_y_eliminar_usuarios($usuarios)
    {
        $moderacionService = ModeracionService::obtenerInstancia();
        return $moderacionService->bloquearYEliminarUsuarios((array) $usuarios);
    }
}

/**
 * Restringe usuarios (rol limitado).
 * 
 * @deprecated Usar ModeracionService::restringirUsuarios()
 * @param array $usuarios Lista de identificadores.
 * @return array Resultado de la operación.
 */
if (!function_exists('restringir_usuario')) {
    function restringir_usuario($usuarios)
    {
        $moderacionService = ModeracionService::obtenerInstancia();
        return $moderacionService->restringirUsuarios((array) $usuarios);
    }
}

/**
 * Restringe un usuario individual.
 * 
 * @deprecated Usar ModeracionService::restringirUsuarios()
 * @param int|string $usuarioId ID o identificador del usuario.
 * @return array Resultado de la operación.
 */
if (!function_exists('rrestringir_usuario')) {
    function rrestringir_usuario($usuarioId)
    {
        $moderacionService = ModeracionService::obtenerInstancia();
        return $moderacionService->restringirUsuarios([$usuarioId]);
    }
}

/**
 * Bloquea una IP en .htaccess.
 * 
 * @deprecated Usar ModeracionService::bloquearIp()
 * @param string $ip Dirección IP a bloquear.
 * @return bool True si se bloqueó.
 */
if (!function_exists('bloquear_ip')) {
    function bloquear_ip($ip)
    {
        $moderacionService = ModeracionService::obtenerInstancia();
        return $moderacionService->bloquearIp($ip);
    }
}

/**
 * Agrega el rol 'restringido'.
 * 
 * @deprecated Usar ModeracionService::crearRolRestringido()
 */
if (!function_exists('agregar_rol_restringido')) {
    function agregar_rol_restringido()
    {
        $moderacionService = ModeracionService::obtenerInstancia();
        $moderacionService->crearRolRestringido();
    }
}

/**
 * Registra un intento de acceso fallido.
 * 
 * @deprecated Usar ModeracionService::registrarIntentoFallido()
 * @param string $username Nombre de usuario.
 */
if (!function_exists('registrar_intento_acceso_fallido')) {
    function registrar_intento_acceso_fallido($username)
    {
        $moderacionService = ModeracionService::obtenerInstancia();
        $moderacionService->registrarIntentoFallido($username);
    }
}
