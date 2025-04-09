<?php


// Refactor: Función reiniciarChats() y su hook AJAX movidos a app/Services/ChatService.php


// Refactor(Org): Funcion conversacionesUsuario() movida a app/Services/ChatService.php

// Refactor: Función obtenerChats() movida a app/Services/ChatService.php
// El código de la función obtenerChats() ha sido eliminado de este archivo.

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
