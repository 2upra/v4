<?php

/**
 * Componente de vista para el bloque de chat individual.
 * 
 * Renderiza el modal de chat para conversación individual.
 *
 * @package Kamples
 * @since 1.0.0
 */

namespace Kamples\Views\Components;

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit('Acceso directo no permitido.');
}

class ChatBox
{
    /**
     * Renderizar el bloque de chat.
     *
     * @return string HTML del chat.
     */
    public function render(): string
    {
        ob_start();
?>
        <div class="bloque modal bloqueChat" id="bloqueChat" data-user-id="" style="display: none;">
            <div class="infoChat">
                <div class="imagenMensaje">
                    <img src="" alt="Imagen de perfil">
                </div>
                <div class="nombreConversacion">
                    <p></p>
                    <span class="estadoConexion">Desconectado</span>
                </div>
                <div class="botoneschat">
                    <button class="minizarChat" id="minizarChat"><?= $GLOBALS['minus'] ?? '' ?></button>
                    <button class="cerrarChat" id="cerrarChat"><?= $GLOBALS['cancelicon'] ?? '' ?></button>
                </div>
            </div>
            <ul class="listaMensajes"></ul>

            <div class="previewsForm NGEESM previewsChat" style="position: relative;">
                <!-- Vista previa de imagen -->
                <div class="previewAreaArchivos previewChatImagen" id="previewChatImagen" style="display: none;">
                    <label>Imagen</label>
                </div>
                <!-- Vista previa de audio -->
                <div class="previewAreaArchivos previewChatAudio" id="previewChatAudio" style="display: none;">
                    <label>Audio</label>
                </div>
                <!-- Vista previa de archivo -->
                <div class="previewAreaArchivos previewChatArchivo" id="previewChatArchivo" style="display: none;">
                    <label>Archivo</label>
                </div>

                <!-- Botón de cancelar único -->
                <button class="cancelButton borde cancelUploadButton" id="cancelUploadButton" style="display: none;">Cancelar</button>
            </div>

            <div class="chatEnvio individualSend">
                <textarea class="mensajeContenido" rows="1"></textarea>
                <button class="enviarMensaje"><?= $GLOBALS['enviarMensaje'] ?? '' ?></button>
                <button class="enviarAdjunto" id="enviarAdjunto"><?= $GLOBALS['enviarAdjunto'] ?? '' ?></button>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Método estático para uso rápido.
     *
     * @return string HTML del chat.
     */
    public static function mostrar(): string
    {
        $instance = new self();
        return $instance->render();
    }
}

/**
 * Función global para compatibilidad.
 * 
 * @return string HTML del chat.
 */
function renderChat(): string
{
    return ChatBox::mostrar();
}
