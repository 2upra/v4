<?php // Asegúrate de que el tag de apertura de PHP esté presente y sea correcto

// Asumiendo que esta acción llama a una función que procesa las notificaciones
// add_action('wp_enqueue_notifications', 'procesar_notificaciones');


add_filter('cron_schedules', function ($schedules) {
    $schedules['minute'] = [
        'interval' => 15,
        'display'  => __('Cada minuto')
    ];
    return $schedules;
});


// Refactor(Org): Funciones registrarNombreRolas y registrarPrecios movidas a app/Services/PostService.php

// Refactor(Org): Funcion datosParaAlgoritmo movida a app/Services/PostService.php

// Refactor(Org): Funcion confirmarArchivos movida a app/Services/Post/PostAttachmentService.php

// Refactor(Org): Funciones de manejo de adjuntos y audio movidas a app/Services/Post/PostAttachmentService.php

?>
