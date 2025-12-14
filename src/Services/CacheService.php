<?php

namespace Kamples\Services;

/**
 * Servicio de gestión de cache basado en archivos.
 * 
 * Proporciona métodos para guardar, obtener y borrar cache
 * con soporte para compresión gzip y expiración automática.
 *
 * @since 1.0.0
 */
class CacheService
{
    private string $cacheDir;
    private static ?CacheService $instancia = null;

    /**
     * Constructor del servicio de cache.
     *
     * @param string|null $subdir Subdirectorio dentro de cache (ej: 'feed', 'colecciones')
     */
    public function __construct(?string $subdir = 'feed')
    {
        $baseDir = WP_CONTENT_DIR . '/cache/';
        $this->cacheDir = $subdir ? $baseDir . $subdir . '/' : $baseDir;

        $this->asegurarDirectorio();
    }

    /**
     * Obtiene la instancia singleton del servicio.
     *
     * @param string|null $subdir Subdirectorio de cache
     * @return self
     */
    public static function obtenerInstancia(?string $subdir = 'feed'): self
    {
        if (self::$instancia === null || self::$instancia->cacheDir !== WP_CONTENT_DIR . '/cache/' . $subdir . '/') {
            self::$instancia = new self($subdir);
        }
        return self::$instancia;
    }

    /**
     * Guarda datos en cache con tiempo de expiración.
     *
     * @param string $cacheKey Clave única para identificar la cache
     * @param mixed $data Datos a almacenar
     * @param int $expiracion Tiempo de expiración en segundos
     * @return bool True si se guardó correctamente
     */
    public function guardar(string $cacheKey, mixed $data, int $expiracion): bool
    {
        $ruta = $this->obtenerRuta($cacheKey);

        $almacenamiento = [
            'exp' => time() + $expiracion,
            'data' => $data,
        ];

        $datosSerializados = serialize($almacenamiento);
        $datosComprimidos = gzcompress($datosSerializados);

        $resultado = file_put_contents($ruta, $datosComprimidos);

        if ($resultado === false) {
            $this->log('error', "Error al guardar cache: {$cacheKey}");
            return false;
        }

        return true;
    }

    /**
     * Obtiene datos de cache si existen y no han expirado.
     *
     * @param string $cacheKey Clave de la cache
     * @return mixed|false Datos almacenados o false si no existe/expirado
     */
    public function obtener(string $cacheKey): mixed
    {
        $ruta = $this->obtenerRuta($cacheKey);

        if (!file_exists($ruta)) {
            return false;
        }

        $datosComprimidos = file_get_contents($ruta);
        if ($datosComprimidos === false) {
            return false;
        }

        $datosSerializados = @gzuncompress($datosComprimidos);
        if ($datosSerializados === false) {
            $this->borrar($cacheKey);
            return false;
        }

        $datos = @unserialize($datosSerializados);
        if ($datos === false || !isset($datos['exp'], $datos['data'])) {
            $this->borrar($cacheKey);
            return false;
        }

        if ($datos['exp'] <= time()) {
            $this->borrar($cacheKey);
            return false;
        }

        return $datos['data'];
    }

    /**
     * Elimina una cache específica.
     *
     * @param string $cacheKey Clave de la cache a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function borrar(string $cacheKey): bool
    {
        $ruta = $this->obtenerRuta($cacheKey);

        if (file_exists($ruta)) {
            return unlink($ruta);
        }

        return true;
    }

    /**
     * Elimina todas las caches de ideas de un usuario.
     *
     * @param int $userId ID del usuario
     * @return int Número de caches eliminadas
     */
    public function borrarCacheIdeasUsuario(int $userId): int
    {
        $cacheMasterKey = 'cache_idea_user_' . $userId;
        $cacheKeys = $this->obtener($cacheMasterKey);
        $eliminadas = 0;

        if (is_array($cacheKeys)) {
            foreach ($cacheKeys as $cacheKey) {
                if ($this->borrar($cacheKey)) {
                    $eliminadas++;
                }
            }
            $this->borrar($cacheMasterKey);
        }

        return $eliminadas;
    }

    /**
     * Elimina las caches de una colección específica.
     *
     * @param int $coleccionId ID de la colección
     * @return int Número de caches eliminadas
     */
    public function borrarCacheColeccion(int $coleccionId): int
    {
        $cacheMasterKey = 'cache_colec_' . $coleccionId;
        $cacheKeys = $this->obtener($cacheMasterKey);
        $eliminadas = 0;

        if (is_array($cacheKeys)) {
            foreach ($cacheKeys as $cacheKey) {
                if ($this->borrar($cacheKey)) {
                    $eliminadas++;
                }
            }
            $this->borrar($cacheMasterKey);

            $userId = get_current_user_id();
            if ($userId > 0) {
                $eliminadas += $this->borrarCacheIdeasUsuario($userId);
            }
        }

        return $eliminadas;
    }

    /**
     * Elimina todas las caches que coincidan con un patrón.
     *
     * @param string $patron Patrón glob para buscar archivos
     * @return int Número de archivos eliminados
     */
    public function borrarPorPatron(string $patron): int
    {
        $archivos = glob($this->cacheDir . $patron . '.cache');
        $eliminados = 0;

        if (is_array($archivos)) {
            foreach ($archivos as $archivo) {
                if (unlink($archivo)) {
                    $eliminados++;
                }
            }
        }

        return $eliminados;
    }

    /**
     * Obtiene la ruta completa del archivo de cache.
     *
     * @param string $cacheKey Clave de la cache
     * @return string Ruta completa del archivo
     */
    private function obtenerRuta(string $cacheKey): string
    {
        $claveLimpia = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cacheKey);
        return $this->cacheDir . $claveLimpia . '.cache';
    }

    /**
     * Asegura que el directorio de cache existe.
     *
     * @return void
     */
    private function asegurarDirectorio(): void
    {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Registra un mensaje en el log.
     *
     * @param string $nivel Nivel del log (info, error, warning)
     * @param string $mensaje Mensaje a registrar
     * @return void
     */
    private function log(string $nivel, string $mensaje): void
    {
        $logger = \Logger::obtenerInstancia();
        $logger->{$nivel}('guardar', $mensaje);
    }

    /**
     * Obtiene el directorio de cache actual.
     *
     * @return string
     */
    public function obtenerDirectorio(): string
    {
        return $this->cacheDir;
    }

    /**
     * Limpia caches expiradas del directorio.
     *
     * @return int Número de archivos eliminados
     */
    public function limpiarExpiradas(): int
    {
        $archivos = glob($this->cacheDir . '*.cache');
        $eliminados = 0;

        if (!is_array($archivos)) {
            return 0;
        }

        foreach ($archivos as $archivo) {
            $contenido = @file_get_contents($archivo);
            if ($contenido === false) {
                continue;
            }

            $descomprimido = @gzuncompress($contenido);
            if ($descomprimido === false) {
                unlink($archivo);
                $eliminados++;
                continue;
            }

            $datos = @unserialize($descomprimido);
            if ($datos === false || !isset($datos['exp']) || $datos['exp'] <= time()) {
                unlink($archivo);
                $eliminados++;
            }
        }

        return $eliminados;
    }
}
