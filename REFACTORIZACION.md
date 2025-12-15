# Refactorización del Tema v4 (2upra/kamples)

> **Última actualización:** 2025-12-14
> **Estado:** En progreso - Fase 1

---

## Contexto del Proyecto

Este es un tema de WordPress para una aplicación social/musical. El código fue escrito originalmente por alguien sin experiencia en programación, por lo que requiere no solo refactorización, sino también:

- Corrección de vulnerabilidades de seguridad
- Arreglo de malas implementaciones
- Organización coherente del código
- Aplicación progresiva de principios SOLID

---

## Reglas Esenciales

### 1. Cambios Progresivos
- **NUNCA** hacer cambios drásticos
- Un archivo/módulo a la vez
- Verificar funcionamiento después de cada cambio
- Si algo se rompe, revertir inmediatamente

### 2. Principios SOLID
| Principio                 | Aplicación                                     |
| ------------------------- | ---------------------------------------------- |
| **S**ingle Responsibility | Un archivo = una responsabilidad clara         |
| **O**pen/Closed           | Código extensible sin modificar el original    |
| **L**iskov Substitution   | Clases hijas intercambiables con padres        |
| **I**nterface Segregation | Interfaces pequeñas y específicas              |
| **D**ependency Inversion  | Depender de abstracciones, no implementaciones |

### 3. Nomenclatura
- Variables y funciones: `camelCase`
- Clases: `PascalCase`
- Constantes: `SCREAMING_SNAKE_CASE`
- Archivos PHP de clases: `NombreClase.php`
- Archivos PHP de funciones: `nombreModulo.php`

### 4. Comentarios Profesionales
```php
/**
 * Descripción breve de la función.
 * 
 * Descripción más detallada si es necesaria.
 *
 * @param string $parametro Descripción del parámetro.
 * @return bool Descripción del retorno.
 * @since 1.0.0
 */
```

### 5. Seguridad
- Sanitizar TODAS las entradas: `sanitize_text_field()`, `esc_html()`, etc.
- Usar nonces en formularios
- Validar permisos con `current_user_can()`
- Escapar salidas: `esc_attr()`, `esc_html()`, `esc_url()`

### 7. Logging (OBLIGATORIO)
- **PROHIBIDO** usar `error_log()`, `print_r()`, o `var_dump()`.
- **SIEMPRE** usar la clase `Logger`:
  ```php
  $logger = \Logger::obtenerInstancia();
  $logger->info('canal', 'Mensaje');
  ```
- Canales disponibles: `like`, `chat`, `auth`, `debug`, etc. (Ver `inc/Config/constants.php`)

### 6. Estructura de Archivos

```
├── inc/                     # Módulos de soporte (funciones helpers)
│   ├── Config/              # Configuración y constantes
│   ├── Logging/             # Sistema de logs (clase Logger)
│   ├── Setup/               # Inicialización (ScriptsManager)
│   ├── Core/                # Clases principales auxiliares
│   ├── Utils/               # Utilidades y helpers
│   └── Security/            # Funciones de seguridad
│
├── src/                     # CÓDIGO REFACTORIZADO (namespace Kamples)
│   ├── autoload.php         # Autoloader PSR-4
│   ├── Core/                # Clases base (DatabaseMigrations, etc.)
│   ├── Services/            # Servicios (lógica de negocio)
│   ├── Controllers/         # Controladores (AJAX, REST API)
│   ├── Models/              # Modelos de datos
│   └── Views/               # Componentes de vista
│       └── Components/      # LikeButtons, ChatBox, ChatList
│
├── app/                     # CÓDIGO LEGACY (pendiente de refactorizar)
│   ├── deprecated/          # Wrappers temporales (SE ELIMINARÁN)
│   ├── Functions/           # Funciones legacy
│   ├── Content/             # 36 archivos - contenido y posts
│   ├── Finanza/             # Sistema financiero/Stripe
│   └── ...                  # Otros módulos legacy
│
└── js/                      # Scripts JavaScript
```

### 7. Namespace del Tema

Las clases refactorizadas usan el namespace `Kamples`:

```php
namespace Kamples\Services;

class LikeService
{
    // Lógica de likes refactorizada
}
```

Uso:
```php
use Kamples\Services\LikeService;

$likeService = new LikeService();
```

---

## Roadmap de Refactorización

