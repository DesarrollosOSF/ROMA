<?php
/**
 * Modelo de Orden de Trabajo
 * Sistema ROMA - Módulo de Órdenes de Trabajo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class OrdenTrabajo {
    private $conn;
    private $table = 'ordenes_trabajo';

    public $id_orden;
    public $numero_radicado;
    public $id_activo;
    public $id_solicitud;
    public $id_solicitante;
    public $id_usuario_asignado;
    public $tipo_mantenimiento;
    public $nivel_criticidad;
    public $descripcion_corta;
    public $descripcion_detallada;
    public $estado_proceso;
    public $fecha_limite_ejecucion;
    public $usuario_creacion;

    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            if (!$this->conn) {
                throw new Exception("No se pudo establecer conexión con la base de datos");
            }
        } catch (Exception $e) {
            error_log("Error en OrdenTrabajo::__construct(): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar número de radicado único
     */
    private function generarNumeroRadicado() {
        $year = date('Y');
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE numero_radicado LIKE :pattern AND YEAR(fecha_creacion) = :year";
        
        $stmt = $this->conn->prepare($query);
        $pattern = 'OT-' . $year . '-%';
        $stmt->bindParam(':pattern', $pattern);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $numero = $result['total'] + 1;
        
        return 'OT-' . $year . '-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Listar órdenes con filtros
     */
    public function listar($filtros = []) {
        $query = "SELECT ot.*, 
                         a.nombre_activo, a.codigo_interno, a.foto_principal,
                         u_solicitante.nombre as nombre_solicitante, u_solicitante.email as email_solicitante,
                         u_asignado.nombre as nombre_asignado, u_asignado.email as email_asignado
                  FROM " . $this->table . " ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  LEFT JOIN usuarios u_solicitante ON ot.id_solicitante = u_solicitante.id_usuario
                  LEFT JOIN usuarios u_asignado ON ot.id_usuario_asignado = u_asignado.id_usuario
                  WHERE ot.activo = 1";
        
        $params = [];

        // Filtros
        if (!empty($filtros['estado'])) {
            $query .= " AND ot.estado_proceso = :estado";
            $params[':estado'] = $filtros['estado'];
        } elseif (!empty($filtros['excluir_finalizado_por_defecto'])) {
            // Solo en la web: por defecto no mostrar órdenes finalizadas
            $query .= " AND ot.estado_proceso != 'finalizado'";
        }

        if (!empty($filtros['tipo_mantenimiento'])) {
            $query .= " AND ot.tipo_mantenimiento = :tipo";
            $params[':tipo'] = $filtros['tipo_mantenimiento'];
        }

        if (!empty($filtros['criticidad'])) {
            $query .= " AND ot.nivel_criticidad = :criticidad";
            $params[':criticidad'] = $filtros['criticidad'];
        }

        if (!empty($filtros['asignado_a'])) {
            $query .= " AND ot.id_usuario_asignado = :asignado";
            $params[':asignado'] = $filtros['asignado_a'];
        }

        if (!empty($filtros['solicitante'])) {
            $query .= " AND ot.id_solicitante = :solicitante";
            $params[':solicitante'] = $filtros['solicitante'];
        }

        if (!empty($filtros['activo'])) {
            $query .= " AND ot.id_activo = :activo";
            $params[':activo'] = $filtros['activo'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND ot.fecha_limite_ejecucion >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND ot.fecha_limite_ejecucion <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['busqueda'])) {
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $query .= " AND (ot.numero_radicado LIKE :busqueda1 
                      OR ot.descripcion_corta LIKE :busqueda2
                      OR a.nombre_activo LIKE :busqueda3)";
            $params[':busqueda1'] = $busqueda;
            $params[':busqueda2'] = $busqueda;
            $params[':busqueda3'] = $busqueda;
        }

        // Prioridad: fecha límite más cercana primero, luego criticidad, luego creación
        $query .= " ORDER BY 
                    (ot.fecha_limite_ejecucion IS NULL),
                    ot.fecha_limite_ejecucion ASC,
                    CASE ot.nivel_criticidad 
                        WHEN 'critica' THEN 1
                        WHEN 'alta' THEN 2
                        WHEN 'normal' THEN 3
                        WHEN 'baja' THEN 4
                        ELSE 5
                    END,
                    ot.fecha_creacion DESC";

        if (isset($filtros['limit']) && (int)$filtros['limit'] > 0) {
            $query .= " LIMIT " . (int)$filtros['limit'];
            if (isset($filtros['offset']) && (int)$filtros['offset'] >= 0) {
                $query .= " OFFSET " . (int)$filtros['offset'];
            }
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Contar órdenes con los mismos filtros que listar() (sin paginación)
     */
    public function contar($filtros = []) {
        $query = "SELECT COUNT(*) as total
                  FROM " . $this->table . " ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  LEFT JOIN usuarios u_solicitante ON ot.id_solicitante = u_solicitante.id_usuario
                  LEFT JOIN usuarios u_asignado ON ot.id_usuario_asignado = u_asignado.id_usuario
                  WHERE ot.activo = 1";

        $params = [];

        if (!empty($filtros['estado'])) {
            $query .= " AND ot.estado_proceso = :estado";
            $params[':estado'] = $filtros['estado'];
        } elseif (!empty($filtros['excluir_finalizado_por_defecto'])) {
            $query .= " AND ot.estado_proceso != 'finalizado'";
        }

        if (!empty($filtros['tipo_mantenimiento'])) {
            $query .= " AND ot.tipo_mantenimiento = :tipo";
            $params[':tipo'] = $filtros['tipo_mantenimiento'];
        }

        if (!empty($filtros['criticidad'])) {
            $query .= " AND ot.nivel_criticidad = :criticidad";
            $params[':criticidad'] = $filtros['criticidad'];
        }

        if (!empty($filtros['asignado_a'])) {
            $query .= " AND ot.id_usuario_asignado = :asignado";
            $params[':asignado'] = $filtros['asignado_a'];
        }

        if (!empty($filtros['solicitante'])) {
            $query .= " AND ot.id_solicitante = :solicitante";
            $params[':solicitante'] = $filtros['solicitante'];
        }

        if (!empty($filtros['activo'])) {
            $query .= " AND ot.id_activo = :activo";
            $params[':activo'] = $filtros['activo'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $query .= " AND ot.fecha_limite_ejecucion >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query .= " AND ot.fecha_limite_ejecucion <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['busqueda'])) {
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $query .= " AND (ot.numero_radicado LIKE :busqueda1 
                      OR ot.descripcion_corta LIKE :busqueda2
                      OR a.nombre_activo LIKE :busqueda3)";
            $params[':busqueda1'] = $busqueda;
            $params[':busqueda2'] = $busqueda;
            $params[':busqueda3'] = $busqueda;
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Obtener métricas resumidas para el dashboard de órdenes (con filtros por rol).
     * Retorna: en_proceso, finalizadas, criticas, atrasadas, recibido, rechazado, total_activas.
     */
    public function obtenerMetricasResumen($filtros = []) {
        $base = "SELECT 
            SUM(CASE WHEN ot.estado_proceso = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
            SUM(CASE WHEN ot.estado_proceso = 'finalizado' THEN 1 ELSE 0 END) AS finalizadas,
            SUM(CASE WHEN ot.nivel_criticidad = 'critica' AND ot.estado_proceso != 'finalizado' THEN 1 ELSE 0 END) AS criticas,
            SUM(CASE WHEN ot.fecha_limite_ejecucion < CURDATE() AND ot.estado_proceso NOT IN ('finalizado','rechazado') THEN 1 ELSE 0 END) AS atrasadas,
            SUM(CASE WHEN ot.estado_proceso = 'recibido' THEN 1 ELSE 0 END) AS recibido,
            SUM(CASE WHEN ot.estado_proceso = 'rechazado' THEN 1 ELSE 0 END) AS rechazado,
            SUM(CASE WHEN ot.estado_proceso IN ('recibido','en_proceso') THEN 1 ELSE 0 END) AS total_activas
            FROM " . $this->table . " ot
            LEFT JOIN activos a ON ot.id_activo = a.id_activo
            WHERE ot.activo = 1";
        $params = [];
        if (!empty($filtros['asignado_a'])) {
            $base .= " AND ot.id_usuario_asignado = :asignado";
            $params[':asignado'] = $filtros['asignado_a'];
        }
        if (!empty($filtros['solicitante'])) {
            $base .= " AND ot.id_solicitante = :solicitante";
            $params[':solicitante'] = $filtros['solicitante'];
        }
        $stmt = $this->conn->prepare($base);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'en_proceso' => (int)($row['en_proceso'] ?? 0),
            'finalizadas' => (int)($row['finalizadas'] ?? 0),
            'criticas' => (int)($row['criticas'] ?? 0),
            'atrasadas' => (int)($row['atrasadas'] ?? 0),
            'recibido' => (int)($row['recibido'] ?? 0),
            'rechazado' => (int)($row['rechazado'] ?? 0),
            'total_activas' => (int)($row['total_activas'] ?? 0),
        ];
    }

    /**
     * Obtener datos para informe de tiempos de finalización (órdenes finalizadas).
     * Incluye fecha_creacion, fecha_limite_ejecucion, fecha_finalizacion (desde historial).
     */
    public function obtenerDatosTiemposFinalizacion($filtros = []) {
        $query = "SELECT ot.id_orden, ot.numero_radicado, ot.fecha_creacion, ot.fecha_limite_ejecucion, ot.fecha_actualizacion,
                  (SELECT MIN(h.fecha_cambio) FROM ordenes_historial h 
                   WHERE h.id_orden = ot.id_orden AND h.tipo_cambio = 'estado' AND h.valor_nuevo = 'finalizado') AS fecha_finalizacion
                  FROM " . $this->table . " ot
                  WHERE ot.activo = 1 AND ot.estado_proceso = 'finalizado'";
        $params = [];
        if (!empty($filtros['asignado_a'])) {
            $query .= " AND ot.id_usuario_asignado = :asignado";
            $params[':asignado'] = $filtros['asignado_a'];
        }
        if (!empty($filtros['solicitante'])) {
            $query .= " AND ot.id_solicitante = :solicitante";
            $params[':solicitante'] = $filtros['solicitante'];
        }
        $query .= " ORDER BY ot.fecha_creacion DESC";
        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener carga de trabajo por operario (usuarios con rol operario).
     * Incluye operarios con 0 órdenes. Retorna: id_usuario, nombre, email, total_activas, en_proceso, recibido, finalizadas, atrasadas, criticas.
     */
    public function obtenerCargaPorOperario() {
        $query = "SELECT 
                    u.id_usuario,
                    u.nombre,
                    u.email,
                    COALESCE(SUM(CASE WHEN ot.estado_proceso IN ('recibido','en_proceso') THEN 1 ELSE 0 END), 0) AS total_activas,
                    COALESCE(SUM(CASE WHEN ot.estado_proceso = 'en_proceso' THEN 1 ELSE 0 END), 0) AS en_proceso,
                    COALESCE(SUM(CASE WHEN ot.estado_proceso = 'recibido' THEN 1 ELSE 0 END), 0) AS recibido,
                    COALESCE(SUM(CASE WHEN ot.estado_proceso = 'finalizado' THEN 1 ELSE 0 END), 0) AS finalizadas,
                    COALESCE(SUM(CASE WHEN ot.fecha_limite_ejecucion < CURDATE() AND ot.estado_proceso NOT IN ('finalizado','rechazado') THEN 1 ELSE 0 END), 0) AS atrasadas,
                    COALESCE(SUM(CASE WHEN ot.nivel_criticidad = 'critica' AND ot.estado_proceso != 'finalizado' THEN 1 ELSE 0 END), 0) AS criticas
                  FROM usuarios u
                  LEFT JOIN " . $this->table . " ot ON ot.id_usuario_asignado = u.id_usuario AND ot.activo = 1
                  WHERE u.rol = 'operario' AND u.activo = 1
                  GROUP BY u.id_usuario, u.nombre, u.email
                  ORDER BY total_activas DESC, atrasadas DESC, u.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener orden por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT ot.*, 
                         a.nombre_activo, a.codigo_interno, a.id_activo, a.foto_principal,
                         u_solicitante.nombre as nombre_solicitante, u_solicitante.email as email_solicitante,
                         u_asignado.nombre as nombre_asignado, u_asignado.email as email_asignado
                  FROM " . $this->table . " ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  LEFT JOIN usuarios u_solicitante ON ot.id_solicitante = u_solicitante.id_usuario
                  LEFT JOIN usuarios u_asignado ON ot.id_usuario_asignado = u_asignado.id_usuario
                  WHERE ot.id_orden = :id AND ot.activo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $orden = $stmt->fetch();

        if ($orden) {
            $orden['adjuntos'] = $this->obtenerAdjuntos($id);
            $orden['comentarios'] = $this->obtenerComentarios($id);
        }

        return $orden;
    }

    /**
     * Crear nueva orden
     */
    public function crear() {
        $this->numero_radicado = $this->generarNumeroRadicado();

        $query = "INSERT INTO " . $this->table . " 
                  (numero_radicado, id_activo, id_solicitud, id_solicitante, id_usuario_asignado,
                   tipo_mantenimiento, nivel_criticidad, descripcion_corta, descripcion_detallada,
                   estado_proceso, fecha_limite_ejecucion, usuario_creacion)
                  VALUES 
                  (:numero_radicado, :id_activo, :id_solicitud, :id_solicitante, :id_usuario_asignado,
                   :tipo_mantenimiento, :nivel_criticidad, :descripcion_corta, :descripcion_detallada,
                   :estado_proceso, :fecha_limite_ejecucion, :usuario_creacion)";

        $stmt = $this->conn->prepare($query);

        // Limpiar valores
        $this->descripcion_corta = htmlspecialchars(strip_tags($this->descripcion_corta));
        $this->descripcion_detallada = htmlspecialchars(strip_tags($this->descripcion_detallada ?? ''));

        $id_solicitud = !empty($this->id_solicitud) ? $this->id_solicitud : null;
        $id_usuario_asignado = !empty($this->id_usuario_asignado) ? $this->id_usuario_asignado : null;

        $stmt->bindParam(':numero_radicado', $this->numero_radicado);
        $stmt->bindParam(':id_activo', $this->id_activo, PDO::PARAM_INT);
        $stmt->bindParam(':id_solicitud', $id_solicitud, PDO::PARAM_INT);
        $stmt->bindParam(':id_solicitante', $this->id_solicitante, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario_asignado', $id_usuario_asignado, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_mantenimiento', $this->tipo_mantenimiento);
        $stmt->bindParam(':nivel_criticidad', $this->nivel_criticidad);
        $stmt->bindParam(':descripcion_corta', $this->descripcion_corta);
        $stmt->bindParam(':descripcion_detallada', $this->descripcion_detallada);
        $stmt->bindParam(':estado_proceso', $this->estado_proceso);
        $stmt->bindParam(':fecha_limite_ejecucion', $this->fecha_limite_ejecucion);
        $stmt->bindParam(':usuario_creacion', $this->usuario_creacion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->id_orden = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Actualizar orden
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table . " SET
                  id_activo = :id_activo,
                  id_usuario_asignado = :id_usuario_asignado,
                  tipo_mantenimiento = :tipo_mantenimiento,
                  nivel_criticidad = :nivel_criticidad,
                  descripcion_corta = :descripcion_corta,
                  descripcion_detallada = :descripcion_detallada,
                  estado_proceso = :estado_proceso,
                  fecha_limite_ejecucion = :fecha_limite_ejecucion
                  WHERE id_orden = :id_orden";

        $stmt = $this->conn->prepare($query);

        $this->descripcion_corta = htmlspecialchars(strip_tags($this->descripcion_corta));
        $this->descripcion_detallada = htmlspecialchars(strip_tags($this->descripcion_detallada ?? ''));

        $id_usuario_asignado = !empty($this->id_usuario_asignado) ? $this->id_usuario_asignado : null;

        $stmt->bindParam(':id_orden', $this->id_orden, PDO::PARAM_INT);
        $stmt->bindParam(':id_activo', $this->id_activo, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario_asignado', $id_usuario_asignado, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_mantenimiento', $this->tipo_mantenimiento);
        $stmt->bindParam(':nivel_criticidad', $this->nivel_criticidad);
        $stmt->bindParam(':descripcion_corta', $this->descripcion_corta);
        $stmt->bindParam(':descripcion_detallada', $this->descripcion_detallada);
        $stmt->bindParam(':estado_proceso', $this->estado_proceso);
        $stmt->bindParam(':fecha_limite_ejecucion', $this->fecha_limite_ejecucion);

        return $stmt->execute();
    }

    /**
     * Eliminar orden (soft delete)
     */
    public function eliminar() {
        $query = "UPDATE " . $this->table . " SET activo = 0 WHERE id_orden = :id_orden";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $this->id_orden, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Obtener adjuntos de una orden
     */
    public function obtenerAdjuntos($id_orden) {
        $query = "SELECT * FROM ordenes_adjuntos WHERE id_orden = :id_orden ORDER BY fecha_subida DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener comentarios de una orden
     */
    public function obtenerComentarios($id_orden) {
        $query = "SELECT oc.*, u.nombre as nombre_usuario, u.email as email_usuario
                  FROM ordenes_comentarios oc
                  LEFT JOIN usuarios u ON oc.id_usuario = u.id_usuario
                  WHERE oc.id_orden = :id_orden 
                  ORDER BY oc.fecha_creacion ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Agregar comentario a una orden
     */
    public function agregarComentario($id_orden, $id_usuario, $comentario) {
        $query = "INSERT INTO ordenes_comentarios (id_orden, id_usuario, comentario)
                  VALUES (:id_orden, :id_usuario, :comentario)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $comentario = htmlspecialchars(strip_tags($comentario));
        $stmt->bindParam(':comentario', $comentario);
        return $stmt->execute();
    }

    /**
     * Agregar adjunto a una orden
     */
    public function agregarAdjunto($id_orden, $nombre_archivo, $ruta_archivo, $tipo_archivo, $tamaño_archivo, $id_usuario, $descripcion = '') {
        // Usar backticks para el nombre de columna con caracteres especiales
        $query = "INSERT INTO ordenes_adjuntos 
                  (id_orden, nombre_archivo, ruta_archivo, tipo_archivo, `tamaño_archivo`, descripcion, usuario_subida)
                  VALUES 
                  (:id_orden, :nombre_archivo, :ruta_archivo, :tipo_archivo, :tamano_archivo, :descripcion, :usuario_subida)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        $stmt->bindParam(':nombre_archivo', $nombre_archivo);
        $stmt->bindParam(':ruta_archivo', $ruta_archivo);
        $stmt->bindParam(':tipo_archivo', $tipo_archivo);
        $stmt->bindParam(':tamano_archivo', $tamaño_archivo, PDO::PARAM_INT);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':usuario_subida', $id_usuario, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Registrar cambio en historial
     */
    public function registrarHistorial($id_orden, $id_usuario, $tipo_cambio, $campo_anterior = null, $valor_anterior = null, $campo_nuevo = null, $valor_nuevo = null, $descripcion = null, $ruta_evidencia = null) {
        $query = "INSERT INTO ordenes_historial 
                  (id_orden, id_usuario, tipo_cambio, campo_anterior, valor_anterior, campo_nuevo, valor_nuevo, descripcion, ruta_evidencia)
                  VALUES 
                  (:id_orden, :id_usuario, :tipo_cambio, :campo_anterior, :valor_anterior, :campo_nuevo, :valor_nuevo, :descripcion, :ruta_evidencia)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_cambio', $tipo_cambio);
        $stmt->bindParam(':campo_anterior', $campo_anterior);
        $stmt->bindParam(':valor_anterior', $valor_anterior);
        $stmt->bindParam(':campo_nuevo', $campo_nuevo);
        $stmt->bindParam(':valor_nuevo', $valor_nuevo);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':ruta_evidencia', $ruta_evidencia);
        
        return $stmt->execute();
    }

    /**
     * Obtener historial de cambios de una orden
     */
    public function obtenerHistorial($id_orden) {
        $query = "SELECT h.*, 
                         u.nombre as nombre_usuario, 
                         u.email as email_usuario,
                         u_anterior.nombre as nombre_usuario_anterior,
                         u_nuevo.nombre as nombre_usuario_nuevo
                  FROM ordenes_historial h
                  LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
                  LEFT JOIN usuarios u_anterior ON h.campo_anterior = 'id_usuario_asignado' 
                       AND h.valor_anterior IS NOT NULL 
                       AND CAST(h.valor_anterior AS UNSIGNED) = u_anterior.id_usuario
                  LEFT JOIN usuarios u_nuevo ON h.campo_nuevo = 'id_usuario_asignado' 
                       AND h.valor_nuevo IS NOT NULL 
                       AND CAST(h.valor_nuevo AS UNSIGNED) = u_nuevo.id_usuario
                  WHERE h.id_orden = :id_orden
                  ORDER BY h.fecha_cambio DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultados = $stmt->fetchAll();
        
        // Procesar resultados para convertir IDs de usuario en nombres
        foreach ($resultados as &$cambio) {
            // Si el campo es id_usuario_asignado y tenemos nombres, usarlos
            if ($cambio['campo_anterior'] === 'id_usuario_asignado' && !empty($cambio['nombre_usuario_anterior'])) {
                $cambio['valor_anterior'] = $cambio['nombre_usuario_anterior'];
            }
            if ($cambio['campo_nuevo'] === 'id_usuario_asignado' && !empty($cambio['nombre_usuario_nuevo'])) {
                $cambio['valor_nuevo'] = $cambio['nombre_usuario_nuevo'];
            }
        }
        
        return $resultados;
    }

    /**
     * Reasignar orden a otro operario
     */
    public function reasignar($id_orden, $id_nuevo_operario, $id_usuario_que_reasigna, $comentario = '') {
        // Obtener orden actual
        $orden_actual = $this->obtenerPorId($id_orden);
        if (!$orden_actual) {
            return false;
        }

        $id_operario_anterior = $orden_actual['id_usuario_asignado'];
        
        // Actualizar asignación
        $query = "UPDATE " . $this->table 
                  . " SET id_usuario_asignado = :nuevo_operario 
                  WHERE id_orden = :id_orden";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nuevo_operario', $id_nuevo_operario, PDO::PARAM_INT);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Registrar en historial
            $this->registrarHistorial(
                $id_orden,
                $id_usuario_que_reasigna,
                'reasignacion',
                'id_usuario_asignado',
                $id_operario_anterior,
                'id_usuario_asignado',
                $id_nuevo_operario,
                $comentario ?: 'Orden reasignada'
            );
            return true;
        }
        
        return false;
    }

    /**
     * Cambiar estado de orden (para operarios)
     */
    public function cambiarEstado($id_orden, $nuevo_estado, $id_usuario, $comentario = '', $ruta_evidencia = null) {
        // Obtener orden actual
        $orden_actual = $this->obtenerPorId($id_orden);
        if (!$orden_actual) {
            return false;
        }

        $estado_anterior = $orden_actual['estado_proceso'];
        
        // Actualizar estado
        $query = "UPDATE " . $this->table 
                  . " SET estado_proceso = :nuevo_estado 
                  WHERE id_orden = :id_orden";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nuevo_estado', $nuevo_estado);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Registrar en historial
            $this->registrarHistorial(
                $id_orden,
                $id_usuario,
                'estado',
                'estado_proceso',
                $estado_anterior,
                'estado_proceso',
                $nuevo_estado,
                $comentario ?: 'Estado cambiado',
                $ruta_evidencia
            );
            
            // Si hay comentario, agregarlo también
            if (!empty($comentario)) {
                $this->agregarComentario($id_orden, $id_usuario, $comentario);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Obtener órdenes para cronograma (por rango de fechas)
     */
    public function obtenerParaCronograma($fecha_desde, $fecha_hasta, $id_usuario = null, $filtro_estado = null, $filtro_criticidad = null, $id_solicitante = null) {
        $query = "SELECT ot.*, 
                         a.nombre_activo, a.codigo_interno,
                         u_asignado.nombre as nombre_asignado,
                         u_asignado.email as email_asignado
                  FROM " . $this->table . " ot
                  LEFT JOIN activos a ON ot.id_activo = a.id_activo
                  LEFT JOIN usuarios u_asignado ON ot.id_usuario_asignado = u_asignado.id_usuario
                  WHERE ot.activo = 1
                  AND ot.fecha_limite_ejecucion BETWEEN :fecha_desde AND :fecha_hasta";
        
        if ($id_usuario) {
            $query .= " AND ot.id_usuario_asignado = :id_usuario";
        }
        
        if ($id_solicitante) {
            $query .= " AND ot.id_solicitante = :id_solicitante";
        }
        
        if ($filtro_estado) {
            $query .= " AND ot.estado_proceso = :filtro_estado";
        }
        
        if ($filtro_criticidad) {
            $query .= " AND ot.nivel_criticidad = :filtro_criticidad";
        }

        $query .= " ORDER BY ot.fecha_limite_ejecucion ASC, ot.nivel_criticidad DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha_desde', $fecha_desde);
        $stmt->bindParam(':fecha_hasta', $fecha_hasta);
        if ($id_usuario) {
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        }
        if ($id_solicitante) {
            $stmt->bindParam(':id_solicitante', $id_solicitante, PDO::PARAM_INT);
        }
        if ($filtro_estado) {
            $stmt->bindParam(':filtro_estado', $filtro_estado);
        }
        if ($filtro_criticidad) {
            $stmt->bindParam(':filtro_criticidad', $filtro_criticidad);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Actualizar asignación y fecha (para drag & drop en cronograma)
     */
    public function actualizarAsignacion($id_orden, $id_usuario_asignado, $fecha_limite) {
        $query = "UPDATE " . $this->table . " 
                  SET id_usuario_asignado = :id_usuario_asignado,
                      fecha_limite_ejecucion = :fecha_limite
                  WHERE id_orden = :id_orden";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_orden', $id_orden, PDO::PARAM_INT);
        if ($id_usuario_asignado === null || $id_usuario_asignado === '') {
            $stmt->bindValue(':id_usuario_asignado', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':id_usuario_asignado', (int)$id_usuario_asignado, PDO::PARAM_INT);
        }
        $stmt->bindParam(':fecha_limite', $fecha_limite);
        
        return $stmt->execute();
    }
}


