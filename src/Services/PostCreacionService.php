<?php

/**
 * @deprecated Usar Kamples\Services\Publicacion\PostCreacionService en su lugar.
 * Este archivo se mantiene por compatibilidad. Será eliminado en futuras versiones.
 */

namespace Kamples\Services;

use Kamples\Services\Publicacion\PostCreacionService as NewPostCreacionService;

class PostCreacionService
{
    private static ?PostCreacionService $instancia = null;
    private NewPostCreacionService $servicio;

    private function __construct()
    {
        $this->servicio = NewPostCreacionService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function crearPost(string $tipoPost = 'social_post', string $estadoPost = 'publish')
    {
        return $this->servicio->crearPost($tipoPost, $estadoPost);
    }

    public function actualizarMetaDatos(int $postId): void
    {
        $this->servicio->actualizarMetaDatos($postId);
    }

    public function datosParaAlgoritmo(int $postId): void
    {
        $this->servicio->datosParaAlgoritmo($postId);
    }

    public function confirmarArchivos(int $postId): void
    {
        $this->servicio->confirmarArchivos($postId);
    }

    public function procesarURLs(int $postId): void
    {
        $this->servicio->procesarURLs($postId);
    }

    public function asignarTags(int $postId): void
    {
        $this->servicio->asignarTags($postId);
    }
}