### Fase 1: Organizar `functions.php` ✅ **COMPLETADA**
| Paso | Descripción                                          | Estado       |
| ---- | ---------------------------------------------------- | ------------ |
| 1.1  | Crear estructura de carpetas `inc/`                  | ✅ Completado |
| 1.2  | Extraer constantes a `inc/Config/constants.php`      | ✅ Completado |
| 1.3  | Extraer funciones de logging a `inc/Logging/`        | ✅ Completado |
| 1.4  | Extraer enqueue de scripts a `inc/Setup/scripts.php` | ✅ Completado |
| 1.5  | Implementar auto-detección de scripts en /js/        | ✅ Completado |
| 1.6  | Limpiar functions.php (solo includes)                | ✅ Completado |

> **Resultado:** `functions.php` reducido de 715 líneas a 124 líneas (~83% reducción)

### Fase 2: Migrar `/app/` a `/src/` ⬅️ **ACTUAL** (PRIORIDAD)

#### Reglas de Migración (OBLIGATORIAS)

> **OBJETIVO FINAL:** La carpeta `/app/` debe quedar VACÍA.

1. **Migración COMPLETA por módulo**
   - Al migrar un módulo, TODO su código debe ir a `/src/`
   - Lógica de negocio → `src/Services/`
   - Handlers AJAX/REST → `src/Controllers/`
   - Renderizado HTML → `src/Views/Components/`
   - NO dejar funciones sueltas en archivos legacy

2. **Wrappers temporales van a `/app/deprecated/`**
   - Los wrappers de compatibilidad van a `/app/deprecated/nombreModulo.php`
   - Cada wrapper debe tener `@deprecated` con la alternativa correcta
   - El archivo original en `/app/` se **ELIMINA** después de migrar

3. **Estructura destino en `/src/`**
   ```
   src/
   ├── autoload.php           # PSR-4 autoloader (namespace Kamples)
   ├── Services/              # Lógica de negocio
   │   ├── LikeService.php
   │   ├── ChatService.php
   │   └── SeguirService.php
   ├── Controllers/           # Handlers AJAX y REST API
   │   ├── LikeController.php
   │   ├── ChatController.php
   │   └── SeguirController.php
   ├── Views/                 # Componentes de renderizado
   │   └── Components/
   │       └── LikeButtons.php
   └── Models/                # Modelos de datos (futuro)
   ```

4. **Estructura temporal en `/app/deprecated/`**
   ```
   app/deprecated/
   ├── likes.php              # Wrappers deprecados de likes
   ├── seguir.php             # Wrappers deprecados de seguir
   └── chat.php               # Wrappers deprecados de chat
   ```

#### Pasos de la Fase 2
| Paso | Descripción                                   | Estado        |
| ---- | --------------------------------------------- | ------------- |
| 2.1  | Crear estructura `/src/` con autoloader PSR-4 | ✅ Completado  |
| 2.2  | Migrar `app/Functions/likes.php` completo     | ✅ Completado  |
| 2.3  | Migrar `app/Functions/seguir.php` completo    | ✅ Completado  |
| 2.4  | Migrar `app/Chat/` completo                   | ✅ Completado  |
| 2.5a | Migrar `app/Content/Comentarios/` completo    | ✅ Completado  |
| 2.5b | Migrar `app/Content/Colab/` completo          | ✅ Completado  |
| 2.5c | Migrar `app/Content/Colecciones/` completo    | ✅ Completado  |
| 2.5d | Migrar `app/Content/Logic/` completo          | ✅ Completado  |
| 2.5e | Migrar resto de `app/Content/`                | ✅ Completado  |
| 2.6a | Migrar `app/Logic/` (IA, vistas, waveform)    | ✅ Completado  |
| 2.6b | Migrar `app/Authentication/`                  | ✅ Completado  |
| 2.6c | Migrar `app/AlgoritmoPost/`                   | ✅ Completado  |
| 2.6d | Migrar `app/Functions/`                       | ✅ Completado  |
| 2.6e | Migrar `app/Admin/`, `app/Hook/`, `app/View/` | ✅ Completado  |
| 2.6f | Migrar resto de `/app/`                       | ⏳ En progreso |
| 2.7  | Migrar `app/Finanza/` (baja prioridad)        | 🔜 Al final    |

> **Nota:** El módulo `app/Finanza/` (Stripe/pagos) se deja para el final ya que no es prioritario y requiere pruebas especiales con el sistema de pagos.

