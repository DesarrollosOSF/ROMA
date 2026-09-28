# Guía de Ruta Flutter – Cliente Móvil para Sistema ROMA

## 1. Visión
Construir una app Flutter para iOS y Android que replique la lógica del sistema web **ROMA**, aproveche la API REST existente y respete el modelo de datos, roles y flujos de negocio ya implementados en PHP/MySQL.

## 2. Ruta General de Implementación
1. **Auditoría backend** (API, base de datos, autenticación).
2. **Diseño funcional y UI móvil** basado en módulos web actuales.
3. **Creación del proyecto Flutter** y configuración de entornos.
4. **Modelado de datos y contratos API** (entidades, DTO, mapeos).
5. **Implementación de infraestructura** (cliente HTTP, auth, DI, persistencia).
6. **Entrega incremental de módulos**: login → dashboard → activos → órdenes → notificaciones → perfil.
7. **QA, seguridad, publicación interna y documentación final.**

Cada etapa debe cerrarse con un entregable: checklist de API disponible, wireframes, repositorio inicial, modelos implementados, módulos funcionales, plan de pruebas.

## 3. Preparación del Backend
- Revisar `database/instalacion.sql` para entender tablas clave (usuarios, activos, órdenes, historial, evidencias).
- Verificar endpoints disponibles en `api/` (autenticación, usuarios, dashboard, activos, órdenes). Documentar parámetros y respuestas reales usando Postman/Insomnia.
- Identificar brechas: si algún KPI del dashboard no tiene endpoint REST, planificar sprint backend para exponerse en JSON.
- Confirmar políticas de autenticación: ya existe JWT (`HS256`) con expiración configurable (`API_TOKEN_EXPIRATION`) y secret (`API_TOKEN_SECRET`). Si se requiere refresh token, definirlo como extensión.
- Establecer ambientes: QA/Staging (con URL y credenciales) y Producción (`https://roma.osf.com.co/api/`).

### 3.1 Checklist de API habilitada
| Área | Endpoint | Método | Descripción | Permisos mínimos |
|------|----------|--------|-------------|------------------|
| Auth | `/api/auth/login` | POST | Obtiene token JWT y perfil con matriz de permisos | Público |
| Auth | `/api/auth/profile` | GET | Retorna usuario autenticado | Cualquier rol con token |
| Auth | `/api/auth/logout` | POST | Invalida sesión en cliente (stateless) | Cualquier rol |
| Auth | `/api/auth/ping` | GET | Verifica validez del token | Cualquier rol |
| Dashboard | `/api/dashboard` | GET | KPI, colecciones y métricas (filtradas por rol) | Permisos `ver_activos` o `ver_ordenes` según data |
| Dashboard | `/api/dashboard?include=estadisticas,ordenes_recientes` | GET | Permite limitar payload | Idem |
| Activos | `/api/assets` | GET/POST | Listado (filtros/paginación) y creación con foto (multipart/base64) | `ver_activos` / `crear_activos` |
| Activos | `/api/assets/{id}` | GET/PUT/PATCH/DELETE | Detalle, actualización parcial y baja lógica | `ver_activos` / `editar_activos` / `eliminar_activos` |
| Activos | `/api/assets/{id}/history` | GET | Hoja de vida + órdenes asociadas (`?incluir_ordenes=1`) | `ver_activos` |
| Activos | `/api/assets/categories` | GET | Catálogo de categorías (`ASSET_CATEGORIES`) | `ver_activos` |
| Órdenes | `/api/orders` | GET/POST | Listado con filtros según rol y creación | `ver_ordenes` / `crear_ordenes` |
| Órdenes | `/api/orders/{id}` | GET | Detalle con historial completo | Permisos según rol |
| Órdenes | `/api/orders/{id}/history` | GET | Historial cronológico de cambios | Permisos según rol |
| Órdenes | `/api/orders/{id}/status` | POST | Cambio de estado (requiere comentario y evidencia si → `finalizado`) | `cambiar_estado_orden` |
| Órdenes | `/api/orders/{id}/reassign` | POST | Reasignar a otro operario | `reasignar_orden` |
| Órdenes | `/api/orders/{id}/comments` | POST | Agrega comentario y registra historial | `ver_ordenes` / `ver_mis_ordenes` |

> **Recomendado:** preparar colección de Postman/Insomnia exportable para el equipo móvil (QA, QA Prod, Production).

