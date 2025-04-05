<?php

// Refactor(Exec): Funcion modalColeccion() movida desde app/View/Helpers/UIHelper.php
function modalColeccion()
{
    ob_start();
?>
    <div id="modalColeccion" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Añadir a Colección</h2>
            <div id="listaColeccionesExistentes">
                <!-- Las colecciones existentes se cargarán aquí -->
            </div>
            <div class="crearNuevaColeccion">
                <input type="text" id="nombreNuevaColeccion" placeholder="Nombre nueva colección">
                <button id="botonCrearYAnadir">Crear y Añadir</button>
            </div>
            <button id="botonAnadirAColeccion" style="display:none;">Añadir a Selección</button> <!-- Oculto inicialmente -->
        </div>
    </div>
    <style>
        /* Estilos básicos para el modal de colección */
        #modalColeccion .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            position: relative;
        }

        #modalColeccion .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        #modalColeccion .close-button:hover,
        #modalColeccion .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        #modalColeccion #listaColeccionesExistentes div {
            padding: 8px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        #modalColeccion #listaColeccionesExistentes div:hover {
            background-color: #f0f0f0;
        }
         #modalColeccion #listaColeccionesExistentes div.selected {
            background-color: #d0e0f0; /* Estilo para indicar selección */
            font-weight: bold;
        }

        #modalColeccion .crearNuevaColeccion {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
         #modalColeccion .crearNuevaColeccion input[type="text"] {
            flex-grow: 1;
         }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalColeccion');
            const closeButton = modal.querySelector('.close-button');
            const listaColecciones = document.getElementById('listaColeccionesExistentes');
            const botonAnadir = document.getElementById('botonAnadirAColeccion');
            const botonCrearYAnadir = document.getElementById('botonCrearYAnadir');
            const inputNuevaColeccion = document.getElementById('nombreNuevaColeccion');
            let postIdParaAnadir = null; // Variable para guardar el ID del post
            let coleccionSeleccionadaId = null; // Variable para guardar el ID de la colección seleccionada

            // Función para abrir el modal (se llamará desde el botón 'Añadir a Colección')
            window.abrirModalColeccion = function(postId) {
                postIdParaAnadir = postId;
                cargarColeccionesExistentes();
                modal.style.display = 'block';
                botonAnadir.style.display = 'none'; // Ocultar al abrir
                coleccionSeleccionadaId = null; // Resetear selección
                // Desmarcar visualmente cualquier selección previa
                listaColecciones.querySelectorAll('div.selected').forEach(el => el.classList.remove('selected'));
            }

            // Cerrar el modal
            closeButton.onclick = function() {
                modal.style.display = 'none';
            }
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }

            // Cargar colecciones existentes vía AJAX
            function cargarColeccionesExistentes() {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        const respuesta = JSON.parse(xhr.responseText);
                        listaColecciones.innerHTML = ''; // Limpiar lista
                        if (respuesta.success && respuesta.data.length > 0) {
                            respuesta.data.forEach(coleccion => {
                                const div = document.createElement('div');
                                div.textContent = coleccion.post_title;
                                div.dataset.coleccionId = coleccion.ID;
                                div.onclick = function() {
                                    // Desmarcar selección anterior
                                    listaColecciones.querySelectorAll('div.selected').forEach(el => el.classList.remove('selected'));
                                    // Marcar nueva selección
                                    this.classList.add('selected');
                                    coleccionSeleccionadaId = this.dataset.coleccionId;
                                    botonAnadir.style.display = 'inline-block'; // Mostrar botón de añadir
                                };
                                listaColecciones.appendChild(div);
                            });
                        } else {
                            listaColecciones.innerHTML = '<p>No tienes colecciones aún.</p>';
                        }
                    } else {
                        console.error('Error al cargar colecciones');
                         listaColecciones.innerHTML = '<p>Error al cargar colecciones.</p>';
                    }
                };
                 xhr.onerror = function() {
                    console.error('Error de red al cargar colecciones');
                     listaColecciones.innerHTML = '<p>Error de red al cargar colecciones.</p>';
                };
                xhr.send('action=listar_colecciones_usuario');
            }

             // Añadir post a colección existente
            botonAnadir.onclick = function() {
                if (!postIdParaAnadir || !coleccionSeleccionadaId) {
                    alert('Error: No se ha seleccionado post o colección.');
                    return;
                }
                anadirPostAColeccion(postIdParaAnadir, coleccionSeleccionadaId);
            };

            // Crear nueva colección y añadir post
            botonCrearYAnadir.onclick = function() {
                const nombreNueva = inputNuevaColeccion.value.trim();
                if (!nombreNueva) {
                    alert('Por favor, introduce un nombre para la nueva colección.');
                    return;
                }
                 if (!postIdParaAnadir) {
                    alert('Error: No se ha especificado el post a añadir.');
                    return;
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                     if (xhr.status >= 200 && xhr.status < 400) {
                        const respuesta = JSON.parse(xhr.responseText);
                        if (respuesta.success) {
                            //alert('Colección creada y post añadido.');
                            // Añadir el post a la nueva colección creada
                            anadirPostAColeccion(postIdParaAnadir, respuesta.data.coleccion_id);
                            inputNuevaColeccion.value = ''; // Limpiar input
                            // Opcional: Recargar lista o añadir visualmente la nueva colección
                            // cargarColeccionesExistentes();
                            // modal.style.display = 'none'; // Cerrar modal tras éxito
                        } else {
                            alert('Error al crear la colección: ' + respuesta.data.message);
                        }
                    } else {
                         alert('Error del servidor al crear la colección.');
                    }
                };
                xhr.onerror = function() {
                    alert('Error de red al crear la colección.');
                };
                xhr.send('action=crear_coleccion_usuario&nombre_coleccion=' + encodeURIComponent(nombreNueva));
            };

             // Función AJAX para añadir post a colección
            function anadirPostAColeccion(postId, coleccionId) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        const respuesta = JSON.parse(xhr.responseText);
                        if (respuesta.success) {
                            //alert('Post añadido a la colección.');
                            modal.style.display = 'none'; // Cerrar modal
                            // Opcional: Actualizar UI para reflejar que el post está en una colección
                            const botonColeccionOriginal = document.querySelector(`.botonColeccion[data-post-id="${postId}"]`);
                            if(botonColeccionOriginal) {
                                // Cambiar icono o texto para indicar que ya está en una colección
                                // Ejemplo: botonColeccionOriginal.innerHTML = 'En Colección';
                                // O añadir una clase para cambiar el estilo
                                botonColeccionOriginal.classList.add('en-coleccion');
                                // Podrías querer actualizar el tooltip también
                            }
                        } else {
                            alert('Error al añadir el post: ' + respuesta.data.message);
                        }
                    } else {
                        alert('Error del servidor al añadir el post.');
                    }
                };
                xhr.onerror = function() {
                    alert('Error de red al añadir el post.');
                };
                xhr.send('action=anadir_post_a_coleccion&post_id=' + postId + '&coleccion_id=' + coleccionId);
            }

        });
    </script>
<?php
    return ob_get_clean();
}
?>