#### Carpetas pendientes en `/app/` (2.6f)
| Carpeta         | Archivos | Descripción           | Prioridad    |
| --------------- | -------- | --------------------- | ------------ |
| ~~`Auto/`~~     | 7        | Posts automáticos, IA | ✅ Completado |
| `Form/`         | 4        | Formularios de subida | Alta         |
| `Misc/`         | 6        | Iconos, emergencias   | Baja         |
| `Pages/`        | 15       | Tabs de páginas       | Media        |
| ~~`Perfiles/`~~ | 4        | Perfiles de usuario   | ✅ Completado |
| `Sync/`         | 1        | API de sincronización | Baja         |
| `Test/`         | 3        | Archivos de prueba    | Baja         |
| `Commands/`     | 2        | Scripts shell         | Baja         |
| `python/`       | 2        | Scripts Python        | Baja         |



### Fase 3: Limpiar `header.php`
| Paso | Descripción                             | Estado      |
| ---- | --------------------------------------- | ----------- |
| 3.1  | Extraer CSS inline a archivos CSS       | ⏳ Pendiente |
| 3.2  | Crear componentes de menú reutilizables | ⏳ Pendiente |
| 3.3  | Separar lógica de usuario               | ⏳ Pendiente |

### Fase 4: Organizar Templates
| Paso | Descripción                              | Estado      |

| ---- | ---------------------------------------- | ----------- |
| 3.1  | Mover Template*.php a carpeta templates/ | ⏳ Pendiente |
| 3.2  | Estandarizar estructura de templates     | ⏳ Pendiente |

### Fase 4: Modularizar JavaScript
| Paso | Descripción                   | Estado      |
| ---- | ----------------------------- | ----------- |
| 4.1  | Auditar scripts actuales      | ⏳ Pendiente |
| 4.2  | Agrupar por funcionalidad     | ⏳ Pendiente |
| 4.3  | Implementar carga condicional | ⏳ Pendiente |

### Fase 5: Segmentar CSS
| Paso | Descripción                       | Estado      |
| ---- | --------------------------------- | ----------- |
| 5.1  | Crear variables.css               | ⏳ Pendiente |
| 5.2  | Dividir style.css por componentes | ⏳ Pendiente |
| 5.3  | Eliminar estilos duplicados       | ⏳ Pendiente |

---

## Historial de Cambios

### 2025-12-14
- **[INICIO]** Creación del documento de refactorización
- **[ANÁLISIS]** Diagnóstico inicial del proyecto:
  - `functions.php`: 715 líneas, múltiples responsabilidades
  - `header.php`: 498 líneas, CSS inline, lógica mezclada
  - `style.css`: 166KB sin segmentación
  - 56 archivos JS sin modularización
  - 18 templates en la raíz
- **[1.1]** Creada estructura de carpetas `inc/` (Core, Config, Logging, Setup, Utils, Security)
- **[1.2]** Creado `inc/Config/constants.php` - Constantes de logging y rutas centralizadas
- **[1.3]** Creado `inc/Logging/Logger.php` - Clase Logger con patrón Singleton
  - Implementación OOP del sistema de logging
  - Funciones wrapper para compatibilidad con código existente
  - Métodos tipados para cada tipo de log
- **[1.3]** Modificado `functions.php` - Eliminadas funciones de logging duplicadas (~140 líneas)
- **[1.3+]** Mejora del sistema de logging:
  - Niveles: DEBUG(0), INFO(1), WARNING(2), ERROR(3), CRITICAL(4)
  - Canales independientes con configuración granular
  - Auto-limpieza de archivos grandes
  - Canal `refactor` para seguimiento de cambios
- **[1.4]** Creado `inc/Setup/scripts.php` - ScriptsManager con auto-detección de JS
  - Los scripts de /js/ se cargan automáticamente
  - Solo se especifican excepciones (dependencias, usuarios logueados)
- **[1.5]** `functions.php` reducido de 715 a ~310 líneas (56% reducción)
- **[2.0]** Nueva estructura de carpetas:
  - `/src/` - Código refactorizado con namespaces (Kamples)
  - `/app/` - Código legacy pendiente de refactorizar
  - `src/autoload.php` - Autoloader PSR-4 para clases en /src/
