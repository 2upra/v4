<?php
// Refactor(Org): Funciones de hashing y estado de archivos movidas desde app/Form/Hash.php

define('HASH_SCRIPT_PATH', '/var/www/wordpress/wp-content/themes/2upra3v/app/python/hashAudio.py');
define('PROCESO_DELAY', 500000); // 0.5 segundos en microsegundos
define('MAX_EXECUTION_TIME', 30); // 30 segundos por archivo
define('BATCH_SIZEHASH', 50);
set_time_limit(0);

if (!defined('HASH_SIMILARITY_THRESHOLD')) {
    define('HASH_SIMILARITY_THRESHOLD', 0.7);
}
define('WRAPPER_SCRIPT_PATH', '/var/www/wordpress/wp-content/themes/2upra3v/app/Commands/process_audio.sh');


// Refactor(Org): Moved function sonHashesSimilares() to app/Services/FileHashService.php

// Refactor(Org): Moved function handle_recalcular_hash() and its hook to app/Services/FileHashService.php

// Refactor(Org): Moved function recalcularHash to app/Services/FileHashService.php

// Refactor(Org): Moved function actualizarEstadoArchivo to app/Services/FileHashService.php

// Refactor(Org): Moved function subidaArchivo() and its hook to app/Services/FileHashService.php

// Refactor(Org): Moved function guardarHash to app/Services/FileHashService.php


// Refactor(Org): Moved function actualizarUrlArchivo to app/Services/FileHashService.php

// Refactor(Org): Moved function nombreUnicoFile to FileUtils.php


// Refactor(Org): Moved function confirmarHashId to app/Services/FileHashService.php


// Refactor(Org): Moved function eliminarHash to app/Services/FileHashService.php

// Refactor(Org): Moved function eliminarPorHash to app/Services/FileHashService.php

// Refactor(Org): Moved function obtenerFileIDPorURL to app/Services/FileHashService.php

// Refactor(Org): Moved function limpiarArchivosPendientes() and its hook/schedule to app/Services/FileHashService.php


?>