<?php

/**
 * Wrappers de compatibilidad para el sistema de colecciones.
 * 
 * Este archivo contiene funciones wrapper deprecadas que mantienen
 * la compatibilidad con código legacy mientras se completa la migración.
 * 
 * @deprecated Usar las clases en Kamples\Services, Kamples\Controllers y Kamples\Views
 * @package Kamples
 * @since 1.0.0
 */

/* Evitar acceso directo */
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\ColeccionService;
use Kamples\Controllers\ColeccionController;
use Kamples\Views\Components\ColeccionComponents;

/* 
 * Instancia del servicio para reutilización 
 */

$GLOBALS['_coleccionService'] = null;

/**
 * Obtener instancia del servicio de colecciones.
 * 
 * @return ColeccionService
 */
function obtenerColeccionService(): ColeccionService
{
    if ($GLOBALS['_coleccionService'] === null) {
        $GLOBALS['_coleccionService'] = new ColeccionService();
    }
    return $GLOBALS['_coleccionService'];
}

/**
 * Renderizar botón de colección para un post.
 * 
 * @deprecated Usar ColeccionService::renderizarBotonColeccion()
 * @param int $postId ID del post.
 * @return string HTML del botón.
 */
function botonColeccion($postId): string
{
    return obtenerColeccionService()->renderizarBotonColeccion((int) $postId);
}

/**
 * Obtener variables de una colección.
 * 
 * @deprecated Usar ColeccionService::obtenerVariablesColec()
 * @param int|null $postId ID de la colección.
 * @return array Variables de la colección.
 */
function variablesColec($postId = null): array
{
    return obtenerColeccionService()->obtenerVariablesColec($postId ? (int) $postId : null);
}

/**
 * Renderizar modal de selección de colección.
 * 
 * @deprecated Usar ColeccionComponents::renderModalColeccion()
 */
function modalColeccion(): void
{
    ColeccionComponents::renderModalColeccion();
}

/**
 * Renderizar modal de creación de colección.
 * 
 * @deprecated Usar ColeccionComponents::renderModalCreacionColeccion()
 */
function modalCreacionColeccion(): void
{
    ColeccionComponents::renderModalCreacionColeccion();
}

/**
 * Renderizar HTML de un post de colección.
 * 
 * @deprecated Usar ColeccionComponents::renderHtmlColec()
 * @param string $filtro Filtro del post.
 * @return string HTML del post.
 */
function htmlColec($filtro): string
{
    return ColeccionComponents::renderHtmlColec($filtro);
}

/**
 * Renderizar imagen de colección.
 * 
 * @deprecated Usar ColeccionComponents::renderImagenColeccion()
 * @param int $postId ID del post.
 * @return string HTML de la imagen.
 */
function imagenColeccion($postId): string
{
    return ColeccionComponents::renderImagenColeccion((int) $postId);
}

/**
 * Renderizar vista single de colección.
 * 
 * @deprecated Usar ColeccionComponents::renderSingleColec()
 * @param int $postId ID de la colección.
 * @return string HTML de la vista.
 */
function singleColec($postId): string
{
    return ColeccionComponents::renderSingleColec((int) $postId);
}

/**
 * Renderizar más ideas de colección.
 * 
 * @deprecated Usar ColeccionComponents::renderMasIdeasColec()
 * @param int $postId ID de la colección.
 * @return string HTML.
 */
function masIdeasColeb($postId): string
{
    return ColeccionComponents::renderMasIdeasColec((int) $postId);
}

/**
 * Renderizar opciones de colección.
 * 
 * @deprecated Usar ColeccionComponents::renderOpcionesColec()
 * @param int $postId  ID de la colección.
 * @param int $autorId ID del autor.
 * @return string HTML de las opciones.
 */
function opcionesColec($postId, $autorId): string
{
    return ColeccionComponents::renderOpcionesColec((int) $postId, (int) $autorId);
}

