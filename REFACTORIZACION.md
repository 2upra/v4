# Refactorización del Tema v4 (2upra/kamples)

> **Última actualización:** 2025-12-15
> **Estado:** Fase 3 - Revisión SOLID y limpieza de deprecated

---

## Contexto del Proyecto

Tema de WordPress para aplicación social/musical. Requiere refactorización progresiva aplicando principios SOLID.

---

## Reglas Esenciales

### Principios SOLID
| Principio                     | Aplicación                                     |
| ----------------------------- | ---------------------------------------------- |
| **S** - Single Responsibility | Un archivo = una responsabilidad clara         |
| **O** - Open/Closed           | Código extensible sin modificar el original    |
| **L** - Liskov Substitution   | Clases hijas intercambiables con padres        |
| **I** - Interface Segregation | Interfaces pequeñas y específicas              |
| **D** - Dependency Inversion  | Depender de abstracciones, no implementaciones |

### Límites de Tamaño (OBLIGATORIO)
| Tipo de Archivo | Máximo     | Acción si se excede                  |
| --------------- | ---------- | ------------------------------------ |
| Servicio        | 300 líneas | Dividir en servicios más específicos |
| Controlador     | 150 líneas | Extraer a servicios la lógica        |
| Componente      | 200 líneas | Dividir en subcomponentes            |

### Nomenclatura y Estructura
- Variables/funciones: `camelCase` | Clases: `PascalCase` | Constantes: `SCREAMING_SNAKE_CASE`
- Namespace: `Kamples\{Services|Controllers|Views\Components}`
- Logging: **Solo** usar `Logger::obtenerInstancia()->info('canal', 'mensaje')`

---

## Roadmap General

| Fase  | Descripción                              | Estado       |
| ----- | ---------------------------------------- | ------------ |
| 1     | Organizar `functions.php`                | ✅ Completada |
| 2     | Migrar `/app/` a `/src/`                 | ✅ Completada |
| **3** | **Revisión SOLID y limpieza deprecated** | ⬅️ **ACTUAL** |
| 4     | Limpiar `header.php` y templates         | ⏳ Pendiente  |
| 5     | Modularizar JavaScript                   | ⏳ Pendiente  |
| 6     | Segmentar CSS                            | ⏳ Pendiente  |

---

## Fase 3: Revisión SOLID y Limpieza

### Objetivos
1. **Organizar por módulos/dominios** - Agrupar servicios, controladores y componentes relacionados en carpetas
2. **Revisar cada servicio y controlador** aplicando principios SOLID
3. **Eliminar wrappers deprecated** de forma progresiva
4. **Separar responsabilidades** en servicios muy grandes (>300 líneas)

### Estructura de Carpetas Propuesta

Organizar `src/` en módulos por dominio para facilitar navegación y mantenimiento:

