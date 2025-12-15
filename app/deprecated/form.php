<?php

/**
 * Wrappers deprecados para funciones de formularios
 * 
 * @deprecated Usar Kamples\Services\HashService, PostCreacionService y
 *             Kamples\Controllers\ArchivoController, FormularioController en su lugar.
 * @package app\deprecated
 */

use Kamples\Services\HashService;
use Kamples\Services\PostCreacionService;

/* Inicializar servicios */

HashService::inicializar();

/**
 * @deprecated Usar HashService::sonHashesSimilares()
 */
if (!function_exists('sonHashesSimilares')) {
    function sonHashesSimilares($hash1, $hash2, $umbral = 0.7): bool
    {
        return HashService::obtenerInstancia()->sonHashesSimilares($hash1, $hash2, $umbral);
    }
}

/**
 * @deprecated Usar HashService::recalcularHash()
 */
if (!function_exists('recalcularHash')) {
    function recalcularHash($audioFilePath)
    {
        return HashService::obtenerInstancia()->recalcularHash($audioFilePath);
    }
}

/**
 * @deprecated Usar HashService::guardarHash()
 */
if (!function_exists('guardarHash')) {
    function guardarHash($hash, $url, $userId, $status = 'pending')
    {
        return HashService::obtenerInstancia()->guardarHash($hash, $url, (int)$userId, $status);
    }
}

/**
 * @deprecated Usar HashService::actualizarEstadoArchivo()
 */
if (!function_exists('actualizarEstadoArchivo')) {
    function actualizarEstadoArchivo($id, $estado): bool
    {
        return HashService::obtenerInstancia()->actualizarEstadoArchivo((int)$id, $estado);
    }
}

/**
 * @deprecated Usar HashService::actualizarUrlArchivo()
 */
if (!function_exists('actualizarUrlArchivo')) {
    function actualizarUrlArchivo($fileId, $newUrl): bool
    {
        return HashService::obtenerInstancia()->actualizarUrlArchivo((int)$fileId, $newUrl);
    }
}

/**
 * @deprecated Usar HashService::confirmarHashId()
 */
if (!function_exists('confirmarHashId')) {
    function confirmarHashId($fileId): bool
    {
        return HashService::obtenerInstancia()->confirmarHashId((int)$fileId);
    }
}

/**
 * @deprecated Usar HashService::eliminarHash()
 */
if (!function_exists('eliminarHash')) {
    function eliminarHash($id): bool
    {
        return HashService::obtenerInstancia()->eliminarHash((int)$id);
    }
}

/**
 * @deprecated Usar HashService::eliminarPorHash()
 */
if (!function_exists('eliminarPorHash')) {
    function eliminarPorHash($fileHash): bool
    {
        return HashService::obtenerInstancia()->eliminarPorHash($fileHash);
    }
}

/**
 * @deprecated Usar HashService::obtenerFileIdPorUrl()
 */
if (!function_exists('obtenerFileIDPorURL')) {
    function obtenerFileIDPorURL($url)
    {
        return HashService::obtenerInstancia()->obtenerFileIdPorUrl($url);
    }
}

/**
 * @deprecated Usar HashService::nombreUnicoFile()
 */
if (!function_exists('nombreUnicoFile')) {
    function nombreUnicoFile($dir, $name, $ext): string
    {
        return HashService::obtenerInstancia()->nombreUnicoFile($dir, $name, $ext);
    }
}

/**
 * @deprecated Usar HashService::limpiarArchivosPendientes()
 */
if (!function_exists('limpiarArchivosPendientes')) {
    function limpiarArchivosPendientes(): void
    {
        HashService::obtenerInstancia()->limpiarArchivosPendientes();
    }
}

/**
 * @deprecated Usar PostCreacionService::crearPost()
 */
if (!function_exists('crearPost')) {
    function crearPost($tipoPost = 'social_post', $estadoPost = 'publish')
    {
        return PostCreacionService::obtenerInstancia()->crearPost($tipoPost, $estadoPost);
    }
}

/**
 * @deprecated Usar PostCreacionService::actualizarMetaDatos()
 */
if (!function_exists('actualizarMetaDatos')) {
    function actualizarMetaDatos($postId): void
    {
        PostCreacionService::obtenerInstancia()->actualizarMetaDatos((int)$postId);
    }
}

/**
 * @deprecated Usar PostCreacionService::datosParaAlgoritmo()
 */
if (!function_exists('datosParaAlgoritmo')) {
    function datosParaAlgoritmo($postId): void
    {
        PostCreacionService::obtenerInstancia()->datosParaAlgoritmo((int)$postId);
    }
}

/**
 * @deprecated Usar PostCreacionService::confirmarArchivos()
 */
if (!function_exists('confirmarArchivos')) {
    function confirmarArchivos($postId): void
    {
        PostCreacionService::obtenerInstancia()->confirmarArchivos((int)$postId);
    }
}

/**
 * @deprecated Usar PostCreacionService::procesarURLs()
 */
if (!function_exists('procesarURLs')) {
    function procesarURLs($postId): void
    {
        PostCreacionService::obtenerInstancia()->procesarURLs((int)$postId);
    }
}

/**
 * @deprecated Usar PostCreacionService::asignarTags()
 */
if (!function_exists('asignarTags')) {
    function asignarTags($postId): void
    {
        PostCreacionService::obtenerInstancia()->asignarTags((int)$postId);
    }
}

/**
 * @deprecated Usar Kamples\Views\Components\PostFormComponents::renderFormRs()
 */
if (!function_exists('formRs')) {
    function formRs()
    {
        return \Kamples\Views\Components\PostFormComponents::renderFormRs();
    }
}
