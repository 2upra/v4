<?

use GuzzleHttp\Client;

// Refactor(Org): Función generarDescripcionIAConURI movida a app/Services/IAService.php



// Refactor(Org): Función subirArchivo movida a app/Services/IAService.php

// Refactor(Org): Función generarDescripcionIA ya se encuentra en app/Services/IAService.php
// // No se realiza la acción de mover ya que la función no existe en este archivo.


add_action('wp_ajax_ai_request', 'iaSend');
add_action('wp_ajax_nopriv_ai_request', 'iaSend');

// Refactor(Org): Función generarDescripcionIAPro movida a app/Services/IAService.php

add_action('wp_ajax_ai_request', 'iaSend');
add_action('wp_ajax_nopriv_ai_request', 'iaSend');


