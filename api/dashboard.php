<?php
/**
 * API del Dashboard
 * Devuelve métricas y colecciones necesarias para la app móvil.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ApiAuth.php';
require_once __DIR__ . '/services/DashboardService.php';

ApiAuth::allowCors();
ApiAuth::handlePreflight();

$segmentos = ApiAuth::getPathSegments();
$recurso = $segmentos[0] ?? 'dashboard';

if ($recurso !== 'dashboard') {
    ApiResponse::error('Endpoint no encontrado', 404);
}

try {
    $usuario = ApiAuth::requireUser();

    $include = [];
    if (!empty($_GET['include'])) {
        $include = array_filter(array_map('trim', explode(',', strtolower($_GET['include']))));
    }

    $dashboard = DashboardService::obtenerDashboard($usuario);

    if (!empty($include)) {
        $dashboard = array_filter(
            $dashboard,
            function ($key) use ($include) {
                return in_array(strtolower($key), $include, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    ApiResponse::success($dashboard, 'Dashboard generado correctamente');
} catch (Exception $e) {
    error_log('API Dashboard: ' . $e->getMessage());
    ApiResponse::error('Error interno del servidor', 500);
}

