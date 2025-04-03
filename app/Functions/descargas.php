<?php

// Refactor(Org): Función procesarDescarga() y su hook AJAX movidos a app/Services/DownloadService.php
// Refactor(Org): Función generarEnlaceDescarga() movida a app/Services/DownloadService.php
// Refactor(Org): Función descargaAudio() y su hook template_redirect movidos a app/Services/DownloadService.php

add_action('wp_ajax_procesarDescarga', 'procesarDescarga');


// Refactor(Org): Funcion botonSincronizar() movida a app/View/Helpers/UIHelper.php

// Refactor(Org): Funcion botonDescarga movida a app/View/Helpers/UIHelper.php
}
