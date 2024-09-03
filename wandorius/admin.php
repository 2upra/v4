<?php

function reportesAdmin() {

    if ( ! current_user_can( 'administrator' ) ) {
        return ''; 
    }

    ob_start(); 
    ?>
        <div class="QUHTCR">

            <div class="iconosacciones">
                <!-- Transacciones-->
                <button id="BotonListaTransacciones" style="all:unset">
                    <?php echo $GLOBALS['iconolista']; ?><span>Transacciones</span>
                </button>
                <!-- Reporte de errores-->
                <button id="BotonErrores" style="all:unset">
                    <?php echo $GLOBALS['iconobugs']; ?><span>Reportes</span>
                </button>
            </div>
            <!-- Contenido transacciones -->
            <div id="ContenidoListaTranssacciones" class="transacciones">
                <?php echo generate_transactions_table(); ?>
            </div>

            <div id="ContenidoErrores" class="transacciones reportescontenido">
                <?php echo reportes(); ?>
            </div>

        </div>


    <?php
    $contenido = ob_get_clean(); 
    return $contenido;
}

function logsAdmin() {
    if ( ! current_user_can( 'administrator' ) ) {
        return ''; 
    }

    ob_start();
    ?>
        <div class="QUHTCR">

            <div class="iconosacciones">
                <!-- Transacciones -->
                <button id="BotonLogs1">
                    <?php echo $GLOBALS['iconobugs']; ?><span>Propios</span>
                </button>
                <!-- Reporte de errores -->
                <button id="BotonLogs2">
                    <?php echo $GLOBALS['iconobugs']; ?><span>Wordpress</span>
                </button>
            </div>
            <!-- Contenido logs propios -->
            <div id="ContenidoLogs1" class="logscontenido">
                <?php echo do_shortcode('[mostrar_logs_para_admin]'); ?>
            </div>

            <!-- Contenido logs Wordpress -->
            <div id="ContenidoLogs2" class="logscontenido">
                <?php echo do_shortcode('[mostrar_logs_para_admin_w]'); ?>
            </div>

        </div>

    <?php
    $contenido = ob_get_clean();
    return $contenido;
}
