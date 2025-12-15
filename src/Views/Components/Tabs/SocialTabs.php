<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de tabs para la sección social.
 * 
 * Renderiza las pestañas principales del feed social,
 * adaptadas según el tipo de usuario (Artista/Fan).
 *
 * @since 2.0.0
 */
class SocialTabs
{
    /**
     * Renderiza los tabs sociales según el tipo de usuario.
     * 
     * @return string HTML de los tabs
     */
    public static function render(): string
    {
        $usuarioTipo = get_user_meta(get_current_user_id(), 'tipoUsuario', true);

        ob_start();
?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <?php if ($usuarioTipo === 'Artista'): ?>
                <div data-tab="Samples"></div>
            <?php endif; ?>
            <?php if ($usuarioTipo === 'Fan'): ?>
                <div data-tab="Feed"></div>
            <?php endif; ?>
        </div>

        <div class="tabs">
            <div class="tab-content">
                <?php if ($usuarioTipo === 'Artista'): ?>
                    <?php echo self::renderArtistaContent(); ?>
                <?php endif; ?>

                <?php if ($usuarioTipo === 'Fan'): ?>
                    <?php echo self::renderFanContent(); ?>
                <?php endif; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza contenido para usuarios tipo Artista.
     */
    private static function renderArtistaContent(): string
    {
        ob_start();
    ?>
        <div class="divmomento artista">
            <?php echo function_exists('momentos') ? momentos() : ''; ?>
        </div>
        <div class="BPLBDE UP">
            <div class="DHRDTAG">
                <?php echo function_exists('tagsPosts') ? tagsPosts() : ''; ?>
            </div>
            <div class="FDGEDF">
                <p id="resultadosPost-sampleList"></p>
                <?php echo function_exists('renderFiltroSampleList') ? renderFiltroSampleList() : ''; ?>
            </div>
            <?php if (wp_is_mobile()): ?>
                <div class="search-container SSmovil" id="filtros" style="display: flex;">
                    <input type="text" id="identifier" class="inputBusquedaRs" placeholder="Busqueda" style="display: flex;">
                    <button id="clearSearch" class="clear-search" style="display: none;">
                        <?php echo $GLOBALS['flechaAtras'] ?? ''; ?>
                    </button>
                    <button id="estrellitasTooltip" class="tooltip-element" data-tooltip="Para excluir palabras de tu busqueda, usa el signo menos (-) antes del termino o encierra frases con ello.">
                        <?php echo $GLOBALS['iconoestrellitas'] ?? ''; ?>
                    </button>
                    <div class="resultadosBusqueda modal" id="resultadoBusqueda" style="display: none;"></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab INICIO S4K7I3" id="Samples">
            <div class="BPLBDE">
                <div class="FOFDV5">
                    <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'sampleList', 'tab_id' => 'Samples', 'posts' => 12, 'tipoUsuario' => 'Artista']) : ''; ?>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza contenido para usuarios tipo Fan.
     */
    private static function renderFanContent(): string
    {
        ob_start();
    ?>
        <div class="divmomento fan">
            <?php echo function_exists('momentos') ? momentos() : ''; ?>
        </div>
        <div class="tab INICIO S4K7I3" id="Feed">
            <div class="OXMGLZ">
                <div class="OAXRVB">
                    <div class="FEDAG5">
                        <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'sample', 'tab_id' => 'Feed', 'posts' => 12, 'tipoUsuario' => 'Fan']) : ''; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tabs sociales forzando tipo Fan (para feed).
     */
    public static function renderFeed(): string
    {
        ob_start();
    ?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Feed"></div>
        </div>
        <div class="tabs">
            <div class="tab-content">
                <div class="tab INICIO S4K7I3" id="Feed">
                    <div class="OXMGLZ">
                        <div class="OAXRVB">
                            <div class="FEDAG5">
                                <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'sample', 'tab_id' => 'Feed', 'posts' => 12, 'tipoUsuario' => 'Fan']) : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza tabs sociales forzando tipo Artista (para samples).
     */
    public static function renderSamples(): string
    {
        ob_start();
    ?>
        <div id="menuData" style="display:none;" pestanaActual="">
            <div data-tab="Samples"></div>
        </div>
        <div class="tabs">
            <div class="tab-content">
                <div class="BPLBDE UP">
                    <div class="DHRDTAG">
                        <?php echo function_exists('tagsPosts') ? tagsPosts() : ''; ?>
                    </div>
                    <div class="FDGEDF">
                        <p id="resultadosPost-sampleList"></p>
                        <?php echo function_exists('renderFiltroSampleList') ? renderFiltroSampleList() : ''; ?>
                    </div>
                </div>
                <div class="tab INICIO S4K7I3" id="Samples">
                    <div class="BPLBDE">
                        <div class="FOFDV5">
                            <?php echo function_exists('publicaciones') ? publicaciones(['filtro' => 'sampleList', 'tab_id' => 'Samples', 'posts' => 12, 'tipoUsuario' => 'Artista']) : ''; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza momentos fijos (enlaces destacados).
     */
    public static function renderMomentosFijos(): string
    {
        $imagenUno = "https://images.ctfassets.net/kftzwdyauwt9/2CPrXUZS0yLGo894hU24zv/b9e1759c6f213a8888e17852266c515b/apple-art-2a-3x4.jpg?w=640&q=90&fm=webp";
        $imagenDos = "https://images.ctfassets.net/kftzwdyauwt9/1ZTOGp7opuUflFmI2CsATh/df5da4be74f62c70d35e2f5518bf2660/ChatGPT_Carousel1.png?w=640&q=90&fm=webp";
        $imagenTres = "https://images.ctfassets.net/kftzwdyauwt9/3XDJfuQZLCKWAIOleFIFZn/14b93d23652347ee7706eca921e3a716/enterprise.png?w=640&q=90&fm=webp";

        ob_start();
    ?>
        <div class="ZCOPHT" style="background-image: url('<?php echo esc_url($imagenUno); ?>');" onclick="window.location.href='<?php echo home_url('/quehacer'); ?>';">
            <p>Que hacer en 2upra</p>
        </div>
        <div class="ZCOPHT" style="background-image: url('<?php echo esc_url($imagenDos); ?>');" onclick="window.location.href='<?php echo home_url('/descubrir2upra'); ?>';">
            <p>Descubre el proyecto</p>
        </div>
        <div class="ZCOPHT" style="background-image: url('<?php echo esc_url($imagenTres); ?>');" onclick="window.location.href='<?php echo home_url('/reglas'); ?>';">
            <p>Normas y Politicas</p>
        </div>
<?php
        return ob_get_clean();
    }
}
