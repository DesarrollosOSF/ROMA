<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Activo.php';
require_once __DIR__ . '/../../models/OrdenTrabajo.php';

class DashboardService
{
    public static function obtenerDashboard(array $usuario): array
    {
        $rol = $usuario['rol'] ?? '';
        $usuarioId = $usuario['id_usuario'] ?? null;

        $puedeVerActivos = Usuario::tienePermiso($rol, 'ver_activos') || Usuario::tienePermiso($rol, 'ver_lista_activos');
        $puedeVerOrdenes = Usuario::tienePermiso($rol, 'ver_ordenes') || Usuario::tienePermiso($rol, 'ver_mis_ordenes');
        $esAdmin = $rol === 'administrador';

        $activoModel = new Activo();

        return [
            'estadisticas' => self::obtenerEstadisticas($rol, $usuarioId, $puedeVerActivos, $puedeVerOrdenes),
            'activos_recientes' => $puedeVerActivos ? self::obtenerActivosRecientes($activoModel) : [],
            'mantenimientos_proximos' => $puedeVerActivos ? self::obtenerMantenimientosProximos() : [],
            'activos_por_categoria' => $puedeVerActivos ? self::obtenerActivosPorCategoria() : [],
            'activos_por_estado' => $puedeVerActivos ? self::obtenerActivosPorEstado() : [],
            'activos_sin_plan' => ($puedeVerActivos && $esAdmin) ? self::obtenerActivosSinPlanMantenimiento() : [],
            'activos_criticos' => ($puedeVerActivos && $esAdmin) ? self::obtenerCriticidadPorActivo() : [],
            'ordenes_recientes' => $puedeVerOrdenes ? self::obtenerOrdenesRecientes($rol, $usuarioId) : [],
            'ordenes_por_estado' => $puedeVerOrdenes ? self::obtenerOrdenesPorEstado($rol, $usuarioId) : [],
            'ordenes_criticas' => ($puedeVerOrdenes && $esAdmin) ? self::obtenerOrdenesCriticas($rol, $usuarioId) : [],
            'rendimiento_operarios' => ($puedeVerOrdenes && $esAdmin) ? self::obtenerRendimientoOperarios() : [],
            'tiempos_cierre' => ($puedeVerOrdenes && $esAdmin) ? self::obtenerTiemposCierre($rol, $usuarioId) : [],
        ];
    }

    private static function obtenerEstadisticas($rol, $usuarioId, $puedeVerActivos, $puedeVerOrdenes): array
    {
        $conn = self::getConnection();
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
            'cierre_promedio' => null,
        ];

        if ($puedeVerActivos) {
            $stats['total_activos'] = self::fetchScalar($conn, "SELECT COUNT(*) FROM activos WHERE activo = 1");
            $stats['activos_operativos'] = self::fetchScalar($conn, "SELECT COUNT(*) FROM activos WHERE activo = 1 AND estado_actual = 'operativo'");
            $stats['activos_reparacion'] = self::fetchScalar($conn, "SELECT COUNT(*) FROM activos WHERE activo = 1 AND estado_actual = 'en_reparacion'");
            $stats['mantenimientos_proximos'] = self::fetchScalar($conn, "SELECT COUNT(*) FROM activos WHERE activo = 1 AND proximo_mantenimiento IS NOT NULL AND proximo_mantenimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
            $stats['total_mantenimientos'] = self::fetchScalar($conn, "SELECT COUNT(*) FROM activos_hoja_vida");
            $stats['activos_sin_plan'] = self::fetchScalar($conn, "SELECT COUNT(*) FROM activos WHERE activo = 1 AND proximo_mantenimiento IS NULL");
            $stats['activos_vencidos'] = self::fetchScalar($conn, "SELECT COUNT(*) FROM activos WHERE activo = 1 AND proximo_mantenimiento IS NOT NULL AND proximo_mantenimiento < CURDATE()");
        }

        if ($puedeVerOrdenes) {
            $stats['total_ordenes'] = self::fetchScalarFiltered($conn, "SELECT COUNT(*) FROM ordenes_trabajo ot WHERE ot.activo = 1", $rol, $usuarioId, 'ot');
            $stats['ordenes_pendientes'] = self::fetchScalarFiltered($conn, "SELECT COUNT(*) FROM ordenes_trabajo ot WHERE ot.activo = 1 AND ot.estado_proceso IN ('recibido', 'en_proceso')", $rol, $usuarioId, 'ot');
            $stats['ordenes_finalizadas'] = self::fetchScalarFiltered($conn, "SELECT COUNT(*) FROM ordenes_trabajo ot WHERE ot.activo = 1 AND ot.estado_proceso = 'finalizado'", $rol, $usuarioId, 'ot');
            $stats['ordenes_urgentes'] = self::fetchScalarFiltered($conn, "SELECT COUNT(*) FROM ordenes_trabajo ot WHERE ot.activo = 1 AND ot.estado_proceso IN ('recibido', 'en_proceso') AND ot.fecha_limite_ejecucion IS NOT NULL AND ot.fecha_limite_ejecucion <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)", $rol, $usuarioId, 'ot');
            $stats['ordenes_criticas'] = self::fetchScalarFiltered($conn, "SELECT COUNT(*) FROM ordenes_trabajo ot WHERE ot.activo = 1 AND ot.estado_proceso IN ('recibido', 'en_proceso') AND ot.nivel_criticidad IN ('critica', 'alta')", $rol, $usuarioId, 'ot');

            $params = [];
            $query = "SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_actualizacion)) as horas_promedio
                      FROM ordenes_trabajo ot
                      WHERE ot.activo = 1 
                      AND ot.estado_proceso = 'finalizado'
                      AND ot.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $query = self::aplicarFiltroOrdenes($query, $rol, $usuarioId, $params, 'ot');
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->execute();
            $stats['cierre_promedio'] = $stmt->fetch()['horas_promedio'] ?? null;
        }

        return $stats;
    }

