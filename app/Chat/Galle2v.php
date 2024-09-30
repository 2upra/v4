<?php


// No se que estoy haciendo exactamente, solo quiero que el chat sea seguro y que solo el usuario que corresponde envie el guardarMensaje

add_action('rest_api_init', function () {
    register_rest_route('galle/v1', '/procesarMensaje', array(
        'methods' => 'POST',
        'callback' => 'procesarMensaje',
        'permission_callback' => function ($request) {
            $token = $request->get_header('X-WP-Nonce');
            return wp_verify_nonce($token, 'mi_chat_nonce');
        }
    ));
});

add_action('rest_api_init', function () {
    register_rest_route('galle/v1', '/verificarToken', array(
        'methods' => 'POST',
        'callback' => 'verificarToken',
        'permission_callback' => '__return_true'
    ));
});

add_action('wp_ajax_generarToken', 'generarToken');
//esto envia un token al cliente
function generarToken() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
    }
    $user_id = get_current_user_id();
    $token = wp_create_nonce('mi_chat_nonce');
    wp_send_json_success(['token' => $token]);
}

function verificarToken($request) {
    $token = $request->get_param('token');
    $user_id = wp_verify_nonce($token, 'mi_chat_nonce');
    if ($user_id) {
        return new WP_REST_Response(['valid' => true, 'user_id' => $user_id], 200);
    } else {
        return new WP_REST_Response(['valid' => false], 401);
    }
}

/*
cliente

    function connectWebSocket() {
        ws = new WebSocket(wsUrl);
        ws.onopen = () => {
            console.log('Conexión WebSocket abierta');
            ws.send(
                JSON.stringify({
                    emisor,
                    type: 'auth',
                    token: token
                })
            );
            pingInterval = setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({type: 'ping'}));
                }
            }, 30000); 
        };
        ws.onclose = () => {
            clearInterval(pingInterval);
            console.log('Conexión cerrada. Reintentando en 5 segundos...');
            setTimeout(connectWebSocket, 5000); 
        };
        ws.onerror = error => {
            console.error('Error en WebSocket:', error);
        };
        ws.onmessage = ({data}) => {
            const message = JSON.parse(data);
            if (message.type === 'pong') {
                console.log('Pong recibido');
            } else if (message.type === 'set_emisor') {
                ws.send(JSON.stringify({emisor}));
            } else {
                manejarMensajeWebSocket(JSON.stringify(message));
            }
        };
    }

    parte del websocket

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->users = [];
        $this->autenticados = [];
        echo "Chat instance created\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Envía una instrucción para que el cliente establezca el emisor
        $conn->send(json_encode(['type' => 'set_emisor']));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Mensaje recibido de {$from->resourceId}: " . $msg . "\n";

        $data = json_decode($msg, true);

        if (!$data) {
            echo "Error: Mensaje no es JSON válido\n";
            return;
        }

        if (isset($data['type']) && $data['type'] === 'auth') {
            $this->verificarToken($from, $data['token']);
            return;
        }

        if (!isset($this->autenticados[$from->resourceId])) {
            $from->send(json_encode(['error' => 'No autenticado']));
            return;
        }

        if (isset($data['type']) && $data['type'] === 'ping') {
            // Responder con un pong
            $from->send(json_encode(['type' => 'pong']));
            echo "Pong enviado a {$from->resourceId}\n";
            return;
        }

        // Si el mensaje tiene un emisor y aún no se ha asociado con la conexión
        if (isset($data['emisor']) && !isset($this->users[$from->resourceId])) {
            $this->users[$from->resourceId] = $data['emisor'];
            echo "Emisor {$data['emisor']} asociado con conexión {$from->resourceId}\n";
        }

        // Verificar si el emisor está correctamente asociado
        if (!isset($this->users[$from->resourceId])) {
            echo "Error: Emisor no está asociado a la conexión {$from->resourceId}\n";
            return;
        }

        // Enviar el mensaje al receptor si está especificado
        if (isset($data['receptor'])) {
            $receptorId = $data['receptor'];
            echo "Buscando receptor con ID: {$receptorId}\n";

            $receptorEncontrado = false;
            foreach ($this->clients as $client) {
                if (isset($this->users[$client->resourceId]) && $this->users[$client->resourceId] == $receptorId) {
                    $client->send($msg);
                    echo "Mensaje enviado al receptor {$receptorId} (conexión {$client->resourceId})\n";
                    $receptorEncontrado = true;
                    break;
                }
            }

            if (!$receptorEncontrado) {
                echo "Receptor {$receptorId} no encontrado o no conectado\n";
            }
        } else {
            echo "Mensaje sin receptor\n";
        }

        // Guardar el mensaje en WordPress
        echo "Intentando guardar mensaje en WordPress...\n";
        $this->guardarMensajeEnWordPress($data, $this->autenticados[$from->resourceId]);
    }

    private function verificarToken(ConnectionInterface $conn, $token)
    {
        $url = 'https://2upra.com/wp-json/galle/v1/verificarToken';
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode(['token' => $token])
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            $conn->send(json_encode(['type' => 'auth', 'status' => 'error']));
        } else {
            $response = json_decode($result, true);
            if ($response['valid']) {
                $this->autenticados[$conn->resourceId] = $token;
                $conn->send(json_encode(['type' => 'auth', 'status' => 'success']));
            } else {
                $conn->send(json_encode(['type' => 'auth', 'status' => 'failed']));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->users[$conn->resourceId]);
        unset($this->autenticados[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error en la conexión {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    private function guardarMensajeEnWordPress($data, $token)
    {
        $url = 'https://2upra.com/wp-json/galle/v1/procesarMensaje';

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n" .
                             "X-WP-Nonce: $token\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === FALSE) {
            echo "Error al guardar el mensaje en WordPress. Detalles del error:\n";
            print_r(error_get_last());
        } else {
            echo "Respuesta de WordPress: " . $result . "\n";
        }
    }

*/

