<?php
// Refactor(Org): Moved function sumaAcciones to app/Services/EconomyService.php
// Refactor(Org): Moved monthly cron functions and hooks to app/Cron/MonthlyActionsCron.php

// Eliminar evento cron mensual al desactivar
function eliminar_evento_mensual() {
    wp_clear_scheduled_hook('accion_mensual_user_pro');
}
register_deactivation_hook(__FILE__, 'eliminar_evento_mensual');