    private static function obtenerActivosRecientes(Activo $activoModel, $limite = 6): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT DISTINCT id_activo 
                                FROM activos 
                                WHERE activo = 1 
                                AND proximo_mantenimiento IS NOT NULL 
                                AND proximo_mantenimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute();
        $idsExcluir = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $filtros = ['limit' => $limite + count($idsExcluir)];
        $activos = $activoModel->listar($filtros);

        if (!empty($idsExcluir)) {
            $activos = array_filter($activos, function ($item) use ($idsExcluir) {
                return !in_array($item['id_activo'], $idsExcluir);
            });
        }

        return array_slice(array_values($activos), 0, $limite);
    }

    private static function obtenerMantenimientosProximos($limite = 5): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT id_activo, nombre_activo, codigo_interno, proximo_mantenimiento, estado_actual
                                FROM activos 
                                WHERE activo = 1 
                                AND proximo_mantenimiento IS NOT NULL 
                                AND proximo_mantenimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                ORDER BY proximo_mantenimiento ASC
                                LIMIT :limite");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerActivosPorCategoria(): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT categoria, COUNT(*) as cantidad 
                                FROM activos 
                                WHERE activo = 1 
                                GROUP BY categoria 
                                ORDER BY cantidad DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerActivosPorEstado(): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT estado_actual, COUNT(*) as cantidad 
                                FROM activos 
                                WHERE activo = 1 
                                GROUP BY estado_actual 
                                ORDER BY cantidad DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerActivosSinPlanMantenimiento($limite = 5): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT id_activo, nombre_activo, codigo_interno, categoria, estado_actual, proximo_mantenimiento, fecha_creacion
                                FROM activos
                                WHERE activo = 1
                                AND (proximo_mantenimiento IS NULL OR proximo_mantenimiento < CURDATE())
                                ORDER BY proximo_mantenimiento IS NULL DESC, proximo_mantenimiento ASC
                                LIMIT :limite");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerCriticidadPorActivo(): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT a.id_activo, a.nombre_activo, a.codigo_interno,
                         SUM(CASE WHEN ot.nivel_criticidad = 'critica' THEN 1 ELSE 0 END) as criticas,
                         SUM(CASE WHEN ot.nivel_criticidad = 'alta' THEN 1 ELSE 0 END) as altas,
                         SUM(CASE WHEN ot.estado_proceso <> 'finalizado' THEN 1 ELSE 0 END) as abiertas
                  FROM activos a
                  LEFT JOIN ordenes_trabajo ot ON ot.id_activo = a.id_activo AND ot.activo = 1
                  WHERE a.activo = 1
                  GROUP BY a.id_activo
                  HAVING criticas > 0 OR altas > 0
                  ORDER BY criticas DESC, altas DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerOrdenesRecientes($rol, $usuarioId, $limite = 5): array
    {
        $conn = self::getConnection();
        $params = [];
        $query = "SELECT DISTINCT ot.id_orden, ot.numero_radicado, ot.estado_proceso, 
                         ot.nivel_criticidad, ot.fecha_limite_ejecucion, ot.fecha_creacion,
                         a.nombre_activo, a.codigo_interno
                  FROM ordenes_trabajo ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  WHERE ot.activo = 1";
        $query = self::aplicarFiltroOrdenes($query, $rol, $usuarioId, $params, 'ot');
        $query .= " ORDER BY ot.fecha_creacion DESC LIMIT :limite";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerOrdenesPorEstado($rol, $usuarioId): array
    {
        $conn = self::getConnection();
        $params = [];
        $query = "SELECT estado_proceso, COUNT(*) as cantidad 
                  FROM ordenes_trabajo 
                  WHERE activo = 1";
        $query = self::aplicarFiltroOrdenes($query, $rol, $usuarioId, $params);
        $query .= " GROUP BY estado_proceso ORDER BY cantidad DESC";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerOrdenesCriticas($rol, $usuarioId, $limite = 6): array
    {
        $conn = self::getConnection();
        $params = [];
        $query = "SELECT DISTINCT ot.id_orden, ot.numero_radicado, ot.estado_proceso,
                         ot.nivel_criticidad, ot.fecha_limite_ejecucion, ot.fecha_creacion,
                         a.nombre_activo, a.codigo_interno
                  FROM ordenes_trabajo ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  WHERE ot.activo = 1
                  AND ot.estado_proceso IN ('recibido', 'en_proceso')
                  AND ot.nivel_criticidad IN ('critica', 'alta')";
        $query = self::aplicarFiltroOrdenes($query, $rol, $usuarioId, $params, 'ot');
        $query .= " ORDER BY FIELD(ot.nivel_criticidad, 'critica', 'alta'), ot.fecha_limite_ejecucion ASC LIMIT :limite";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerRendimientoOperarios($limite = 5): array
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT u.id_usuario, u.nombre,
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
                  LIMIT :limite");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function obtenerTiemposCierre($rol, $usuarioId): array
    {
        $conn = self::getConnection();
        $params = [];
        $query = "SELECT DATE(ot.fecha_actualizacion) as fecha,
                         AVG(TIMESTAMPDIFF(HOUR, ot.fecha_creacion, ot.fecha_actualizacion)) as horas_promedio
                  FROM ordenes_trabajo ot
                  WHERE ot.activo = 1
                  AND ot.estado_proceso = 'finalizado'
                  AND ot.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 14 DAY)";
        $query = self::aplicarFiltroOrdenes($query, $rol, $usuarioId, $params, 'ot');
        $query .= " GROUP BY DATE(ot.fecha_actualizacion)
                    ORDER BY DATE(ot.fecha_actualizacion) ASC";

        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function fetchScalar(PDO $conn, string $query): int
    {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return (int)($stmt->fetchColumn() ?? 0);
    }

    private static function fetchScalarFiltered(PDO $conn, string $query, $rol, $usuarioId, $alias = ''): int
    {
        $params = [];
        $query = self::aplicarFiltroOrdenes($query, $rol, $usuarioId, $params, $alias);
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)($stmt->fetchColumn() ?? 0);
    }

    private static function aplicarFiltroOrdenes(string $query, $rol, $usuarioId, array &$params, $alias = ''): string
    {
        if (!$usuarioId) {
            return $query;
        }

        $colPrefix = $alias ? $alias . '.' : '';

        if ($rol === 'operario') {
            $query .= " AND {$colPrefix}id_usuario_asignado = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        } elseif (in_array($rol, ['jefe', 'director'])) {
            $query .= " AND {$colPrefix}id_solicitante = :usuario_id";
            $params[':usuario_id'] = $usuarioId;
        }

        return $query;
    }

    private static function getConnection(): PDO
    {
        static $conn = null;
        if ($conn === null) {
            $database = new Database();
            $conn = $database->getConnection();
        }
        return $conn;
    }
}

