<?php

/**
 * Wrappers deprecados para funciones de usuario.
 * 
 * DEPRECADO: Usar las clases en src/Services/ y src/Controllers/ directamente.
 * 
 * @deprecated 1.0.0 Usar Kamples\Services\UsuarioService
 * @see \Kamples\Services\UsuarioService
 */

use Kamples\Services\UsuarioService;
use Kamples\Services\ReporteService;
use Kamples\Services\ImagenService;
use Kamples\Services\TagService;

/* 
*  TIPO USUARIO
*/

/**
 * @deprecated Usar UsuarioController
 */
function cambiar_tipo_usuario_callback()
{
    $servicio = UsuarioService::obtenerInstancia();
    $userId = get_current_user_id();
    $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';

    $nuevoEstado = $servicio->cambiarTipoUsuario($userId, $tipo);
    echo $nuevoEstado ? '1' : '0';
    wp_die();
}

/* 
*  BLOQUEOS
*/

/**
 * @deprecated Usar UsuarioService::crearTablaBloqueo()
 */
function tablaBloqueo()
{
    $servicio = UsuarioService::obtenerInstancia();
    $servicio->crearTablaBloqueo();
}

/**
 * @deprecated Usar UsuarioController
 */
function guardarBloqueo()
{
    $servicio = UsuarioService::obtenerInstancia();
    $usuarioActual = get_current_user_id();
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    $resultado = $servicio->toggleBloqueo($usuarioActual, $postId);

    if ($resultado['success']) {
        wp_send_json_success($resultado['message']);
    } else {
        wp_send_json_error($resultado['message']);
    }
}

/**
 * @deprecated Usar UsuarioController
 */
function quitarBloqueo()
{
    $servicio = UsuarioService::obtenerInstancia();
    $usuarioActual = get_current_user_id();
    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    $resultado = $servicio->quitarBloqueo($usuarioActual, $postId);

    if ($resultado['success']) {
        wp_send_json_success($resultado['message']);
    } else {
        wp_send_json_error($resultado['message']);
    }
}

/* 
*  PINKYS
*/

/**
 * @deprecated Usar UsuarioService::agregarPinkys()
 */
function agregarPinkys($userID, $cantidad)
{
    $servicio = UsuarioService::obtenerInstancia();
    return $servicio->agregarPinkys((int)$userID, (int)$cantidad);
}

/**
 * @deprecated Usar UsuarioService::restarPinkys()
 */
function restarPinkys($userID, $cantidad)
{
    $servicio = UsuarioService::obtenerInstancia();
    return $servicio->restarPinkys((int)$userID, (int)$cantidad);
}

/**
 * @deprecated Usar UsuarioService::restarPinkysPorEliminacion()
 */
function restarPinkysEliminacion($postID)
{
    $servicio = UsuarioService::obtenerInstancia();
    return $servicio->restarPinkysPorEliminacion((int)$postID);
}

/**
 * @deprecated Usar UsuarioController (hook user_register)
 */
function pinkysRegistro($userId)
{
    $servicio = UsuarioService::obtenerInstancia();
    $servicio->asignarPinkysRegistro((int)$userId, 10);
}

/**
 * @deprecated Usar UsuarioService::restablecerPinkys()
 */
function restablecerPinkys()
{
    $servicio = UsuarioService::obtenerInstancia();
    return $servicio->restablecerPinkys(10);
}

/**
 * @deprecated Componente HTML legacy
 */
function botonDescargaPrueba()
{
    ob_start();
?>
    <div class="ZAQIBB ASDGD8">
        <button aria-label="Descarga ejemplo">
            <?= $GLOBALS['descargaicono'] ?? ''; ?>
        </button>
    </div>
<?php
    return ob_get_clean();
}

/* 
*  REPORTES
*/

/**
 * @deprecated Usar ReporteController
 */
function guardarReporte()
{
    $servicio = ReporteService::obtenerInstancia();
    $userId = get_current_user_id();
    $idContenido = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $tipoContenido = isset($_POST['tipoContenido']) ? sanitize_text_field($_POST['tipoContenido']) : '';
    $detalles = isset($_POST['detalles']) ? sanitize_textarea_field($_POST['detalles']) : '';

    $resultado = $servicio->guardarReporte($userId, $idContenido, $tipoContenido, $detalles);

    if ($resultado['success']) {
        wp_send_json_success($resultado['message']);
    } else {
        wp_send_json_error($resultado['message']);
    }
}

/* 
*  IMAGENES
*/

/**
 * Optimiza una URL de imagen usando CDN.
 * 
 * @deprecated Usar ImagenService::optimizar()
 */
function img($url, $quality = 40, $strip = 'all')
{
    $servicio = ImagenService::obtenerInstancia();
    return $servicio->optimizar($url, (int)$quality, $strip);
}

/**
 * Sube una imagen desde URL.
 * 
 * @deprecated Usar ImagenService::subirImagenDesdeUrl()
 */
function subirImagenDesdeURL($imageUrl, $postId)
{
    $servicio = ImagenService::obtenerInstancia();
    return $servicio->subirImagenDesdeUrl($imageUrl, (int)$postId);
}

/**
 * Adjunta un archivo a un post.
 * 
 * @deprecated Usar ImagenService::adjuntarArchivo()
 */
function adjuntarArchivo($newPostId, $fileUrl)
{
    $servicio = ImagenService::obtenerInstancia();
    return $servicio->adjuntarArchivo((int)$newPostId, $fileUrl);
}

/* 
*  TAGS
*/

/**
 * Obtiene los tags más frecuentes.
 * 
 * @deprecated Usar TagService::obtenerTagsFrecuentes()
 */
function obtenerTagsFrecuentes(): array
{
    $servicio = TagService::obtenerInstancia();
    return $servicio->obtenerTagsFrecuentes(32);
}

/**
 * Muestra los tags frecuentes.
 * 
 * @deprecated Usar Kamples\Views\Components\TagComponents::mostrarTagsPosts()
 */
function tagsPosts()
{
    echo \Kamples\Views\Components\TagComponents::tagsPosts();
}
