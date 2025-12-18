# Refactorización del Tema v4 (2upra/kamples)

> **Última actualización:** 2025-12-17
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

### Estado Actual
- **Servicios:** ✅ **46/46 completados** - Todos organizados en carpetas de dominio
- **Controladores:** ✅ **31/31 completados** - Todos organizados en carpetas de dominio
- **Componentes:** ⏳ 0/33 pendientes

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

## Inventario de Servicios (46 archivos) ✅ COMPLETADO

### Todos organizados en carpetas de dominio
| #   | Servicio                 | Líneas | Responsabilidad          | Prioridad | Estado       |
| --- | ------------------------ | ------ | ------------------------ | --------- | ------------ |
| 1   | PublicacionService       | ~230   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 2   | AlgoritmoService         | ~330   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 3   | ColeccionService         | ~200   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 4   | AudioProcessingService   | ~120   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 5   | AutoPostService          | ~110   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 6   | NotificacionService      | ~100   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 7   | FinanzaService           | ~115   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 8   | FeedService              | ~230   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 9   | StreamService            | ~180   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 10  | ColeccionDescargaService | ~155   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 11  | ComentarioService        | ~135   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 12  | ColabService             | ~100   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 13  | SyncService              | ~100   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 14  | FiltroService            | ~110   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 15  | HashService              | ~120   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 16  | ChatService              | ~100   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 17  | AuthService              | ~100   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 18  | AudioProteccionService   | ~100   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 19  | PerfilService            | ~290   | Dentro límite (revisado) | ✅ Hecho   | ✅ Completado |
| 20  | PostEdicionService       | ~285   | Dentro límite (revisado) | ✅ Hecho   | ✅ Completado |
| 21  | InteresService           | ~295   | Dentro límite (revisado) | ✅ Hecho   | ✅ Completado |
| 22  | NormalizacionService     | ~320   | Dentro límite (revisado) | ✅ Hecho   | ✅ Completado |
| 23  | ModeracionService        | ~240   | Movido a Moderacion/     | ✅ Hecho   | ✅ Completado |
| 24  | AutoContentService       | ~210   | Movido a Contenido/      | ✅ Hecho   | ✅ Completado |
| 25  | DescargaService          | ~180   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 26  | LikeService              | ~100   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 27  | AlbumService             | ~303   | Movido a Coleccion/      | ✅ Hecho   | ✅ Completado |
| 28  | BusquedaService          | ~271   | Movido a Core/           | ✅ Hecho   | ✅ Completado |
| 29  | PostRenderService        | ~277   | Movido a Publicacion/    | ✅ Hecho   | ✅ Completado |
| 30  | PostCreacionService      | ~160   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 31  | IAService                | ~224   | Movido a Contenido/      | ✅ Hecho   | ✅ Completado |
| 32  | IdeaService              | ~281   | Movido a Contenido/      | ✅ Hecho   | ✅ Completado |
| 33  | ImagenService            | ~237   | Movido a Contenido/      | ✅ Hecho   | ✅ Completado |
| 34  | UsuarioService           | ~170   | Fachada (refactorizado)  | ✅ Hecho   | ✅ Completado |
| 35  | SeguirService            | ~220   | Movido a Usuario/        | ✅ Hecho   | ✅ Completado |
| 36  | CacheService             | ~290   | Movido a Core/           | ✅ Hecho   | ✅ Completado |
| 37  | PostEstadoService        | ~170   | Movido a Publicacion/    | ✅ Hecho   | ✅ Completado |
| 38  | WaveformService          | ~150   | Movido a Audio/          | ✅ Hecho   | ✅ Completado |
| 39  | ReproductorService       | ~140   | Movido a Audio/          | ✅ Hecho   | ✅ Completado |
| 40  | PostSlugService          | ~130   | Movido a Publicacion/    | ✅ Hecho   | ✅ Completado |
| 41  | ReporteService           | ~120   | Movido a Moderacion/     | ✅ Hecho   | ✅ Completado |
| 42  | TagService               | ~110   | Movido a Core/           | ✅ Hecho   | ✅ Completado |
| 43  | UtilService              | ~110   | Movido a Core/           | ✅ Hecho   | ✅ Completado |
| 44  | VistaService             | ~110   | Movido a Core/           | ✅ Hecho   | ✅ Completado |
| 45  | PythonService            | ~80    | Movido a Core/           | ✅ Hecho   | ✅ Completado |
| 46  | ContadorService          | ~50    | Movido a Core/           | ✅ Hecho   | ✅ Completado |

