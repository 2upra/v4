<?php

// Refactor(Org): Función obtenerImagenAleatoria movida a app/Utils/ImageUtils.php
// Refactor(Org): Función subirImagenDesdeURL movida a app/Utils/ImageUtils.php

// Refactor(Org): Función adjuntarArchivo() movida a app/Services/Post/PostAttachmentService.php

// Refactor(Org): Moved function nombreUnicoFile from HashUtils.php
function nombreUnicoFile($dir, $name, $ext)
{
    return basename($name, $ext) . $ext;
}

// Refactor(Org): Función subirImagenALibreria movida a app/Utils/ImageUtils.php

// Refactor(Org): Función renombrarArchivoAdjunto() movida a app/Services/Post/PostAttachmentService.php

