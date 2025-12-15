<?php

namespace Kamples\Services\Feed;

/**
 * Servicio fachada de gestion de filtros para posts.
 * 
 * Orquesta los servicios especializados:
 * - FiltroAplicacionService: Aplicacion de filtros a queries
 * - FiltroCondicionService: Definiciones de condiciones meta_query
 * - FiltroPreferenciaService: Gestion de preferencias del usuario
 *
 * @since 1.0.0
 */
class FiltroService
{
    private static ?FiltroService $instancia = null;
    private FiltroAplicacionService $aplicacionService;
    private FiltroCondicionService $condicionService;
    private FiltroPreferenciaService $preferenciaService;

    public function __construct()
    {
        $this->aplicacionService = FiltroAplicacionService::obtenerInstancia();
        $this->condicionService = FiltroCondicionService::obtenerInstancia();
        $this->preferenciaService = FiltroPreferenciaService::obtenerInstancia();
    }

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /* 
     * Metodos de aplicacion de filtros (delegados a FiltroAplicacionService)
     */

    public function aplicarFiltroGlobal(
        array $queryArgs,
        array $args,
        int $usuarioActual,
        ?int $userId = null,
        ?string $tipoUsuario = null
    ): array {
        return $this->aplicacionService->aplicarFiltroGlobal($queryArgs, $args, $usuarioActual, $userId, $tipoUsuario);
    }

    public function aplicarFiltroPorAutor(array $queryArgs, int $userId, string $filtro): array
    {
        return $this->aplicacionService->aplicarFiltroPorAutor($queryArgs, $userId, $filtro);
    }

    public function aplicarFiltrosDeUsuario(array $queryArgs, int $usuarioId, string $filtro): array
    {
        return $this->aplicacionService->aplicarFiltrosDeUsuario($queryArgs, $usuarioId, $filtro);
    }

    public function aplicarCondicionesDeMetaQuery(
        array $queryArgs,
        string $filtro,
        int $usuarioActual,
        ?string $tipoUsuario = null
    ): array {
        return $this->aplicacionService->aplicarCondicionesDeMetaQuery($queryArgs, $filtro, $usuarioActual, $tipoUsuario);
    }

    /* 
     * Metodos de condiciones (delegados a FiltroCondicionService)
     */

    public function obtenerCondicionesMetaQuery(int $usuarioActual, ?string $tipoUsuario = null): array
    {
        return $this->condicionService->obtenerCondicionesMetaQuery($usuarioActual, $tipoUsuario);
    }

    /* 
     * Metodos de preferencias (delegados a FiltroPreferenciaService)
     */

    public function obtenerFiltroActual(int $userId): array
    {
        return $this->preferenciaService->obtenerFiltroActual($userId);
    }

    public function guardarFiltroTiempo(int $userId, int $filtroTiempo): bool
    {
        return $this->preferenciaService->guardarFiltroTiempo($userId, $filtroTiempo);
    }

    public function guardarFiltroPost(int $userId, array $filtros): bool
    {
        return $this->preferenciaService->guardarFiltroPost($userId, $filtros);
    }

    public function obtenerFiltros(int $userId): array
    {
        return $this->preferenciaService->obtenerFiltros($userId);
    }

    public function obtenerFiltrosTotal(int $userId): array
    {
        return $this->preferenciaService->obtenerFiltrosTotal($userId);
    }

    public function restablecerFiltros(int $userId, bool $restablecerPost = false, bool $restablecerColeccion = false): bool
    {
        return $this->preferenciaService->restablecerFiltros($userId, $restablecerPost, $restablecerColeccion);
    }
}
