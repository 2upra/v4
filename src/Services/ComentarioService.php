<?php

/**
 * ComentarioService - Wrapper Deprecated
 * 
 * @deprecated Usar Kamples\Services\Social\ComentarioService
 * @package Kamples\Services
 */

namespace Kamples\Services;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

use Kamples\Services\Social\ComentarioService as NuevoComentarioService;

class ComentarioService
{
    private static ?ComentarioService $instancia = null;
    private NuevoComentarioService $servicio;

    private function __construct()
    {
        $this->servicio = NuevoComentarioService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function usuarioPuedeComentario(int $userId): bool
    {
        return $this->servicio->usuarioPuedeComentario($userId);
    }

    public function incrementarContadorComentarios(int $userId): void
    {
        $this->servicio->incrementarContadorComentarios($userId);
    }

    public function validarDatosComentario(array $datos): array
    {
        return $this->servicio->validarDatosComentario($datos);
    }

    public function crearComentario(array $datos): array
    {
        return $this->servicio->crearComentario($datos);
    }

    public function eliminarComentario(int $comentarioId, int $userId): array
    {
        return $this->servicio->eliminarComentario($comentarioId, $userId);
    }

    public function obtenerComentariosPost(int $postId, int $pagina = 1): array
    {
        return $this->servicio->obtenerComentariosPost($postId, $pagina);
    }

    public function formatearComentario(\WP_Post $comentario): array
    {
        return $this->servicio->formatearComentario($comentario);
    }
}
