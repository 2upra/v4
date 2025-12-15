<?php

/**
 * Controlador AJAX para el sistema de comentarios.
 * 
 * Maneja las solicitudes AJAX relacionadas con la creación,
 * listado y eliminación de comentarios.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Controllers\Social;

use Kamples\Services\ComentarioService;

if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ComentarioController
{
    private ComentarioService $comentarioService;
    private $logger;

    public function __construct(?ComentarioService $comentarioService = null)
    {
        $this->comentarioService = $comentarioService ?? new ComentarioService();

        if (class_exists('\Logger')) {
            $this->logger = \Logger::obtenerInstancia();
        }
    }

    /**
     * Registrar acciones AJAX de WordPress.
     */
    public function registrar(): void
    {
        add_action('wp_ajax_procesarComentario', [$this, 'procesarComentario']);
        add_action('wp_ajax_renderComentarios', [$this, 'renderComentarios']);
        add_action('wp_ajax_nopriv_renderComentarios', [$this, 'renderComentarios']);
        add_action('wp_ajax_eliminarComentario', [$this, 'eliminarComentario']);
    }

    /**
     * Procesar creación de un nuevo comentario.
     */
    public function procesarComentario(): void
    {
        if ($this->logger) {
            $this->logger->info('post', '[ComentarioController] Procesando nuevo comentario');
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión para comentar.']);
            return;
        }

        $userId = get_current_user_id();

        if (!$this->comentarioService->usuarioPuedeComentario($userId)) {
            wp_send_json_error([
                'message' => 'Has alcanzado el límite de comentarios por minuto. Por favor, espera un momento.'
            ]);
            return;
        }

        $datos = $this->obtenerDatosCreacion($userId);
        $validacion = $this->comentarioService->validarDatosComentario($datos);

        if (!$validacion['valido']) {
            wp_send_json_error(['message' => $validacion['mensaje']]);
            return;
        }

        $resultado = $this->comentarioService->crearComentario($datos);

        if ($resultado['exito']) {
            wp_send_json_success([
                'message' => $resultado['mensaje'],
                'post_id' => $resultado['comentarioId']
            ]);
        } else {
            wp_send_json_error(['message' => $resultado['mensaje']]);
        }
    }

    /**
     * Renderizar lista de comentarios de un post.
     */
    public function renderComentarios(): void
    {
        $postId = isset($_POST['postId']) ? absint($_POST['postId']) : 0;
        $pagina = isset($_POST['page']) ? absint($_POST['page']) : 1;

        if ($postId <= 0) {
            $this->enviarRespuestaComentarios(true, '<p class="sinnotifi">ID de post inválido</p>');
            return;
        }

        $resultado = $this->comentarioService->obtenerComentariosPost($postId, $pagina);

        if (empty($resultado['comentarios'])) {
            $this->enviarRespuestaComentarios(true, '<p class="sinnotifi">No hay comentarios para este post</p>');
            return;
        }

        $html = $this->renderizarListaComentarios($resultado['comentarios']);
        $this->enviarRespuestaComentarios(false, $html, $resultado['hayMas']);
    }

    /**
     * Eliminar un comentario.
     */
    public function eliminarComentario(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión.']);
            return;
        }

        $comentarioId = isset($_POST['comentarioId']) ? absint($_POST['comentarioId']) : 0;
        $userId = get_current_user_id();

        if ($comentarioId <= 0) {
            wp_send_json_error(['message' => 'ID de comentario inválido.']);
            return;
        }

        $resultado = $this->comentarioService->eliminarComentario($comentarioId, $userId);

        if ($resultado['exito']) {
            wp_send_json_success(['message' => $resultado['mensaje']]);
        } else {
            wp_send_json_error(['message' => $resultado['mensaje']]);
        }
    }

    private function obtenerDatosCreacion(int $userId): array
    {
        return [
            'userId'     => $userId,
            'comentario' => isset($_POST['comentario'])
                ? sanitize_textarea_field($_POST['comentario'])
                : '',
            'postId'     => isset($_POST['postId'])
                ? absint($_POST['postId'])
                : 0,
            'imagenUrl'  => isset($_POST['imagenUrl'])
                ? esc_url_raw($_POST['imagenUrl'])
                : '',
            'audioUrl'   => isset($_POST['audioUrl'])
                ? esc_url_raw($_POST['audioUrl'])
                : '',
            'imagenId'   => isset($_POST['imagenId'])
                ? sanitize_text_field($_POST['imagenId'])
                : '',
            'audioId'    => isset($_POST['audioId'])
                ? sanitize_text_field($_POST['audioId'])
                : '',
        ];
    }

    private function renderizarListaComentarios(array $comentarios): string
    {
        ob_start();
        echo '<ul class="lista-comentarios">';

        foreach ($comentarios as $comentario) {
            $datos = $this->comentarioService->formatearComentario($comentario);
            $this->renderizarComentarioItem($datos);
        }

        echo '</ul>';
        return ob_get_clean();
    }

    private function renderizarComentarioItem(array $datos): void
    {
?>
        <li class="comentarioPost" id="comentario-<?php echo esc_attr($datos['id']); ?>">
            <div class="avatarComentario">
                <img class="avatar"
                    src="<?php echo esc_url($datos['avatar']); ?>"
                    alt="Avatar del emisor">
                <div class="spaceComentario">
                    <div class="MGDEOP">
                        <p><?php echo esc_html($datos['autorNombre']); ?></p>
                        <span class="fecha"><?php echo esc_html($datos['fechaRelativa']); ?></span>
                        <?php
                        if (function_exists('opcionesComentarios')) {
                            echo opcionesComentarios($datos['id'], $datos['autorId']);
                        }
                        ?>
                    </div>
                    <div class="contenidoComentario">
                        <div class="texto"><?php echo wp_kses_post($datos['contenido']); ?></div>

                        <?php if (!empty($datos['imagenPortada'])): ?>
                            <div class="imagenComentario">
                                <img src="<?php echo esc_url($datos['imagenPortada']); ?>"
                                    alt="Imagen de portada" />
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($datos['audio'])): ?>
                            <div class="audioComentario">
                                <?php
                                if (function_exists('wave')) {
                                    wave($datos['audioUrl'], $datos['audio'], $datos['id']);
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="controlComentario">
                            <?php
                            if (function_exists('renderPostControls')) {
                                echo renderPostControls($datos['id'], '', $datos['audio']);
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </li>
<?php
    }

    private function enviarRespuestaComentarios(bool $noComentarios, string $html, bool $hayMas = false): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'noComentarios' => $noComentarios,
            'html' => $html,
            'hayMas' => $hayMas
        ]);
        wp_die();
    }
}
