<?php
/**
 * Controlador de Configuración
 * Sistema ROMA - Configuración del Sistema
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/AuthController.php';

class ConfiguracionController {
    
    public function __construct() {
        // Verificar autenticación y permisos de administrador
        AuthController::verificarAutenticacion();
        AuthController::verificarPermiso('configurar_sistema');
    }

    /**
     * Mostrar página principal de configuración
     */
    public function index() {
        $configuraciones = [
            'categorias' => ASSET_CATEGORIES,
            'estados' => ASSET_STATES,
            'tipos_mantenimiento' => MAINTENANCE_TYPES,
            'estados_ordenes' => ORDER_STATES,
            'estados_solicitudes' => REQUEST_STATES,
            'niveles_criticidad' => CRITICITY_LEVELS,
            'roles' => USER_ROLES,
            'areas' => ASSIGNED_AREAS
        ];

        require_once __DIR__ . '/../views/configuracion/index.php';
    }
}

