<?php

// Refactor(Org): Funcion confirmarArchivos movida desde app/Form/Manejar.php
function confirmarArchivos($postId)
{
    $tiposCampos = ['archivoId', 'audioId', 'imagenId'];
    $maxCampos = 30;
    foreach ($tiposCampos as $tipo) {
        for ($i = 1; $i <= $maxCampos; $i++) {
            $campo = $tipo . $i;
            if (!empty($_POST[$campo])) {
                $file_id = intval($_POST[$campo]);
                if ($file_id > 0 && get_post_type($file_id) === 'attachment') {
                    $meta_key = 'idHash_' . $campo;
                    if (update_post_meta($postId, $meta_key, $file_id) === false) {
                        error_log("Error en confirmarArchivos: Fallo al actualizar meta {$meta_key} para el post ID: {$postId}");
                    }
                    confirmarHashId($file_id);
                } elseif ($file_id <= 0) {
                    error_log("Error en confirmarArchivos: ID de archivo inválido recibido para el campo {$campo}. Valor: {$_POST[$campo]}");
                } else {
                    error_log("Error en confirmarArchivos: ID {$file_id} recibido para el campo {$campo} no es un adjunto válido.");
                }
            }
        }
    }
}
