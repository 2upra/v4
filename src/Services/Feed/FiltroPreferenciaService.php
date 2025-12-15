<?php

namespace Kamples\Services\Feed;

/**
 * Servicio de gestion de preferencias de filtros del usuario.
 * 
 * Responsabilidad unica: CRUD de preferencias de filtros en user_meta.
 *
 * @since 1.0.0
 */
class FiltroPreferenciaService
{
    private static ?FiltroPreferenciaService $instancia = null;

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene el filtro de tiempo actual del usuario.
     *
     * @param int $userId ID del usuario
     * @return array Datos del filtro
     */
    public function obtenerFiltroActual(int $userId): array
    {
        $filtroTiempo = intval(get_user_meta($userId, 'filtroTiempo', true) ?: 0);
        $nombresFiltros = ['Feed', 'Reciente', 'Semanal', 'Mensual'];

        return [
            'filtroTiempo' => $filtroTiempo,
            'nombreFiltro' => $nombresFiltros[$filtroTiempo] ?? 'Feed'
        ];
    }

    /**
     * Guarda el filtro de tiempo del usuario.
     *
     * @param int $userId ID del usuario
     * @param int $filtroTiempo Valor del filtro
     * @return bool
     */
    public function guardarFiltroTiempo(int $userId, int $filtroTiempo): bool
    {
        return (bool)update_user_meta($userId, 'filtroTiempo', $filtroTiempo);
    }

    /**
     * Guarda los filtros de post del usuario.
     *
     * @param int $userId ID del usuario
     * @param array $filtros Filtros a guardar
     * @return bool
     */
    public function guardarFiltroPost(int $userId, array $filtros): bool
    {
        return (bool)update_user_meta($userId, 'filtroPost', $filtros);
    }

    /**
     * Obtiene los filtros de post del usuario.
     *
     * @param int $userId ID del usuario
     * @return array
     */
    public function obtenerFiltros(int $userId): array
    {
        $filtros = get_user_meta($userId, 'filtroPost', true);
        return is_array($filtros) ? $filtros : [];
    }

    /**
     * Obtiene todos los filtros del usuario.
     *
     * @param int $userId ID del usuario
     * @return array
     */
    public function obtenerFiltrosTotal(int $userId): array
    {
        return [
            'filtroPost' => $this->obtenerFiltros($userId),
            'filtroTiempo' => get_user_meta($userId, 'filtroTiempo', true) ?: 0,
        ];
    }

    /**
     * Restablece los filtros del usuario.
     *
     * @param int $userId ID del usuario
     * @param bool $restablecerPost Si restablecer filtros de post
     * @param bool $restablecerColeccion Si restablecer filtros de coleccion
     * @return bool
     */
    public function restablecerFiltros(int $userId, bool $restablecerPost = false, bool $restablecerColeccion = false): bool
    {
        $filtroPost = get_user_meta($userId, 'filtroPost', true);

        if (is_string($filtroPost)) {
            $filtroPostArray = @unserialize($filtroPost);
            if ($filtroPostArray === false && $filtroPost !== 'b:0;') {
                $filtroPostArray = [];
            }
        } elseif (is_array($filtroPost)) {
            $filtroPostArray = $filtroPost;
        } else {
            $filtroPostArray = [];
        }

        if ($restablecerPost) {
            $filtrosAEliminar = ['misPost', 'mostrarMeGustan', 'ocultarEnColeccion', 'ocultarDescargados'];
            if (is_array($filtroPostArray)) {
                $filtroPostArray = array_values(array_filter($filtroPostArray, function ($filtro) use ($filtrosAEliminar) {
                    return !in_array($filtro, $filtrosAEliminar);
                }));
            }
        }

        if ($restablecerColeccion) {
            if (is_array($filtroPostArray)) {
                $filtroPostArray = array_values(array_filter($filtroPostArray, function ($filtro) {
                    return $filtro !== 'misColecciones';
                }));
            }
        }

        if (empty($filtroPostArray)) {
            delete_user_meta($userId, 'filtroPost');
        } else {
            update_user_meta($userId, 'filtroPost', $filtroPostArray);
        }

        delete_user_meta($userId, 'filtroTiempo');

        return true;
    }
}
