<?php

namespace Kamples\Services\Audio;

/**
 * Servicio de calculo y comparacion de hashes.
 * 
 * Responsabilidad unica: generar y comparar hashes de archivos de audio.
 *
 * @since 1.0.0
 */
class HashCalculoService
{
    private static ?HashCalculoService $instancia = null;
    private \Logger $logger;

    private const HASH_SIMILARITY_THRESHOLD = 0.7;
    private const WRAPPER_SCRIPT_PATH = '/var/www/wordpress/wp-content/themes/2upra3v/app/Commands/process_audio.sh';
    private const PERMISOS_SCRIPT_PATH = '/var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh';

    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Compara dos hashes para determinar si son similares
     *
     * @param string $hash1 Primer hash
     * @param string $hash2 Segundo hash
     * @param float $umbral Umbral de similitud (0-1)
     * @return bool True si son similares
     */
    public function sonHashesSimilares(string $hash1, string $hash2, float $umbral = self::HASH_SIMILARITY_THRESHOLD): bool
    {
        if (empty($hash1) || empty($hash2)) {
            return false;
        }

        $bin1 = @hex2bin($hash1);
        $bin2 = @hex2bin($hash2);

        if ($bin1 === false || $bin2 === false) {
            return false;
        }

        $similitud = 1 - (count(array_diff_assoc(str_split($bin1), str_split($bin2))) / strlen($bin1));

        return $similitud >= $umbral;
    }

    /**
     * Compara dos hashes usando distancia euclidiana
     *
     * @param string $hash1 Primer hash
     * @param string $hash2 Segundo hash
     * @param float $umbral Umbral de similitud
     * @return bool True si son similares
     */
    public function sonHashesSimilaresEuclidean(string $hash1, string $hash2, float $umbral = 0.85): bool
    {
        if (empty($hash1) || empty($hash2)) {
            return false;
        }

        $valores1 = array_map('hexdec', str_split($hash1, 2));
        $valores2 = array_map('hexdec', str_split($hash2, 2));

        if (count($valores1) !== count($valores2)) {
            return false;
        }

        $sumaDiferenciasCuadradas = 0;
        $maxDiferencia = 255;

        for ($i = 0; $i < count($valores1); $i++) {
            $diferencia = abs($valores1[$i] - $valores2[$i]);
            $sumaDiferenciasCuadradas += pow($diferencia, 2);
        }

        $distancia = sqrt($sumaDiferenciasCuadradas);
        $similitud = 1 - ($distancia / (sqrt(count($valores1)) * $maxDiferencia));

        return $similitud >= $umbral;
    }

    /**
     * Recalcula el hash de un archivo de audio usando Python
     *
     * @param string $audioFilePath Ruta del archivo de audio
     * @return string|false Hash calculado o false en error
     */
    public function recalcularHash(string $audioFilePath)
    {
        try {
            if (!is_string($audioFilePath) || empty($audioFilePath)) {
                throw new \Exception("Ruta de archivo invalida: " . $audioFilePath);
            }

            if (!file_exists($audioFilePath)) {
                throw new \Exception("Archivo no encontrado: " . $audioFilePath);
            }

            if (!is_readable($audioFilePath)) {
                $this->ejecutarScriptPermisos();
                throw new \Exception("No hay permisos de lectura para el archivo: " . $audioFilePath);
            }

            if (!file_exists(self::WRAPPER_SCRIPT_PATH)) {
                throw new \Exception("Script wrapper no encontrado en: " . self::WRAPPER_SCRIPT_PATH);
            }

            if (!is_executable(self::WRAPPER_SCRIPT_PATH)) {
                throw new \Exception("Script wrapper no tiene permisos de ejecucion");
            }

            $command = escapeshellarg(self::WRAPPER_SCRIPT_PATH) . ' ' . escapeshellarg($audioFilePath);

            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (!is_resource($process)) {
                throw new \Exception("No se pudo iniciar el proceso");
            }

            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);

            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            $returnValue = proc_close($process);

            if ($returnValue !== 0) {
                throw new \Exception("Error en el proceso Python: " . $error);
            }

            $hash = trim($output);
            if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
                throw new \Exception("Hash invalido generado: " . $output);
            }

            return $hash;
        } catch (\Exception $e) {
            $this->logger->error('hash', 'Error recalculando hash', ['error' => $e->getMessage()]);
            $this->ejecutarScriptPermisos();
            return false;
        }
    }

    /**
     * Genera un nombre unico para archivos
     */
    public function nombreUnicoFile(string $dir, string $name, string $ext): string
    {
        return basename($name, $ext) . $ext;
    }

    /**
     * Ejecuta el script de permisos
     */
    private function ejecutarScriptPermisos(): void
    {
        if (file_exists(self::PERMISOS_SCRIPT_PATH)) {
            @shell_exec('sudo ' . self::PERMISOS_SCRIPT_PATH . ' 2>&1');
        }
    }
}
