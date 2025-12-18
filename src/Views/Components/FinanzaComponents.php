<?php

namespace Kamples\Views\Components;

use Kamples\Services\Finanza\FinanzaService;
use Kamples\Services\Usuario\PerfilService;

/**
 * Componentes de UI para el módulo financiero.
 * 
 * Incluye panel de inversor, gráficos, modales y botones de compra.
 *
 * @since 1.0.0
 */
class FinanzaComponents
{
    /**
     * Renderiza el panel del inversor.
     *
     * @return string HTML del panel
     */
    public static function renderPanelInversor(): string
    {
        $finanzaService = FinanzaService::obtenerInstancia();
        $resultados = $finanzaService->calcularIngresos();
        $valEmp = "$" . number_format($resultados['valEmp'], 2, '.', '.');
        $valAcc = "$" . number_format($resultados['valAcc'], 2, '.', '.');

        $user = wp_get_current_user();
        $userId = get_current_user_id();
        $acc = get_user_meta($user->ID, 'acciones', true);
        $valD = $acc * $resultados['valAcc'];
        $name = esc_html($user->display_name);

        ob_start();
?>
        <div id="panel-inversor-container" class="XIGFOL">
            <p>Hola <?php echo $name; ?></p>
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
            <div class="progress-containerA1">
                <div class="progress-barA1"></div>
            </div>
        </div>

        <div class="GTVVIG">
            <div class="XFBZWO">
                <div class="flex justify-between items-center">
                    <p class="ZTHAWI">Tu valor actual</p>
                    <?php echo self::renderBotonDonar('Donar'); ?>
                </div>
                <p class="BFUUUL">$<?php echo number_format($valD, 2, '.', '.'); ?></p>
                <div class="GraficoCapital">
                    <?php echo self::renderGraficoHistorial(); ?>
                </div>
            </div>

            <div class="XFBZWO">
                <p class="ZTHAWI">Valor 2upra</p>
                <p class="BFUUUL"><?php echo $valEmp; ?></p>
                <div class="GraficoCapital">
                    <?php echo self::renderGraficoCapital(); ?>
                </div>
            </div>

            <div class="XFBZWO">
                <p class="ZTHAWI">Valor Acción</p>
                <p class="BFUUUL"><?php echo $valAcc; ?></p>
                <div class="GraficoCapital">
                    <?php echo self::renderGraficoBolsa(); ?>
                </div>
            </div>
        </div>

        <?php echo self::renderModalComprarAcciones(); ?>

        <?php if (current_user_can('administrator')) : ?>
            <div class="YXJWYY flex">
                <div class="XFBZWO">
                    <?php echo self::renderFormCompraAcciones(); ?>
                </div>
                <div class="XFBZWO">
                    <?php echo self::renderTablaAcciones(); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza los valores de la empresa.
     *
     * @return string HTML con los valores
     */
    public static function renderValores(): string
    {
        $finanzaService = FinanzaService::obtenerInstancia();
        $resultados = $finanzaService->calcularIngresos();

        $pIng = "$" . number_format($resultados['pIng'], 2, ',', '.');
        $valEmp = "$" . number_format($resultados['valEmp'], 2, ',', '.');
        $valAcc = "$" . number_format($resultados['valAcc'], 2, ',', '.');

        $output = '<div class="XXDD valorbolsa1" title="Ingresos promedio estimado">' . $pIng . '</div>';
        $output .= '<div class="XXDD valorbolsa1" title="Valor de la empresa estimado">' . $valEmp . '</div>';
        $output .= '<div class="XXDD valorbolsa1" title="Valor de la acción estimado">' . $valAcc . '</div>';

        return $output;
    }

    /**
     * Renderiza el botón de donar/comprar acciones.
     *
     * @param string $textoBoton Texto del botón
     * @return string HTML del botón
     */
    public static function renderBotonDonar(string $textoBoton = 'Donar'): string
    {
        $claseExtra = !is_user_logged_in() ? ' boton-sesion' : '';

        ob_start();
    ?>
        <button class="DZYBQD donar<?php echo $claseExtra; ?>" id="donarproyecto">
            <?php echo $GLOBALS['dolar'] ?? ''; ?><?php echo esc_html($textoBoton); ?>
        </button>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el botón de compra de beat/sample.
     *
     * @param int $postId ID del post
     * @return string HTML del botón
     */
    public static function renderBotonCompra(int $postId): string
    {
        $userId = get_current_user_id();
        $precio = get_post_meta($postId, 'precioRola1', true);

        if (empty($precio)) {
            $precio = get_post_meta($postId, 'precioRola', true);
        }

        $precio = is_numeric($precio) ? $precio : '0.00';

        ob_start();
    ?>
        <div class="TJKQGJ botonCompraDiv">
            <button
                class="botonCompra"
                data-post_id="<?php echo esc_attr($postId); ?>"
                data-user_id="<?php echo esc_attr($userId); ?>"
                data-nonce="<?php echo wp_create_nonce('compraNonce'); ?>">
                <?php echo $GLOBALS['dolar'] ?? ''; ?>
            </button>
            <span class="precioCount"><?php echo esc_html($precio); ?></span>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el botón de sponsor.
     *
     * @return string HTML del botón
     */
    public static function renderBotonSponsor(): string
    {
        $claseLogueado = is_user_logged_in() ? ' subpro' : ' boton-sesion';

        ob_start();
    ?>
        <button class="DZYBQD<?php echo $claseLogueado; ?>" id="sponsorButton">
            <?php echo $GLOBALS['iconoCorazon'] ?? ''; ?>Sponsor
        </button>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el modal de compra de acciones.
     *
     * @return string HTML del modal
     */
    public static function renderModalComprarAcciones(): string
    {
        ob_start();
    ?>
        <div class="HMPGRM" id="modalinvertir">
            <div id="contenidocomprar">
                <p class="ETXLXB">Ingresa la cantidad a donar</p>
                <input type="text" id="cantidadCompra" placeholder="$20">
                <input type="hidden" id="cantidadReal">
                <input type="hidden" id="userID" value="<?php echo get_current_user_id(); ?>">
                <p>"Al donar, parte de tu contribución se convierte en acciones de nuestra empresa a través de un fondo de inversión algorítmico que ajusta su valor automáticamente. Tu apoyo impulsa el proyecto y te hace parte de nuestro crecimiento con la autoridad de beneficiarte de nuestros frutos en el futuro"</p>
                <div class="DZYSQD DZYSQF">
                    <button class="DZYBQD cerrardonar">Volver</button>
                    <button class="DZYBQD botonprincipal" id="botonComprar">Donar</button>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el modal de suscripción PRO.
     *
     * @return string HTML del modal
     */
    public static function renderModalPro(): string
    {
        $planTitle = 'Suscripción';
        $highlight = '✨';

        ob_start();
    ?>
        <div class="panelperfilsup modalpro" id="propro">
            <div class="panelperfilsupsec pla1">
                <p class="titulomodal">Apoya el proyecto y recibe beneficios</p>
            </div>
            <div class="panelperfilsupsec plan2">
                <p class="tituloplan"><?php echo $planTitle . $highlight; ?></p>
                <p class="priceplan">$5 <span>USD/mensual</span></p>
                <p class="beneficiosplan">+ Contenido exclusivo</p>
                <p class="beneficiosplan">+ Reconocimiento</p>
                <p class="beneficiosplan">+ Sin limites de descarga</p>
                <p class="beneficiosplan">+ Sin limites de sincronización</p>
                <p class="beneficiosplan">+ Reprodución HD</p>
                <button class="DZYBQD MQKUSE">Suscribirte</button>
            </div>
        </div>

        <div class="panelperfilsup modalpro" id="proproacciones">
            <div class="panelperfilsupsec pla1">
                <p class="titulomodal">Apoya el proyecto y recibe beneficios</p>
            </div>
            <div class="panelperfilsupsec plan2">
                <p class="tituloplan"><?php echo $planTitle . $highlight; ?></p>
                <p class="priceplan">$5 <span>USD/mensual</span></p>
                <p class="beneficiosplan">+ Contenido exclusivo</p>
                <p class="beneficiosplan">+ Reconocimiento</p>
                <p class="beneficiosplan">+ Sin limites de descarga</p>
                <p class="beneficiosplan">+ Sin limites de sincronización</p>
                <p class="beneficiosplan">+ Reprodución HD</p>
                <button class="DZYBQD MQKUSE">Suscribirte</button>
            </div>
        </div>
        <div id="modalBackground" class="modal-background"></div>
    <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el formulario de compra de acciones (admin).
     *
     * @return string HTML del formulario
     */
    public static function renderFormCompraAcciones(): string
    {
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
<?php

        if (isset($_POST['submit_acciones'])) {
            $userId = intval($_POST['user_id']);
            $montoPagado = floatval($_POST['monto_pagado']);

            $finanzaService = FinanzaService::obtenerInstancia();
            $resultado = $finanzaService->agregarAccionesUnicaVez($userId, $montoPagado);

            if ($resultado['status'] === 'success') {
                echo '<p>Acciones agregadas exitosamente.</p>';
                echo '<p>ID de Usuario: ' . esc_html($resultado['user_id']) . '</p>';
                echo '<p>Acciones Compradas: ' . esc_html($resultado['acciones_compradas']) . '</p>';
                echo '<p>Acciones Totales: ' . esc_html($resultado['acciones_totales']) . '</p>';
                echo '<p>Valor de la Acción: ' . esc_html($resultado['valor_accion']) . '</p>';
            } else {
                echo '<p>Error: ' . esc_html($resultado['message']) . '</p>';
            }
        }

        return ob_get_clean();
    }

    /**
     * Renderiza la tabla de acciones por usuario.
     *
     * @param bool $mostrarTodos Mostrar todos o solo el actual
     * @return string HTML de la tabla
     */
    public static function renderTablaAcciones(bool $mostrarTodos = true): string
    {
        $finanzaService = FinanzaService::obtenerInstancia();
        $datos = $finanzaService->calcularAccionPorUsuario($mostrarTodos);

        if (is_string($datos)) {
            return esc_html($datos);
        }

        $output = '<table><thead><tr><th>Perfil</th><th>Usuario</th><th>Valor Total</th></tr></thead><tbody>';

        foreach ($datos as $usuario) {
            $imagen = PerfilService::obtenerInstancia()->obtenerImagenPerfil($usuario['user_id']);
            $output .= sprintf(
                '<tr><td><img src="%s" alt="%s" /></td><td>%s</td><td>$%s</td></tr>',
                esc_url($imagen),
                esc_attr($usuario['usuario']),
                esc_html($usuario['usuario']),
                number_format($usuario['valor_total'], 2, '.', '.')
            );
        }

        return $output . '</tbody></table>';
    }

    /**
     * Renderiza la tabla de transacciones.
     *
     * @return string HTML de la tabla
     */
    public static function renderTablaTransacciones(): string
    {
        $finanzaService = FinanzaService::obtenerInstancia();
        $transacciones = $finanzaService->obtenerTodasLasTransacciones();

        $output = '<table class="transactions-table"><thead><tr><th>Perfil</th><th>Usuario</th><th>Cantidad</th><th>Fecha</th></tr></thead><tbody>';

        foreach ($transacciones as $transaction) {
            $user = get_user_by('email', $transaction['user_email']);
            if ($user) {
                $imagen = PerfilService::obtenerInstancia()->obtenerImagenPerfil($user->ID);
                $output .= sprintf(
                    '<tr class="XXDD"><td><img src="%s" alt="%s" /></td><td>%s</td><td>$%s</td><td>%s</td></tr>',
                    esc_url($imagen),
                    esc_attr($user->user_login),
                    esc_html($user->user_login),
                    esc_html($transaction['cantidad']),
                    esc_html($transaction['fecha'])
                );
            }
        }

        return $output . '</tbody></table>';
    }

    /* 
     * Métodos de gráficos
     */

    /**
     * Renderiza el gráfico del historial de acciones del usuario.
     *
     * @return string HTML del gráfico
     */
    public static function renderGraficoHistorial(): string
    {
        $finanzaService = FinanzaService::obtenerInstancia();
        $historial = $finanzaService->obtenerHistorialAccionesUsuario();

        $datos = [];
        foreach ($historial as $registro) {
            $datos[] = ['time' => $registro->fecha, 'value' => $registro->acciones];
        }

        return self::generarCodigoGrafico('myChartHistorial', json_encode($datos));
    }

    /**
     * Renderiza el gráfico del valor de capital.
     *
     * @return string HTML del gráfico
     */
    public static function renderGraficoCapital(): string
    {
        $finanzaService = FinanzaService::obtenerInstancia();
        $resultado = $finanzaService->calcularIngresos(48, []);
        $valEmp = $resultado['valEmp'];

        $datosJSON = self::obtenerDatosGrafico('capital', 'time1', 'value1', $valEmp);

        return self::generarCodigoGrafico('myChart', $datosJSON);
    }

    /**
     * Renderiza el gráfico del valor de la acción.
     *
     * @return string HTML del gráfico
     */
    public static function renderGraficoBolsa(): string
    {
        $finanzaService = FinanzaService::obtenerInstancia();
        $resultado = $finanzaService->calcularIngresos(48, []);
        $valAcc = $resultado['valAcc'];

        $datosJSON = self::obtenerDatosGrafico('bolsa', 'time', 'value', $valAcc);

        return self::generarCodigoGrafico('myChartBolsa', $datosJSON);
    }

    /**
     * Obtiene y actualiza datos del gráfico desde la BD.
     */
    private static function obtenerDatosGrafico(string $tabla, string $columnaTiempo, string $columnaValor, float $valor): string
    {
        $mysqli = self::getDatabaseConnection();
        if (!$mysqli) {
            return '[]';
        }

        self::limpiarDatosHistoricos($mysqli, $tabla, $columnaTiempo);
        self::actualizarOInsertarValor($mysqli, $tabla, $columnaTiempo, $columnaValor, $valor);
        $datosJSON = self::obtenerDatosJSON($mysqli, $tabla, $columnaTiempo, $columnaValor);
        $mysqli->close();

        return $datosJSON;
    }

    /**
     * Obtiene conexión a la base de datos.
     */
    private static function getDatabaseConnection(): ?\mysqli
    {
        if (!isset($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME'])) {
            return null;
        }

        $mysqli = new \mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

        if ($mysqli->connect_error) {
            return null;
        }

        return $mysqli;
    }

    /**
     * Limpia datos históricos de una tabla.
     */
    private static function limpiarDatosHistoricos(\mysqli $mysqli, string $tabla, string $columnaTiempo): void
    {
        $mysqli->query("
            DELETE t1 FROM $tabla t1
            INNER JOIN (
                SELECT DATE($columnaTiempo) as date, MAX($columnaTiempo) as max_time
                FROM $tabla
                GROUP BY DATE($columnaTiempo)
            ) t2 ON DATE(t1.$columnaTiempo) = t2.date AND t1.$columnaTiempo < t2.max_time
        ");
    }

    /**
     * Actualiza o inserta un valor en una tabla.
     */
    private static function actualizarOInsertarValor(\mysqli $mysqli, string $tabla, string $columnaTiempo, string $columnaValor, float $valor): void
    {
        $currentTime = time();
        $currentDate = date('Y-m-d');

        $result = $mysqli->query("SELECT * FROM $tabla WHERE DATE($columnaTiempo) = '$currentDate'");
        $existingRow = $result->fetch_assoc();

        if ($existingRow) {
            $lastTime = strtotime($existingRow[$columnaTiempo]);
            if ($currentTime - $lastTime >= 5) {
                $time = date('Y-m-d H:i:s');
                $stmt = $mysqli->prepare("UPDATE $tabla SET $columnaTiempo = ?, $columnaValor = ? WHERE DATE($columnaTiempo) = ?");
                $stmt->bind_param("sds", $time, $valor, $currentDate);
                $stmt->execute();
            }
        } else {
            $time = date('Y-m-d H:i:s');
            $stmt = $mysqli->prepare("INSERT INTO $tabla ($columnaTiempo, $columnaValor) VALUES (?, ?)");
            $stmt->bind_param("sd", $time, $valor);
            $stmt->execute();
        }
    }

    /**
     * Obtiene datos de una tabla y los convierte a JSON.
     */
    private static function obtenerDatosJSON(\mysqli $mysqli, string $tabla, string $columnaTiempo, string $columnaValor): string
    {
        $datos = [];
        $result = $mysqli->query("SELECT * FROM $tabla ORDER BY $columnaTiempo DESC");

        while ($row = $result->fetch_assoc()) {
            $datos[] = ['time' => $row[$columnaTiempo], 'value' => $row[$columnaValor]];
        }

        return json_encode($datos);
    }

    /**
     * Genera el código del gráfico con Chart.js.
     */
    private static function generarCodigoGrafico(string $idCanvas, string $datosJSON): string
    {
        return '
        <canvas id="' . esc_attr($idCanvas) . '"></canvas>
        <script type="text/javascript">
            (function() {
                var chart_' . $idCanvas . ';

                function generarGrafico_' . $idCanvas . '() {
                    var ctx = document.getElementById("' . $idCanvas . '").getContext("2d");
                    var datos = ' . $datosJSON . ';

                    var labels = datos.map(function(e) { return e.time; });
                    var data = datos.map(function(e) { return e.value; });

                    if (chart_' . $idCanvas . ') {
                        chart_' . $idCanvas . '.destroy();
                    }

                    chart_' . $idCanvas . ' = new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                borderColor: "#fff",
                                borderWidth: 2,
                                pointRadius: 0,
                                fill: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: {
                                    type: "time",
                                    time: { unit: "week", stepSize: 7 },
                                    ticks: { display: false },
                                    grid: { display: false }
                                },
                                y: {
                                    beginAtZero: false,
                                    ticks: { display: false },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }

                function esperarChartJS_' . $idCanvas . '() {
                    if (typeof Chart !== "undefined") {
                        generarGrafico_' . $idCanvas . '();
                    } else {
                        setTimeout(esperarChartJS_' . $idCanvas . ', 100);
                    }
                }

                esperarChartJS_' . $idCanvas . '();
            })();
        </script>';
    }
}

/* 
 * Registrar modal PRO en el footer
 */
add_action('wp_footer', function () {
    echo FinanzaComponents::renderModalPro();
});