function procesarMensaje($request) {
    chatLog($request, 'Iniciando procesarMensaje');
    
    // Obtener el usuario actual autenticado
    $usuario_actual = wp_get_current_user();
    
    // Si no hay usuario autenticado o ID no es válido
    if (!$usuario_actual->exists()) {
        chatLog($request, 'Error: Usuario no autenticado');
        return new WP_Error('usuario_no_autenticado', 'Usuario no autenticado', array('status' => 403));
    }

    $params = $request->get_json_params();
    chatLog($request, 'Parámetros recibidos: ' . json_encode($params));
    
    $emisor = isset($params['emisor']) ? $params['emisor'] : null;
    $receptor = isset($params['receptor']) ? $params['receptor'] : null;
    $mensaje = isset($params['mensaje']) ? $params['mensaje'] : null;
    $adjunto = isset($params['adjunto']) ? $params['adjunto'] : null;
    $metadata = isset($params['metadata']) ? $params['metadata'] : null;

    // Verificar si los parámetros requeridos están presentes
    if (!$emisor || !$receptor || !$mensaje) {
        chatLog($request, 'Error: Datos incompletos');
        return new WP_Error('datos_incompletos', 'Faltan datos requeridos', array('status' => 400));
    }

    // Verificar si el emisor es el mismo que el usuario autenticado
    if ($emisor != $usuario_actual->ID) {
        chatLog($request, 'Error: El emisor no coincide con el usuario autenticado. Emisor: ' . $emisor . ', Usuario autenticado: ' . $usuario_actual->ID);
        return new WP_Error('emisor_no_autorizado', 'El emisor no coincide con el usuario autenticado', array('status' => 403));
    }

    chatLog($request, 'Intentando guardar mensaje');
    
    try {
        // Intentar guardar el mensaje
        $resultado = guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata);
        
        if ($resultado) {
            chatLog($request, 'Mensaje guardado con éxito');
            return new WP_REST_Response(['success' => true], 200);
        } else {
            chatLog($request, 'Error: No se pudo guardar el mensaje');
            return new WP_Error('error_guardado', 'No se pudo guardar el mensaje', array('status' => 500));
        }
    } catch (Exception $e) {
        chatLog($request, 'Excepción al guardar mensaje: ' . $e->getMessage());
        return new WP_Error('error_interno', 'Se produjo un error interno', array('status' => 500));
    }
}

function guardarMensaje($emisor, $receptor, $mensaje, $adjunto = null, $metadata = null)
{
    global $wpdb;
    $tablaMensajes = $wpdb->prefix . 'mensajes';
    $tablaConversacion = $wpdb->prefix . 'conversacion';

    // Asegurarse de que los valores de emisor y receptor sean enteros
    $emisor = (int) $emisor;
    $receptor = (int) $receptor;

    // Iniciar la transacción
    $wpdb->query('START TRANSACTION');

    try {
        // Intentar obtener la conversación
        $query = $wpdb->prepare("
            SELECT id FROM $tablaConversacion
            WHERE tipo = 1
            AND JSON_CONTAINS(participantes, %s)
            AND JSON_CONTAINS(participantes, %s)
        ", json_encode($emisor), json_encode($receptor));

        $conversacionID = $wpdb->get_var($query);

        if (!$conversacionID) {
            // Guardar los participantes como enteros en formato JSON
            $participantes = json_encode([$emisor, $receptor], JSON_NUMERIC_CHECK);
            $wpdb->insert($tablaConversacion, [
                'tipo' => 1,
                'participantes' => $participantes,
                'fecha' => current_time('mysql')
            ]);
            $conversacionID = $wpdb->insert_id;
            chatLog("Nueva conversación creada con ID: $conversacionID");
        } else {
            chatLog("Conversación existente encontrada con ID: $conversacionID");
        }

        $resultado = $wpdb->insert($tablaMensajes, [
            'conversacion' => $conversacionID,
            'emisor' => $emisor,
            'mensaje' => $mensaje,
            'fecha' => current_time('mysql'),
            'adjunto' => isset($adjunto) ? json_encode($adjunto) : null,
            'metadata' => isset($metadata) ? json_encode($metadata) : null,
        ]);

        if ($resultado === false) {
            throw new Exception("Error al insertar el mensaje: " . $wpdb->last_error);
        }

        $mensajeID = $wpdb->insert_id;

        // Confirmar la transacción
        $wpdb->query('COMMIT');
        chatLog("Mensaje guardado con ID: $mensajeID en la conversación: $conversacionID");

        return $mensajeID;
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log($e->getMessage());
        chatLog("Error al guardar el mensaje: " . $e->getMessage());
        return false;
    }
}



