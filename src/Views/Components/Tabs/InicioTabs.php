<?php

namespace Kamples\Views\Components\Tabs;

/**
 * Componentes de la pagina de inicio para usuarios no logueados.
 *
 * @since 2.0.0
 */
class InicioTabs
{
    /**
     * Renderiza la pagina de inicio con registro/login.
     * 
     * @return string HTML de la pagina
     */
    public static function render(): string
    {
        ob_start();
?>
        <div class="XX1" id="XX1">
            <p class="XXT1">Distribuye tu musica en todas partes</p>
            <p class="XXT2">Sello discografico gratuito de Phonk/Lo-Fi para artistas emergentes</p>
            <div class="XX12">
                <button class="XXB1 boton-registro" id="botonregistro">Registrate</button>
                <button class="XXB1 XXB3 boton-sesion" id="botonsesion">Iniciar Sesion</button>
            </div>
            <p class="XXT3">Tu musica en todas las tiendas y playlists gratis</p>

            <div class="CGUNVP" id="modalregistro">
                <?php echo function_exists('registrar_usuario') ? registrar_usuario() : ''; ?>
            </div>
            <div class="EJRINA" id="modalsesion">
                <?php echo function_exists('iniciar_sesion') ? iniciar_sesion() : ''; ?>
            </div>

            <div id="fondonegro"></div>

            <div class="XXI!">
                <?php
                $iconos = ['spotify', 'apple', 'instagram', 'facebook', 'tiktok', 'soundcloud', 'tidal', 'amazonmusic', 'deezer', 'youtube'];
                foreach ($iconos as $icono) {
                    echo $GLOBALS[$icono] ?? '';
                }
                ?>
            </div>
        </div>

        <?php echo self::renderCaracteristicas(); ?>
        <?php echo self::renderDescargaApp(); ?>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la seccion de caracteristicas.
     */
    private static function renderCaracteristicas(): string
    {
        $images = [
            [
                'url' => site_url('/wp-content/uploads/2024/05/0177.png'),
                'alt' => 'Recursos gratuitos',
                'title' => 'Recursos gratuitos para artistas',
                'description' => 'Accede a una biblioteca exclusiva de samples, plugins y sample packs de alta calidad, actualizados semanalmente.'
            ],
            [
                'url' => site_url('/wp-content/uploads/2024/05/fsfs5.png'),
                'alt' => 'Colaboraciones',
                'title' => 'Fomenta la colaboracion',
                'description' => 'Publica tus proyectos musicales y conecta con otros artistas talentosos.'
            ],
            [
                'url' => site_url('/wp-content/uploads/2024/05/asfsdf4.png'),
                'alt' => 'Vende tus trabajos',
                'title' => 'Monetiza tu musica',
                'description' => 'Publica tus beats y composiciones en nuestra plataforma con comisiones bajas.'
            ],
            [
                'url' => site_url('/wp-content/uploads/2024/05/adsfadsf4.png'),
                'alt' => 'Sello emergente',
                'title' => 'Sello discografico emergente',
                'description' => 'Te apoyamos en la distribucion, promocion y alcance de tu musica.'
            ]
        ];

        ob_start();
    ?>
        <div class="XX1 XX2" style="display: none;">
            <?php foreach ($images as $image): ?>
                <?php $optimizedUrl = function_exists('optimizeImageUrl') ? optimizeImageUrl($image['url'], 'medium', 50, 'all') : $image['url']; ?>
                <div class="XXDD">
                    <div class="spaceimagen">
                        <img src="<?php echo esc_url($optimizedUrl); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                    </div>
                    <h3 class="XXD1"><?php echo esc_html($image['title']); ?></h3>
                    <p class="XXD2"><?php echo esc_html($image['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la seccion de descarga de app.
     */
    private static function renderDescargaApp(): string
    {
        ob_start();
    ?>
        <div class="XX1 XX2 XX7" style="display: n;">
            <div class="XXDD XX9">
                <div class="XX10">
                    <button class="XXB1 XXB2">Descargar</button>
                    <h3 class="XXD1">Descarga nuestra app</h3>
                    <p class="XXD2">Lleva tu creatividad musical a todas partes con nuestra app movil.</p>
                </div>
                <div class="spaceimagen XX8">
                    <img src="<?php echo esc_url(site_url('/wp-content/uploads/2024/05/asdfar4.png')); ?>" alt="Descargar App">
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
