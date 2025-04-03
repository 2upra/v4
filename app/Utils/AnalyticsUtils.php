<?php

// Funcion guardarVista() movida a app/Services/AnalyticsService.php
// Funcion obtenerVistasPosts() movida a app/Services/AnalyticsService.php
// Funcion limpiarVistasAntiguas() movida a app/Services/AnalyticsService.php

// Hooks para manejar la petición AJAX de guardar vistas
// ADVERTENCIA: La función 'guardarVista' fue movida a AnalyticsService.php.
// Estos hooks ahora apuntan a una función que no existe en este archivo.
// Deberán ser actualizados o movidos para que funcionen correctamente.
add_action('wp_ajax_guardar_vistas', 'guardarVista');        // Para usuarios logueados
add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista'); // Para usuarios no logueados

