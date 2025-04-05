<?php

add_action('wp_ajax_ajustar_zona_horaria', 'ajustarZonaHoraria');
add_action('wp_ajax_nopriv_ajustar_zona_horaria', 'ajustarZonaHoraria');

function ajustarZonaHoraria()
{
    $zona_horaria = isset($_POST['timezone']) ? $_POST['timezone'] : 'UTC';
    // Asegurarse de que la zona horaria es válida antes de usarla
    try {
        new DateTimeZone($zona_horaria);
    } catch (Exception $e) {
        $zona_horaria = 'UTC'; // Volver a UTC si no es válida
    }
    setcookie('usuario_zona_horaria', $zona_horaria, time() + 86400, '/');
    wp_die();
}

// Refactor(Org): Funcion TiempoRelativoNoti movida a app/View/Helpers/NotificationHelper.php

// Refactor(Org): Funcion tiempoRelativo() movida a app/View/Helpers/DateHelper.php