- **[FIX]** Restauradas funciones eliminadas: `incluirArchivos`, `loadingBar`, `limpiarLogs`
- **[2.2]** Migrado sistema de likes a arquitectura OOP:
  - `src/Services/LikeService.php` - Lógica de negocio (290 líneas)
  - `src/Controllers/LikeController.php` - Handler AJAX (135 líneas)
  - `app/Functions/likes.php` - Convertido a wrappers de compatibilidad
  - Mejoras: tipado estricto, inyección de dependencias, métodos SRP
- **[2.3]** Migrado sistema de seguimiento a arquitectura OOP:
  - `src/Services/SeguirService.php` - Lógica de seguimiento (190 líneas)
  - `src/Controllers/SeguirController.php` - Handler AJAX (105 líneas)
  - `app/Functions/seguir.php` - Convertido a wrappers
- **[2.4]** Migrado Core del Chat:
  - `src/Services/ChatService.php` - Gestión de tokens, mensajes y conversaciones
  - `src/Controllers/ChatController.php` - Endpoints REST y AJAX (360+ líneas)
- **[2.4+]** Migración COMPLETA de Chat:
  - `src/Views/Components/ChatBox.php` - Vista del modal de chat
  - `src/Views/Components/ChatList.php` - Vista de lista de conversaciones
  - `src/Views/Components/LikeButtons.php` - Botones de like refactorizados
  - `src/Core/DatabaseMigrations.php` - Migraciones de tablas centralizadas
  - **ELIMINADA** carpeta `app/Chat/` completamente
  - Wrappers movidos a `app/deprecated/chat.php`
- **[NAMESPACE]** Renombrado namespace global de `Theme\V4` a `Kamples`
- **[FIX]** Estandarización de logs: Reemplazado `error_log` por `Logger::obtenerInstancia()` en todos los servicios y controladores nuevos.
- **[2.5a]** Migración COMPLETA de Comentarios:
  - `src/Services/ComentarioService.php` - Lógica de negocio (creación, validación, rate limiting, paginación, eliminación)
  - `src/Controllers/ComentarioController.php` - Handlers AJAX (procesarComentario, renderComentarios, eliminarComentario)
  - `src/Views/Components/ComentarioForm.php` - Formulario de comentarios
  - **ELIMINADA** carpeta `app/Content/Comentarios/` completamente
  - Wrappers movidos a `app/deprecated/comentarios.php`
  - Mejoras: tipado estricto, escape de datos, Logger integrado
- **[2.5b]** Migración COMPLETA de Colab (Colaboraciones):
  - `src/Services/ColabService.php` - Lógica de negocio (crear colab, validaciones, variables, cambio de estado, resumen)
  - `src/Controllers/ColabController.php` - Handler AJAX (empezarColab)
  - `src/Views/Components/ColabComponents.php` - Vistas HTML (opciones, contenido, audio, título, participantes, chat, resumen)
  - **ELIMINADA** carpeta `app/Content/Colab/` completamente
  - Wrappers movidos a `app/deprecated/colab.php`
  - Mejoras: tipado estricto, inyección de dependencias, Logger integrado, escape de datos
- **[2.5c]** Migración COMPLETA de Colecciones:
  - `src/Services/ColeccionService.php` - Lógica de negocio (CRUD colecciones, samples, colecciones especiales, cache)
  - `src/Controllers/ColeccionController.php` - 7 handlers AJAX (crear, editar, borrar, guardarSample, eliminarSample, verificar, listar)
  - `src/Views/Components/ColeccionComponents.php` - Vistas HTML (modales, posts, single, opciones)
  - **ELIMINADA** carpeta `app/Content/Colecciones/` completamente
  - Wrappers movidos a `app/deprecated/colecciones.php`
  - Funciones helper mantenidas: `maybe_unserialize_dos`, `datosColeccion`, `imagenPost`
  - Mejoras: tipado estricto, Logger integrado, separación de responsabilidades