/**
 * Función helper para deserializar datos.
 * 
 * @deprecated Esta función se mantiene por compatibilidad.
 * @param mixed $data Datos a deserializar.
 * @return mixed Datos deserializados.
 */
function maybe_unserialize_dos($data)
{
    if (empty($data)) {
        return $data;
    }

    if (is_array($data)) {
        return $data;
    }

    if (is_string($data)) {
        $json = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
    }

    $unserialized = @unserialize($data);
    if ($unserialized !== false || $data === 'b:0;') {
        return $unserialized;
    }

    return $data;
}

/**
 * Función helper para aplanar arrays anidados.
 * 
 * @deprecated Esta función se mantiene por compatibilidad.
 * @param mixed $input Array a aplanar.
 * @return array Array aplanado.
 */
function aplanarArray($input): array
{
    $result = [];
    if (is_array($input)) {
        foreach ($input as $element) {
            if (is_array($element)) {
                $result = array_merge($result, aplanarArray($element));
            } else {
                $result[] = $element;
            }
        }
    } else {
        $result[] = $input;
    }
    return $result;
}

/**
 * Calcular datos de colección desde sus samples.
 * 
 * @deprecated Esta función se mantiene por compatibilidad (lógica compleja de algoritmo).
 * @param int $postId ID de la colección.
 */
