<?php

namespace Kamples\Services;

/**
 * Servicio para ejecutar scripts de Python.
 * 
 * @since 1.0.0
 */
class PythonService
{
    private \Logger $logger;
    private string $pythonPath;
    private string $scriptsPath;

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        // Ajustar rutas según el entorno si es necesario
        $this->pythonPath = 'python3';
        // Se asume que los scripts de python están en app/python/ por ahora, 
        // o deberíamos moverlos a inc/Scripts/python? 
        // El código legacy usa /var/www/wordpress/wp-content/themes/2upra3v/app/python/
        // Vamos a mantener la compatibilidad con la ubicación actual o idealmente moverlos.
        // Por ahora usaremos una constante o ruta relativa al theme.
        $this->scriptsPath = get_template_directory() . '/app/python/';
    }

    /**
     * Procesa un archivo de audio usando el script python audio.py.
     *
     * @param string $rutaArchivo Ruta absoluta al archivo de audio.
     * @return array|null Resultados del análisis o null si falla.
     */
    public function procesarAudio(string $rutaArchivo): ?array
    {
        $script = $this->scriptsPath . 'audio.py';
        if (!file_exists($script)) {
            $this->logger->error('python', "Script no encontrado: {$script}");
            return null;
        }

        $command = escapeshellcmd("{$this->pythonPath} \"{$script}\" \"{$rutaArchivo}\"");
        $this->logger->info('python', "Ejecutando comando: {$command}");

        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            $this->logger->error('python', "Error ejecutando script. Código: {$return_var}. Salida: " . implode("\n", $output));
            return null;
        }

        // Buscar archivo de resultados
        $resultadosPath = "{$rutaArchivo}_resultados.json";
        if (!file_exists($resultadosPath)) {
            $this->logger->error('python', "Archivo de resultados no encontrado: {$resultadosPath}");
            return null;
        }

        $jsonContent = file_get_contents($resultadosPath);
        $resultados = json_decode($jsonContent, true);

        if (!$resultados || !is_array($resultados)) {
            $this->logger->error('python', "JSON inválido en: {$resultadosPath}");
            return null;
        }

        // Limpiar archivo de resultados
        @unlink($resultadosPath);

        $camposEsperados = ['bpm', 'pitch', 'emotion', 'key', 'scale', 'strength'];
        $datos = [];

        foreach ($camposEsperados as $campo) {
            $datos[$campo] = $resultados[$campo] ?? null;
        }

        return $datos;
    }
}