- **[2.5d]** Migración PARCIAL de Logic (app/Content/Logic/):
  - `src/Services/CacheService.php` - Sistema de cache con compresión gzip, expiración y limpieza
  - `src/Services/FeedService.php` - Feed personalizado, datos de cálculo, reinicio de feed
  - `src/Services/FiltroService.php` - Filtros globales, por autor, condiciones meta query
  - `src/Controllers/FiltroController.php` - 6 handlers AJAX (obtener, guardar, restablecer filtros)
  - `src/Services/BusquedaService.php` - Búsqueda de posts, usuarios, balanceo de resultados
  - `src/Controllers/BusquedaController.php` - Handler AJAX de búsqueda con cache
  - `src/Views/Components/BusquedaComponents.php` - Componente de buscador local
  - `src/Services/PostEstadoService.php` - Estados de posts, verificación, cambio de imagen
  - `src/Controllers/PostEstadoController.php` - 8 handlers AJAX (verificar, cambiar estado, imagen)
  - `src/Services/ContadorService.php` - Conteo de posts filtrados
  - `src/Controllers/ContadorController.php` - Handler AJAX de conteo
  - **ELIMINADOS** archivos: `cache.php`, `feed.php`, `reiniciarFeed.php`, `datosParaCalculo.php`, `filtroGlobal.php`, `filtroLogic.php`, `busqueda.php`, `estado.php`, `contador.php`, `manejarColeccion.php`, `localControl.php`
  - Wrappers creados en `app/deprecated/`: `cache.php`, `feed.php`, `filtro.php`, `busqueda.php`, `estado.php`, `contador.php`
- **[2.5d+]** Migración COMPLETA de Logic (queryPost y procesarIdeas):
  - `src/Services/IdeaService.php` - Procesamiento de ideas basado en colecciones (posts similares, puntuación por vistas)
  - `src/Services/PublicacionService.php` - Servicio central de queries (~1100 líneas, 20+ métodos)
  - `src/Controllers/PublicacionController.php` - Handler AJAX cargar_mas_publicaciones
  - **ELIMINADA** carpeta `app/Content/Logic/` completamente
  - Wrappers creados en `app/deprecated/`: `ideas.php`, `publicaciones.php`
  - Funciones migradas: `publicaciones`, `publicacionAjax`, `configuracionQueryArgs`, `preOrdenamiento`, `ordenamiento`, `ordenamientoColecciones`, `aplicarFiltrosUsuario`, `prefiltrarIdentifier`, `procesarPublicaciones`, `obtenerUserId`, `manejarIdea`, `procesarIdeas`, `asignarPuntuacionPorVistas`
  - Mejoras: arquitectura OOP, tipado estricto, Logger integrado, separación de responsabilidades, uso de servicios existentes (FeedService, CacheService)
- **[2.5e]** Migración COMPLETA de resto de Content (Momentos, Options, Posts):
  - `src/Core/PostTypes.php` - Registro centralizado de CPTs (social_post, albums, stories, colab, colecciones, etc.) y estados (rejected, pending_deletion)
  - `src/Services/PostSlugService.php` - Gestión automática de títulos y slugs para social_posts verificados
  - `src/Services/PostRenderService.php` - Preparación de variables para renderizado (variablesPosts, variablesArticulo, imágenes, audio)
  - `src/Views/Components/PostComponents.php` - Componentes HTML de posts (~600 líneas: fondoPost, imagenPostList, infoPost, opcionesPost, wave, audioPost, etc.)
  - `src/Views/Components/PostContentComponents.php` - Renderizado especializado (~400 líneas: renderMusicContent, renderNonMusicContent, sampleListHtml, htmlArticulo)
  - `src/Views/Components/MomentoComponents.php` - Componentes de Momentos (momentos, publicarMomento)
  - `src/Controllers/PostController.php` - Handlers AJAX (handle_user_modification, update_post_content)
  - **ELIMINADA** carpeta `app/Content/` completamente (Momentos/, Options/, Posts/)
  - Wrappers creados en `app/deprecated/`: `posts.php`, `momentos.php`, `typesPosts.php`, `ajustes.php`
  - 30+ funciones migradas: htmlPost, variablesPosts, botonseguir, opcionesPost, opcionesRola, wave, audioPost, infoPost, fondoPost, imagenPostList, renderMusicContent, renderNonMusicContent, sampleListHtml, renderPostControls, nohayPost, etc.
  - Mejoras: arquitectura OOP, separación de responsabilidades (Service/Component), tipado estricto, Logger integrado
