# Sistema ROMA
## Registro y Operación de Mantenimiento Automatizado

Sistema web totalmente responsive para la gestión inteligente del mantenimiento de activos empresariales. Diseñado para centralizar información, digitalizar procesos y optimizar la gestión de órdenes de trabajo, inventarios y flujos.

## 🚀 Características Principales

✅ **Sistema de Autenticación Completo**
- Login seguro con hash de contraseñas (BCRYPT)
- Control de sesiones
- Protección de rutas según roles

✅ **Dashboard Interactivo**
- Estadísticas en tiempo real
- Gráficos y visualizaciones
- Resumen de actividades

✅ **Gestión de Activos**
- Registro completo de activos con 6 categorías
- Hoja de vida digital con historial completo
- Carga masiva CSV
- Reportes PDF

✅ **Sistema de Roles y Permisos**
- **Administrador**: Acceso total al sistema
- **Jefe/Director**: Crear solicitudes, ver órdenes
- **Operario**: Ver y actualizar órdenes asignadas

✅ **Base de Datos Completa**
- Órdenes de Trabajo
- Solicitudes de Mantenimiento
- Inventario de Repuestos
- Planes de Mantenimiento Preventivo
- Sistema de Notificaciones
- Flujos Automáticos

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx con mod_rewrite
- Navegador moderno (Chrome, Firefox, Edge)

## 🔧 Instalación

### 1. Configurar Base de Datos

1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Importar el archivo `database/instalacion.sql`
   - Seleccionar "Importar"
   - Elegir el archivo `database/instalacion.sql`
   - Ejecutar

**O desde línea de comandos:**
```bash
mysql -u root -p < database/instalacion.sql
```

### 2. Configurar Conexión

Editar `config/database.php` si es necesario:
```php
private $host = 'localhost';
private $db_name = 'roma_db';
private $username = 'root';
private $password = '';
```

### 3. Configurar Rutas

Si el proyecto no está en `/Desarrollos/Roma/`, editar `config/config.php`:
```php
define('BASE_URL', '/tu/ruta/aqui/');
```

### 4. Acceder al Sistema

1. Iniciar Apache y MySQL desde XAMPP
2. Abrir navegador: `http://localhost/Desarrollos/Roma/`
3. Iniciar sesión con las credenciales por defecto

## 👤 Credenciales por Defecto

Todos los usuarios tienen la contraseña: **admin123**

| Rol | Email | Descripción |
|-----|-------|-------------|
| Administrador | `admin@roma.com` | Acceso total al sistema |
| Jefe | `jefe@roma.com` | Puede crear solicitudes |
| Director | `director@roma.com` | Puede crear solicitudes |
| Operario | `operario@roma.com` | Ver y actualizar órdenes asignadas |
| Operario 2 | `operario2@roma.com` | Ver y actualizar órdenes asignadas |

> ⚠️ **IMPORTANTE:** Cambiar todas las contraseñas en producción.

## 📁 Estructura del Proyecto

```
Roma/
├── api/                    # API RESTful para Flutter
├── assets/                 # CSS, JS, imágenes, plantillas
├── config/                 # Configuración
├── controllers/            # Controladores MVC
├── database/               # Scripts SQL
│   └── instalacion.sql     # Instalación completa
├── models/                 # Modelos de datos
├── views/                  # Vistas HTML/PHP
├── uploads/                # Archivos subidos
└── logs/                   # Logs del sistema
```

## 🔐 Sistema de Permisos

### Administrador
- ✅ Acceso total al sistema
- ✅ Gestionar usuarios
- ✅ Crear, asignar y modificar órdenes
- ✅ Configurar sistema

### Jefe / Director
- ✅ Ver activos
- ✅ Crear solicitudes de mantenimiento
- ✅ Ver estado de sus solicitudes
- ✅ Ver órdenes (no puede modificarlas)

### Operario
- ✅ Ver activos
- ✅ Ver órdenes asignadas
- ✅ Actualizar estado de órdenes
- ✅ Subir evidencias y comentarios

## 📊 Módulos del Sistema

1. **Dashboard** - Página principal con estadísticas
2. **Gestión de Activos** - Registro y control de activos
3. **Órdenes de Trabajo** - Gestión de órdenes (Base de datos lista)
4. **Solicitudes** - Solicitudes de mantenimiento (Base de datos lista)
5. **Repuestos** - Inventario de repuestos (Base de datos lista)
6. **Mantenimiento Preventivo** - Planes preventivos (Base de datos lista)
7. **Reportes** - Analytics e informes (Base de datos lista)

## 🔌 API RESTful

Endpoints disponibles para integración con Flutter:

- `GET /api/assets` - Listar activos
- `GET /api/assets/{id}` - Obtener activo
- `POST /api/assets` - Crear activo
- `PUT /api/assets/{id}` - Actualizar activo
- `GET /api/assets/{id}/history` - Historial
- `GET /api/assets/categories` - Categorías

## 🛠️ Tecnologías

- **Backend:** PHP 7.4+ (POO, PDO)
- **Base de Datos:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Arquitectura:** MVC (Modelo-Vista-Controlador)
- **Estilos:** CSS Responsive con Flexbox/Grid
- **Iconos:** Font Awesome 6.4.0

## 📝 Notas

- El sistema está preparado para escalar con módulos adicionales
- La API está lista para integración con Flutter v2
- El diseño es completamente responsive
- Se implementó auditoría de cambios para trazabilidad
- Soft delete implementado para preservar datos históricos

## 📞 Soporte

Para problemas o consultas:
1. Revisar logs en `logs/error.log`
2. Verificar consola del navegador (F12)
3. Verificar configuración de base de datos

---

**Versión:** 1.0.0  
**Estado:** ✅ Sistema Base Completo - Listo para Desarrollo de Módulos