```
src/
├── Services/
│   ├── Audio/                    # Todo lo relacionado con audio
│   │   ├── AudioProcessingService.php
│   │   ├── AudioProteccionService.php
│   │   ├── StreamService.php
│   │   ├── WaveformService.php
│   │   ├── HashService.php
│   │   └── ReproductorService.php
│   │
│   ├── Publicacion/              # Gestión de publicaciones/posts
│   │   ├── PublicacionService.php      (fachada)
│   │   ├── PublicacionQueryService.php
│   │   ├── PublicacionOrdenamientoService.php
│   │   ├── PublicacionFiltroService.php
│   │   ├── PostCreacionService.php
│   │   ├── PostEdicionService.php
│   │   ├── PostEstadoService.php
│   │   ├── PostRenderService.php
│   │   └── PostSlugService.php
│   │
│   ├── Usuario/                  # Gestión de usuarios
│   │   ├── UsuarioService.php
│   │   ├── PerfilService.php
│   │   ├── AuthService.php
│   │   ├── SeguirService.php
│   │   └── InteresService.php
│   │
│   ├── Social/                   # Interacciones sociales
│   │   ├── LikeService.php
│   │   ├── ComentarioService.php
│   │   ├── NotificacionService.php
│   │   ├── ChatService.php
│   │   └── ColabService.php
│   │
│   ├── Coleccion/                # Colecciones/playlists
│   │   ├── ColeccionService.php
│   │   ├── ColeccionDescargaService.php
│   │   └── AlbumService.php
│   │
│   ├── Feed/                     # Feed y algoritmo
│   │   ├── FeedService.php
│   │   ├── AlgoritmoService.php
│   │   └── FiltroService.php
│   │
│   ├── Finanza/                  # Sistema financiero
│   │   └── FinanzaService.php
│   │
│   ├── Contenido/                # Procesamiento de contenido
│   │   ├── ImagenService.php
│   │   ├── IAService.php
│   │   ├── AutoContentService.php
│   │   ├── AutoPostService.php
│   │   ├── IdeaService.php
│   │   └── NormalizacionService.php
│   │
│   ├── Moderacion/               # Moderación y reportes
│   │   ├── ModeracionService.php
│   │   └── ReporteService.php
│   │
│   └── Core/                     # Utilidades y servicios base
│       ├── CacheService.php
│       ├── UtilService.php
│       ├── PythonService.php
│       ├── SyncService.php
│       ├── VistaService.php
│       ├── ContadorService.php
│       ├── TagService.php
│       ├── BusquedaService.php
│       └── DescargaService.php
│
├── Controllers/                  # Misma estructura por módulos
│   ├── Audio/
│   ├── Publicacion/
│   ├── Usuario/
│   ├── Social/
│   ├── Coleccion/
│   ├── Finanza/
│   └── Core/
│
└── Views/Components/             # Misma estructura por módulos
    ├── Audio/
    ├── Publicacion/
    ├── Usuario/
    ├── Social/
    ├── Coleccion/
    ├── Finanza/
    └── Core/
```

> **Beneficios:**
> - Fácil encontrar archivos relacionados
> - Namespace claro por dominio
> - Cambios en un módulo no afectan otros
> - Mejor escalabilidad

### Criterios de Revisión por Archivo
- [ ] **SRP**: ¿Tiene una sola responsabilidad clara?
- [ ] **Tamaño**: ¿Está dentro de los límites? (300/150/200 líneas)
- [ ] **Dependencias**: ¿Usa inyección de dependencias correctamente?
- [ ] **Logger**: ¿Usa el Logger en vez de error_log?
- [ ] **Tipado**: ¿Tiene tipos de parámetros y retorno?
- [ ] **Deprecated**: ¿Hay wrappers que ya pueden eliminarse?

---

## Inventario de Servicios (46 archivos)