> **Nota:** Líneas aproximadas según tamaño de archivo. Prioridad alta = servicios grandes que probablemente violan SRP.

---

## Inventario de Controladores (31 archivos) ✅ COMPLETADO

| #   | Controlador            | Tamaño | Destino        | Estado       |
| --- | ---------------------- | ------ | -------------- | ------------ |
| 1   | FinanzaController      | ~460   | `Finanza/`     | ✅ Completado |
| 2   | ChatController         | ~320   | `Social/`      | ✅ Completado |
| 3   | ComentarioController   | ~270   | `Social/`      | ✅ Completado |
| 4   | ColeccionController    | ~240   | `Coleccion/`   | ✅ Completado |
| 5   | ArchivoController      | ~200   | `Audio/`       | ✅ Completado |
| 6   | PerfilController       | ~200   | `Usuario/`     | ✅ Completado |
| 7   | SyncController         | ~150   | `Core/`        | ✅ Completado |
| 8   | AuthController         | ~150   | `Usuario/`     | ✅ Completado |
| 9   | FiltroController       | ~140   | `Feed/`        | ✅ Completado |
| 10  | UsuarioController      | ~130   | `Usuario/`     | ✅ Completado |
| 11  | PostController         | ~130   | `Publicacion/` | ✅ Completado |
| 12  | LikeController         | ~115   | `Social/`      | ✅ Completado |
| 13  | PostEstadoController   | ~95    | `Publicacion/` | ✅ Completado |
| 14  | NotificacionController | ~95    | `Social/`      | ✅ Completado |
| 15  | SeguirController       | ~75    | `Social/`      | ✅ Completado |
| 16  | StreamController       | ~80    | `Audio/`       | ✅ Completado |
| 17  | ColabController        | ~75    | `Social/`      | ✅ Completado |
| 18  | ModeracionController   | ~70    | `Moderacion/`  | ✅ Completado |
| 19  | PublicacionController  | ~75    | `Publicacion/` | ✅ Completado |
| 20  | ReproductorController  | ~60    | `Audio/`       | ✅ Completado |
| 21  | ReporteController      | ~55    | `Moderacion/`  | ✅ Completado |
| 22  | OnboardingController   | ~55    | `Usuario/`     | ✅ Completado |
| 23  | PostEdicionController  | ~65    | `Publicacion/` | ✅ Completado |
| 24  | FormularioController   | ~55    | `Core/`        | ✅ Completado |
| 25  | IAController           | ~50    | `Contenido/`   | ✅ Completado |
| 26  | DescargaController     | ~50    | `Core/`        | ✅ Completado |
| 27  | ContadorController     | ~45    | `Core/`        | ✅ Completado |
| 28  | WaveformController     | ~45    | `Audio/`       | ✅ Completado |
| 29  | BusquedaController     | ~40    | `Core/`        | ✅ Completado |
| 30  | VistaController        | ~35    | `Core/`        | ✅ Completado |
| 31  | UtilController         | ~35    | `Core/`        | ✅ Completado |

> **Nota:** Todos los controladores fueron movidos a sus carpetas de dominio (2025-12-17). Los archivos originales en la raíz deben ser eliminados o convertidos en wrappers deprecated.

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

### Revisiones Completadas (Resumen)

> **46 servicios refactorizados el 2025-12-15**

