<?php

// Refactor(Org): Moved function definir_acciones_usuario to app/Services/EconomyService.php

$usuarios_acciones = [
    1 => 420000,
    40 => 4000,
    41 => 12000,
    45 => 9000, //HORACIO
    49 => 5000,
    51 => 6500
];


// Refactor(Org): Moved function obtenerHistorialAccionesUsuario to app/Services/EconomyCalculationService.php

// Refactor(Org): Moved function registrarHistorialAcciones to app/Cron/HourlyActionsCron.php

// Refactor(Org): Moved function registrar_evento_cron_historial_acciones and its hook to app/Cron/HourlyActionsCron.php

// Refactor(Org): Moved function calcularAccionPorUsuario to app/Services/EconomyService.php