### Por revisar
| #   | Servicio                 | Líneas | Responsabilidad         | Prioridad | Estado       |
| --- | ------------------------ | ------ | ----------------------- | --------- | ------------ |
| 1   | PublicacionService       | ~230   | Fachada (refactorizado) | ✅ Hecho   | ✅ Completado |
| 2   | AlgoritmoService         | ~330   | Fachada (refactorizado) | ✅ Hecho   | ✅ Completado |
| 3   | ColeccionService         | ~200   | Fachada (refactorizado) | ✅ Hecho   | ✅ Completado |
| 4   | AudioProcessingService   | ~620   | Procesamiento de audio  | 🟡 Media   | ⏳ Pendiente  |
| 5   | AutoPostService          | ~650   | Posts automáticos       | 🟡 Media   | ⏳ Pendiente  |
| 6   | NotificacionService      | ~470   | Notificaciones push     | 🟡 Media   | ⏳ Pendiente  |
| 7   | FinanzaService           | ~450   | Sistema financiero      | 🟡 Media   | ⏳ Pendiente  |
| 8   | FeedService              | ~460   | Feed personalizado      | 🟡 Media   | ⏳ Pendiente  |
| 9   | StreamService            | ~420   | Streaming de audio      | 🟡 Media   | ⏳ Pendiente  |
| 10  | ColeccionDescargaService | ~400   | Descarga ZIP            | 🟢 Baja    | ⏳ Pendiente  |
| 11  | ComentarioService        | ~400   | Comentarios             | 🟢 Baja    | ⏳ Pendiente  |
| 12  | ColabService             | ~410   | Colaboraciones          | 🟢 Baja    | ⏳ Pendiente  |
| 13  | SyncService              | ~390   | Sincronización Electron | 🟢 Baja    | ⏳ Pendiente  |
| 14  | FiltroService            | ~360   | Filtros de posts        | 🟢 Baja    | ⏳ Pendiente  |
| 15  | HashService              | ~400   | Hashing de audio        | 🟢 Baja    | ⏳ Pendiente  |
| 16  | ChatService              | ~330   | Sistema de chat         | 🟢 Baja    | ⏳ Pendiente  |
| 17  | AuthService              | ~300   | Autenticación           | 🟢 Baja    | ⏳ Pendiente  |
| 18  | AudioProteccionService   | ~300   | Protección de audio     | 🟢 Baja    | ⏳ Pendiente  |
| 19  | PerfilService            | ~280   | Perfiles de usuario     | 🟢 Baja    | ⏳ Pendiente  |
| 20  | PostEdicionService       | ~270   | Edición de posts        | 🟢 Baja    | ⏳ Pendiente  |
| 21  | InteresService           | ~270   | Intereses del usuario   | 🟢 Baja    | ⏳ Pendiente  |
| 22  | NormalizacionService     | ~260   | Normalización de tags   | 🟢 Baja    | ⏳ Pendiente  |
| 23  | ModeracionService        | ~240   | Moderación/bloqueos     | 🟢 Baja    | ⏳ Pendiente  |
| 24  | AutoContentService       | ~250   | Mejora con IA           | 🟢 Baja    | ⏳ Pendiente  |
| 25  | DescargaService          | ~260   | Descargas de audio      | 🟢 Baja    | ⏳ Pendiente  |
| 26  | LikeService              | ~290   | Sistema de likes        | 🟢 Baja    | ⏳ Pendiente  |
| 27  | AlbumService             | ~250   | Procesamiento álbumes   | 🟢 Baja    | ⏳ Pendiente  |
| 28  | BusquedaService          | ~230   | Búsqueda de contenido   | 🟢 Baja    | ⏳ Pendiente  |
| 29  | PostRenderService        | ~240   | Renderizado de posts    | 🟢 Baja    | ⏳ Pendiente  |
| 30  | PostCreacionService      | ~320   | Creación de posts       | 🟢 Baja    | ⏳ Pendiente  |
| 31  | IAService                | ~220   | Comunicación con IA     | 🟢 Baja    | ⏳ Pendiente  |
| 32  | IdeaService              | ~240   | Procesamiento de ideas  | 🟢 Baja    | ⏳ Pendiente  |
| 33  | ImagenService            | ~200   | Optimización imágenes   | 🟢 Baja    | ⏳ Pendiente  |
| 34  | UsuarioService           | ~290   | Gestión de usuarios     | 🟢 Baja    | ⏳ Pendiente  |
| 35  | SeguirService            | ~190   | Sistema de seguimiento  | 🟢 Baja    | ⏳ Pendiente  |
| 36  | CacheService             | ~220   | Sistema de cache        | 🟢 Baja    | ⏳ Pendiente  |
| 37  | PostEstadoService        | ~170   | Estados de posts        | 🟢 Baja    | ⏳ Pendiente  |
| 38  | WaveformService          | ~150   | Waveforms de audio      | 🟢 Baja    | ⏳ Pendiente  |
| 39  | ReproductorService       | ~140   | Control reproductor     | 🟢 Baja    | ⏳ Pendiente  |
| 40  | PostSlugService          | ~130   | Slugs de posts          | 🟢 Baja    | ⏳ Pendiente  |
| 41  | ReporteService           | ~120   | Reportes de contenido   | 🟢 Baja    | ⏳ Pendiente  |
| 42  | TagService               | ~110   | Tags frecuentes         | 🟢 Baja    | ⏳ Pendiente  |
| 43  | UtilService              | ~110   | Utilidades generales    | 🟢 Baja    | ⏳ Pendiente  |
| 44  | VistaService             | ~110   | Tracking de vistas      | 🟢 Baja    | ⏳ Pendiente  |
| 45  | PythonService            | ~80    | Ejecución Python        | 🟢 Baja    | ⏳ Pendiente  |
| 46  | ContadorService          | ~50    | Conteo de posts         | 🟢 Baja    | ⏳ Pendiente  |