### 3.2 Ejemplos de consumo
**Login**
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "operario@roma.com",
  "password": "admin123"
}
```
Respuesta:
```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "data": {
    "token": "<jwt>",
    "expira_en": 3600,
    "usuario": {
      "id_usuario": 4,
      "nombre": "Operario ROMA",
      "email": "operario@roma.com",
      "rol": "operario",
      "permisos": {
        "ver_activos": true,
        "cambiar_estado_orden": true,
        "gestionar_usuarios": false
      }
    }
  }
}
```

**Dashboard filtrado**
```http
GET /api/dashboard?include=estadisticas,ordenes_recientes
Authorization: Bearer <jwt>
```

**Cambio de estado OT**
```http
POST /api/orders/12/status
Authorization: Bearer <jwt>
Content-Type: multipart/form-data

comentario=Trabajo ejecutado&
nuevo_estado=finalizado&
evidencia=@/path/foto.jpg
```

### 3.3 Gestión de errores
- 401: token ausente/expirado → app debe forzar re-login.
- 403: rol sin permiso → ocultar funcionalidad y mostrar mensaje contextual.
- 422: errores de validación (estructura `{ success: false, message, errors: {...} }`).
- 500: logear en Crashlytics y sugerir reintento/manual fallback.

### 3.4 Configuración de entornos
Variables sugeridas en `lib/core/config/env.dart` (usar `flutter_dotenv`):
```
API_BASE_URL=https://roma.osf.com.co/api
API_TIMEOUT=15000
SENTRY_DSN=<opcional>
FIREBASE_PROJECT_ID=roma-prod
```

Estructura de flavors:
- `dev`: apunta a servidor local (`https://10.0.2.2/Desarrollos/Roma/api`), debug logging habilitado.
- `qa`: staging público, tokens de prueba.
- `prod`: `https://roma.osf.com.co/api`, logging mínimo y Crashlytics habilitado.

## 4. Creación del Proyecto Flutter
1. Instalar Flutter 3.16+ y ejecutar `flutter doctor`.
2. Crear repo nuevo: `flutter create roma_app_flutter`.
3. Configurar `analysis_options.yaml` con lints recomendadas (`pedantic`/`lints`).
4. Organizar ramas (`main`, `develop`, `feature/*`). Integrar Git hooks (`flutter format`, `flutter analyze`).
5. Añadir carpetas `android/app/src/`, `ios/Runner/` con configuración de nombre de app, id de paquete (`com.osf.roma` sugerido).
6. Configurar flavors (`dev`, `qa`, `prod`) para URLs y claves de Firebase.

## 5. Arquitectura, Librerías y Estructura
- Patrón: Clean Architecture (capas `data`, `domain`, `presentation`).
- Gestión de estado: `flutter_bloc` (o `riverpod` si el equipo lo prefiere).
- Dependencias base:
  - `dio`, `pretty_dio_logger`, `retrofit` (opcional) para HTTP.
  - `json_serializable`, `build_runner` para auto generación de modelos.
  - `get_it` + `injectable` para DI.
  - `flutter_secure_storage`, `shared_preferences` para persistencia.
  - `image_picker`, `file_picker`, `dio` (multipart) para evidencias.
  - `firebase_messaging`, `firebase_crashlytics`, `firebase_analytics`.
  - `intl`, `flutter_localizations` para soporte multilenguaje.
- Estructura sugerida (mantener desde inicio):
```
lib/
 ├─ core/        # constantes, helpers, theme, env
 ├─ data/        # datasources, dtos, repositorios impl
 ├─ domain/      # entidades, repositorios abstractos, usecases
 ├─ presentation/# blocs/providers, pages, widgets, navigation
 └─ main.dart
```

## 6. Modelado de Datos y Contratos API
### 6.1 Tablas prioritarias (ver `instalacion.sql`)
- `usuarios`: id, nombre, email, rol, area, contraseña.
- `activos`: id_activo, nombre_activo, codigo_interno, categoria, estado_actual, proximo_mantenimiento, ubicacion, responsable, foto.
- `ordenes_trabajo`: id_orden, codigo, id_activo, id_solicitante, id_usuario_asignado, estado, criticidad, fecha_creacion, fecha_cierre, comentario.
- `ordenes_historial`: id_historial, id_orden, estado_nuevo, comentario, evidencia_foto, fecha.
- `ordenes_checklist`, `ordenes_archivos`, `notificaciones`, `repuestos` (según módulos a cubrir).

