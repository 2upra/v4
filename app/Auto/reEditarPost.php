<?

use App
\Services\Post\PostAudioRenamingService;
use App
\Utils\Logger;

function rehacerNombreAudio($post_id, $archivo_audio)
{
    // Instantiate dependencies
    $logger = new Logger();
    $audioRenamingService = new PostAudioRenamingService($logger);

    // Delegate the entire logic to the service
    return $audioRenamingService->renameAudio($post_id, $archivo_audio);
}

// Función auxiliar para buscar archivos en subcarpetas, filtrando solo archivos de audio válidos
function buscarArchivoEnSubcarpetas($directorio_base, $nombre_archivo)
{
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio_base));
    foreach ($iterador as $archivo) {
        // Obtener la extensión y el nombre del archivo
        $extension = strtolower($archivo->getExtension());
        $nombre = $archivo->getFilename();

        // Ignorar archivos que no sean .wav o .mp3 y que no empiecen con "2upra"
        if (!in_array($extension, ['wav', 'mp3']) || strpos($nombre, '2upra') !== 0) {
            continue;
        }

        // Si el nombre coincide exactamente con el archivo buscado, devolver la ruta del directorio
        if ($nombre === $nombre_archivo) {
            return $archivo->getPath();
        }
    }
    return false;
}


function renombrar_archivo_adjunto($attachment_id, $nuevo_nombre, $es_lite = false)
{
    // Obtener el path completo del archivo adjunto
    $ruta_archivo = get_attached_file($attachment_id);
    if (!file_exists($ruta_archivo)) {
        //error_log("El archivo adjunto con ID {$attachment_id} no existe en la ruta: {$ruta_archivo}");
        return false;
    }

    // Obtener la carpeta y la extensión del archivo
    $carpeta = pathinfo($ruta_archivo, PATHINFO_DIRNAME);
    $extension = pathinfo($ruta_archivo, PATHINFO_EXTENSION);
    if ($es_lite) {
        $nuevo_nombre .= '_lite';
    }
    $nueva_ruta = $carpeta . '/' . $nuevo_nombre . '.' . $extension;

    // Renombrar el archivo
    if (!rename($ruta_archivo, $nueva_ruta)) {
        //error_log("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        guardarLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        return false;
    }

    //error_log("Archivo renombrado de {$ruta_archivo} a {$nueva_ruta}");
    guardarLog("Archivo renombrado en el servidor de {$ruta_archivo} a {$nueva_ruta}");

    // Actualizar la ruta del adjunto en la base de datos
    $wp_filetype = wp_check_filetype(basename($nueva_ruta), null);
    $attachment_data = array(
        'ID' => $attachment_id,
        'post_name' => sanitize_title($nuevo_nombre),
        'guid' => home_url('/') . str_replace(ABSPATH, '', $nueva_ruta),
    );

    // Actualizar el post del adjunto
    if (function_exists('wp_update_post')) {
        wp_update_post($attachment_data);
    }

    update_attached_file($attachment_id, $nueva_ruta);

    return true;
}
