<?php

// Funciones y hooks relacionados con el estado del post movidos a app/Services/Post/PostStatusService.php

add_action('wp_ajax_corregirTags', 'corregirTags');
// Refactor(Org): Hook AJAX cambiarTitulo movido a PostTitleService.php



add_action('wp_ajax_cambiarDescripcion', 'cambiarDescripcion');


// Refactor(Org): Mover función cambiar_imagen_post_handler y hook AJAX a PostAttachmentService.php
// La función y su hook asociado han sido movidos a app/Services/Post/PostAttachmentService.php

