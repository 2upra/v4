<?php

// Refactor(Org): Moved function comprobarConexionBD from app/Content/Logic/datosParaCalculo.php
function comprobarConexionBD() {
    global $wpdb;
    $tiempoInicio = microtime(true);

    if (!$wpdb) {
        //guardarLog("[comprobarConexionBD] Error crítico: No se pudo acceder a la base de datos wpdb");
        //rendimientolog("[comprobarConexionBD] Terminó con error crítico (sin acceso a \$wpdb) en " . (microtime(true) - $tiempoInicio) . " segundos");
        return false;
    }
    return true;
}