- **[2.6a]** Migración COMPLETA de app/Logic/:
  - `src/Services/IAService.php` - Servicio de IA con comunicación a API Gemini (descripción, subida de archivos)
  - `src/Controllers/IAController.php` - Handler AJAX para peticiones de IA
  - `src/Services/UtilService.php` - Utilidades generales (normalizar texto, tiempo relativo, zona horaria)
  - `src/Controllers/UtilController.php` - Handler AJAX para ajuste de zona horaria
  - `src/Services/VistaService.php` - Servicio de tracking de vistas (por usuario y post)
  - `src/Controllers/VistaController.php` - Handler AJAX para guardar vistas
  - `src/Services/WaveformService.php` - Gestión de imágenes de waveform de audio
  - `src/Controllers/WaveformController.php` - Handler AJAX para guardar waveforms
  - **ELIMINADA** carpeta `app/Logic/` completamente
  - Wrappers creados en `app/deprecated/`: `ia.php`, `auxiliar.php`, `vistas.php`, `waveform.php`
  - Funciones migradas: generarDescripcionIA, subirArchivo, normalizarTexto, TiempoRelativoNoti, guardarVista, save_waveform_image, etc.
  - Mejoras: tipado estricto, Logger integrado, método CURL centralizado, validación de archivos
- **[2.6b]** Migración COMPLETA de app/Authentication/:
  - `src/Services/AuthService.php` - Servicio de autenticación (~280 líneas: login, registro, Google OAuth, tokens, Firebase)
  - `src/Controllers/AuthController.php` - Controlador REST (endpoints de verificación, Firebase, user-agent)
  - `src/Views/Components/AuthComponents.php` - Formularios de login y registro con Google OAuth
  - **ELIMINADA** carpeta `app/Authentication/` completamente
  - Wrappers creados en `app/deprecated/`: `auth.php`
  - Funciones migradas: iniciar_sesion, registrar_usuario, handle_google_callback, generate_secure_token, verify_secure_token, save_firebase_token, is_electron_app
  - Mejoras: tipado estricto, Logger integrado, separación Service/Controller/Component, detección de navegadores embebidos
- **[2.6c]** Migración COMPLETA de AlgoritmoPost:
  - `src/Services/AlgoritmoService.php` - Algoritmo de recomendación (~650 líneas: calcularFeedPersonalizado, puntos por intereses, identificadores, similitud, decaimiento)
  - `src/Services/InteresService.php` - Gestión de intereses del usuario (~270 líneas: generarMetaDeIntereses, obtenerLikesDelUsuario)
  - FeedService actualizado para usar AlgoritmoService e InteresService directamente
  - **ELIMINADA** carpeta `app/AlgoritmoPost/` completamente
  - Wrappers creados en `app/deprecated/`: `algoritmo.php`
  - Funciones migradas: calcularFeedPersonalizado, calcularPuntosParaPost, calcularPuntosIntereses, calcularPuntosIdentifier, calcularPuntosSimilarTo, getDecayFactor, generarMetaDeIntereses, obtenerLikesDelUsuario, actualizarIntereses
  - Mejoras: arquitectura OOP con Singleton, tipado estricto, Logger integrado, separación de responsabilidades
- **[2.6d]** Migración COMPLETA de app/Functions/:
  - `src/Services/UsuarioService.php` - Gestión de usuarios (~300 líneas: tipos, bloqueos, pinkys)
  - `src/Controllers/UsuarioController.php` - Handlers AJAX de usuario
  - `src/Services/ReporteService.php` - Sistema de reportes de contenido
  - `src/Controllers/ReporteController.php` - Handler AJAX de reportes
  - `src/Services/ImagenService.php` - Optimización de imágenes y adjuntos
  - `src/Services/TagService.php` - Tags frecuentes con cache
  - `src/Views/Components/TagComponents.php` - Componente de visualización de tags
  - `src/Services/StreamService.php` - Streaming de audio con tokens seguros y cache (~400 líneas)
  - `src/Controllers/StreamController.php` - Endpoints REST para streaming
  - `src/Services/DescargaService.php` - Descargas de audio con tokens y pinkys (~260 líneas)
  - `src/Controllers/DescargaController.php` - Handler AJAX y redirect de descargas
  - `src/Views/Components/DescargaComponents.php` - Botones de descarga y sincronización
  - `src/Services/ReproductorService.php` - Reproducciones y oyentes con rate limiting
  - `src/Controllers/ReproductorController.php` - Endpoint REST de reproducciones
  - `src/Views/Components/ReproductorComponents.php` - Reproductor flotante de audio
  - `src/Core/OptimizacionWP.php` - Optimizaciones de WordPress (emojis, embeds, feeds, etc.)
  - `src/Views/Components/FiltroComponents.php` - Componente de filtros de samples
  - `src/Views/Components/AppModalComponents.php` - Modales de descarga/actualización de app
  - `src/Services/NormalizacionService.php` - Normalización de tags de posts (~290 líneas)
  - `src/Services/AudioProteccionService.php` - Protección y optimización de audio (~300 líneas)
  - `src/Services/PostEdicionService.php` - Edición de posts con IA (~280 líneas)
  - `src/Controllers/PostEdicionController.php` - Handlers AJAX de edición de posts
  - Wrappers creados en `app/deprecated/`: `functions.php`, `stream.php`, `descargas.php`, `reproductor.php`, `optimizacion.php`, `modales.php`, `renderFiltro.php`, `normalizarTags.php`, `protegerAudio.php`, `ajaxPost.php`
  - Funciones migradas: stream, descargas, reproductor, filtros, modales, normalización de tags, protección de audio, edición de posts
  - Pendiente: `descargarColeccion.php` (utiliza ColeccionService existente)
