<?

function crearTablaLista() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Definir los nombres de las tablas con el prefijo de WordPress
    $tablaListas = $wpdb->prefix . 'listas';
    $tablaItemsLista = $wpdb->prefix . 'listasItem';

    // Verificar y crear la tabla wp_user_lists si no existe
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tablaListas ) ) != $tablaListas ) {
        $sqlListas = "CREATE TABLE $tablaListas (
            listaId BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuarioId BIGINT UNSIGNED NOT NULL,
            nombreLista VARCHAR(255) NOT NULL,
            descripcion TEXT DEFAULT NULL,
            fechaCreada DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (listaId),
            KEY usuarioId (usuarioId),
            FOREIGN KEY (usuarioId) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sqlListas );

        // Opcional: Puedes registrar una opción para indicar que la tabla ha sido creada
        add_option( 'user_lists_tables_created', true );
    }

    // Verificar y crear la tabla wp_user_list_items si no existe
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $tablaItemsLista ) ) != $tablaItemsLista ) {
        $sqlItemsLista = "CREATE TABLE $tablaItemsLista (
            itemId BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            listaId BIGINT UNSIGNED NOT NULL,
            postId BIGINT UNSIGNED NOT NULL,
            fechaAgregada DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (itemId),
            KEY listaId (listaId),
            KEY postId (postId),
            FOREIGN KEY (listaId) REFERENCES $tablaListas(listaId) ON DELETE CASCADE,
            FOREIGN KEY (postId) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sqlItemsLista );

        // Opcional: Puedes registrar una opción para indicar que la tabla ha sido creada
        add_option( 'user_list_items_tables_created', true );
    }
}

// Ejecutar la función en el hook 'init'
add_action( 'init', 'crearTablaLista' );

function crearLista($usuarioId, $nombreLista, $descripcion = '') {
    global $wpdb;
    $tablaListas = $wpdb->prefix . 'listas';
    
    // Verificar que el usuario exista
    if ( ! get_userdata( $usuarioId ) ) {
        return new WP_Error( 'usuario_invalido', 'El usuario no existe.' );
    }
    
    // Verificar que el nombre de la lista no esté vacío
    if ( empty( $nombreLista ) ) {
        return new WP_Error( 'nombre_vacio', 'El nombre de la lista no puede estar vacío.' );
    }
    
    // Insertar la nueva lista
    $resultado = $wpdb->insert(
        $tablaListas,
        array(
            'usuarioId'    => $usuarioId,
            'nombreLista'  => sanitize_text_field( $nombreLista ),
            'descripcion'  => sanitize_textarea_field( $descripcion ),
            'fechaCreada'  => current_time( 'mysql' ),
        ),
        array(
            '%d',
            '%s',
            '%s',
            '%s',
        )
    );
    
    if ( false === $resultado ) {
        return new WP_Error( 'error_bd', 'Error al crear la lista.' );
    }
    
    return $wpdb->insert_id;
}

function enlistarPost($listaId, $postId) {
    global $wpdb;
    $tablaListas = $wpdb->prefix . 'listas';
    $tablaItemsLista = $wpdb->prefix . 'listasItem';
    
    // Verificar que la lista existe
    $lista = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tablaListas WHERE listaId = %d", $listaId ) );
    if ( ! $lista ) {
        return new WP_Error( 'lista_invalida', 'La lista no existe.' );
    }
    
    // Verificar que el post existe y está publicado
    $post = get_post( $postId );
    if ( ! $post || 'publish' !== $post->post_status ) {
        return new WP_Error( 'post_invalido', 'El post no existe o no está publicado.' );
    }
    
    // Verificar si el post ya está en la lista
    $existe = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tablaItemsLista WHERE listaId = %d AND postId = %d", $listaId, $postId ) );
    if ( $existe > 0 ) {
        return new WP_Error( 'entrada_duplicada', 'El post ya está en la lista.' );
    }
    
    // Insertar el post en la lista
    $resultado = $wpdb->insert(
        $tablaItemsLista,
        array(
            'listaId'       => $listaId,
            'postId'        => $postId,
            'fechaAgregada' => current_time( 'mysql' ),
        ),
        array(
            '%d',
            '%d',
            '%s',
        )
    );
    
    if ( false === $resultado ) {
        return new WP_Error( 'error_bd', 'Error al añadir el post a la lista.' );
    }
    
    return true;
}

function crearListasPorDefecto( $usuarioId ) {
    // Crear "Favoritos"
    $favoritos = crearLista( $usuarioId, 'Favoritos', 'Lista de posts favoritos.' );
    
    if ( is_wp_error( $favoritos ) ) {
        error_log( 'Error al crear la lista "Favoritos" para el usuario ' . $usuarioId . ': ' . $favoritos->get_error_message() );
    }
    $paraDespues = crearLista( $usuarioId, 'Para después', 'Lista de posts para revisar más tarde.' );
    
    if ( is_wp_error( $paraDespues ) ) {
        error_log( 'Error al crear la lista "Para después" para el usuario ' . $usuarioId . ': ' . $paraDespues->get_error_message() );
    }
}
add_action( 'user_register', 'crearListasPorDefecto' );
