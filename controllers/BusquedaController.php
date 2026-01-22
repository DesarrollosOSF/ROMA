<?php
/**
 * Controlador de Búsqueda Global
 * Sistema ROMA - Búsqueda Rápida
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Activo.php';
require_once __DIR__ . '/../models/OrdenTrabajo.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class BusquedaController {
    private $activo;
    private $orden;
    private $usuario;

    public function __construct() {
        AuthController::verificarAutenticacion();
        
        try {
            $this->activo = new Activo();
            $this->orden = new OrdenTrabajo();
            $this->usuario = new Usuario();
        } catch (Exception $e) {
            error_log("Error en BusquedaController::__construct(): " . $e->getMessage());
            // Continuar con valores null, se manejará en buscar()
        }
    }

    /**
     * Búsqueda global rápida
     */
    public function buscar() {
        // Configurar headers JSON
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = 5; // Límite por tipo
        
        if (empty($query) || strlen($query) < 2) {
            echo json_encode(['resultados' => [
                'activos' => [],
                'ordenes' => [],
                'usuarios' => []
            ]], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultados = [
            'activos' => [],
            'ordenes' => [],
            'usuarios' => []
        ];

        // Buscar activos (solo si tiene permiso)
        $rol_actual = $_SESSION['usuario_rol'] ?? '';
        if (Usuario::tienePermiso($rol_actual, 'ver_lista_activos') || $rol_actual === 'administrador') {
            try {
                if ($this->activo) {
                    $activos = $this->activo->listar(['busqueda' => $query]);
                    if (is_array($activos)) {
                        $resultados['activos'] = array_slice($activos, 0, $limit);
                    }
                }
            } catch (Exception $e) {
                error_log("Error en búsqueda de activos: " . $e->getMessage());
                $resultados['activos'] = [];
            }
        }

        // Buscar órdenes
        try {
            if ($this->orden) {
                $ordenes = $this->orden->listar(['busqueda' => $query]);
                if (is_array($ordenes)) {
                    $resultados['ordenes'] = array_slice($ordenes, 0, $limit);
                }
            }
        } catch (Exception $e) {
            error_log("Error en búsqueda de órdenes: " . $e->getMessage());
            $resultados['ordenes'] = [];
        }

        // Buscar usuarios (solo administradores)
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') {
            try {
                if ($this->usuario) {
                    $usuarios = $this->usuario->listar(['busqueda' => $query]);
                    if (is_array($usuarios)) {
                        $resultados['usuarios'] = array_slice($usuarios, 0, $limit);
                    }
                }
            } catch (Exception $e) {
                error_log("Error en búsqueda de usuarios: " . $e->getMessage());
                $resultados['usuarios'] = [];
            }
        }

        echo json_encode(['resultados' => $resultados], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

