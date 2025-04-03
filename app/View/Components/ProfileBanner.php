<?php
// Funcion perfilBanner movida desde app/Perfiles/perfiles.php
function perfilBanner($idUsuario) {
    $idUsuarioActual = get_current_user_id();
    $esMismoAutor = ($idUsuario === $idUsuarioActual);

    $numSeguidores = count(obtener_seguidores_o_siguiendo($idUsuario, 'seguidores'));
    $numSiguiendo = count(obtener_seguidores_o_siguiendo($idUsuario, 'siguiendo'));

    $suscripciones = (array) get_user_meta($idUsuarioActual, 'offering_user_ids', true);
    $estaSuscrito = in_array($idUsuario, $suscripciones);

    $idPrecioSub = 'price_1PBgGfCdHJpmDkrrHorFUNaV'; // Mantenido por si es un ID externo importante
    $urlImagen = imagenPerfil($idUsuario); // Asegurarse que imagenPerfil() está disponible globalmente o incluida
    $infoUsuario = get_userdata($idUsuario);

    if (!$infoUsuario) {
        return 'Usuario no encontrado';
    }

    $desc = get_user_meta($idUsuario, 'profile_description', true);

    ob_start();
?>
    <div class="X522YA FRRVBB" data-iduser="<? echo esc_attr($idUsuario); ?>">
        <div class="JKBZKR">
            <img src="<? echo esc_url($urlImagen); ?>" alt="">
            <div class="KFEVRT">
                <p class="ZEKRWP"><? echo esc_html($infoUsuario->display_name); ?></p>
                <p class="NZERUU">@<? echo esc_html($infoUsuario->user_login); ?></p>
                <p class="ZBNIRW"><? echo esc_html($desc); ?></p>
            </div>
        </div>

        <div class="KNIDBC">
            <p><? echo esc_html($numSeguidores); ?> seguidores ·</p>
            <p><? echo esc_html($numSiguiendo); ?> siguiendo</p>
        </div>

        <div class="R0A915">
            <? if (!$esMismoAutor): ?>
                <?
                // Asumiendo que botonSeguirPerfilBanner existe y funciona correctamente
                // Asegurarse que botonSeguirPerfilBanner() está disponible globalmente o incluida
                echo botonSeguirPerfilBanner($idUsuario);
                ?>
                <button class="borde PRJWWT mensajeBoton" data-receptor="<? echo esc_attr($idUsuario); ?>">Enviar mensaje</button>
            <? endif; ?>
            <? if ($esMismoAutor): ?>
                <button class="botonConfig borde">Configuración</button>
                <button class="compartirPerfil borde" data-username="<? echo esc_attr($infoUsuario->user_login); ?>">Compartir perfil</button>
            <? endif; ?>
        </div>
    </div>
<?
    return ob_get_clean();
}
?>