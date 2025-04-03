<?php

// Funcion guardarVista() movida a app/Services/AnalyticsService.php
// Funcion obtenerVistasPosts() movida a app/Services/AnalyticsService.php
// Funcion limpiarVistasAntiguas() movida a app/Services/AnalyticsService.php

// Hooks AJAX movidos a app/Services/AnalyticsService.php junto con su callback guardarVista()
// add_action('wp_ajax_guardar_vistas', 'guardarVista');        // Para usuarios logueados
// add_action('wp_ajax_nopriv_guardar_vistas', 'guardarVista'); // Para usuarios no logueados