### 6.2 Entidades y DTOs en Flutter
- Crear entidades en `domain/entities` reflejando reglas de negocio (campos obligatorios, tipos).
- Crear DTOs (`data/models`) con `fromJson`/`toJson` alineados a respuestas API.
- Mantener un documento OpenAPI/Swagger (puede generarse manualmente basándose en la tabla de endpoints) para estandarizar contratos y facilitar mock servers.

Ejemplo básico:
```dart
// domain/entities/activo.dart
class Activo {
  final int id;
  final String nombre;
  final String codigoInterno;
  final String categoria;
  final String estado;
  final DateTime? proximoMantenimiento;
  final String ubicacion;
  final String responsable;
  final String? fotoUrl;

  const Activo({
    required this.id,
    required this.nombre,
    required this.codigoInterno,
    required this.categoria,
    required this.estado,
    this.proximoMantenimiento,
    required this.ubicacion,
    required this.responsable,
    this.fotoUrl,
  });
}

// data/models/activo_dto.dart
import 'package:json_annotation/json_annotation.dart';

part 'activo_dto.g.dart';

@JsonSerializable()
class ActivoDto {
  final int id_activo;
  final String nombre_activo;
  final String codigo_interno;
  final String categoria;
  final String estado_actual;
  final String? proximo_mantenimiento;
  final String ubicacion;
  final String responsabilidad_ubicacion;
  final String? foto;

  ActivoDto({
    required this.id_activo,
    required this.nombre_activo,
    required this.codigo_interno,
    required this.categoria,
    required this.estado_actual,
    this.proximo_mantenimiento,
    required this.ubicacion,
    required this.responsabilidad_ubicacion,
    this.foto,
  });

  factory ActivoDto.fromJson(Map<String, dynamic> json) =>
      _$ActivoDtoFromJson(json);

  Map<String, dynamic> toJson() => _$ActivoDtoToJson(this);
}
```

Crear `mapper` para convertir DTO ↔ entidad, ubicados en `data/mappers`.

### 6.3 Diagramas
- Elaborar ERD actualizado (MySQL Workbench) y compartir versión PDF.
- Definir diagrama de clases para entidades Flutter más relevantes.
- Registrar diagrama de secuencia para los flujos clave: login, actualización de orden con evidencia, lectura de dashboard.

## 7. Consumo de la Base de Datos vía API
1. **Capa datasource remota** (`data/datasources/remote`):
   - Interface `DashboardRemoteDataSource`, `ActivosRemoteDataSource`, etc.
   - Implementaciones usando `dio`. Manejar errores con excepciones propias (`ServerException`, `AuthException`).
2. **Repositorios** (`data/repositories_impl`):
   - Orquestan datasources y caché local.
3. **Casos de uso** (`domain/usecases`):
   - Encapsulan consultas y mutaciones (`GetDashboardStats`, `UpdateOrdenEstado`).
4. **Presentación**:
   - Los BLoC/Providers consumen casos de uso y exponen estados visuales (loading, success, error).
5. **Persistencia local**:
   - Guardar tokens en `SecureStorage`.
   - Cachear catálogos (categorías, ubicaciones) en Hive/SharedPreferences para modo offline básico.

### Flujo de autenticación sugerido
1. Usuario ingresa credenciales → `POST /api/auth/login`.
2. Backend responde con `accessToken`, datos de usuario y permisos.
3. Guardar token en `SecureStorage`, refresco automático previo a expiración.
4. Interceptor `dio` agrega `Authorization: Bearer TOKEN`.
5. Si backend retorna 401, limpiar sesión y redirigir a login.

### Subida de evidencias
- Utilizar `FormData` y `MultipartFile`:
```dart
final formData = FormData.fromMap({
  'comentario': comentario,
  'estado_nuevo': estado,
  'archivo': await MultipartFile.fromFile(
    file.path,
    filename: path.basename(file.path),
  ),
});
await dio.post('/api/orders/$id/evidence', data: formData);
```

