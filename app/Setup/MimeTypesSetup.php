<?php

// Refactor(Org): Moved MIME type filters from app/Setup/ThemeSetup.php

/**
 * Adds support for JFIF image uploads.
 *
 * @param array $mimes Existing MIME types.
 * @return array Modified MIME types.
 */
function agregar_soporte_jfif($mimes)
{
    $mimes['jfif'] = 'image/jpeg';
    return $mimes;
}
add_filter('upload_mimes', 'agregar_soporte_jfif');

/**
 * Extends wp_check_filetype to recognize .jfif files.
 *
 * @param array  $types    File type data.
 * @param string $filename File name.
 * @param array  $mimes    Allowed MIME types.
 * @return array Modified file type data.
 */
function extender_wp_check_filetype($types, $filename, $mimes)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'jfif') {
        return ['ext' => 'jpeg', 'type' => 'image/jpeg'];
    }
    return $types;
}
add_filter('wp_check_filetype_and_ext', 'extender_wp_check_filetype', 10, 3);

/**
 * Adds allowed MIME types for various project and audio files.
 *
 * @param array $mimes Existing MIME types.
 * @return array Modified MIME types.
 */
function mimesPermitidos($mimes)
{
    $mimes['flp'] = 'application/octet-stream'; // FL Studio Project
    $mimes['zip'] = 'application/zip';
    $mimes['rar'] = 'application/x-rar-compressed';
    $mimes['cubase'] = 'application/octet-stream'; // Cubase Project
    $mimes['proj'] = 'application/octet-stream'; // Generic Project
    $mimes['aiff'] = 'audio/aiff';
    $mimes['midi'] = 'audio/midi';
    $mimes['ptx'] = 'application/octet-stream'; // Pro Tools Session
    $mimes['sng'] = 'application/octet-stream'; // Korg Song File
    $mimes['aup'] = 'application/octet-stream'; // Audacity Project
    $mimes['omg'] = 'application/octet-stream'; // ??? (Consider specifying)
    $mimes['rpp'] = 'application/octet-stream'; // Reaper Project
    $mimes['xpm'] = 'image/x-xpixmap';
    $mimes['tst'] = 'application/octet-stream'; // ??? (Consider specifying)

    return $mimes;
}
add_filter('upload_mimes', 'mimesPermitidos');

/**
 * Allows uploading APK files.
 *
 * @param array $mime_types Existing MIME types.
 * @return array Modified MIME types.
 */
function permitir_subir_apks($mime_types)
{
    $mime_types['apk'] = 'application/vnd.android.package-archive';
    return $mime_types;
}
add_filter('upload_mimes', 'permitir_subir_apks');

/**
 * Verifies APK file uploads, checking permissions.
 *
 * @param array  $data     File data array containing 'ext', 'type', and potentially 'error'.
 * @param string $file     Full path to the file.
 * @param string $filename The name of the file (may differ from $file due to $file being in tmp directory).
 * @param array  $mimes    Array of allowed MIME types.
 * @return array Modified file data array.
 */
function verificar_subida_apk($data, $file, $filename, $mimes)
{
    // Check if the file extension is .apk
    if (strtolower(substr($filename, -4)) === '.apk') {
        // Check if the current user has 'manage_options' capability (typically administrators)
        if (! current_user_can('manage_options')) {
            // If not, set an error message
            $data['error'] = __('Lo siento, no tienes permisos para subir archivos APK.', 'your-text-domain'); // Added text domain for translation
        } else {
            // If the user has permission, explicitly set the correct MIME type
            // This might override incorrect detection by WordPress
            $data['ext']  = 'apk'; // Ensure the extension is set correctly
            $data['type'] = 'application/vnd.android.package-archive';
        }
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'verificar_subida_apk', 10, 4);

?>
