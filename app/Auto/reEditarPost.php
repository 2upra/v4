<?

use App\Services\Post\PostAudioRenamingService;
use App\Utils\Logger;
use App\Services\IAService;

function rehacerNombreAudio($post_id, $archivo_audio)
{
    $logger = new Logger();
    $iaService = new IAService();
    $audioRenamingService = new PostAudioRenamingService($logger, $iaService);

    return $audioRenamingService->renameAudio($post_id, $archivo_audio);
}

function buscarArchivoEnSubcarpetas($directorio_base, $nombre_archivo)
{
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio_base));
    foreach ($iterador as $archivo) {
        $extension = strtolower($archivo->getExtension());
        $nombre = $archivo->getFilename();

        if (!in_array($extension, ['wav', 'mp3']) || strpos($nombre, '2upra') !== 0) {
            continue;
        }

        if ($nombre === $nombre_archivo) {
            return $archivo->getPath();
        }
    }
    return false;
}


function renombrar_archivo_adjunto($attachment_id, $nuevo_nombre, $es_lite = false)
{
    $ruta_archivo = get_attached_file($attachment_id);
    if (!file_exists($ruta_archivo)) {
        return false;
    }

    $carpeta = pathinfo($ruta_archivo, PATHINFO_DIRNAME);
    $extension = pathinfo($ruta_archivo, PATHINFO_EXTENSION);
    if ($es_lite) {
        $nuevo_nombre .= '_lite';
    }
    $nueva_ruta = $carpeta . '/' . $nuevo_nombre . '.' . $extension;

    if (!rename($ruta_archivo, $nueva_ruta)) {
        guardarLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        return false;
    }

    guardarLog("Archivo renombrado en el servidor de {$ruta_archivo} a {$nueva_ruta}");

    $wp_filetype = wp_check_filetype(basename($nueva_ruta), null);
    $attachment_data = array(
        'ID' => $attachment_id,
        'post_name' => sanitize_title($nuevo_nombre),
        'guid' => home_url('/') . str_replace(ABSPATH, '', $nueva_ruta),
    );

    if (function_exists('wp_update_post')) {
        wp_update_post($attachment_data);
    }

    update_attached_file($attachment_id, $nueva_ruta);

    return true;
}
