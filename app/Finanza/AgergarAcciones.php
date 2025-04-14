<?
// Refactor(Org): Moved function agregar_acciones_unica_vez to app/Services/EconomyService.php

function formCompraAcciones() {
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
    <?
    if (isset($_POST['submit_acciones'])) {
        $user_id = intval($_POST['user_id']);
        $monto_pagado = floatval($_POST['monto_pagado']);
        
        // Ensure EconomyService is loaded or include it if necessary
        // Assuming EconomyService.php and its functions are globally available or autoloaded
        if (function_exists('agregar_acciones_unica_vez')) {
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
        } else {
             echo '<p>Error: La función agregar_acciones_unica_vez no está disponible.</p>';
        }
    }
    return ob_get_clean();
}