> **Nota:** Líneas aproximadas según tamaño de archivo. Prioridad alta = servicios grandes que probablemente violan SRP.

---

## Inventario de Controladores (31 archivos)

| #   | Controlador            | Tamaño | Estado      |
| --- | ---------------------- | ------ | ----------- |
| 1   | FinanzaController      | ~460   | ⏳ Pendiente |
| 2   | ChatController         | ~320   | ⏳ Pendiente |
| 3   | ComentarioController   | ~270   | ⏳ Pendiente |
| 4   | ColeccionController    | ~240   | ⏳ Pendiente |
| 5   | ArchivoController      | ~200   | ⏳ Pendiente |
| 6   | PerfilController       | ~200   | ⏳ Pendiente |
| 7   | SyncController         | ~150   | ⏳ Pendiente |
| 8   | AuthController         | ~150   | ⏳ Pendiente |
| 9   | FiltroController       | ~140   | ⏳ Pendiente |
| 10  | UsuarioController      | ~130   | ⏳ Pendiente |
| 11  | PostController         | ~130   | ⏳ Pendiente |
| 12  | LikeController         | ~115   | ⏳ Pendiente |
| 13  | PostEstadoController   | ~95    | ⏳ Pendiente |
| 14  | NotificacionController | ~95    | ⏳ Pendiente |
| 15  | SeguirController       | ~75    | ⏳ Pendiente |
| 16  | StreamController       | ~80    | ⏳ Pendiente |
| 17  | ColabController        | ~75    | ⏳ Pendiente |
| 18  | ModeracionController   | ~70    | ⏳ Pendiente |
| 19  | PublicacionController  | ~75    | ⏳ Pendiente |
| 20  | ReproductorController  | ~60    | ⏳ Pendiente |
| 21  | ReporteController      | ~55    | ⏳ Pendiente |
| 22  | OnboardingController   | ~55    | ⏳ Pendiente |
| 23  | PostEdicionController  | ~65    | ⏳ Pendiente |
| 24  | FormularioController   | ~55    | ⏳ Pendiente |
| 25  | IAController           | ~50    | ⏳ Pendiente |
| 26  | DescargaController     | ~50    | ⏳ Pendiente |
| 27  | ContadorController     | ~45    | ⏳ Pendiente |
| 28  | WaveformController     | ~45    | ⏳ Pendiente |
| 29  | BusquedaController     | ~40    | ⏳ Pendiente |
| 30  | VistaController        | ~35    | ⏳ Pendiente |
| 31  | UtilController         | ~35    | ⏳ Pendiente |

---

## Inventario de Componentes (22 + 11 Tabs)