- **[2.6e]** Migración de carpetas restantes de /app/:
  - `src/Services/ColeccionDescargaService.php` - Descarga de colecciones en ZIP (~450 líneas)
  - `src/Core/AdminConfig.php` - Configuración centralizada de administración (~210 líneas)
  - `src/Core/CleanupService.php` - Limpieza automática de posts pending/trash
  - `src/Views/Components/AdminComponents.php` - Componentes de panel admin
  - `src/Views/Components/OnboardingComponents.php` - Modales de tipo de usuario y géneros
  - `src/Controllers/OnboardingController.php` - Handlers AJAX de onboarding
  - Actualizado `ColeccionComponents.php` con métodos `renderBotonDescarga` y `renderBotonSincronizar`
  - **ELIMINADA** carpeta `app/Functions/` completamente (incluido `Ajax Post/`)
  - **ELIMINADA** carpeta `app/Admin/` completamente
  - **ELIMINADA** carpeta `app/Hook/` completamente
  - **ELIMINADA** carpeta `app/View/` completamente
  - Wrappers creados en `app/deprecated/`: `descargarColeccion.php`, `admin.php`, `hooks.php`, `onboarding.php`
  - **[2.6f]** Migración de `app/Perfiles/`:
    - `src/Services/PerfilService.php` - Actualizado con gestión de avatar, nombres y enlaces.
    - `src/Controllers/PerfilController.php` - Unificación de handlers AJAX de perfil.
    - `src/Views/Components/PerfilComponents.php` - Componentes de UI (banner, modales, shortcodes).
    - `src/Core/ProfileRoutes.php` - Reglas de reescritura y redirecciones de usuarios.
    - `app/deprecated/perfiles.php` - Wrapper de compatibilidad.
    - **ELIMINADA** carpeta `app/Perfiles/` completamente.
  - **[2.6g]** Migración de `app/Auto/`:
    - `src/Services/AutoPostService.php` - Gestión de posts automáticos y escaneo.
    - `src/Services/AutoContentService.php` - Mejora de contenido con IA y renombrado.
    - `src/Services/PythonService.php` - Ejecución de scripts Python.
    - `src/Services/HashService.php` - Actualizado con métodos de hashing de audio.
    - `app/deprecated/auto.php` - Wrappers de compatibilidad.
    - **ELIMINADA** carpeta `app/Auto/` completamente.
  - **[FIX]** Restaurado y refactorizado `formRs`:
    - `src/Views/Components/PostFormComponents.php` - Creado componente para el formulario de publicación.
    - `app/deprecated/form.php` - Añadido wrapper de compatibilidad para `formRs()`.
    - `header.php` - Actualizado para usar `PostFormComponents::renderFormRs()`.

---


## Problemas de Seguridad Identificados

> Esta sección se actualizará conforme se encuentren vulnerabilidades.

| Archivo | Problema               | Severidad | Estado |
| ------- | ---------------------- | --------- | ------ |
| -       | Pendiente de auditoría | -         | ⏳      |

---

## Sistema de Logging

### Niveles de Log
| Nivel    | Valor | Uso                                               |
| -------- | ----- | ------------------------------------------------- |
| DEBUG    | 0     | Información detallada para desarrollo             |
| INFO     | 1     | Información general del flujo de la aplicación    |
| WARNING  | 2     | Situaciones inesperadas pero no críticas          |
| ERROR    | 3     | Errores que requieren atención                    |
| CRITICAL | 4     | Fallos graves que pueden causar caída del sistema |
| OFF      | 5     | Desactivar logs completamente                     |

