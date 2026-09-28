<?php
/**
 * Controlador del Dashboard
 * Sistema ROMA - Página Principal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Activo.php';
require_once __DIR__ . '/../models/OrdenTrabajo.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class DashboardController {
    private $activo;
    private $orden;

    public function __construct() {
        // Verificar autenticación
        AuthController::verificarAutenticacion();
        
        $this->activo = new Activo();
        $this->orden = new OrdenTrabajo();
    }

    /**
     * Mostrar dashboard principal
     */
    public function index() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        $usuario_id = $_SESSION['usuario_id'] ?? null;

        $puede_ver_activos = Usuario::tienePermiso($rol, 'ver_activos') || Usuario::tienePermiso($rol, 'ver_lista_activos');
        $puede_ver_ordenes = Usuario::tienePermiso($rol, 'ver_ordenes') || Usuario::tienePermiso($rol, 'ver_mis_ordenes');
        $es_admin = Usuario::esAdminGeneral($rol);

        // Obtener información filtrada por rol
        $estadisticas = $this->obtenerEstadisticas($rol, $usuario_id, $puede_ver_activos, $puede_ver_ordenes);

        $activos_recientes = $puede_ver_activos ? $this->obtenerActivosRecientes() : [];
        $mantenimientos_proximos = $puede_ver_activos ? $this->obtenerMantenimientosProximos() : [];
        $activos_por_categoria = $puede_ver_activos ? $this->obtenerActivosPorCategoria() : [];
        $activos_por_estado = $puede_ver_activos ? $this->obtenerActivosPorEstado() : [];
        $activos_sin_plan_mantenimiento = ($puede_ver_activos && $es_admin) ? $this->obtenerActivosSinPlanMantenimiento() : [];
        $criticidad_por_activo = ($puede_ver_activos && $es_admin) ? $this->obtenerCriticidadPorActivo() : [];

        $ordenes_recientes = $puede_ver_ordenes ? $this->obtenerOrdenesRecientes($rol, $usuario_id) : [];
        $ordenes_por_estado = $puede_ver_ordenes ? $this->obtenerOrdenesPorEstado($rol, $usuario_id) : [];
        $ordenes_criticas = ($puede_ver_ordenes && $es_admin) ? $this->obtenerOrdenesCriticas($rol, $usuario_id) : [];
        $rendimiento_operarios = ($puede_ver_ordenes && $es_admin) ? $this->obtenerRendimientoOperarios($rol, $usuario_id) : [];
        $tiempos_cierre = ($puede_ver_ordenes && $es_admin) ? $this->obtenerTiemposCierre($rol, $usuario_id) : [];

        require_once __DIR__ . '/../views/dashboard/index.php';
    }

    /**
     * Obtener estadísticas generales
     */
    private function obtenerEstadisticas($rol, $usuario_id, $puede_ver_activos, $puede_ver_ordenes) {
        $database = new Database();
        $conn = $database->getConnection();

        $stats = [
            'total_activos' => 0,
            'activos_operativos' => 0,
            'activos_reparacion' => 0,
            'mantenimientos_proximos' => 0,
            'total_mantenimientos' => 0,
            'activos_sin_plan' => 0,
            'activos_vencidos' => 0,
            'total_ordenes' => 0,
            'ordenes_pendientes' => 0,
            'ordenes_finalizadas' => 0,
            'ordenes_urgentes' => 0,
            'ordenes_criticas' => 0,
            'cierre_promedio' => null
        ];

        if ($puede_ver_activos) {
            // Total de activos
            $query = "SELECT COUNT(*) as total FROM activos WHERE activo = 1";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['total_activos'] = $stmt->fetch()['total'];

            // Activos operativos
            $query = "SELECT COUNT(*) as total FROM activos WHERE activo = 1 AND estado_actual = 'operativo'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['activos_operativos'] = $stmt->fetch()['total'];

            // Activos en reparación
            $query = "SELECT COUNT(*) as total FROM activos WHERE activo = 1 AND estado_actual = 'en_reparacion'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['activos_reparacion'] = $stmt->fetch()['total'];

            // Mantenimientos próximos (próximos 30 días)
            $query = "SELECT COUNT(*) as total FROM activos 
                      WHERE activo = 1 
                      AND proximo_mantenimiento IS NOT NULL 
                      AND proximo_mantenimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['mantenimientos_proximos'] = $stmt->fetch()['total'];

            // Total de mantenimientos registrados
            $query = "SELECT COUNT(*) as total FROM activos_hoja_vida";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['total_mantenimientos'] = $stmt->fetch()['total'];

            // Activos sin próximo mantenimiento definido
            $query = "SELECT COUNT(*) as total FROM activos 
                      WHERE activo = 1 AND proximo_mantenimiento IS NULL";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['activos_sin_plan'] = $stmt->fetch()['total'];

            // Activos con mantenimiento vencido
            $query = "SELECT COUNT(*) as total FROM activos 
                      WHERE activo = 1 
                      AND proximo_mantenimiento IS NOT NULL 
                      AND proximo_mantenimiento < CURDATE()";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['activos_vencidos'] = $stmt->fetch()['total'];
        }

        if ($puede_ver_ordenes) {
            // Total de órdenes de trabajo
            $params = [];
            $query = "SELECT COUNT(*) as total FROM ordenes_trabajo ot WHERE ot.activo = 1";
            $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $stats['total_ordenes'] = $stmt->fetch()['total'];

            // Órdenes pendientes
            $params = [];
            $query = "SELECT COUNT(*) as total FROM ordenes_trabajo ot 
                      WHERE ot.activo = 1 AND ot.estado_proceso IN ('recibido', 'en_proceso')";
            $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $stats['ordenes_pendientes'] = $stmt->fetch()['total'];

            // Órdenes finalizadas
            $params = [];
            $query = "SELECT COUNT(*) as total FROM ordenes_trabajo ot 
                      WHERE ot.activo = 1 AND ot.estado_proceso = 'finalizado'";
            $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $stats['ordenes_finalizadas'] = $stmt->fetch()['total'];

            // Órdenes urgentes (vencidas o próximas a vencer)
            $params = [];
            $query = "SELECT COUNT(*) as total FROM ordenes_trabajo ot 
                      WHERE ot.activo = 1 
                      AND ot.estado_proceso IN ('recibido', 'en_proceso')
                      AND ot.fecha_limite_ejecucion IS NOT NULL
                      AND ot.fecha_limite_ejecucion <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
            $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $stats['ordenes_urgentes'] = $stmt->fetch()['total'];

            // Órdenes críticas abiertas
            $params = [];
            $query = "SELECT COUNT(*) as total FROM ordenes_trabajo ot 
                      WHERE ot.activo = 1 
                      AND ot.estado_proceso IN ('recibido', 'en_proceso')
                      AND ot.nivel_criticidad IN ('critica', 'alta')";
            $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $stats['ordenes_criticas'] = $stmt->fetch()['total'];

            // Tiempo promedio de cierre últimos 30 días
            $params = [];
            $query = "SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_actualizacion)) as horas_promedio
                      FROM ordenes_trabajo ot
                      WHERE ot.activo = 1 
                      AND ot.estado_proceso = 'finalizado'
                      AND ot.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $stats['cierre_promedio'] = $stmt->fetch()['horas_promedio'];
        }

        return $stats;
    }

    /**
     * Obtener órdenes recientes (sin duplicados)
     */
    private function obtenerOrdenesRecientes($rol, $usuario_id, $limite = 5) {
        $database = new Database();
        $conn = $database->getConnection();

        // Consulta directa para evitar posibles duplicados de JOINs
        $query = "SELECT DISTINCT ot.id_orden, ot.numero_radicado, ot.estado_proceso, 
                         ot.nivel_criticidad, ot.fecha_limite_ejecucion, ot.fecha_creacion,
                         a.nombre_activo, a.codigo_interno
                  FROM ordenes_trabajo ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  WHERE ot.activo = 1";

        $params = [];
        $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');

        $query .= " ORDER BY ot.fecha_creacion DESC
                  LIMIT :limite";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtener órdenes por estado
     */
    private function obtenerOrdenesPorEstado($rol, $usuario_id) {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT estado_proceso, COUNT(*) as cantidad 
                  FROM ordenes_trabajo 
                  WHERE activo = 1";

        $params = [];
        $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params);

        $query .= " GROUP BY estado_proceso 
                  ORDER BY cantidad DESC";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Aplica filtros según rol para consultas de órdenes
     */
    private function aplicarFiltroOrdenes($query, $rol, $usuario_id, &$params, $alias = '') {
        if (!$usuario_id) {
            return $query;
        }

        $colPrefix = $alias ? $alias . '.' : '';

        if ($rol === 'operario') {
            $query .= " AND {$colPrefix}id_usuario_asignado = :usuario_id";
            $params[':usuario_id'] = $usuario_id;
        } elseif (in_array($rol, ['jefe', 'director'])) {
            $query .= " AND {$colPrefix}id_solicitante = :usuario_id";
            $params[':usuario_id'] = $usuario_id;
        }

        return $query;
    }

    /**
     * Obtener activos recientes (excluyendo los que tienen mantenimientos próximos)
     */
    private function obtenerActivosRecientes($limite = 6) {
        $database = new Database();
        $conn = $database->getConnection();

        // Obtener IDs de activos con mantenimientos próximos para excluirlos
        $query_excluir = "SELECT DISTINCT id_activo 
                         FROM activos 
                         WHERE activo = 1 
                         AND proximo_mantenimiento IS NOT NULL 
                         AND proximo_mantenimiento >= CURDATE()
                         AND proximo_mantenimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        
        $stmt_excluir = $conn->prepare($query_excluir);
        $stmt_excluir->execute();
        $ids_excluir = $stmt_excluir->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener activos recientes excluyendo los que tienen mantenimientos próximos
        $filtros = ['limit' => $limite + count($ids_excluir)]; // Obtener más para compensar exclusiones
        $activos = $this->activo->listar($filtros);
        
        // Filtrar activos que no están en la lista de exclusión
        if (!empty($ids_excluir)) {
            $activos = array_filter($activos, function($activo) use ($ids_excluir) {
                return !in_array($activo['id_activo'], $ids_excluir);
            });
        }
        
        // Limitar y reindexar
        return array_slice(array_values($activos), 0, $limite);
    }

    /**
     * Obtener mantenimientos próximos (solo los próximos 30 días)
     */
    private function obtenerMantenimientosProximos($limite = 5) {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT id_activo, nombre_activo, codigo_interno, proximo_mantenimiento, estado_actual
                  FROM activos 
                  WHERE activo = 1 
                  AND proximo_mantenimiento IS NOT NULL 
                  AND proximo_mantenimiento >= CURDATE()
                  AND proximo_mantenimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  ORDER BY proximo_mantenimiento ASC
                  LIMIT :limite";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtener activos por categoría
     */
    private function obtenerActivosPorCategoria() {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT categoria, COUNT(*) as cantidad 
                  FROM activos 
                  WHERE activo = 1 
                  GROUP BY categoria 
                  ORDER BY cantidad DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtener activos por estado
     */
    private function obtenerActivosPorEstado() {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT estado_actual, COUNT(*) as cantidad 
                  FROM activos 
                  WHERE activo = 1 
                  GROUP BY estado_actual 
                  ORDER BY cantidad DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function obtenerActivosSinPlanMantenimiento($limite = 5) {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT id_activo, nombre_activo, codigo_interno, categoria, estado_actual, proximo_mantenimiento, fecha_creacion
                  FROM activos
                  WHERE activo = 1
                  AND (proximo_mantenimiento IS NULL OR proximo_mantenimiento < CURDATE())
                  ORDER BY proximo_mantenimiento IS NULL DESC, proximo_mantenimiento ASC
                  LIMIT :limite";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function obtenerCriticidadPorActivo() {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT a.id_activo, a.nombre_activo, a.codigo_interno,
                         SUM(CASE WHEN ot.nivel_criticidad = 'critica' THEN 1 ELSE 0 END) as criticas,
                         SUM(CASE WHEN ot.nivel_criticidad = 'alta' THEN 1 ELSE 0 END) as altas,
                         SUM(CASE WHEN ot.estado_proceso <> 'finalizado' THEN 1 ELSE 0 END) as abiertas
                  FROM activos a
                  LEFT JOIN ordenes_trabajo ot ON ot.id_activo = a.id_activo AND ot.activo = 1
                  WHERE a.activo = 1
                  GROUP BY a.id_activo
                  HAVING criticas > 0 OR altas > 0
                  ORDER BY criticas DESC, altas DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function obtenerOrdenesCriticas($rol, $usuario_id, $limite = 6) {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT DISTINCT ot.id_orden, ot.numero_radicado, ot.estado_proceso,
                         ot.nivel_criticidad, ot.fecha_limite_ejecucion, ot.fecha_creacion,
                         a.nombre_activo, a.codigo_interno
                  FROM ordenes_trabajo ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  WHERE ot.activo = 1
                  AND ot.estado_proceso IN ('recibido', 'en_proceso')
                  AND ot.nivel_criticidad IN ('critica', 'alta')";

        $params = [];
        $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');

        $query .= " ORDER BY FIELD(ot.nivel_criticidad, 'critica', 'alta'), ot.fecha_limite_ejecucion ASC
                    LIMIT :limite";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function obtenerRendimientoOperarios($rol, $usuario_id, $limite = 5) {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT u.id_usuario, u.nombre,
                         SUM(CASE WHEN ot.estado_proceso = 'finalizado' THEN 1 ELSE 0 END) as finalizadas,
                         SUM(CASE WHEN ot.estado_proceso IN ('recibido','en_proceso') THEN 1 ELSE 0 END) as abiertas,
                         AVG(CASE WHEN ot.estado_proceso = 'finalizado'
                                  THEN TIMESTAMPDIFF(HOUR, ot.fecha_creacion, ot.fecha_actualizacion)
                                  ELSE NULL END) as horas_promedio
                  FROM usuarios u
                  LEFT JOIN ordenes_trabajo ot ON ot.id_usuario_asignado = u.id_usuario AND ot.activo = 1
                  WHERE u.rol = 'operario' AND u.activo = 1
                  GROUP BY u.id_usuario
                  ORDER BY finalizadas DESC, abiertas ASC
                  LIMIT :limite";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function obtenerTiemposCierre($rol, $usuario_id) {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT DATE(ot.fecha_actualizacion) as fecha,
                         AVG(TIMESTAMPDIFF(HOUR, ot.fecha_creacion, ot.fecha_actualizacion)) as horas_promedio
                  FROM ordenes_trabajo ot
                  WHERE ot.activo = 1
                  AND ot.estado_proceso = 'finalizado'
                  AND ot.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 14 DAY)";

        $params = [];
        $query = $this->aplicarFiltroOrdenes($query, $rol, $usuario_id, $params, 'ot');

        $query .= " GROUP BY DATE(ot.fecha_actualizacion)
                    ORDER BY DATE(ot.fecha_actualizacion) ASC";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

