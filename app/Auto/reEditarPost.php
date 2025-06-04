<?php

use App\Services\Post\PostAudioRenamingService;
use App\Utils\Logger;
use App\Services\IAService;
use App\Services\Post\PostAttachmentService;

function rehacerNombreAudio($post_id, $archivo_audio)
{
    $logger = new Logger();
    $iaService = new IAService();
    $postAttachmentService = new PostAttachmentService($logger, $iaService); 
    $audioRenamingService = new PostAudioRenamingService($logger, $iaService, $postAttachmentService);

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