function datosColeccion($postId): void
{
    $logger = \Logger::obtenerInstancia();
    $logger->debug('algoritmo', "Inicio datosColeccion para post ID: $postId");

    try {
        $samplesSerialized = get_post_meta($postId, 'samples', true);
        if (empty($samplesSerialized)) {
            $logger->warning('algoritmo', "Metadato 'samples' vacío para post ID: $postId");
            return;
        }

        $samples = maybe_unserialize_dos($samplesSerialized);

        if (!is_array($samples)) {
            preg_match_all('/i:\d+;i:(\d+);/', $samplesSerialized, $matches);
            if (isset($matches[1])) {
                $samples = array_map('intval', $matches[1]);
            } else {
                $logger->error('algoritmo', "No se pudo deserializar 'samples' para post ID: $postId");
                return;
            }
        }

        $datosColeccion = [
            'estado_animo'           => [],
            'artista_posible'        => [],
            'genero_posible'         => [],
            'instrumentos_principal' => [],
            'tags_posibles'          => [],
        ];

        $campos = array_keys($datosColeccion);

        foreach ($samples as $sampleId) {
            $datosAlgoritmo = get_post_meta($sampleId, 'datosAlgoritmo', true);
            if (empty($datosAlgoritmo)) {
                $datosAlgoritmoRespaldo = get_post_meta($sampleId, 'datosAlgoritmo_respaldo', true);
                if (!empty($datosAlgoritmoRespaldo)) {
                    if (is_array($datosAlgoritmoRespaldo) || is_object($datosAlgoritmoRespaldo)) {
                        $datosAlgoritmo = json_encode($datosAlgoritmoRespaldo);
                    } else {
                        $datosAlgoritmo = maybe_unserialize_dos($datosAlgoritmoRespaldo);
                        if (is_object($datosAlgoritmo) || is_array($datosAlgoritmo)) {
                            $datosAlgoritmo = json_encode($datosAlgoritmo);
                        }
                    }
                } else {
                    continue;
                }
            }

            if (is_array($datosAlgoritmo)) {
                $datosAlgoritmo = json_encode($datosAlgoritmo);
            } elseif (!is_string($datosAlgoritmo)) {
                continue;
            }

            $datosAlgoritmoArray = json_decode($datosAlgoritmo, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $datosAlgoritmoArray = maybe_unserialize_dos($datosAlgoritmo);
                if (!is_array($datosAlgoritmoArray)) {
                    continue;
                }
            }

            foreach ($campos as $campo) {
                if (isset($datosAlgoritmoArray[$campo])) {
                    if (isset($datosAlgoritmoArray[$campo]['en'])) {
                        $valores = $datosAlgoritmoArray[$campo]['en'];
                    } else {
                        continue;
                    }

                    $valores = aplanarArray($valores);

                    if (!is_array($valores)) {
                        $valores = [$valores];
                    }

                    foreach ($valores as $valor) {
                        if (is_array($valor)) {
                            $subvalores = aplanarArray($valor);
                            foreach ($subvalores as $subvalor) {
                                $subvalor = trim((string) $subvalor);
                                if ($subvalor === '') continue;
                                if (isset($datosColeccion[$campo][$subvalor])) {
                                    $datosColeccion[$campo][$subvalor]++;
                                } else {
                                    $datosColeccion[$campo][$subvalor] = 1;
                                }
                            }
                            continue;
                        }

                        $valor = trim((string) $valor);
                        if ($valor === '') continue;
                        if (isset($datosColeccion[$campo][$valor])) {
                            $datosColeccion[$campo][$valor]++;
                        } else {
                            $datosColeccion[$campo][$valor] = 1;
                        }
                    }
                }
            }
        }

        foreach ($datosColeccion as &$campo) {
            arsort($campo);
        }
        unset($campo);

        $datosColeccionJson = json_encode($datosColeccion, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        update_post_meta($postId, 'datosColeccion', $datosColeccionJson);

        $logger->info('algoritmo', "datosColeccion completado para post ID: $postId");
    } catch (\Exception $e) {
        $logger = \Logger::obtenerInstancia();
        $logger->error('algoritmo', "Error en datosColeccion para post ID: $postId", ['error' => $e->getMessage()]);
    }
}

/**
 * Procesar imagen de post.
 * 
 * @deprecated Esta función se mantiene por compatibilidad (lógica de imágenes complicada).
 * @param int    $postId   ID del post.
 * @param string $size     Tamaño de imagen.
 * @param int    $quality  Calidad.
 * @param string $strip    Strip option.
 * @param bool   $pixelated Si es pixelada.
 * @param bool   $useTemp   Usar imagen temporal.
 * @return string|false URL de la imagen.
 */
function imagenPost($postId, $size = 'medium', $quality = 50, $strip = 'all', $pixelated = false, $useTemp = false)
{
    $postThumbnailId = get_post_thumbnail_id($postId);

    if ($postThumbnailId) {
        $url = wp_get_attachment_image_url($postThumbnailId, $size);
    } elseif ($useTemp) {
        $tempImageId = get_post_meta($postId, 'imagenTemporal', true);

        if ($tempImageId && wp_attachment_is_image($tempImageId)) {
            $url = wp_get_attachment_image_url($tempImageId, $size);
        } else {
            if (function_exists('obtenerImagenAleatoria')) {
                $randomImagePath = obtenerImagenAleatoria('/home/asley01/MEGA/Waw/random');
                if (!$randomImagePath) {
                    if (function_exists('ejecutarScriptPermisos')) {
                        ejecutarScriptPermisos();
                    }
                    return false;
                }
                if (function_exists('subirImagenALibreria')) {
                    $tempImageId = subirImagenALibreria($randomImagePath, $postId);
                    if (!$tempImageId) {
                        if (function_exists('ejecutarScriptPermisos')) {
                            ejecutarScriptPermisos();
                        }
                        return false;
                    }
                    update_post_meta($postId, 'imagenTemporal', $tempImageId);
                    $url = wp_get_attachment_image_url($tempImageId, $size);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    } else {
        return false;
    }

    if (function_exists('jetpack_photon_url') && $url) {
        $args = ['quality' => $quality, 'strip' => $strip];
        if ($pixelated) {
            $args['w']    = 50;
            $args['h']    = 50;
            $args['zoom'] = 2;
        }
        return jetpack_photon_url($url, $args);
    }

    return $url;
}

/**
 * Inicializar el controlador de colecciones.
 * Registra las acciones AJAX.
 */
function inicializarColeccionController(): void
{
    $controller = new ColeccionController();
    $controller->registrar();
}

add_action('init', 'inicializarColeccionController');
