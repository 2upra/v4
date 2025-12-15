<?php

namespace Kamples\Services\Contenido;

/**
 * Servicio de Inteligencia Artificial.
 * 
 * Maneja la comunicación con la API de Gemini para generar descripciones.
 *
 * @since 1.0.0
 */
class IAService
{
    private \Logger $logger;
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    private string $uploadUrl = 'https://generativelanguage.googleapis.com/v1beta/media:upload';

    public function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
        $this->apiKey = $_ENV['API_KEY'] ?? '';
    }

    /**
     * Genera una descripción usando IA con un URI de audio.
     *
     * @param string $audioUri URI del archivo de audio en la API de Google.
     * @param string $prompt Prompt para la generación.
     * @return string|false Texto generado o false en caso de error.
     */
    public function generarDescripcionConUri(string $audioUri, string $prompt): string|false
    {
        $this->logger->info('ia', "Generando descripción IA con prompt: {$prompt} y URI: {$audioUri}");

        try {
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => 'audio/mp3',
                                    'uri' => $audioUri
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->hacerPeticion($this->baseUrl, $data);

            if ($response === false) {
                return false;
            }

            if (isset($response['contents'][0]['parts'][0]['text'])) {
                $textoGenerado = $response['contents'][0]['parts'][0]['text'];
                $this->logger->info('ia', "Contenido generado: {$textoGenerado}");
                return $textoGenerado;
            }

            $this->logger->error('ia', 'Respuesta inesperada de la API', ['respuesta' => $response]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('ia', 'Error en generarDescripcionConUri', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Sube un archivo de audio a la API de Google.
     *
     * @param string $archivoPath Ruta local del archivo de audio.
     * @return string|false URI del archivo subido o false en caso de error.
     */
    public function subirArchivo(string $archivoPath): string|false
    {
        $this->logger->info('ia', "Subiendo archivo: {$archivoPath}");

        try {
            if (!file_exists($archivoPath)) {
                $this->logger->error('ia', "Archivo no encontrado: {$archivoPath}");
                return false;
            }

            $audioData = file_get_contents($archivoPath);
            $this->logger->info('ia', 'Archivo de audio cargado con éxito.');

            $data = [
                'file' => [
                    'mimeType' => 'audio/mp3',
                    'data' => base64_encode($audioData)
                ]
            ];

            $url = "{$this->uploadUrl}?key={$this->apiKey}";
            $response = $this->hacerPeticion($url, $data, false);

            if ($response === false) {
                return false;
            }

            if (isset($response['uri'])) {
                $this->logger->info('ia', "Archivo subido exitosamente. URI: {$response['uri']}");
                return $response['uri'];
            }

            $this->logger->error('ia', 'Respuesta inesperada durante la subida del archivo', ['respuesta' => $response]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('ia', 'Error en subirArchivo', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Genera una descripción usando IA con un archivo local de audio.
     *
     * @param string $archivoPath Ruta local del archivo de audio.
     * @param string $prompt Prompt para la generación.
     * @return string|false Texto generado o false en caso de error.
     */
    public function generarDescripcion(string $archivoPath, string $prompt): string|false
    {
        $this->logger->info('ia', "Inicio de generarDescripcion con prompt: {$prompt}");
        $this->logger->info('ia', "Archivo de audio: {$archivoPath}");

        try {
            if (!file_exists($archivoPath)) {
                $this->logger->error('ia', "Archivo no encontrado: {$archivoPath}");
                return false;
            }

            $audioData = file_get_contents($archivoPath);
            $audioBase64 = base64_encode($audioData);
            $this->logger->info('ia', 'Archivo de audio cargado y convertido a base64 con éxito.');

            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => 'audio/mp3',
                                    'data' => $audioBase64
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->hacerPeticion($this->baseUrl, $data);

            if ($response === false) {
                return false;
            }

            $this->logger->debug('ia', 'Respuesta completa de la API', ['respuesta' => $response]);

            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $textoGenerado = $response['candidates'][0]['content']['parts'][0]['text'];
                $this->logger->info('ia', "Contenido generado: {$textoGenerado}");
                return $textoGenerado;
            }

            $this->logger->error('ia', 'Respuesta inesperada de la API', ['respuesta' => $response]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('ia', 'Error en generarDescripcion', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Genera una descripción usando IA Pro (modelo optimizado).
     *
     * @param string $archivoPath Ruta local del archivo de audio.
     * @param string $prompt Prompt para la generación.
     * @return string|false Texto generado o false en caso de error.
     */
    public function generarDescripcionPro(string $archivoPath, string $prompt): string|false
    {
        $this->logger->info('ia', "Inicio de generarDescripcionPro con prompt: {$prompt}");
        return $this->generarDescripcion($archivoPath, $prompt);
    }

    /**
     * Realiza una petición HTTP POST a la API.
     *
     * @param string $url URL de la API.
     * @param array $data Datos a enviar.
     * @param bool $appendKey Si debe agregar la API key a la URL.
     * @return array|false Respuesta decodificada o false en caso de error.
     */
    private function hacerPeticion(string $url, array $data, bool $appendKey = true): array|false
    {
        if ($appendKey) {
            $url .= "?key={$this->apiKey}";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->logger->error('ia', 'Error en CURL', ['error' => curl_error($ch)]);
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        return json_decode($response, true);
    }
}