### Canales Disponibles
| Canal        | Nivel por defecto | Solo Admin | Descripción                    |
| ------------ | ----------------- | ---------- | ------------------------------ |
| stream       | INFO              | No         | Streaming de audio             |
| seo          | INFO              | No         | Eventos de SEO                 |
| audio        | DEBUG             | No         | Procesamiento de audio         |
| rendimiento  | INFO              | No         | Métricas de rendimiento        |
| chat         | DEBUG             | Sí         | Sistema de chat                |
| stripe       | WARNING           | Sí         | Pagos y errores de Stripe      |
| automatico   | INFO              | No         | Posts automáticos              |
| guardar      | INFO              | No         | Operaciones de guardado        |
| algoritmo    | DEBUG             | Sí         | Algoritmo de recomendación     |
| ajaxPost     | DEBUG             | Sí         | Peticiones AJAX de posts       |
| ia           | INFO              | Sí         | Operaciones de IA              |
| post         | DEBUG             | Sí         | Operaciones de posts           |
| **refactor** | INFO              | No         | Seguimiento de refactorización |

### Uso del Logger

```php
// API moderna (recomendada)
$logger = Logger::obtenerInstancia();
$logger->info('refactor', 'Migrado módulo X a inc/Setup/');
$logger->warning('stripe', 'Reintentando conexión', ['intento' => 3]);
$logger->error('ia', 'Fallo en procesamiento', ['error' => $e->getMessage()]);

// Funciones wrapper (compatibilidad)
refactorLog('Cambio realizado en functions.php');
iaLog('Procesando solicitud');
```

### Configuración de Canales
Para modificar la configuración de un canal, editar `inc/Config/constants.php`:

```php
'ia' => [
    'enabled'   => true,        // Activar/desactivar canal
    'level'     => LOG_LEVEL_INFO, // Nivel mínimo para loggear
    'adminOnly' => true,        // Solo registrar para admins
],
```

---

## Ideas y Mejoras Propuestas

> Espacio para documentar ideas de mejora encontradas durante la refactorización.

1. ~~**Sistema de Logging**: Implementar clase Logger con niveles (debug, info, warning, error)~~ ✅ Completado
2. **Autoloader**: Implementar PSR-4 autoloading para clases
3. **Inyección de Dependencias**: Considerar un contenedor simple para servicios
4. **Configuración Externa**: Mover configuración a archivo .env o base de datos

---

## Notas Importantes

- El CSS se refactorizará **al final** cuando todo el código esté organizado
- Cada cambio debe ser testeado antes de continuar
- Documentar cualquier comportamiento extraño encontrado
- Si una función parece hacer demasiado, probablemente lo hace
- **Usar `refactorLog()` para registrar cambios importantes durante la refactorización**

# Errores de consola corregidos (2025-12-14)

Los siguientes errores fueron identificados y corregidos:

| Error                                                                | Archivo                               | Causa                                                                                  | Corrección                                                                       |
| -------------------------------------------------------------------- | ------------------------------------- | -------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| `400 Bad Request` en AJAX (obtenerFiltrosTotal, obtenerFiltroActual) | `functions.php`, `src/Controllers/`   | Los controladores no se cargaban - el autoloader PSR-4 solo carga clases al invocarlas | Creado `src/Controllers/init.php` que carga todos los controladores              |
| `userAgent is not defined`                                           | `app/Functions/modalapp.php` línea 54 | Variable usada sin definir                                                             | Cambiado `userAgent` por `navigator.userAgent`                                   |
| `Cannot read properties of undefined (reading 'querySelector')`      | `js/wavejs.js` línea 264              | Función `handleWaveformClick` llamada sin parámetro `post`                             | Unificada función duplicada y añadida lógica para obtener `post` del `container` |
| `No se encontró el elemento 'filtrosPost'`                           | `js/filtros.js` línea 229             | `console.error` innecesario en páginas sin filtros                                     | Cambiado a return silencioso                                                     |
| `404 Not Found` en `/wp-json/1/v1/2`                                 | `functions.php`                       | `StreamController` y otros controladores no se inicializaban                           | Incluido `src/Controllers/init.php` en `functions.php`                           |
| `TypeError: Cannot read properties of undefined` en `wavejs.js`      | `js/wavejs.js`                        | `window.wavesurfers` no estaba inicializado globalmente                                | Inicializado `window.wavesurfers = {}` al inicio del archivo                     |

