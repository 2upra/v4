<?php
// Archivo creado para contener funciones de renderizado relacionadas con el chat.
// Inicialmente contendrá la lógica de renderListaChats.

// Refactor(Org): Función renderListaChats() movida desde app/Chat/renderListaChats.php
function renderListaChats($conversaciones, $usuarioId)
{
    ob_start();

    if ($conversaciones) {
?>
        <div class="bloqueConversaciones bloque" id="bloqueConversaciones-chatIcono" style="display: none;">
            <ul class="mensajes">
                <?php
                foreach ($conversaciones as $conversacion):
                    $participantes = json_decode($conversacion->participantes);
                    $otrosParticipantes = array_diff($participantes, [$usuarioId]);
                    $receptor = reset($otrosParticipantes);
                    $imagenPerfil = imagenPerfil($receptor);
                    $nombreUsuario = obtenerNombreUsuario($receptor);

                    $mensajeMostrado = "Mensaje desconocido";
                    $fechaOriginal = "";
                    $leido = 0; // Valor por defecto

                    if ($conversacion->ultimoMensaje) {
                        if (!empty($conversacion->ultimoMensaje->mensaje)) {
                            $mensajeMostrado = ($conversacion->ultimoMensaje->emisor == $usuarioId ? "Tú: " : "") . $conversacion->ultimoMensaje->mensaje;
                        } else {
                            $mensajeMostrado = "Mensaje desconocido";
                        }
                        $fechaOriginal = $conversacion->ultimoMensaje->fecha;
                        $leido = isset($conversacion->ultimoMensaje->leido) ? (int)$conversacion->ultimoMensaje->leido : 0;
                    }
                ?>
                    <li class="mensaje <?php echo $leido ? 'leido' : 'no-leido'; ?>"
                        data-receptor="<?= esc_attr($receptor); ?>"
                        data-conversacion="<?= esc_attr($conversacion->id); ?>"
                        data-leido="<?= esc_attr($leido); ?>">
                        <div class="imagenMensaje">
                            <img src="<?= esc_url($imagenPerfil); ?>" alt="Imagen de perfil">
                        </div>
                        <div class="infoMensaje">
                            <div class="nombreUsuario">
                                <strong><?= esc_html($nombreUsuario); ?></strong>
                            </div>
                            <div class="vistaPrevia">
                                <p><?= esc_html($mensajeMostrado); ?></p>
                            </div>
                        </div>
                        <div class="tiempoMensaje" data-fecha="<?= esc_attr($fechaOriginal); ?>">
                            <span></span>
                        </div>
                        <?php if ($leido): ?>
                            <div class="iconoLeido">
                                ✓
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php
    } else {
    ?>
        <div class="bloqueConversaciones bloque" id="bloqueConversaciones-chatIcono" style="display: none;">
            <p>Aquí apareceran tus mensajes</p>
        </div>
        <?php
        ?>

<?php
    }

    $htmlGenerado = ob_get_clean();
    return $htmlGenerado;
}

// Refactor(Org): Mueve función renderChat() de app/Chat/renderChat.php a app/View/Renderers/ChatRenderer.php
function renderChat()
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
                <button class="minizarChat" id="minizarChat"><? echo $GLOBALS['minus']; ?></button>
                <button class="cerrarChat" id="cerrarChat"><? echo $GLOBALS['cancelicon']; ?></button>
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

            <!-- Botón de cancelar único, que aparecerá en cualquier vista previa -->
            <button class="cancelButton borde cancelUploadButton" id="cancelUploadButton" style="display: none;">Cancelar</button>
        </div>

        <div class="chatEnvio individualSend">
            <textarea class="mensajeContenido" rows="1"></textarea>
            <button class="enviarMensaje"><? echo $GLOBALS['enviarMensaje']; ?></button>
            <button class="enviarAdjunto" id="enviarAdjunto"><? echo $GLOBALS['enviarAdjunto']; ?></button>
        </div>
    </div>
    <?
    $htmlGenerado = ob_get_clean();
    return $htmlGenerado;
}
