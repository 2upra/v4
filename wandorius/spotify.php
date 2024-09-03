<?php 

function spotify_monthly_listeners_shortcode() {
    if (!is_user_logged_in()) {
        return "Necesitas estar conectado para ver esta información.";
    }

    $current_user = wp_get_current_user();
    $artistID = get_user_meta($current_user->ID, 'spotify_id', true);

    if (empty($artistID)) {
        return "No se ha configurado una ID de artista de Spotify.";
    }

    $clientID = '1fb3d379765e4e8492f77a45e07cf51b';
    $clientSecret = '65f484720ad2432ebd294c7a08ecfaed';
    $accessToken = get_spotify_access_token($clientID, $clientSecret);
    if (!$accessToken) {
        return "Error al obtener token de acceso";
    }

    $artistData = spotify_api_request("https://api.spotify.com/v1/artists/{$artistID}", $accessToken);
    if (isset($artistData['error'])) {
        return "Error de la API de Spotify: " . $artistData['error']['message'];
    }

    $topTracksData = spotify_api_request("https://api.spotify.com/v1/artists/{$artistID}/top-tracks?market=ES", $accessToken)['tracks'];
    $topTracks = array_slice($topTracksData, 0, 5); // Top 5 tracks

    $recentAlbumsData = spotify_api_request("https://api.spotify.com/v1/artists/{$artistID}/albums?include_groups=single,album&market=ES&limit=5", $accessToken)['items'];

    usort($recentAlbumsData, function($a, $b) {
        return strcmp($b['release_date'], $a['release_date']);
    });

    $recentTracks = array_slice($recentAlbumsData, 0, 5); 

    // Formatear la salida
    $output = "<div style='color: #fff;'>";
    $output .= "<h2>Oyentes mensuales: " . number_format($artistData['followers']['total']) . "</h2>";
    $output .= "<div><img src='" . $artistData['images'][0]['url'] . "' style='width: 100px; border-radius: 50%;'></div>";
    $output .= "<div style='margin-top: 20px;'><h3>Top 5 Canciones:</h3>";

    foreach ($topTracks as $track) {
        $output .= "<div style='background: #080808; margin-bottom: 10px; padding: 10px; border-radius: 5px; display: flex; align-items: center;'>";
        $output .= "<img src='" . $track['album']['images'][0]['url'] . "' style='width: 50px; margin-right: 10px;'>";
        $output .= "<span>" . $track['name'] . "</span>";
        $output .= "</div>";
    }

    $output .= "</div><div style='margin-top: 20px;'><h3>Lanzamientos Recientes:</h3>";

    foreach ($recentTracks as $album) {
        $output .= "<div style='background: #080808; margin-bottom: 10px; padding: 10px; border-radius: 5px; display: flex; align-items: center;'>";
        $output .= "<img src='" . $album['images'][0]['url'] . "' style='width: 50px; margin-right: 10px;'>";
        $output .= "<span>" . $album['name'] . "</span>";
        $output .= "</div>";
    }

    $output .= "</div></div>";

    return $output;
}

function get_spotify_access_token($clientID, $clientSecret) {
    $tokenURL = 'https://accounts.spotify.com/api/token';
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $tokenURL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Authorization: Basic '.base64_encode($clientID.':'.$clientSecret)],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    if ($response) {
        return json_decode($response, true)['access_token'];
    }
    return false;
}

function spotify_api_request($url, $accessToken) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_RETURNTRANSFER => true
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

add_shortcode('spotify_monthly_listeners', 'spotify_monthly_listeners_shortcode');

function custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
    $user = false;

    if ( is_numeric( $id_or_email ) ) {
        $id = (int) $id_or_email;
        $user = get_user_by( 'id', $id );
    } elseif ( is_object( $id_or_email ) ) {
        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id', $id );
        }
    } else {
        $user = get_user_by( 'email', $id_or_email );
    }

    if ( $user && is_object( $user ) ) {
        $user_id = $user->ID;
        $imagen_perfil_url = obtener_url_imagen_perfil_o_defecto($user_id);
        if ( !empty( $imagen_perfil_url ) ) {
            $avatar = "<img alt='{$alt}' src='{$imagen_perfil_url}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        }
    }

    return $avatar;
}
add_filter( 'get_avatar', 'custom_avatar', 999999, 5 );

function cambiar_tamano_thumbnail() {
    set_post_thumbnail_size(150, 150, true);
}
add_action('after_setup_theme', 'cambiar_tamano_thumbnail');

