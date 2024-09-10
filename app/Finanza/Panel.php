<?php

function panelInversor()
{

    $resultados = calc_ing();
    $valEmp = "$" . number_format($resultados['valEmp'], 2, '.', '.');
    $valAcc = "$" . number_format($resultados['valAcc'], 2, '.', '.');

    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    $pro = get_user_meta($user_id, 'user_pro', true);
    $acc = get_user_meta($user->ID, 'acciones', true);
    $valD = $acc * $resultados['valAcc'];
    $name = ($user->display_name);
    ob_start();
?>

    <div class="XIGFOL">
        <p>Hola <?php echo $name ?></p>
        <p class="GJYGYE">Esta página solo es visible para inversores o sponsors</p>

    </div>

    <div class="XFBZWO MLJOFR">
        <div class="flex">

            <div class="QSBVLN">
                <p class="ZTHAWI">Total recaudado</p>
                <p class="BFUUUL">612$</p>
            </div>

            <div class="MDOKUH">
                <p class="ZTHAWI">Meta</p>
                <p class="BFUUUL">5000$</p>
            </div>

        </div>

        <div class="progress-container">
            <div class="progress-barA1"></div>
        </div>

    </div>

    <div class="GTVVIG">

        <div class="XFBZWO">
            <div class="flex justify-between items-center">
                <p class="ZTHAWI">Tu valor actual</p>
                <?php echo botonComprarAcciones('Comprar') ?>
            </div>
            <p class="BFUUUL">$<?php echo number_format($valD, 2, '.', '.'); ?></p>
            <div class="GraficoCapital">
                <?php echo graficoHistorialAcciones() ?>
            </div>
        </div>

        <div class="XFBZWO">
            <p class="ZTHAWI">Valor 2upra</p>
            <p class="BFUUUL"><?php echo $valEmp ?></p>
            <div class="GraficoCapital">
                <?php echo capitalValores() ?>
            </div>
        </div>

        <div class="XFBZWO">
            <p class="ZTHAWI">Valor Acción</p>
            <p class="BFUUUL"><?php echo $valAcc ?></p>
            <div class="GraficoCapital">
                <?php echo bolsavalores() ?>
            </div>
        </div>

    </div>

    <?php echo modalComprarAcciones() ?>

    <div class="YXJWYY flex ">
        <div class="XFBZWO">
            <?php echo formCompraAcciones() ?>
        </div>
        <div class="XFBZWO">
            <?php echo calcularAccionPorUsuario() ?>
        </div>
    </div>


<?php
    $contenido = ob_get_clean();
    return $contenido;
}
