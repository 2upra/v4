<?php

/**
 * Componentes de visualización de filtros
 * 
 * Renderiza los elementos de UI para filtros de posts
 *
 * @package Kamples\Views\Components
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

class FiltroComponents
{
    /**
     * Renderiza el menú de filtros para la lista de samples
     *
     * @return string HTML del componente de filtros
     */
    public static function renderFiltroSampleList(): string
    {
        $userId = get_current_user_id();
        $filtroTiempo = get_user_meta($userId, 'filtroTiempo', true);
        $filtroTiempo = $filtroTiempo === '' ? 0 : intval($filtroTiempo);

        $nombresFiltros = [
            0 => 'Feed',
            1 => 'Reciente',
            2 => 'Semanal',
            3 => 'Mensual'
        ];

        $nombreFiltro = $nombresFiltros[$filtroTiempo] ?? 'Feed';

        $iconoFlechaArribaAbajo = $GLOBALS['iconoflechaArriAba'] ?? '';
        $iconoFlechaAbajo = $GLOBALS['flechaAbajo'] ?? '';

        ob_start();
?>
        <div class="OPCDGED" id="filtro-sample-list-container">
            <button class="restablecerBusqueda postRestablecer" style="display: none;">Restablecer filtros</button>
            <button class="filtrosboton">
                <?= esc_html($nombreFiltro) . ' ' . $iconoFlechaArribaAbajo; ?>
            </button>

            <div class="A1806241" id="filtrosMenu-default">
                <div class="A1806242">
                    <button class="filtroFeed <?= ($filtroTiempo == 0 || $filtroTiempo === '') ? 'filtroSelec' : ''; ?>">Para mí</button>
                    <button class="filtroReciente <?= ($filtroTiempo == 1) ? 'filtroSelec' : ''; ?>">Recientes</button>
                    <button class="filtroSemanal <?= ($filtroTiempo == 2) ? 'filtroSelec' : ''; ?>">Top Semanal</button>
                    <button class="filtroMensual <?= ($filtroTiempo == 3) ? 'filtroSelec' : ''; ?>">Top Mensual</button>
                </div>
            </div>

            <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<?= $iconoFlechaAbajo; ?></button>

            <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">
                <?= self::renderOpcionesFiltro(); ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza las opciones de filtro (checkboxes)
     *
     * @return string HTML de las opciones
     */
    private static function renderOpcionesFiltro(): string
    {
        ob_start();
    ?>
        <div class="opcionCheck">
            <div>
                <label>Ocultar descargados</label>
                <p class="description">No verás samples ya descargados.</p>
            </div>
            <label class="switch">
                <input type="checkbox" name="ocultarDescargados" id="ocultarDescargados">
                <span class="slider"></span>
            </label>
        </div>

        <div class="opcionCheck">
            <div>
                <label>Ocultar en colecciones</label>
                <p class="description">Se excluyen samples en tus colecciones.</p>
            </div>
            <label class="switch">
                <input type="checkbox" name="ocultarEnColeccion" id="ocultarEnColeccion">
                <span class="slider"></span>
            </label>
        </div>

        <div class="opcionCheck">
            <div>
                <label>Mostrar solo con mi like</label>
                <p class="description">Verás solo samples que te gustaron.</p>
            </div>
            <label class="switch">
                <input type="checkbox" name="mostrarMeGustan" id="mostrarMeGustan">
                <span class="slider"></span>
            </label>
        </div>

        <div class="opcionCheck">
            <div>
                <label>Mostrar solo mi contenido</label>
                <p class="description">Solo se muestra lo que publicaste.</p>
            </div>
            <label class="switch">
                <input type="checkbox" name="misPost" id="misPost">
                <span class="slider"></span>
            </label>
        </div>

        <div class="XJAAHB">
            <button class="botonsecundario borde">Restablecer</button>
            <button class="botonprincipal">Guardar</button>
        </div>
<?php
        return ob_get_clean();
    }
}
