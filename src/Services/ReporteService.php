<?php

namespace Kamples\Services;

/**
 * Servicio de gestión de reportes.
 * 
 * Maneja la creación y gestión de reportes de contenido
 * por parte de los usuarios.
 *
 * @since 1.0.0
 */
class ReporteService
{
    private static ?ReporteService $instancia = null;
    private ?\Logger $logger = null;

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        $this->logger = \Logger::obtenerInstancia();
    }

    /**
     * Obtiene la instancia singleton del servicio.
     *
     * @return self
     */
    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Guarda un reporte de contenido.
     *
     * @param int $userId ID del usuario que reporta
     * @param int $idContenido ID del contenido reportado
     * @param string $tipoContenido Tipo de contenido ('post', 'comentario')
     * @param string $detalles Detalles del reporte
     * @return array ['success' => bool, 'message' => string, 'reporte_id' => int|null]
     */
    public function guardarReporte(
        int $userId,
        int $idContenido,
        string $tipoContenido,
        string $detalles
    ): array {
        if (!$userId) {
            return ['success' => false, 'message' => 'Usuario no autenticado.', 'reporte_id' => null];
        }

        /* Construir título del reporte */
        $userData = get_userdata($userId);
        $userName = $userData ? $userData->display_name : 'Usuario desconocido';
        $postTitle = "Reporte de " . $userName;

        if ($tipoContenido === 'comentario') {
            $comentarioTitle = get_the_title($idContenido);
            $comentarioTitleShort = wp_trim_words($comentarioTitle, 10, '...');
            $postTitle .= " sobre el comentario: " . $comentarioTitleShort;
        } else {
            $postTitle .= " sobre la publicación ID: " . $idContenido;
        }

        /* Crear el post del reporte */
        $reporteId = wp_insert_post([
            'post_title'   => $postTitle,
            'post_content' => $detalles,
            'post_status'  => 'publish',
            'post_type'    => 'reporte',
            'post_author'  => $userId,
        ]);

        if (is_wp_error($reporteId)) {
            $this->log('error', 'Error al crear reporte: ' . $reporteId->get_error_message());
            return [
                'success' => false,
                'message' => 'Error al crear el reporte: ' . $reporteId->get_error_message(),
                'reporte_id' => null
            ];
        }

        /* Guardar metadatos del reporte */
        update_post_meta($reporteId, 'idContenido', $idContenido);
        update_post_meta($reporteId, 'tipoContenido', $tipoContenido);

        $this->log('info', "Reporte creado con ID: $reporteId por usuario: $userId");

        return [
            'success' => true,
            'message' => 'Reporte guardado con ID: ' . $reporteId,
            'reporte_id' => $reporteId
        ];
    }

    /**
     * Obtiene los reportes de un contenido específico.
     *
     * @param int $idContenido ID del contenido
     * @return array Reportes encontrados
     */
    public function obtenerReportesDeContenido(int $idContenido): array
    {
        $args = [
            'post_type'   => 'reporte',
            'post_status' => 'publish',
            'meta_query'  => [
                [
                    'key'   => 'idContenido',
                    'value' => $idContenido,
                ]
            ],
            'posts_per_page' => -1,
        ];

        return get_posts($args);
    }

    /**
     * Cuenta los reportes de un contenido específico.
     *
     * @param int $idContenido ID del contenido
     * @return int Número de reportes
     */
    public function contarReportesDeContenido(int $idContenido): int
    {
        $reportes = $this->obtenerReportesDeContenido($idContenido);
        return count($reportes);
    }

    /**
     * Registra un mensaje en el log.
     *
     * @param string $nivel Nivel del log
     * @param string $mensaje Mensaje
     * @return void
     */
    private function log(string $nivel, string $mensaje): void
    {
        if ($this->logger) {
            $this->logger->$nivel('post', $mensaje);
        }
    }
}
