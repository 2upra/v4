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
