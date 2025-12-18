<?php

/**
 * Componentes de vista para el sistema de colecciones.
 * 
 * Contiene todos los métodos de renderizado HTML para las
 * colecciones (modales, listas, posts de colección, etc.).
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

use Kamples\Services\Coleccion\ColeccionService;
use Kamples\Services\Contenido\ImagenService;
use Kamples\Services\Publicacion\PublicacionService;
use Kamples\Views\Components\FinanzaComponents;

/* Evitar acceso directo */

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ColeccionComponents
{
    /**
     * Servicio de colecciones.
     */
    private static ?ColeccionService $coleccionService = null;

    /**
     * Obtener instancia del servicio.
     */
    private static function getService(): ColeccionService
    {
        if (self::$coleccionService === null) {
            self::$coleccionService = new ColeccionService();
        }
        return self::$coleccionService;
    }

    /**
     * Renderizar modal de selección de colección.
     */
    public static function renderModalColeccion(): void
    {
        $currentUserId = get_current_user_id();

        $favoritosId = get_user_meta($currentUserId, 'favoritos_coleccion_id', true);
        $despuesId   = get_user_meta($currentUserId, 'despues_coleccion_id', true);

        $colecciones = self::getService()->obtenerColeccionesUsuario($currentUserId);
        $defaultImage = site_url('/wp-content/uploads/2024/10/699bc48ebc970652670ff977acc0fd92.jpg');
        $iconPapelera = $GLOBALS['iconPapelera'] ?? '';
?>
        <div class="modalColec modal" style="display: none;">
            <div class="colecciones">
                <h3>Colecciones</h3>
                <input type="text" placeholder="Buscar colección" id="buscarColeccion">
                <ul class="listaColeccion borde">
                    <?php if (!$favoritosId): ?>
                        <li class="coleccion" id="favoritos" data-post_id="favoritos">
                            <img src="<?php echo esc_url(site_url('/wp-content/uploads/2024/10/2ed26c91a215be4ac0a1e3332482c042.jpg')); ?>" alt="">
                            <span>Favoritos</span>
                        </li>
                    <?php endif; ?>

                    <?php if (!$despuesId): ?>
                        <li class="coleccion borde" id="despues" data-post_id="despues">
                            <img src="<?php echo esc_url(site_url('/wp-content/uploads/2024/10/b029d18ac320a9d6923cf7ca0bdc397d.jpg')); ?>" alt="">
                            <span>Usar más tarde</span>
                        </li>
                    <?php endif; ?>

                    <?php foreach ($colecciones as $coleccion): ?>
                        <li class="coleccion borde" data-post_id="<?php echo esc_attr($coleccion['id']); ?>">
                            <img src="<?php echo esc_url($coleccion['imagen']); ?>" alt="">
                            <span><?php echo esc_html($coleccion['titulo']); ?></span>
                            <button class="borrarColec" data-post_id="<?php echo esc_attr($coleccion['id']); ?>">
                                <?php echo $iconPapelera; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="XJAAHB">
                    <button class="botonsecundario" id="btnEmpezarCreaColec">Nueva colección</button>
                    <button class="botonprincipal" id="btnListo">Listo</button>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Renderizar modal de creación de colección.
     */
    public static function renderModalCreacionColeccion(): void
    {
        $iconoPrivado = $GLOBALS['iconoPrivado'] ?? '';
    ?>
        <div class="modalColec crearColec modalCrearColec modal" id="modalCrearColec" style="display: none;">
            <div class="colecciones formColec">
                <h3>Crear colección</h3>
                <div class="previewAreaArchivos previewColec" id="previewImagenColec">
                    <label>Agregar imagen (opcional)</label>
                </div>
                <input type="text" placeholder="Nombre de la colección" id="tituloColec">
                <input type="text" placeholder="Descripción de la colección (opcional)" id="descripColec">

                <div class="bloque flex-row" id="opcionesColec" style="display: flex">
                    <p>Opciones de post</p>
                    <div class="flex flex-row gap-2">
                        <label class="custom-checkbox">
                            <input type="checkbox" id="privadoColec" name="privadoColec" value="1">
                            <span class="checkmark"></span>
                            <?php echo $iconoPrivado; ?>
                        </label>
                    </div>
                </div>
                <div class="XJAAHB">
                    <button class="botonsecundario" id="btnVolverColec">Volver</button>
                    <button class="botonprincipal" id="btnCrearColec">Crear</button>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Renderizar HTML de un post de colección.
     * 
     * @param string $filtro Filtro del post.
     * @return string HTML del post.
     */
    public static function renderHtmlColec(string $filtro): string
    {
        ob_start();
        $postId = get_the_ID();
        $vars   = self::getService()->obtenerVariablesColec($postId);
        $autorId = $vars['autorId'];
    ?>
        <li class="POST-<?php echo esc_attr($filtro); ?> EDYQHV no-refresh"
            filtro="<?php echo esc_attr($filtro); ?>"
            id-post="<?php echo esc_attr($postId); ?>"
            autor="<?php echo esc_attr($autorId); ?>">

            <div class="post-content">
                <?php echo self::renderImagenColeccion($postId); ?>
                <div class="KLYJBY">
                    <?php
                    if (function_exists('audioPost')) {
                        echo audioPost($postId);
                    }
                    ?>
                </div>

                <?php
                $postType = get_post_type($postId);
                if ($postType !== 'social_post'):
                ?>
                    <h2 class="post-title" data-post-id="<?php echo esc_attr($postId); ?>">
                        <?php echo get_the_title($postId); ?>
                    </h2>
                <?php else: ?>
                    <div class="LRKHLC">
                        <div class="XOKALG">
                            <?php echo self::renderNombreRola($postId); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="CPQBEN" style="display: none;">
                    <?php
                    if (function_exists('like')) echo like($postId);
                    echo FinanzaComponents::renderBotonCompra($postId);
                    ?>
                    <div class="CPQBAU"><?php echo get_the_author_meta('display_name', $autorId); ?></div>
                    <div class="CPQBCO">
                        <?php echo self::renderNombreRola($postId); ?>
                    </div>
                </div>
                <p class="post-author"><?php echo get_the_author_meta('display_name', $autorId); ?></p>

                <?php
                $coleccionesMeta = get_post_meta($postId, 'colecciones', true);
                $rolaMeta        = get_post_meta($postId, 'rola', true);

                if (!$coleccionesMeta && !$rolaMeta) {
                    echo FinanzaComponents::renderBotonCompra($postId);
                }
                ?>
            </div>
        </li>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar imagen de colección.
     * 
     * @param int $postId ID del post.
     * @return string HTML de la imagen.
     */
    public static function renderImagenColeccion(int $postId): string
    {
        $renderService = new \Kamples\Services\Publicacion\PostRenderService();
        $imagenUrl = $renderService->obtenerImagenPost($postId, 'large', 60, 'all', false, true);

        if (!$imagenUrl) {
            $imagenUrl = get_the_post_thumbnail_url($postId, 'large') ?: '';
        }

        if ($imagenUrl) {
            $imagenProcesada = ImagenService::obtenerInstancia()->optimizar($imagenUrl, 60, 'all');
        } else {
            $imagenProcesada = $imagenUrl;
        }

        $postType = get_post_type($postId);

        ob_start();
    ?>
        <div class="post-image-container">
            <?php if ($postType !== 'social_post'): ?>
                <a href="<?php echo esc_url(get_permalink($postId)); ?>" data-post-id="<?php echo $postId; ?>" class="imagenColecS">
                <?php endif; ?>
                <img class="imagenMusic" src="<?php echo esc_url($imagenProcesada); ?>" alt="Post Image" data-post-id="<?php echo $postId; ?>" />
                <div class="KLYJBY">
                    <?php
                    if (function_exists('audioPost')) {
                        echo audioPost($postId);
                    }
                    ?>
                </div>
                <?php if ($postType !== 'social_post'): ?>
                </a>
            <?php endif; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar vista single de colección.
     * 
     * @param int $postId ID de la colección.
     * @return string HTML de la vista.
     */
    public static function renderSingleColec(int $postId): string
    {
        $vars = self::getService()->obtenerVariablesColec($postId);

        ob_start();
    ?>
        <div class="AMORP">
            <?php echo self::renderImagenColeccion($postId); ?>
            <div class="ORGDE">
                <div class="AGDEORF">
                    <p class="post-author"><?php echo get_the_author_meta('display_name', $vars['autorId']); ?></p>
                    <h2 class="tituloColec" data-post-id="<?php echo $postId; ?>"><?php echo get_the_title($postId); ?></h2>
                    <div class="DSEDBE">
                        <?php echo esc_html($vars['samples']); ?>
                    </div>
                    <div class="BOTONESCOLEC">
                        <?php
                        if (function_exists('botonDescargaColec')) echo botonDescargaColec($postId, $vars['sampleCount']);
                        if (function_exists('botonSincronizarColec')) echo botonSincronizarColec($postId, $vars['sampleCount']);
                        if (function_exists('like')) echo like($postId);
                        ?>
                        <?php echo self::renderOpcionesColec($postId, $vars['autorId']); ?>
                    </div>
                </div>

                <div class="INFEIS">
                    <?php
                    /* Calcular datos de colección si es necesario */
                    self::getService()->getSampleService()->calcularDatosColeccion($postId);
                    ?>
                    <div class="tags-container-colec" id="tags-<?php echo get_the_ID(); ?>"></div>

                    <p id="dataColec" id-post-algoritmo="<?php echo get_the_ID(); ?>" style="display:none;">
                        <?php
                        if (function_exists('limpiarJSON')) {
                            echo esc_html(limpiarJSON($vars['datosColeccion']));
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="LISTCOLECSIN">
            <?php
            echo PublicacionService::obtenerInstancia()->obtener([
                'post_type' => 'social_post',
                'filtro'    => 'sampleList',
                'posts'     => 12,
                'colec'     => $postId
            ]);
            ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar opciones de colección.
     * 
     * @param int $postId  ID de la colección.
     * @param int $autorId ID del autor.
     * @return string HTML de las opciones.
     */
    public static function renderOpcionesColec(int $postId, int $autorId): string
    {
        $usuarioActual   = get_current_user_id();
        $postVerificado  = get_post_meta($postId, 'Verificado', true);
        $iconoTresPuntos = $GLOBALS['iconotrespuntos'] ?? '';

        ob_start();
    ?>
        <button class="HR695R8" data-post-id="<?php echo $postId; ?>"><?php echo $iconoTresPuntos; ?></button>

        <div class="A1806241" id="opcionespost-<?php echo $postId; ?>">
            <div class="A1806242">
                <?php if (current_user_can('administrator')): ?>
                    <button class="eliminarPost" data-post-id="<?php echo $postId; ?>">Eliminar</button>
                    <button class="cambiarTitulo" data-post-id="<?php echo $postId; ?>">Cambiar titulo</button>
                    <button class="cambiarImagen" data-post-id="<?php echo $postId; ?>">Cambiar imagen</button>
                    <?php if (!$postVerificado): ?>
                        <button class="verificarPost" data-post-id="<?php echo $postId; ?>">Verificar</button>
                    <?php endif; ?>
                    <button class="editarWordPress" data-post-id="<?php echo $postId; ?>">Editar en WordPress</button>
                    <button class="banearUsuario" data-post-id="<?php echo $postId; ?>">Banear</button>
                <?php elseif ($usuarioActual == $autorId): ?>
                    <button class="eliminarPost" data-post-id="<?php echo $postId; ?>">Eliminar</button>
                    <button class="cambiarImagen" data-post-id="<?php echo $postId; ?>">Cambiar Imagen</button>
                <?php else: ?>
                    <button class="reporte" data-post-id="<?php echo $postId; ?>" tipoContenido="social_post">Reportar</button>
                    <button class="bloquear" data-post-id="<?php echo $postId; ?>">Bloquear</button>
                <?php endif; ?>
            </div>
        </div>

        <div id="modalBackground4" class="modal-background submenu modalBackground2 modalBackground3" style="display: none;"></div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderizar más ideas de colección.
     * 
     * @param int $postId ID de la colección.
     * @return string HTML.
     */
    public static function renderMasIdeasColec(int $postId): string
    {
        ob_start();
    ?>
        <div class="LISTCOLECSIN">
            <?php
            echo PublicacionService::obtenerInstancia()->obtener([
                'post_type' => 'social_post',
                'filtro'    => 'sampleList',
                'posts'     => 12,
                'colec'     => $postId,
                'idea'      => true
            ]);
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar nombre de rola/sample.
     */
    private static function renderNombreRola(int $postId): string
    {
        $rolaMeta   = get_post_meta($postId, 'rola', true);
        $tiendaMeta = get_post_meta($postId, 'tienda', true);

        if ($rolaMeta !== '1' && $tiendaMeta !== '1') {
            return '';
        }

        $nombreRola = get_post_meta($postId, 'nombreRola', true);
        if (empty($nombreRola)) {
            $nombreRola = get_post_meta($postId, 'nombreRola1', true);
        }
        if (empty($nombreRola)) {
            $nombreRola = get_the_title($postId);
        }

        if (!empty($nombreRola)) {
            return '<p class="nameRola">' . esc_html($nombreRola) . '</p>';
        }

        return '';
    }

    /**
     * Renderizar botón de descarga de colección.
     * 
     * @param int $postId ID de la colección.
     * @param int $sampleCount Cantidad de samples.
     * @return string HTML del botón.
     */
    public static function renderBotonDescarga(int $postId, int $sampleCount): string
    {
        ob_start();
        $userId = get_current_user_id();
        $descargaIcono = $GLOBALS['descargaicono'] ?? '';

        if ($userId) {
            $descargasAnteriores = get_user_meta($userId, 'descargas', true);
            $yaDescargado = isset($descargasAnteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
        ?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down botonprincipal <?php echo esc_attr($claseExtra); ?>"
                    data-post-id="<?php echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<?php echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($userId); ?>', 'true', '<?php echo esc_js($sampleCount); ?>')">
                    <?php echo $descargaIcono; ?> Descargar
                </button>
            </div>
        <?php
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    <?php echo $descargaIcono; ?>
                </button>
            </div>
        <?php
        }

        return ob_get_clean();
    }

    /**
     * Renderizar botón de sincronización de colección.
     * 
     * @param int $postId ID de la colección.
     * @param int $sampleCount Cantidad de samples.
     * @return string HTML del botón.
     */
    public static function renderBotonSincronizar(int $postId, int $sampleCount): string
    {
        ob_start();
        $userId = get_current_user_id();

        if ($userId) {
            $descargasAnteriores = get_user_meta($userId, 'descargas', true);
            $yaDescargado = isset($descargasAnteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
        ?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down botonsecundario <?php echo esc_attr($claseExtra); ?>"
                    data-post-id="<?php echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<?php echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($userId); ?>', 'true', '<?php echo esc_js($sampleCount); ?>', 'true')">
                    Sincronizar
                </button>
            </div>
        <?php
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    Sincronizar
                </button>
            </div>
<?php
        }

        return ob_get_clean();
    }
}
