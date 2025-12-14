<?php

/**
 * Clase Logger - Sistema centralizado de logging por canales y niveles.
 * 
 * Implementa el patrón Singleton para garantizar una única instancia.
 * Proporciona métodos tipados para diferentes tipos de logs con:
 * - Niveles de log (DEBUG, INFO, WARNING, ERROR, CRITICAL)
 * - Canales independientes con configuración granular
 * - Auto-limpieza de archivos grandes
 * - Control de acceso por rol (adminOnly)
 *
 * @package Theme_V4
 * @subpackage Logging
 * @since 1.0.0
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class Logger
{
    /**
     * Instancia única de la clase (Singleton).
     *
     * @var Logger|null
     */
    private static $instancia = null;

    /**
     * Nombres de los niveles para formateo.
     *
     * @var array
     */
    private $nombreNiveles = [
        0 => 'DEBUG',
        1 => 'INFO',
        2 => 'WARNING',
        3 => 'ERROR',
        4 => 'CRITICAL',
    ];

    /**
     * Constructor privado para prevenir instanciación directa.
     */
    private function __construct()
    {
        // Constructor privado - patrón Singleton
    }

    /**
     * Prevenir clonación de la instancia.
     */
    private function __clone()
    {
        // Prevenir clonación
    }

    /**
     * Obtener la instancia única del Logger.
     *
     * @return Logger
     */
    public static function obtenerInstancia(): Logger
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Log con nivel y canal específico.
     * 
     * Este es el método principal que implementa toda la lógica de logging.
     *
     * @param string $canal   Canal de log (stream, seo, ia, etc.).
     * @param int    $nivel   Nivel del mensaje (LOG_LEVEL_*).
     * @param mixed  $mensaje Mensaje a registrar.
     * @param array  $contexto Datos adicionales de contexto.
     * @return bool True si se escribió correctamente.
     */
    public function log(string $canal, int $nivel, $mensaje, array $contexto = []): bool
    {
        // Verificar si el canal existe y está habilitado
        if (!$this->canalHabilitado($canal, $nivel)) {
            return false;
        }

        // Formatear el mensaje
        $mensajeFormateado = $this->formatearMensaje($mensaje, $contexto);

        // Crear línea de log con nivel
        $nivelNombre = $this->nombreNiveles[$nivel] ?? 'UNKNOWN';
        $logLinea = "[{$nivelNombre}] [{$canal}] {$mensajeFormateado}";

        // Siempre usar error_log de WordPress
        error_log($logLinea);

        // Escribir en archivo del canal si estamos en producción
        $archivo = $this->obtenerArchivoCanal($canal);
        if (!empty($archivo) && (!defined('LOCAL') || !LOCAL)) {
            return $this->escribirEnArchivo($logLinea, $archivo, $this->obtenerMaxLineasCanal($canal));
        }

        return true;
    }

    /**
     * Verificar si un canal está habilitado para un nivel dado.
     *
     * @param string $canal Canal a verificar.
     * @param int    $nivel Nivel del mensaje.
     * @return bool
     */
    private function canalHabilitado(string $canal, int $nivel): bool
    {
        $canales = defined('LOG_CHANNELS') ? LOG_CHANNELS : [];

        // Si el canal no existe, usar configuración por defecto
        if (!isset($canales[$canal])) {
            return $nivel >= (defined('LOG_LEVEL_DEFAULT') ? LOG_LEVEL_DEFAULT : 1);
        }

        $config = $canales[$canal];

        // Verificar si está habilitado
        if (!$config['enabled']) {
            return false;
        }

        // Verificar nivel
        $nivelCanal = $config['level'] ?? LOG_LEVEL_INFO;
        if ($nivel < $nivelCanal) {
            return false;
        }

        // Verificar restricción de admin
        if ($config['adminOnly'] && function_exists('current_user_can') && !current_user_can('administrator')) {
            return false;
        }

        return true;
    }

    /**
     * Obtener archivo de log para un canal.
     *
     * @param string $canal Canal de log.
     * @return string Ruta del archivo.
     */
    private function obtenerArchivoCanal(string $canal): string
    {
        $archivos = defined('LOG_FILES') ? LOG_FILES : [];
        return $archivos[$canal] ?? '';
    }

    /**
     * Obtener máximo de líneas para un canal.
     *
     * @param string $canal Canal de log.
     * @return int Máximo de líneas.
     */
    private function obtenerMaxLineasCanal(string $canal): int
    {
        // El canal de algoritmo tiene límite reducido
        if ($canal === 'algoritmo') {
            return defined('LOG_MAX_LINES_REDUCED') ? LOG_MAX_LINES_REDUCED : 100;
        }
        return defined('LOG_MAX_LINES_DEFAULT') ? LOG_MAX_LINES_DEFAULT : 10000;
    }

    /**
     * Formatear mensaje para logging.
     *
     * @param mixed $mensaje  Mensaje a formatear.
     * @param array $contexto Datos de contexto adicionales.
     * @return string Mensaje formateado.
     */
    private function formatearMensaje($mensaje, array $contexto = []): string
    {
        if (is_object($mensaje) || is_array($mensaje)) {
            $mensajeStr = print_r($mensaje, true);
        } else {
            $mensajeStr = (string) $mensaje;
        }

        // Añadir contexto si existe
        if (!empty($contexto)) {
            $mensajeStr .= ' | Contexto: ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        }

        return $mensajeStr;
    }

    /**
     * Escribir con nivel y canal (método legado para compatibilidad).
     *
     * @param mixed  $mensaje   Mensaje a registrar.
     * @param string $archivo   Ruta del archivo de log.
     * @param int    $maxLineas Número máximo de líneas.
     * @return bool
     */
    public function escribir($mensaje, string $archivo = '', int $maxLineas = 10000): bool
    {
        $mensajeFormateado = $this->formatearMensaje($mensaje);
        error_log($mensajeFormateado);

        if (!empty($archivo) && (!defined('LOCAL') || !LOCAL)) {
            return $this->escribirEnArchivo($mensajeFormateado, $archivo, $maxLineas);
        }

        return true;
    }

    /**
     * Escribir mensaje en un archivo específico.
     *
     * @param string $mensaje   Mensaje a escribir.
     * @param string $archivo   Ruta del archivo.
     * @param int    $maxLineas Número máximo de líneas.
     * @return bool True si se escribió correctamente.
     */
    private function escribirEnArchivo(string $mensaje, string $archivo, int $maxLineas): bool
    {
        try {
            $directorio = dirname($archivo);

            if (!is_writable($directorio)) {
                error_log("Logger: No se puede escribir en el directorio: {$directorio}");
                return false;
            }

            $log = date('Y-m-d H:i:s') . ' - ' . $mensaje;
            $fp = fopen($archivo, 'a');

            if (!$fp) {
                error_log("Logger: No se pudo abrir el archivo: {$archivo}");
                return false;
            }

            if (flock($fp, LOCK_EX)) {
                fwrite($fp, $log . PHP_EOL);

                // Auto-limpieza con probabilidad configurada
                $probabilidad = defined('LOG_CLEANUP_PROBABILITY') ? LOG_CLEANUP_PROBABILITY : 10000;
                if (rand(1, $probabilidad) === 1) {
                    $this->autoLimpiarArchivo($archivo, $maxLineas);
                }

                flock($fp, LOCK_UN);
            } else {
                error_log("Logger: No se pudo obtener el bloqueo del archivo: {$archivo}");
                fclose($fp);
                return false;
            }

            fclose($fp);
            return true;
        } catch (Exception $e) {
            error_log("Logger: Excepción capturada: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Auto-limpiar archivo si excede el tamaño máximo.
     *
     * @param string $archivo   Ruta del archivo.
     * @param int    $maxLineas Número máximo de líneas a mantener.
     * @return void
     */
    private function autoLimpiarArchivo(string $archivo, int $maxLineas): void
    {
        if (!file_exists($archivo)) {
            return;
        }

        // Verificar tamaño del archivo
        $maxSizeMb = defined('LOG_MAX_FILE_SIZE_MB') ? LOG_MAX_FILE_SIZE_MB : 5;
        $tamanoMb = filesize($archivo) / (1024 * 1024);

        if ($tamanoMb < $maxSizeMb) {
            return;
        }

        // Mantener solo las últimas N líneas
        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (count($lineas) > $maxLineas) {
            $lineas = array_slice($lineas, -$maxLineas);
            file_put_contents($archivo, implode(PHP_EOL, $lineas) . PHP_EOL);
        }
    }

    // =========================================================================
    // MÉTODOS DE NIVEL - API PRINCIPAL RECOMENDADA
    // =========================================================================

    /**
     * Log de nivel DEBUG.
     *
     * @param string $canal   Canal de log.
     * @param mixed  $mensaje Mensaje a registrar.
     * @param array  $contexto Datos adicionales.
     * @return bool
     */
    public function debug(string $canal, $mensaje, array $contexto = []): bool
    {
        return $this->log($canal, LOG_LEVEL_DEBUG, $mensaje, $contexto);
    }

    /**
     * Log de nivel INFO.
     *
     * @param string $canal   Canal de log.
     * @param mixed  $mensaje Mensaje a registrar.
     * @param array  $contexto Datos adicionales.
     * @return bool
     */
    public function info(string $canal, $mensaje, array $contexto = []): bool
    {
        return $this->log($canal, LOG_LEVEL_INFO, $mensaje, $contexto);
    }

    /**
     * Log de nivel WARNING.
     *
     * @param string $canal   Canal de log.
     * @param mixed  $mensaje Mensaje a registrar.
     * @param array  $contexto Datos adicionales.
     * @return bool
     */
    public function warning(string $canal, $mensaje, array $contexto = []): bool
    {
        return $this->log($canal, LOG_LEVEL_WARNING, $mensaje, $contexto);
    }

    /**
     * Log de nivel ERROR.
     *
     * @param string $canal   Canal de log.
     * @param mixed  $mensaje Mensaje a registrar.
     * @param array  $contexto Datos adicionales.
     * @return bool
     */
    public function error(string $canal, $mensaje, array $contexto = []): bool
    {
        return $this->log($canal, LOG_LEVEL_ERROR, $mensaje, $contexto);
    }

    /**
     * Log de nivel CRITICAL.
     *
     * @param string $canal   Canal de log.
     * @param mixed  $mensaje Mensaje a registrar.
     * @param array  $contexto Datos adicionales.
     * @return bool
     */
    public function critical(string $canal, $mensaje, array $contexto = []): bool
    {
        return $this->log($canal, LOG_LEVEL_CRITICAL, $mensaje, $contexto);
    }

    // =========================================================================
    // MÉTODOS DE CANAL - COMPATIBILIDAD CON CÓDIGO EXISTENTE
    // =========================================================================

    /**
     * Log de streaming de audio.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function stream($mensaje, int $nivel = 1): void
    {
        $this->log('stream', $nivel, $mensaje);
    }

    /**
     * Log de SEO.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function seo($mensaje, int $nivel = 1): void
    {
        $this->log('seo', $nivel, $mensaje);
    }

    /**
     * Log de audio.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function audio($mensaje, int $nivel = 1): void
    {
        $this->log('audio', $nivel, $mensaje);
    }

    /**
     * Log de rendimiento.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function rendimiento($mensaje, int $nivel = 1): void
    {
        $this->log('rendimiento', $nivel, $mensaje);
    }

    /**
     * Log de chat.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function chat($mensaje, int $nivel = 1): void
    {
        $this->log('chat', $nivel, $mensaje);
    }

    /**
     * Log de errores de Stripe.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto WARNING).
     * @return void
     */
    public function stripe($mensaje, int $nivel = 2): void
    {
        $this->log('stripe', $nivel, $mensaje);
    }

    /**
     * Log de posts automáticos.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function automatico($mensaje, int $nivel = 1): void
    {
        $this->log('automatico', $nivel, $mensaje);
    }

    /**
     * Log general de guardado.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function guardar($mensaje, int $nivel = 1): void
    {
        $this->log('guardar', $nivel, $mensaje);
    }

    /**
     * Log de algoritmo.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto DEBUG).
     * @return void
     */
    public function algoritmo($mensaje, int $nivel = 0): void
    {
        $this->log('algoritmo', $nivel, $mensaje);
    }

    /**
     * Log de AJAX para posts.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto DEBUG).
     * @return void
     */
    public function ajaxPost($mensaje, int $nivel = 0): void
    {
        $this->log('ajaxPost', $nivel, $mensaje);
    }

    /**
     * Log de IA.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function ia($mensaje, int $nivel = 1): void
    {
        $this->log('ia', $nivel, $mensaje);
    }

    /**
     * Log de posts.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto DEBUG).
     * @return void
     */
    public function post($mensaje, int $nivel = 0): void
    {
        $this->log('post', $nivel, $mensaje);
    }

    /**
     * Log de refactorización.
     * 
     * Canal específico para registrar cambios durante la refactorización.
     *
     * @param mixed $mensaje Mensaje a registrar.
     * @param int   $nivel   Nivel del log (por defecto INFO).
     * @return void
     */
    public function refactor($mensaje, int $nivel = 1): void
    {
        $this->log('refactor', $nivel, $mensaje);
    }
}

// =========================================================================
// FUNCIONES WRAPPER PARA COMPATIBILIDAD CON CÓDIGO EXISTENTE
// =========================================================================

/**
 * Función wrapper para escribirLog (compatibilidad).
 *
 * @param mixed  $mensaje   Mensaje a registrar.
 * @param string $archivo   Ruta del archivo de log.
 * @param int    $maxLineas Número máximo de líneas.
 * @return bool
 */
function escribirLog($mensaje, $archivo = '', $maxLineas = 10000)
{
    return Logger::obtenerInstancia()->escribir($mensaje, $archivo, $maxLineas);
}

/**
 * Función wrapper para streamLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function streamLog($log)
{
    Logger::obtenerInstancia()->stream($log);
}

/**
 * Función wrapper para seoLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function seoLog($log)
{
    Logger::obtenerInstancia()->seo($log);
}

/**
 * Función wrapper para logAudio (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function logAudio($log)
{
    Logger::obtenerInstancia()->audio($log);
}

/**
 * Función wrapper para rendimientolog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function rendimientolog($log)
{
    Logger::obtenerInstancia()->rendimiento($log);
}

/**
 * Función wrapper para chatLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function chatLog($log)
{
    Logger::obtenerInstancia()->chat($log);
}

/**
 * Función wrapper para stripeError (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function stripeError($log)
{
    Logger::obtenerInstancia()->stripe($log);
}

/**
 * Función wrapper para autLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function autLog($log)
{
    Logger::obtenerInstancia()->automatico($log);
}

/**
 * Función wrapper para guardarLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function guardarLog($log)
{
    Logger::obtenerInstancia()->guardar($log);
}

/**
 * Función wrapper para logAlgoritmo (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function logAlgoritmo($log)
{
    Logger::obtenerInstancia()->algoritmo($log);
}

/**
 * Función wrapper para ajaxPostLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function ajaxPostLog($log)
{
    Logger::obtenerInstancia()->ajaxPost($log);
}

/**
 * Función wrapper para iaLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function iaLog($log)
{
    Logger::obtenerInstancia()->ia($log);
}

/**
 * Función wrapper para postLog (compatibilidad).
 *
 * @param mixed $log Mensaje a registrar.
 * @return void
 */
function postLog($log)
{
    Logger::obtenerInstancia()->post($log);
}

/**
 * Función helper para log de refactorización.
 * 
 * Uso: refactorLog("Migrado módulo X a inc/Setup/");
 *
 * @param mixed $log   Mensaje a registrar.
 * @param int   $nivel Nivel del log.
 * @return void
 */
function refactorLog($log, $nivel = 1)
{
    Logger::obtenerInstancia()->refactor($log, $nivel);
}
