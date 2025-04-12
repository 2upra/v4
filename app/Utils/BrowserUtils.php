<?php

// Refactor(Org): Función obtenerIdiomaDelNavegador() movida desde app/Setup/ThemeSetup.php
/**
 * Obtiene el idioma preferido del navegador del usuario.
 *
 * Analiza la cabecera HTTP_ACCEPT_LANGUAGE para determinar el idioma preferido.
 * Prioriza 'es' (español) o 'en' (inglés).
 *
 * @return string Retorna 'es' o 'en', o 'en' por defecto si no se puede determinar o no está en la lista priorizada.
 */
function obtenerIdiomaDelNavegador()
{
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) || empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return 'en'; // Retorna inglés por defecto si la cabecera no está presente o está vacía
    }

    $accepted_languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($accepted_languages as $language) {
        // Extrae el código de idioma principal (ej. 'es' de 'es-ES,es;q=0.9')
        $lang = substr(trim(explode(';', $language)[0]), 0, 2);

        // Verifica si el idioma extraído es español o inglés
        if (in_array($lang, ['es', 'en'])) {
            return $lang; // Retorna el primer idioma coincidente ('es' o 'en')
        }
    }

    return 'en'; // Retorna inglés si ninguno de los idiomas preferidos ('es', 'en') se encuentra
}

// Refactor(Org): Función is_electron_app() movida desde app/Authentication/Iniciar.php
function is_electron_app()
{
    return isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true';
}

// Refactor(Org): Moved function chequearElectron from app/Sync/api.php
// Función de permiso: valida la cabecera X-Electron-App
function chequearElectron()
{
    //error_log("Iniciando chequearElectron...");
    if (isset($_SERVER['HTTP_X_ELECTRON_APP']) && $_SERVER['HTTP_X_ELECTRON_APP'] === 'true') {
        //error_log("Cabecera válida: " . $_SERVER['HTTP_X_ELECTRON_APP']);
        return true;
    }
    //error_log("Cabecera inválida o ausente.");
    return new WP_Error('forbidden', 'Acceso no autorizado', array('status' => 403));
}

?>
