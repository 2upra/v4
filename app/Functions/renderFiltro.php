<?

function renderFiltroSampleList() {
    // Obtener el filtro actual del usuario
    $user_id = get_current_user_id();
    $filtro_tiempo = get_user_meta($user_id, 'filtroTiempo', true);
    $filtro_tiempo = $filtro_tiempo === '' ? 0 : intval($filtro_tiempo);

    // Definir los nombres de los filtros
    $nombres_filtros = array(
        0 => 'Feed',
        1 => 'Reciente',
        2 => 'Semanal',
        3 => 'Mensual'
    );

    // Obtener el nombre del filtro actual
    $nombre_filtro = isset($nombres_filtros[$filtro_tiempo]) ? $nombres_filtros[$filtro_tiempo] : 'Feed';

    // Iconos globales
    $icono_flecha_arriba_abajo = $GLOBALS['iconoflechaArriAba'];
    $icono_flecha_abajo = $GLOBALS['flechaAbajo'];

    // Iniciar el buffer para capturar el HTML
    ob_start();
    ?>
    <div class="OPCDGED">

        <button class="restablecerBusqueda postRestablecer" style="display: none;">Restablecer filtros</button>
        <button class="filtrosboton">
            <?= $nombre_filtro . ' ' . $icono_flecha_arriba_abajo; ?>
        </button>

        <div class="A1806241" id="filtrosMenu-default">
            <div class="A1806242">
                <button class="filtroFeed <?= ($filtro_tiempo == 0 || $filtro_tiempo === '') ? 'filtroSelec' : ''; ?>">Para mí</button>
                <button class="filtroReciente <?= ($filtro_tiempo == 1) ? 'filtroSelec' : ''; ?>">Recientes</button>
                <button class="filtroSemanal <?= ($filtro_tiempo == 2) ? 'filtroSelec' : ''; ?>">Top Semanal</button>
                <button class="filtroMensual <?= ($filtro_tiempo == 3) ? 'filtroSelec' : ''; ?>">Top Mensual</button>
            </div>
        </div>
        <button class="ORDENPOSTSL" id="ORDENPOSTSL">Opciones<?= $icono_flecha_abajo; ?></button>
        <div class="opcionCheckBox modal" id="filtrosPost" style="display: none;">

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
        </div>

    </div>
    <?
    // Capturar y devolver el HTML generado
    return ob_get_clean();
}