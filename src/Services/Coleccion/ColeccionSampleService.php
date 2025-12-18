<?php

/**
 * Servicio para gestionar samples dentro de colecciones.
 * 
 * Responsabilidad única: Operaciones CRUD de samples en colecciones
 * (añadir, eliminar, verificar samples).
 *
 * @package Kamples\Services\Coleccion
 * @since 1.0.0
 */

namespace Kamples\Services\Coleccion;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColeccionSampleService
{
    private $logger;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Guardar un sample en una colección.
     * 
     * @param mixed $colecId  ID de la colección o 'favoritos'/'despues'.
     * @param int   $sampleId ID del sample.
     * @param int   $userId   ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string, 'samples' => array]
     */
    public function guardarSample($colecId, int $sampleId, int $userId): array
    {
        /* Manejar colecciones especiales */
        if ($colecId === 'favoritos' || $colecId === 'despues') {
            $colecId = $this->obtenerOCrearColeccionEspecial($colecId, $userId);
            if (!$colecId) {
                return [
                    'exito'   => false,
                    'mensaje' => 'Error al crear la colección especial',
                    'samples' => []
                ];
            }
        }

        return $this->añadirSampleAColeccion((int) $colecId, $sampleId, $userId);
    }

    /**
     * Añadir un sample a una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $sampleId    ID del sample.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string, 'samples' => array]
     */
    public function añadirSampleAColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        $coleccion = get_post($coleccionId);

        if (!$coleccion || (int) $coleccion->post_author !== $userId) {
            return [
                'exito'   => false,
                'mensaje' => 'No tienes permiso para modificar esta colección',
                'samples' => []
            ];
        }

        $samples = $this->obtenerSamplesDeColeccion($coleccionId);

        /* Verificar duplicados */
        if (in_array($sampleId, $samples)) {
            return [
                'exito'   => false,
                'mensaje' => 'Este sample ya existe en la colección',
                'samples' => $samples
            ];
        }

        /* Añadir sample */
        $samples[] = $sampleId;
        $actualizado = update_post_meta($coleccionId, 'samples', $samples);

        if ($actualizado) {
            update_post_meta($coleccionId, 'ultimaModificacion', current_time('mysql'));
            $this->actualizarSamplesGuardadosUsuario($userId, $sampleId, $coleccionId, 'añadir');
            $this->borrarCacheColeccion($coleccionId);
            $this->actualizarTimestampSamplesGuardados($userId);

            return [
                'exito'   => true,
                'mensaje' => 'Sample agregado exitosamente',
                'samples' => $samples
            ];
        }

        return [
            'exito'   => false,
            'mensaje' => 'Error al guardar el sample en la colección',
            'samples' => []
        ];
    }

    /**
     * Eliminar un sample de una colección.
     * 
     * @param int $coleccionId ID de la colección.
     * @param int $sampleId    ID del sample.
     * @param int $userId      ID del usuario.
     * @return array ['exito' => bool, 'mensaje' => string]
     */
    public function eliminarSampleDeColeccion(int $coleccionId, int $sampleId, int $userId): array
    {
        $coleccion = get_post($coleccionId);

        if (!$coleccion) {
            return [
                'exito'   => false,
                'mensaje' => 'Colección no encontrada'
            ];
        }

        if ((int) $coleccion->post_author !== $userId) {
            return [
                'exito'   => false,
                'mensaje' => 'No tienes permisos para modificar esta colección'
            ];
        }

        $samples = $this->obtenerSamplesDeColeccion($coleccionId);
        $key = array_search($sampleId, $samples);

        if ($key === false) {
            return [
                'exito'   => false,
                'mensaje' => 'No se encontró el sample en la colección'
            ];
        }

        unset($samples[$key]);
        $samples = array_values($samples);
        update_post_meta($coleccionId, 'samples', $samples);

        $this->actualizarSamplesGuardadosUsuario($userId, $sampleId, $coleccionId, 'eliminar');
        $this->borrarCacheColeccion($coleccionId);

        return [
            'exito'   => true,
            'mensaje' => 'Sample eliminado de colección'
        ];
    }

    /**
     * Verificar en qué colecciones está un sample.
     * 
     * @param int $sampleId ID del sample.
     * @param int $userId   ID del usuario.
     * @return array IDs de las colecciones que contienen el sample.
     */
    public function verificarSampleEnColecciones(int $sampleId, int $userId): array
    {
        $coleccionesConSample = [];

        $colecciones = get_posts([
            'post_type'      => 'colecciones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'author'         => $userId,
        ]);

        foreach ($colecciones as $coleccion) {
            $samples = $this->obtenerSamplesDeColeccion($coleccion->ID);
            if (in_array($sampleId, $samples)) {
                $coleccionesConSample[] = $coleccion->ID;
            }
        }

        return $coleccionesConSample;
    }

    /**
     * Obtener samples de una colección.
     */
    public function obtenerSamplesDeColeccion(int $coleccionId): array
    {
        $samples = get_post_meta($coleccionId, 'samples', true);

        if (!is_array($samples)) {
            $samples = $this->deserializarDatos($samples);
        }

        return is_array($samples) ? $samples : [];
    }

    /**
     * Obtener o crear colección especial (favoritos/despues).
     */
    public function obtenerOCrearColeccionEspecial(string $tipo, int $userId): ?int
    {
        $colecEspId = get_user_meta($userId, $tipo . '_coleccion_id', true);

        if ($colecEspId) {
            return (int) $colecEspId;
        }

        $titulo = ($tipo === 'favoritos') ? 'Favoritos' : 'Usar más tarde';
        $imgUrl = ($tipo === 'favoritos')
            ? site_url('/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg')
            : site_url('/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg');

        $colecEspId = wp_insert_post([
            'post_title'  => $titulo,
            'post_type'   => 'colecciones',
            'post_status' => 'publish',
            'post_author' => $userId,
        ]);

        if (!is_wp_error($colecEspId)) {
            update_user_meta($userId, $tipo . '_coleccion_id', $colecEspId);
            update_post_meta($colecEspId, 'coleccion_especial', $titulo);

            if (function_exists('subirImagenDesdeURL')) {
                $imgId = subirImagenDesdeURL($imgUrl, $colecEspId);
                if ($imgId) {
                    set_post_thumbnail($colecEspId, $imgId);
                }
            }

            return $colecEspId;
        }

        return null;
    }

    /**
     * Actualizar samplesGuardados del usuario.
     */
    public function actualizarSamplesGuardadosUsuario(int $userId, int $sampleId, int $coleccionId, string $accion): void
    {
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        if (!is_array($samplesGuardados)) {
            $samplesGuardados = [];
        }

        if ($accion === 'añadir') {
            if (!isset($samplesGuardados[$sampleId])) {
                $samplesGuardados[$sampleId] = [];
            }
            if (!in_array($coleccionId, $samplesGuardados[$sampleId])) {
                $samplesGuardados[$sampleId][] = $coleccionId;
            }
        } elseif ($accion === 'eliminar') {
            if (isset($samplesGuardados[$sampleId])) {
                $index = array_search($coleccionId, $samplesGuardados[$sampleId]);
                if ($index !== false) {
                    unset($samplesGuardados[$sampleId][$index]);
                    $samplesGuardados[$sampleId] = array_values($samplesGuardados[$sampleId]);

                    if (empty($samplesGuardados[$sampleId])) {
                        unset($samplesGuardados[$sampleId]);
                    }
                }
            }
        }

        if (empty($samplesGuardados)) {
            delete_user_meta($userId, 'samplesGuardados');
        } else {
            update_user_meta($userId, 'samplesGuardados', $samplesGuardados);
        }
    }

    /**
     * Limpiar referencias del usuario al eliminar colección.
     */
    public function limpiarReferenciasUsuario(int $userId, array $samples, int $coleccionId): void
    {
        $samplesGuardados = get_user_meta($userId, 'samplesGuardados', true);
        if (!is_array($samplesGuardados)) {
            return;
        }

        $modificado = false;
        foreach ($samples as $sampleId) {
            if (isset($samplesGuardados[$sampleId])) {
                $index = array_search($coleccionId, $samplesGuardados[$sampleId]);
                if ($index !== false) {
                    unset($samplesGuardados[$sampleId][$index]);
                    $samplesGuardados[$sampleId] = array_values($samplesGuardados[$sampleId]);

                    if (empty($samplesGuardados[$sampleId])) {
                        unset($samplesGuardados[$sampleId]);
                    }
                    $modificado = true;
                }
            }
        }

        if ($modificado) {
            if (empty($samplesGuardados)) {
                delete_user_meta($userId, 'samplesGuardados');
            } else {
                update_user_meta($userId, 'samplesGuardados', $samplesGuardados);
            }
        }
    }

    /**
     * Deserializar datos (JSON o serialize).
     */
    private function deserializarDatos($data)
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
     * Borrar caché de colección.
     */
    private function borrarCacheColeccion(int $coleccionId): void
    {
        if (function_exists('borrarCacheColeccion')) {
            borrarCacheColeccion($coleccionId);
        }
    }

    /**
     * Actualizar timestamp de samples guardados.
     */
    private function actualizarTimestampSamplesGuardados(int $userId): void
    {
        if (function_exists('actualizarTimestampSamplesGuardados')) {
            actualizarTimestampSamplesGuardados($userId);
        }
    }

    /**
     * Calcular datos de colección desde sus samples.
     * 
     * @param int $postId ID de la colección.
     */
    public function calcularDatosColeccion(int $postId): void
    {
        $this->logger->debug('algoritmo', "Inicio calcularDatosColeccion para post ID: $postId");

        try {
            $samplesSerialized = get_post_meta($postId, 'samples', true);
            if (empty($samplesSerialized)) {
                $this->logger->warning('algoritmo', "Metadato 'samples' vacío para post ID: $postId");
                return;
            }

            $samples = $this->deserializarDatos($samplesSerialized);

            if (!is_array($samples)) {
                preg_match_all('/i:\d+;i:(\d+);/', $samplesSerialized, $matches);
                if (isset($matches[1])) {
                    $samples = array_map('intval', $matches[1]);
                } else {
                    $this->logger->error('algoritmo', "No se pudo deserializar 'samples' para post ID: $postId");
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
                            $datosAlgoritmo = $this->deserializarDatos($datosAlgoritmoRespaldo);
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
                    $datosAlgoritmoArray = $this->deserializarDatos($datosAlgoritmo);
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

                        $valores = $this->aplanarArray($valores);

                        if (!is_array($valores)) {
                            $valores = [$valores];
                        }

                        foreach ($valores as $valor) {
                            if (is_array($valor)) {
                                $subvalores = $this->aplanarArray($valor);
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

            $this->logger->info('algoritmo', "calcularDatosColeccion completado para post ID: $postId");
        } catch (\Exception $e) {
            $this->logger->error('algoritmo', "Error en calcularDatosColeccion para post ID: $postId", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Función helper para aplanar arrays anidados.
     * 
     * @param mixed $input Array a aplanar.
     * @return array Array aplanado.
     */
    private function aplanarArray($input): array
    {
        $result = [];
        if (is_array($input)) {
            foreach ($input as $element) {
                if (is_array($element)) {
                    $result = array_merge($result, $this->aplanarArray($element));
                } else {
                    $result[] = $element;
                }
            }
        } else {
            $result[] = $input;
        }
        return $result;
    }
}
