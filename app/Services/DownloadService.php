<?php

// Refactor(Org): Función procesarDescarga() y su hook AJAX movidos desde app/Functions/descargas.php

/**
 * Procesa la solicitud de descarga de un audio individual o una colección.
 *
 * Maneja la lógica de verificación de usuario, post, pinkys y registro de descargas.
 * Genera el enlace de descarga o confirma la sincronización.
 */
function procesarDescarga()
{
    $userId = get_current_user_id();
    error_log("Inicio del proceso de descarga. User ID: " . $userId);

    if (!$userId) {
        error_log("Error: Usuario no autorizado.");
        wp_send_json_error(['message' => 'No autorizado.']);
        return;
    }

    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $esColeccion = isset($_POST['coleccion']) && $_POST['coleccion'] === 'true';
    $sync = isset($_POST['sync']) && $_POST['sync'] === 'true';
    error_log("Post ID: " . $postId . ", esColeccion: " . ($esColeccion ? 'true' : 'false') . ", sync: " . ($sync ? 'true' : 'false'));

    $post = get_post($postId);
    if (!$post || $post->post_status !== 'publish') {
        error_log("Error: Post no válido o no publicado. Post ID: " . $postId);
        wp_send_json_error(['message' => 'Post no válido.']);
        return;
    }

    if ($esColeccion) {
        error_log("Procesando colección. Post ID: " . $postId);

        if (!$sync) {
            $zipUrl = procesarColeccion($postId, $userId);
            if (is_wp_error($zipUrl)) {
                error_log("Error en procesarColeccion: " . $zipUrl->get_error_message());
                wp_send_json_error(['message' => $zipUrl->get_error_message()]);
                return;
            }
            // Generar enlace de descarga para la colección
            $downloadUrl = generarEnlaceDescargaColeccion($userId, $zipUrl, $postId);
            error_log("URL de descarga de colección generada: " . $downloadUrl);
        } else {
            procesarColeccion($postId, $userId, true);
        }
    } else {
        error_log("Procesando descarga individual. Post ID: " . $postId);
        $audioId = get_post_meta($postId, 'post_audio', true);
        if (!$audioId) {
            error_log("Error: Audio no encontrado. Post ID: " . $postId);
            wp_send_json_error(['message' => 'Audio no encontrado.']);
            return;
        }

        $descargasAnteriores = get_user_meta($userId, 'descargas', true);
        error_log("Descargas anteriores: " . print_r($descargasAnteriores, true));

        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
            error_log("Descargas anteriores no era un array, se inicializa como array vacío.");
        }

        $yaDescargado = isset($descargasAnteriores[$postId]);
        error_log("Ya descargado: " . ($yaDescargado ? 'true' : 'false'));

        if (!$yaDescargado) {
            $pinky = (int)get_user_meta($userId, 'pinky', true);
            error_log("Pinkys del usuario: " . $pinky);
            if ($pinky < 1) {
                error_log("Error: No hay suficientes Pinkys.");
                wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
                return;
            }
            restarPinkys($userId, 1);
            error_log("Pinkys restados.");
        }

        if (!$yaDescargado) {
            $descargasAnteriores[$postId] = 1;
            error_log("Primera descarga, se agrega al registro.");
        } else {
            $descargasAnteriores[$postId]++;
            error_log("Descarga repetida, se incrementa el contador.");
        }

        update_user_meta($userId, 'descargas', $descargasAnteriores);
        error_log("Descargas del usuario actualizadas.");

        $totalDescargas = (int)get_post_meta($postId, 'totalDescargas', true);
        $totalDescargas++;
        update_post_meta($postId, 'totalDescargas', $totalDescargas);
        error_log("Total de descargas del post actualizado: " . $totalDescargas);

        if (!$sync) {
            // Refactor(Org): Función generarEnlaceDescarga() movida desde app/Functions/descargas.php
            $downloadUrl = generarEnlaceDescarga($userId, $audioId);
            error_log("URL de descarga generada: " . $downloadUrl);
        }
    }

    actualizarTimestampDescargas($userId);
    error_log("Timestamp de descargas actualizado.");

    if (!$sync) {
        wp_send_json_success(['download_url' => $downloadUrl]);
    } else {
        wp_send_json_success(['message' => 'Sincronizado.']);
    }

    error_log("Fin del proceso de descarga.");
}

// Refactor(Org): Función generarEnlaceDescarga() movida desde app/Functions/descargas.php
function generarEnlaceDescarga($userID, $audioID)
{
    $token = bin2hex(random_bytes(16));

    $token_data = array(
        'user_id' => $userID,
        'audio_id' => $audioID,
        'time' => time(),
        'usos' => 0, // Inicializar el contador de usos
    );

    error_log("--------------------------------------------------");
    error_log("[Inicio] Generando enlace de descarga. UserID: " . $userID . ", AudioID: " . $audioID . ", Token: " . $token . ", Time: " . time());

    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora
    error_log("Token data set in transient: " . print_r($token_data, true));

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
    ], home_url());

    error_log("Enlace de descarga generado: " . $enlaceDescarga);
    error_log("[Fin] Generando enlace de descarga.");
    error_log("--------------------------------------------------");
    return $enlaceDescarga;
}

add_action('wp_ajax_descargar_audio', 'procesarDescarga');