### Componentes Principales
| Componente             | Tamaño | Estado      |
| ---------------------- | ------ | ----------- |
| PostComponents         | ~750   | ⏳ Pendiente |
| PostContentComponents  | ~680   | ⏳ Pendiente |
| PerfilComponents       | ~610   | ⏳ Pendiente |
| FinanzaComponents      | ~600   | ⏳ Pendiente |
| ColeccionComponents    | ~520   | ⏳ Pendiente |
| ColabComponents        | ~440   | ⏳ Pendiente |
| AppModalComponents     | ~370   | ⏳ Pendiente |
| AuthComponents         | ~225   | ⏳ Pendiente |
| ChatList               | ~195   | ⏳ Pendiente |
| PostFormComponents     | ~200   | ⏳ Pendiente |
| NotificacionComponents | ~130   | ⏳ Pendiente |
| FiltroComponents       | ~120   | ⏳ Pendiente |
| OnboardingComponents   | ~95    | ⏳ Pendiente |
| DescargaComponents     | ~100   | ⏳ Pendiente |
| AdminComponents        | ~95    | ⏳ Pendiente |
| LikeButtons            | ~100   | ⏳ Pendiente |
| ComentarioForm         | ~80    | ⏳ Pendiente |
| ReproductorComponents  | ~85    | ⏳ Pendiente |
| ChatBox                | ~85    | ⏳ Pendiente |
| MomentoComponents      | ~30    | ⏳ Pendiente |
| BusquedaComponents     | ~20    | ⏳ Pendiente |
| TagComponents          | ~30    | ⏳ Pendiente |

### Tabs (11 archivos en `/Tabs/`)
| Tab                 | Estado      |
| ------------------- | ----------- |
| ProyectoTabs (~800) | ⏳ Pendiente |
| SocialTabs          | ⏳ Pendiente |
| PerfilTabs          | ⏳ Pendiente |
| BibliotecaTabs      | ⏳ Pendiente |
| BusquedaTabs        | ⏳ Pendiente |
| MusicTabs           | ⏳ Pendiente |
| ColabTabs           | ⏳ Pendiente |
| ColeccionTabs       | ⏳ Pendiente |
| TaskTabs            | ⏳ Pendiente |
| InicioTabs          | ⏳ Pendiente |
| InversorTabs        | ⏳ Pendiente |

---

## Wrappers Deprecated (48 archivos)

> Ubicados en `/app/deprecated/`. Se irán eliminando conforme se actualicen las referencias.

| Archivo       | Usos restantes | Estado      |
| ------------- | -------------- | ----------- |
| algoritmo.php | Por verificar  | ⏳ Pendiente |
| auth.php      | Por verificar  | ⏳ Pendiente |
| auto.php      | Por verificar  | ⏳ Pendiente |
| ...           | ...            | ...         |

> **Nota:** Lista completa en `/app/deprecated/`. Cada revisión de servicio incluirá verificar si su wrapper puede eliminarse.

---

## Registro de Revisiones (Fase 3)

### Plantilla de Revisión

```markdown
### [Nombre del Servicio/Controlador]
- **Fecha:** YYYY-MM-DD
- **Líneas:** XXX → YYY
- **Problemas encontrados:**
  - [ ] Problema 1
  - [ ] Problema 2
- **Acciones tomadas:**
  - Acción 1
  - Acción 2
- **Deprecated eliminados:** lista de wrappers
- **Estado:** ✅ Revisado / 🔄 En proceso
```

### Plan de Acción Fase 3

