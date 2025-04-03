<?php

// Refactor(Org): Función procesarDescarga() y su hook AJAX movidos a app/Services/DownloadService.php
// Refactor(Org): Función generarEnlaceDescarga() movida a app/Services/DownloadService.php
// Refactor(Org): Función descargaAudio() y su hook template_redirect movidos a app/Services/DownloadService.php

add_action('wp_ajax_procesarDescarga', 'procesarDescarga');


function botonSincronizar($postId)
{
    ob_start();
    $paraDescarga = get_post_meta($postId, 'paraDescarga', true);
    $userId = get_current_user_id();

    if ($paraDescarga == '1') {
        if ($userId) {
            $descargasAnteriores = get_user_meta($userId, 'descargas', true);
            $yaDescargado = isset($descargasAnteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
            $esColeccion = get_post_type($postId) === 'colecciones' ? 'true' : 'false';


?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down <?php echo esc_attr($claseExtra); ?>"
                    data-post-id="<?php echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<?php echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($userId); ?>', '<?php echo $esColeccion; ?>')">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
        <?php
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
<?php
        }
    }
    return ob_get_clean();
}
}
// Refactor(Org): Funcion botonDescarga movida a app/View/Helpers/UIHelper.php
}