## 8. Diseño de Pantallas y Flujos
- Crear wireframes móviles (Figma) basados en vistas web pero adaptadas a navegaciones verticales.
- Definir navegación principal con `StatefulShellRoute` (GoRouter) o `AutoRoute`, pestañas: Dashboard, Activos, Órdenes, Perfil.
- Mantener accesible sección “Insights Clave” en dashboard con acciones directas a listados.
- Implementar tabla/greed cards para activos con `PaginatedDataTable` o `Slivers`.
- Vista de órdenes: timeline con historial, botón para cambiar estado y adjuntar evidencia.
- Cronograma: planificar vista calendario (sincrónico con web) para fase posterior.

## 9. Reglas de Negocio a Respetar
- Roles (`administrador`, `jefe`, `director`, `operario`) determinan permisos y filtros de datos. Aplicar misma lógica ya codificada en PHP (`DashboardController::aplicarFiltroOrdenes`).
- Campo solicitante en OT es de sólo lectura, estado inicial siempre `Recibido`.
- Cambiar a `Finalizado` obliga a subir fotografía. Validar desde UI y backend.
- Perfil: email, nombre y área no editables (sólo lectura).
- Ubicaciones y categorías deben usar catálogos definidos en `config/config.php`.

## 10. Seguridad y Cumplimiento
- Todas las llamadas a producción deben usar HTTPS (`https://roma.osf.com.co`). Verificar certificados intermedios en Android/iOS.
- Sanitizar datos antes de enviar (evitar XSS/SQLi). Respetar validaciones backend.
- Implementar almacenamiento cifrado (tokens, refresh token).
- Considerar auditoría de eventos críticos (log local + envío a backend si endpoint disponible).

## 11. Automatización y Calidad
- Configurar `flutter_test`, `mocktail` para mocks, `bloc_test` si se usa BLoC.
- Pruebas automatizadas mínimas por módulo (login, dashboard, activos, órdenes).
- Pipeline CI/CD (GitHub Actions/Codemagic):
  - `flutter format --set-exit-if-changed .`
  - `flutter analyze`
  - `flutter test`
  - Generar APK/AAB y IPA para QA.
- Integrar `firebase_crashlytics` y `firebase_performance` para monitoreo.

## 12. Roadmap Detallado por Fases
| Fase | Duración | Objetivos |
|------|----------|-----------|
| 0. Descubrimiento | 1 semana | Auditoría API/BD, acuerdos técnicos, backlog inicial |
| 1. Setup | 1 semana | Crear repo, configurar CI/CD, DI, navegación, theming |
| 2. Autenticación | 1 semana | Login, manejo de tokens, guardado seguro, pruebas unitarias |
| 3. Dashboard | 2 semanas | Estadísticas, insights, gráficas, filtros por rol |
| 4. Activos | 2 semanas | Listado, detalle, historial, subida de fotos |
| 5. Órdenes | 3 semanas | Listado por rol, detalle, cambios de estado, evidencias |
| 6. Complementos | 2 semanas | Notificaciones push, perfil, cronograma (parcial), mejoras UX |
| 7. QA & Release | 1 semana | Pruebas funcionales, pruebas con usuarios reales, hardening |

## 13. Documentación y Herramientas
- Manual técnico del proyecto Flutter (instalación, comandos, arquitectura).
- Colección Postman/Insomnia actualizada (QA/PROD) y scripts de autenticación.
- Diagrama de navegación y flujos críticos (login, cambio de estado, carga foto).
- Registro de endpoints extendidos respecto a la web.
- Guía para publicación interna (Firebase App Distribution / TestFlight).

## 14. Próximos Pasos Concretos
1. Validar endpoints existentes y acuerdos de autenticación con backend PHP.
2. Definir stack de estado (BLoC o Riverpod) y bloquear dependencias en `pubspec.yaml`.
3. Diseñar wireframes móviles y obtener aprobación funcional.
4. Generar entidades y DTOs base (`Usuario`, `Activo`, `OrdenTrabajo`, `HistorialOrden`).
5. Implementar módulo de autenticación y primer llamado al endpoint real.
6. Iterar siguiendo roadmap y monitorear métricas clave (crash free, tiempo de carga).

---

**Responsables sugeridos:**  
- Líder Técnico Flutter  
- Arquitecto Backend / Integraciones  
- QA Engineer  
- Product Owner  

**Referencias:** Sistema web ROMA, base de datos `instalacion.sql`, controladores PHP (`DashboardController`, `OrdenesController`, `ActivosController`). 