**Paso 1: Organizar estructura de carpetas** (EN CURSO)
1. Crear carpetas por módulo en `Services/`, `Controllers/`, `Views/Components/`
2. Mover archivos a sus carpetas correspondientes
3. Actualizar namespaces (`Kamples\Services\Audio\`, etc.)
4. Actualizar autoload en `composer.json` si es necesario
5. Actualizar referencias en wrappers deprecated

**Paso 2: Refactorizar PublicacionService** (~1160 líneas → ~200 + 3 servicios)
- Extraer `PublicacionQueryService` (construcción de queries)
- Extraer `PublicacionOrdenamientoService` (ordenamientos y likes)
- Extraer `PublicacionFiltroService` (filtros de usuario y globales)
- `PublicacionService` queda como fachada orquestadora

**Paso 3: Refactorizar AlgoritmoService** (~750 líneas)
- Analizar responsabilidades y dividir si es necesario

---

### Revisiones Completadas

#### PublicacionService (2025-12-15) ✅
- **Antes:** 1160 líneas, violaba SRP (queries + ordenamiento + filtros + renderizado)
- **Después:** Dividido en 4 servicios en `src/Services/Publicacion/`:
  - `PublicacionService.php` (~230 líneas) - Fachada orquestadora
  - `PublicacionQueryService.php` (~280 líneas) - Construcción de queries
  - `PublicacionOrdenamientoService.php` (~350 líneas) - Ordenamientos y likes
  - `PublicacionFiltroService.php` (~270 líneas) - Filtros de usuario y búsqueda
- **Wrapper deprecated:** `src/Services/PublicacionService.php` redirige al nuevo
- **Reducción:** 1160 → 230 líneas (~80% menos en archivo principal)

#### AlgoritmoService (2025-12-15) ✅
- **Antes:** 837 líneas, violaba SRP (puntuación + similitud + cron)
- **Después:** Dividido en 4 servicios en `src/Services/Feed/`:
  - `AlgoritmoService.php` (~330 líneas) - Fachada orquestadora
  - `AlgoritmoPuntuacionService.php` (~260 líneas) - Cálculo de puntos
  - `AlgoritmoSimilitudService.php` (~130 líneas) - Cálculo de similitud
  - `AlgoritmoCronService.php` (~150 líneas) - Trabajo de cron
- **Wrapper deprecated:** `src/Services/AlgoritmoService.php` redirige al nuevo
- **Reducción:** 837 → 330 líneas (~60% menos en archivo principal)

#### ColeccionService (2025-12-15) ✅
- **Antes:** 722 líneas, violaba SRP (CRUD + samples + consultas + renderizado)
- **Después:** Dividido en 4 servicios en `src/Services/Coleccion/`:
  - `ColeccionService.php` (~200 líneas) - Fachada orquestadora
  - `ColeccionCrudService.php` (~230 líneas) - Crear, editar, eliminar colecciones
  - `ColeccionSampleService.php` (~360 líneas) - Gestión de samples en colecciones
  - `ColeccionQueryService.php` (~160 líneas) - Consultas y renderizado
- **Wrapper deprecated:** `src/Services/ColeccionService.php` redirige al nuevo
- **Reducción:** 722 → 200 líneas (~72% menos en archivo principal)

---

## Historial Comprimido

### Fase 1 (2025-12-14) ✅
- `functions.php` reducido de 715 a 124 líneas (~83%)
- Creada estructura `inc/` (Config, Logging, Setup, Utils, Security)
- Implementado Logger con patrón Singleton

### Fase 2 (2025-12-14 - 2025-12-15) ✅
- **46 servicios** migrados a `src/Services/`
- **31 controladores** migrados a `src/Controllers/`
- **33 componentes** migrados a `src/Views/Components/`
- **48 wrappers** creados en `app/deprecated/`
- Carpetas eliminadas: `app/Chat/`, `app/Content/`, `app/Logic/`, `app/Authentication/`, `app/AlgoritmoPost/`, `app/Functions/`, `app/Admin/`, `app/Hook/`, `app/View/`, `app/Perfiles/`, `app/Auto/`, `app/Sync/`, `app/Pages/`, `app/Finanza/`, `app/Misc/`
- Scripts movidos a `/scripts/python/`, `/scripts/shell/`, `/Tests/`

> Ver archivo `HISTORIAL_FASE2.md` para detalles completos de la migración.

---

## Notas Importantes

- **Revisar 1 servicio/controlador a la vez** - no hacer cambios masivos
- **Verificar funcionamiento** después de cada cambio
- **Actualizar este documento** después de cada revisión
- **Priorizar servicios grandes** (>300 líneas) que probablemente violan SRP
- Los wrappers deprecated se eliminan **solo cuando se confirma** que no hay usos

---

## Sistema de Logging

Canales principales: `like`, `chat`, `auth`, `stripe`, `ia`, `post`, `refactor`

```php
$logger = \Logger::obtenerInstancia();
$logger->info('canal', 'Mensaje');
$logger->error('canal', 'Error', ['contexto' => $valor]);
```
