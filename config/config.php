<?php
/**
 * Configuración General del Sistema ROMA
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de rutas dinámicas
$basePath = realpath(__DIR__ . '/../');
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
$baseUri = '/';

if ($documentRoot && $basePath) {
    $normalizedDocRoot = str_replace('\\', '/', rtrim($documentRoot, '/'));
    $normalizedBasePath = str_replace('\\', '/', $basePath);

    if (stripos(strtolower($normalizedBasePath), strtolower($normalizedDocRoot)) === 0) {
        $relativePath = trim(substr($normalizedBasePath, strlen($normalizedDocRoot)), '/');
        $baseUri = '/' . ($relativePath ? $relativePath . '/' : '');
    }
}

define('BASE_URL', $baseUri);
define('BASE_PATH', $basePath . '/');

// Configuración de autenticación para API
define('API_TOKEN_SECRET', getenv('ROMA_API_TOKEN_SECRET') ?: 'roma_api_secret_change_me');
define('API_TOKEN_EXPIRATION', (int)(getenv('ROMA_API_TOKEN_EXPIRATION') ?: 3600)); // 1 hora
define('API_ALLOWED_ORIGINS', getenv('ROMA_API_ALLOWED_ORIGINS') ?: '*');

// Configuración de uploads
define('UPLOAD_DIR', BASE_PATH . 'uploads/');
define('UPLOAD_MAX_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']);

// Categorías de activos
define('ASSET_CATEGORIES', [
    'equipo_especial' => 'Equipo Especial',
    'vehiculos' => 'Vehículos',
    'maquinas' => 'Máquinas',
    'muebles_enseres' => 'Muebles y Enseres',
    'horno_crematorio' => 'Horno Crematorio',
    'planta_agua_residual' => 'Planta de Agua Residual',
    'cofres_cremacion' => 'Cofres de Cremación'
]);

// Estados de activos
define('ASSET_STATES', [
    'operativo' => 'Operativo',
    'en_reparacion' => 'En reparación',
    'fuera_servicio' => 'Fuera de servicio',
    'en_baja' => 'En baja'
]);

// Tipos de mantenimiento
define('MAINTENANCE_TYPES', [
    'instalacion' => 'Instalación',
    'correctivo' => 'Correctivo',
    'preventivo' => 'Preventivo',
    'documentos_tramites' => 'Documentos y Trámites'
]);

// Estados de órdenes de trabajo
define('ORDER_STATES', [
    'recibido' => 'Recibido',
    'en_proceso' => 'En Proceso',
    'rechazado' => 'Rechazado',
    'finalizado' => 'Finalizado'
]);

// Estados de solicitudes
define('REQUEST_STATES', [
    'pendiente' => 'Pendiente',
    'asignada' => 'Asignada',
    'rechazada' => 'Rechazada',
    'finalizada' => 'Finalizada'
]);

// Niveles de criticidad
define('CRITICITY_LEVELS', [
    'critica' => 'Crítica',
    'alta' => 'Alta',
    'normal' => 'Normal',
    'baja' => 'Baja'
]);

// Roles de usuario
define('USER_ROLES', [
    'administrador' => 'Administrador',
    'superadmin' => 'Super Administrador',
    'jefe' => 'Jefe',
    'director' => 'Director',
    'operario' => 'Operario'
]);

// Áreas asignadas
define('ASSIGNED_AREAS', [
    'gerencia' => 'Gerencia',
    'talento_humano' => 'Talento Humano',
    'operaciones' => 'Operaciones',
    'financiera' => 'Financiera',
    'mercadeo' => 'Mercadeo',
    'servicios' => 'Servicios',
    'comercial_parque' => 'Comercial Parque',
    'comercial_prevision' => 'Comercial Previsión',
    'sistemas' => 'Sistemas',
    'bi' => 'BI',
    'tesoreria' => 'Tesorería',
    'sig' => 'Sig',
    'contabilidad' => 'Contabilidad',
    'recaudo_cartera' => 'Recaudo y Cartera'
]);

// Ubicaciones predefinidas para activos
define('ASSET_LOCATIONS', [
    'Sede principal Tunja',
    'Sede San Rafael Tunja',
    'Sede Santa Sofía',
    'Sede Villa de Leyva',
    'Sede Chiquinquirá',
    'Sede Moniquirá',
    'Sede Barbosa',
    'Sede Sogamoso',
    'Sede Duitama',
    'Sede Soatá',
    'PMJSI',
    'Centro de Operaciones'
]);

// Headers CORS para API (solo se aplican en archivos de API)
// Estos headers se configuran directamente en los archivos de API

