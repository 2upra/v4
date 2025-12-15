<?php

/**
 * @deprecated Usar Kamples\Services\Contenido\NormalizacionService
 * Este wrapper existe por compatibilidad. Migrar a la nueva ubicación.
 */

namespace Kamples\Services;

class NormalizacionService
{
    private static ?NormalizacionService $instancia = null;
    private \Kamples\Services\Contenido\NormalizacionService $servicio;

    private function __construct()
    {
        $this->servicio = \Kamples\Services\Contenido\NormalizacionService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function normalizarNuevoPost(int $postId, \WP_Post $post, bool $update): void
    {
        $this->servicio->normalizarNuevoPost($postId, $post, $update);
    }

    public function normalizarPostActualizado(int $postId, \WP_Post $postAfter, \WP_Post $postBefore): void
    {
        $this->servicio->normalizarPostActualizado($postId, $postAfter, $postBefore);
    }

    public function verificarYRestaurarDatos(int $postId): void
    {
        $this->servicio->verificarYRestaurarDatos($postId);
    }

    public function verificarRestauracion(): void
    {
        $this->servicio->verificarRestauracion();
    }

    public function restaurarDatosAlgoritmo(): void
    {
        $this->servicio->restaurarDatosAlgoritmo();
    }

    public function crearRespaldoYNormalizar(int $batchSize = 100): int
    {
        return $this->servicio->crearRespaldoYNormalizar($batchSize);
    }

    public function revertirNormalizacion(int $batchSize = 100): int
    {
        return $this->servicio->revertirNormalizacion($batchSize);
    }
}
