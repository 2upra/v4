<?php
// Refactor(Org): Se movió la función formCompraAcciones desde app/Finanza/AgergarAcciones.php

function formCompraAcciones() {
    // Asegurarse de que current_user_can y agregar_acciones_unica_vez estén disponibles en el contexto de ejecución.
    // Podría ser necesario incluir 'app/Finanza/AgergarAcciones.php' o asegurar que WordPress cargue las funciones necesarias.
    if (!current_user_can('administrator')) {
        return '<p>No tienes permisos para ver este formulario.</p>';
    }

    ob_start();
    ?>
    <form id="formulario-acciones" method="post">
        <label for="user_id">ID de Usuario:</label>
        <input type="number" id="user_id" name="user_id" required>
        
        <label for="monto_pagado">Monto Pagado:</label>
        <input type="number" id="monto_pagado" name="monto_pagado" required>
        
        <input type="submit" name="submit_acciones" value="Agregar Acciones">
    </form>
    <?php // Cambiado <? a <?php por consistencia
    if (isset($_POST['submit_acciones'])) {
        $user_id = intval($_POST['user_id']);
        $monto_pagado = floatval($_POST['monto_pagado']);
        
        // Llamada a la función que ahora reside en app/Finanza/AgergarAcciones.php
        // Asegurarse de que esta función esté cargada/incluida.
        $resultado = agregar_acciones_unica_vez($user_id, $monto_pagado);
        
        if ($resultado['status'] === 'success') {
            echo '<p>Acciones agregadas exitosamente.</p>';
            echo '<p>ID de Usuario: ' . $resultado['user_id'] . '</p>';
            echo '<p>Acciones Compradas: ' . $resultado['acciones_compradas'] . '</p>';
            echo '<p>Acciones Totales: ' . $resultado['acciones_totales'] . '</p>';
            echo '<p>Valor de la Acción: ' . $resultado['valor_accion'] . '</p>';
        } else {
            echo '<p>Error: ' . $resultado['message'] . '</p>';
        }
    }
    return ob_get_clean();
}

