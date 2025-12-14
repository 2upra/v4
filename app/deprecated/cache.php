<?php

/**
 * Funciones wrapper de compatibilidad para el sistema de cache.
 * 
 * @deprecated Usar Kamples\Services\CacheService directamente
 * @see \Kamples\Services\CacheService
 */

use Kamples\Services\CacheService;

/**
 * Guarda datos en cache.
 *
 * @deprecated Usar CacheService::obtenerInstancia()->guardar()
 * @param string $cacheKey Clave de cache
 * @param mixed $data Datos a guardar
 * @param int $exp Tiempo de expiración en segundos
 * @return bool
 */
function guardarCache(string $cacheKey, mixed $data, int $exp): bool
{
    return CacheService::obtenerInstancia()->guardar($cacheKey, $data, $exp);
}

/**
 * Obtiene datos de cache.
 *
 * @deprecated Usar CacheService::obtenerInstancia()->obtener()
 * @param string $cacheKey Clave de cache
 * @return mixed|false
 */
function obtenerCache(string $cacheKey): mixed
{
    return CacheService::obtenerInstancia()->obtener($cacheKey);
}

/**
 * Borra una cache específica.
 *
 * @deprecated Usar CacheService::obtenerInstancia()->borrar()
 * @param string $cacheKey Clave de cache
 * @return bool
 */
function borrarCache(string $cacheKey): bool
{
    return CacheService::obtenerInstancia()->borrar($cacheKey);
}

/**
 * Borra todas las caches de ideas de un usuario.
 *
 * @deprecated Usar CacheService::obtenerInstancia()->borrarCacheIdeasUsuario()
 * @param int $userId ID del usuario
 * @return int Número de caches eliminadas
 */
function borrarCacheIdeasUsuario(int $userId): int
{
    return CacheService::obtenerInstancia()->borrarCacheIdeasUsuario($userId);
}

/**
 * Borra las caches de una colección.
 *
 * @deprecated Usar CacheService::obtenerInstancia()->borrarCacheColeccion()
 * @param int $colecId ID de la colección
 * @return int Número de caches eliminadas
 */
function borrarCacheColeccion(int $colecId): int
{
    return CacheService::obtenerInstancia()->borrarCacheColeccion($colecId);
}