#### Servicios Divididos (21 servicios - violaban SRP)

| Servicio Original        | Líneas Antes | Líneas Después | Reducción | Servicios Resultantes         |
| ------------------------ | ------------ | -------------- | --------- | ----------------------------- |
| PublicacionService       | 1160         | 230            | 80%       | 4 servicios en `Publicacion/` |
| AlgoritmoService         | 837          | 330            | 60%       | 4 servicios en `Feed/`        |
| ColeccionService         | 722          | 200            | 72%       | 4 servicios en `Coleccion/`   |
| AutoPostService          | 608          | 110            | 82%       | 5 servicios en `Contenido/`   |
| FeedService              | 539          | 230            | 57%       | 4 servicios en `Feed/`        |
| StreamService            | 517          | 180            | 65%       | 4 servicios en `Audio/`       |
| AudioProcessingService   | 510          | 120            | 76%       | 4 servicios en `Audio/`       |
| FinanzaService           | 494          | 115            | 77%       | 4 servicios en `Finanza/`     |
| HashService              | 476          | 120            | 75%       | 4 servicios en `Audio/`       |
| NotificacionService      | 471          | 100            | 79%       | 5 servicios en `Social/`      |
| ComentarioService        | 459          | 135            | 71%       | 4 servicios en `Social/`      |
| ColeccionDescargaService | 453          | 155            | 66%       | 4 servicios en `Coleccion/`   |
| ColabService             | 415          | 100            | 76%       | 3 servicios en `Social/`      |
| SyncService              | 408          | 100            | 75%       | 4 servicios en `Core/`        |
| ChatService              | 385          | 100            | 74%       | 4 servicios en `Social/`      |
| PostCreacionService      | 376          | 160            | 57%       | 3 servicios en `Publicacion/` |
| UsuarioService           | 366          | 170            | 54%       | 3 servicios en `Usuario/`     |
| FiltroService            | 362          | 110            | 70%       | 4 servicios en `Feed/`        |
| LikeService              | 357          | 100            | 72%       | 3 servicios en `Social/`      |
| AuthService              | 340          | 100            | 71%       | 4 servicios en `Usuario/`     |
| AudioProteccionService   | 330          | 100            | 70%       | 3 servicios en `Audio/`       |
| DescargaService          | 303          | 180            | 41%       | 3 servicios en `Core/`        |

#### Servicios Movidos sin Dividir (25 servicios - cumplían SRP)

| Servicio             | Líneas | Destino        |
| -------------------- | ------ | -------------- |
| NormalizacionService | ~320   | `Contenido/`   |
| AlbumService         | ~303   | `Coleccion/`   |
| InteresService       | ~295   | `Usuario/`     |
| CacheService         | ~290   | `Core/`        |
| PerfilService        | ~290   | `Usuario/`     |
| PostEdicionService   | ~285   | `Publicacion/` |
| IdeaService          | ~281   | `Contenido/`   |
| PostRenderService    | ~277   | `Publicacion/` |
| BusquedaService      | ~271   | `Core/`        |
| ModeracionService    | ~254   | `Moderacion/`  |
| ImagenService        | ~237   | `Contenido/`   |
| IAService            | ~224   | `Contenido/`   |
| SeguirService        | ~220   | `Usuario/`     |
| PostEstadoService    | ~197   | `Publicacion/` |
| ReproductorService   | ~173   | `Audio/`       |
| WaveformService      | ~170   | `Audio/`       |
| PostSlugService      | ~155   | `Publicacion/` |
| ReporteService       | ~151   | `Moderacion/`  |
| TagService           | ~134   | `Core/`        |
| VistaService         | ~134   | `Core/`        |
| UtilService          | ~126   | `Core/`        |
| PythonService        | ~83    | `Core/`        |
| ContadorService      | ~70    | `Core/`        |

> **Todos los servicios tienen wrappers deprecated en `src/Services/` que redirigen a las nuevas ubicaciones.**


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
