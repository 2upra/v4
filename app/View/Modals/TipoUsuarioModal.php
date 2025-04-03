<?php
// Refactor(Org): Funcion movida desde app/View/InicialModal.php

/**
 * Genera el HTML para el modal de selección de tipo de usuario.
 * Solo se muestra si el usuario no ha seleccionado un tipo previamente.
 *
 * @return string HTML del modal o cadena vacía si el usuario ya tiene un tipo asignado.
 */
function modalTipoUsuario()
{
    $userId = get_current_user_id();
    $tipoUsuario = get_user_meta($userId, 'tipoUsuario', true);

    // Si el usuario ya tiene un tipo asignado, no mostrar el modal
    if (!empty($tipoUsuario)) {
        return '';
    }

    // Obtener URLs optimizadas para las imágenes de fondo
    $fanDiv = img('https://2upra.com/wp-content/uploads/2024/11/aUZjCl0WQ_mmLypLZNGGJA.webp');
    $artistaBg = img('https://2upra.com/wp-content/uploads/2024/11/ODuY4qpIReS8uWqwSTAQDg.webp');

    // Iniciar captura de salida para el HTML del modal
    ob_start();
?>
    <div class="modal selectorModalUsuario" style="display: none;">
        <h3>Elige un tipo de usuario...</h3>
        <div class="TIPEARTISTSF">
            <div class="selectorUsuario borde" id="fanDiv">
                <p>Fan</p>
            </div>
            <div class="selectorUsuario borde" id="artistaDiv">
                <p>Artista</p>
            </div>
        </div>
        <style>
            /* Aplicar imágenes de fondo a los selectores */
            #fanDiv::before {
                background-image: url('<?php echo $fanDiv; ?>');
            }

            #artistaDiv::before {
                background-image: url('<?php echo $artistaBg; ?>');
            }
        </style>
        <button class="botonsecundario" style="display: none;">Siguiente</button>
    </div>
<?php
    // Devolver el HTML capturado
    return ob_get_clean();
}
?>
