<?php
/**
 * Modelo de Activo
 * Sistema ROMA - Módulo de Gestión de Activos
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class Activo {
    private $conn;
    private $table = 'activos';

    public $id_activo;
    public $nombre_activo;
    public $codigo_interno;
    public $codigo_patrimonial;
    public $descripcion_general;
    public $categoria;
    public $ubicacion;
    public $ruta;
    public $foto_principal;
    public $responsable;
    public $area_asignada;
    public $marca;
    public $modelo;
    public $numero_serie;
    public $fecha_adquisicion;
    public $valor_adquisicion;
    public $estado_actual;
    public $vida_util_estimada;
    public $kilometraje;
    public $horas_uso;
    public $ultimo_mantenimiento;
    public $proximo_mantenimiento;
    public $observaciones;
    public $usuario_creacion;

    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            if (!$this->conn) {
                throw new Exception("No se pudo establecer conexión con la base de datos");
            }
        } catch (Exception $e) {
            error_log("Error en Activo::__construct(): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener todos los activos con filtros opcionales
     */
    public function listar($filtros = []) {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = 1";
        $params = [];

        // Filtros
        if (!empty($filtros['categoria'])) {
            $query .= " AND categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['estado'])) {
            $query .= " AND estado_actual = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['ubicacion'])) {
            $query .= " AND ubicacion LIKE :ubicacion";
            $params[':ubicacion'] = '%' . $filtros['ubicacion'] . '%';
        }

        if (!empty($filtros['ruta'])) {
            $query .= " AND ruta = :ruta";
            $params[':ruta'] = $filtros['ruta'];
        }

        if (!empty($filtros['busqueda'])) {
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $query .= " AND (nombre_activo LIKE :busqueda1 
                      OR codigo_interno LIKE :busqueda2 
                      OR codigo_patrimonial LIKE :busqueda3
                      OR marca LIKE :busqueda4
                      OR modelo LIKE :busqueda5)";
            $params[':busqueda1'] = $busqueda;
            $params[':busqueda2'] = $busqueda;
            $params[':busqueda3'] = $busqueda;
            $params[':busqueda4'] = $busqueda;
            $params[':busqueda5'] = $busqueda;
        }

        $query .= " ORDER BY fecha_creacion DESC";

        // Paginación
        if (!empty($filtros['limit'])) {
            $query .= " LIMIT :limit";
            $params[':limit'] = (int)$filtros['limit'];
            
            if (!empty($filtros['offset'])) {
                $query .= " OFFSET :offset";
                $params[':offset'] = (int)$filtros['offset'];
            }
        }

        try {
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }

            $stmt->execute();
            $result = $stmt->fetchAll();
            return is_array($result) ? $result : [];
        } catch (Exception $e) {
            error_log("Error en Activo::listar(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un activo por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_activo = :id AND activo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $activo = $stmt->fetch();

        if ($activo) {
            // Cargar adjuntos
            $activo['adjuntos'] = $this->obtenerAdjuntos($id);
        }

        return $activo;
    }

    /**
     * Crear nuevo activo
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table . " 
                  (nombre_activo, codigo_interno, codigo_patrimonial, descripcion_general, categoria, 
                   ubicacion, ruta, foto_principal, responsable, area_asignada, marca, modelo, 
                   numero_serie, fecha_adquisicion, valor_adquisicion, estado_actual, 
                   vida_util_estimada, kilometraje, horas_uso, proximo_mantenimiento, 
                   observaciones, usuario_creacion)
                  VALUES 
                  (:nombre_activo, :codigo_interno, :codigo_patrimonial, :descripcion_general, :categoria,
                   :ubicacion, :ruta, :foto_principal, :responsable, :area_asignada, :marca, :modelo,
                   :numero_serie, :fecha_adquisicion, :valor_adquisicion, :estado_actual,
                   :vida_util_estimada, :kilometraje, :horas_uso, :proximo_mantenimiento,
                   :observaciones, :usuario_creacion)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y asignar valores
        $this->nombre_activo = htmlspecialchars(strip_tags($this->nombre_activo));
        $this->codigo_interno = htmlspecialchars(strip_tags($this->codigo_interno ?? ''));
        $this->codigo_patrimonial = htmlspecialchars(strip_tags($this->codigo_patrimonial ?? ''));
        $this->descripcion_general = htmlspecialchars(strip_tags($this->descripcion_general ?? ''));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        $this->ubicacion = htmlspecialchars(strip_tags($this->ubicacion ?? ''));
        $this->ruta = htmlspecialchars(strip_tags($this->ruta ?? ''));
        if (!empty($this->foto_principal)) {
            $this->foto_principal = htmlspecialchars(strip_tags($this->foto_principal));
        } else {
            $this->foto_principal = null;
        }
        $this->responsable = htmlspecialchars(strip_tags($this->responsable ?? ''));
        $this->area_asignada = htmlspecialchars(strip_tags($this->area_asignada ?? ''));
        $this->marca = htmlspecialchars(strip_tags($this->marca ?? ''));
        $this->modelo = htmlspecialchars(strip_tags($this->modelo ?? ''));
        $this->numero_serie = htmlspecialchars(strip_tags($this->numero_serie ?? ''));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones ?? ''));

        // Bind parameters
        $stmt->bindParam(':nombre_activo', $this->nombre_activo);
        $stmt->bindParam(':codigo_interno', $this->codigo_interno);
        $stmt->bindParam(':codigo_patrimonial', $this->codigo_patrimonial);
        $stmt->bindParam(':descripcion_general', $this->descripcion_general);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':ubicacion', $this->ubicacion);
        $stmt->bindParam(':ruta', $this->ruta);
        $stmt->bindParam(':foto_principal', $this->foto_principal);
        $stmt->bindParam(':responsable', $this->responsable);
        $stmt->bindParam(':area_asignada', $this->area_asignada);
        $stmt->bindParam(':marca', $this->marca);
        $stmt->bindParam(':modelo', $this->modelo);
        $stmt->bindParam(':numero_serie', $this->numero_serie);
        $stmt->bindParam(':fecha_adquisicion', $this->fecha_adquisicion);
        $stmt->bindParam(':valor_adquisicion', $this->valor_adquisicion);
        $stmt->bindParam(':estado_actual', $this->estado_actual);
        $stmt->bindParam(':vida_util_estimada', $this->vida_util_estimada);
        $stmt->bindParam(':kilometraje', $this->kilometraje);
        $stmt->bindParam(':horas_uso', $this->horas_uso);
        $stmt->bindParam(':proximo_mantenimiento', $this->proximo_mantenimiento);
        $stmt->bindParam(':observaciones', $this->observaciones);
        $stmt->bindParam(':usuario_creacion', $this->usuario_creacion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->id_activo = $this->conn->lastInsertId();
            $this->registrarAuditoria('INSERT', $this->id_activo);
            return true;
        }

        return false;
    }

    /**
     * Actualizar activo existente
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table . " SET
                  nombre_activo = :nombre_activo,
                  codigo_interno = :codigo_interno,
                  codigo_patrimonial = :codigo_patrimonial,
                  descripcion_general = :descripcion_general,
                  categoria = :categoria,
                  ubicacion = :ubicacion,
                  ruta = :ruta,
                  foto_principal = :foto_principal,
                  responsable = :responsable,
                  area_asignada = :area_asignada,
                  marca = :marca,
                  modelo = :modelo,
                  numero_serie = :numero_serie,
                  fecha_adquisicion = :fecha_adquisicion,
                  valor_adquisicion = :valor_adquisicion,
                  estado_actual = :estado_actual,
                  vida_util_estimada = :vida_util_estimada,
                  kilometraje = :kilometraje,
                  horas_uso = :horas_uso,
                  proximo_mantenimiento = :proximo_mantenimiento,
                  observaciones = :observaciones,
                  usuario_actualizacion = :usuario_actualizacion
                  WHERE id_activo = :id_activo";

        $stmt = $this->conn->prepare($query);

        // Limpiar valores
        $this->nombre_activo = htmlspecialchars(strip_tags($this->nombre_activo));
        $this->codigo_interno = htmlspecialchars(strip_tags($this->codigo_interno ?? ''));
        $this->codigo_patrimonial = htmlspecialchars(strip_tags($this->codigo_patrimonial ?? ''));
        $this->descripcion_general = htmlspecialchars(strip_tags($this->descripcion_general ?? ''));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        $this->ubicacion = htmlspecialchars(strip_tags($this->ubicacion ?? ''));
        $this->ruta = htmlspecialchars(strip_tags($this->ruta ?? ''));
        if (!empty($this->foto_principal)) {
            $this->foto_principal = htmlspecialchars(strip_tags($this->foto_principal));
        } else {
            $this->foto_principal = null;
        }
        $this->responsable = htmlspecialchars(strip_tags($this->responsable ?? ''));
        $this->area_asignada = htmlspecialchars(strip_tags($this->area_asignada ?? ''));
        $this->marca = htmlspecialchars(strip_tags($this->marca ?? ''));
        $this->modelo = htmlspecialchars(strip_tags($this->modelo ?? ''));
        $this->numero_serie = htmlspecialchars(strip_tags($this->numero_serie ?? ''));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones ?? ''));

        $stmt->bindParam(':id_activo', $this->id_activo, PDO::PARAM_INT);
        $stmt->bindParam(':nombre_activo', $this->nombre_activo);
        $stmt->bindParam(':codigo_interno', $this->codigo_interno);
        $stmt->bindParam(':codigo_patrimonial', $this->codigo_patrimonial);
        $stmt->bindParam(':descripcion_general', $this->descripcion_general);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':ubicacion', $this->ubicacion);
        $stmt->bindParam(':ruta', $this->ruta);
        $stmt->bindParam(':foto_principal', $this->foto_principal);
        $stmt->bindParam(':responsable', $this->responsable);
        $stmt->bindParam(':area_asignada', $this->area_asignada);
        $stmt->bindParam(':marca', $this->marca);
        $stmt->bindParam(':modelo', $this->modelo);
        $stmt->bindParam(':numero_serie', $this->numero_serie);
        $stmt->bindParam(':fecha_adquisicion', $this->fecha_adquisicion);
        $stmt->bindParam(':valor_adquisicion', $this->valor_adquisicion);
        $stmt->bindParam(':estado_actual', $this->estado_actual);
        $stmt->bindParam(':vida_util_estimada', $this->vida_util_estimada);
        $stmt->bindParam(':kilometraje', $this->kilometraje);
        $stmt->bindParam(':horas_uso', $this->horas_uso);
        $stmt->bindParam(':proximo_mantenimiento', $this->proximo_mantenimiento);
        $stmt->bindParam(':observaciones', $this->observaciones);
        $stmt->bindParam(':usuario_actualizacion', $this->usuario_creacion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->registrarAuditoria('UPDATE', $this->id_activo);
            return true;
        }

        return false;
    }

    /**
     * Eliminar activo (soft delete)
     */
    public function eliminar() {
        $query = "UPDATE " . $this->table . " 
                  SET activo = 0, usuario_actualizacion = :usuario
                  WHERE id_activo = :id_activo";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_activo', $this->id_activo, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $this->usuario_creacion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->registrarAuditoria('DELETE', $this->id_activo);
            return true;
        }

        return false;
    }

    /**
     * Obtener adjuntos de un activo
     */
    public function obtenerAdjuntos($id_activo) {
        $query = "SELECT * FROM activos_adjuntos 
                  WHERE id_activo = :id_activo 
                  ORDER BY fecha_subida DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_activo', $id_activo, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtener hoja de vida (historial) de un activo
     */
    public function obtenerHojaVida($id_activo, $filtros = []) {
        $query = "SELECT hv.*, 
                  GROUP_CONCAT(e.ruta_imagen) as evidencias,
                  NULL as es_orden,
                  NULL as nivel_criticidad,
                  NULL as estado_orden,
                  NULL as fecha_limite_solicitud,
                  NULL as link_detalle
                  FROM activos_hoja_vida hv
                  LEFT JOIN activos_evidencias e ON hv.id_registro = e.id_registro_hoja_vida
                  WHERE hv.id_activo = :id_activo";

        if (!empty($filtros['tipo_mantenimiento'])) {
            $query .= " AND hv.tipo_mantenimiento = :tipo";
        }

        if (!empty($filtros['tecnico'])) {
            $query .= " AND hv.tecnico_responsable LIKE :tecnico";
        }

        $query .= " GROUP BY hv.id_registro
                    ORDER BY hv.fecha_intervencion DESC";

        // Paginación (solo si se especifica)
        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $limit = (int)$filtros['limit'];
            $offset = (int)$filtros['offset'];
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_activo', $id_activo, PDO::PARAM_INT);
        
        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        if (!empty($filtros['tipo_mantenimiento'])) {
            $stmt->bindParam(':tipo', $filtros['tipo_mantenimiento']);
        }

        // Nota: El filtro de técnico no se aplica a hoja_vida porque técnico_responsable 
        // es un VARCHAR con nombre, no un ID. Solo se aplica a órdenes.

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Contar total de registros de hoja de vida para paginación
     */
    public function contarHojaVida($id_activo, $filtros = []) {
        $query = "SELECT COUNT(DISTINCT hv.id_registro) as total
                  FROM activos_hoja_vida hv
                  WHERE hv.id_activo = :id_activo";

        if (!empty($filtros['tipo_mantenimiento'])) {
            $query .= " AND hv.tipo_mantenimiento = :tipo";
        }

        // Nota: El filtro de técnico no se aplica a hoja_vida porque técnico_responsable 
        // es un VARCHAR con nombre, no un ID. Solo se aplica a órdenes.
        // Si se necesita filtrar por técnico en hoja_vida, se requeriría un JOIN con usuarios

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_activo', $id_activo, PDO::PARAM_INT);

        if (!empty($filtros['tipo_mantenimiento'])) {
            $stmt->bindParam(':tipo', $filtros['tipo_mantenimiento']);
        }

        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function obtenerOrdenesAsociadas($id_activo, $filtros = []) {
        $query = "SELECT 
                    ot.id_orden,
                    ot.numero_radicado,
                    ot.tipo_mantenimiento,
                    ot.descripcion_corta,
                    ot.descripcion_detallada,
                    ot.estado_proceso,
                    ot.nivel_criticidad,
                    ot.fecha_creacion,
                    ot.fecha_limite_ejecucion,
                    u.nombre AS tecnico_responsable,
                    u.id_usuario AS tecnico_id
                  FROM ordenes_trabajo ot
                  LEFT JOIN usuarios u ON ot.id_usuario_asignado = u.id_usuario
                  WHERE ot.activo = 1
                  AND ot.id_activo = :id_activo";

        if (!empty($filtros['tipo_mantenimiento'])) {
            $query .= " AND ot.tipo_mantenimiento = :tipo";
        }

        if (!empty($filtros['tecnico'])) {
            $query .= " AND ot.id_usuario_asignado = :tecnico";
        }

        $query .= " ORDER BY ot.fecha_creacion DESC";

        // Paginación (solo si se especifica)
        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $limit = (int)$filtros['limit'];
            $offset = (int)$filtros['offset'];
            $query .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_activo', $id_activo, PDO::PARAM_INT);
        
        if (isset($filtros['limit']) && isset($filtros['offset'])) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }

        if (!empty($filtros['tipo_mantenimiento'])) {
            $stmt->bindParam(':tipo', $filtros['tipo_mantenimiento']);
        }

        if (!empty($filtros['tecnico'])) {
            $stmt->bindValue(':tecnico', (int)$filtros['tecnico'], PDO::PARAM_INT);
        }

        $stmt->execute();
        $ordenes = $stmt->fetchAll();

        // Los datos ya vienen con el formato correcto desde la consulta SQL
        // Solo necesitamos agregar campos adicionales para compatibilidad
        foreach ($ordenes as &$orden) {
            $orden['id_registro'] = 'orden_' . $orden['id_orden'];
            $orden['es_orden'] = true;
            $orden['numero_orden'] = $orden['numero_radicado'];
            $orden['fecha_intervencion'] = $orden['fecha_creacion'];
            $orden['estado_orden'] = $orden['estado_proceso'];
            $orden['fecha_limite_solicitud'] = $orden['fecha_limite_ejecucion'];
            $orden['observaciones'] = null;
            $orden['descripcion'] = null;
            $orden['kilometraje_evento'] = null;
            $orden['horas_uso_evento'] = null;
            $orden['evidencias'] = null;
            $orden['link_detalle'] = (defined('BASE_URL') ? BASE_URL : '/') . 'index.php?action=ordenes&subaction=ver&id=' . $orden['id_orden'];
        }

        return $ordenes;
    }

    /**
     * Contar total de órdenes asociadas para paginación
     */
    public function contarOrdenesAsociadas($id_activo, $filtros = []) {
        $query = "SELECT COUNT(*) as total
                  FROM ordenes_trabajo ot
                  WHERE ot.activo = 1
                  AND ot.id_activo = :id_activo";

        if (!empty($filtros['tipo_mantenimiento'])) {
            $query .= " AND ot.tipo_mantenimiento = :tipo";
        }

        if (!empty($filtros['tecnico'])) {
            $query .= " AND ot.id_usuario_asignado = :tecnico";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_activo', $id_activo, PDO::PARAM_INT);

        if (!empty($filtros['tipo_mantenimiento'])) {
            $stmt->bindParam(':tipo', $filtros['tipo_mantenimiento']);
        }

        if (!empty($filtros['tecnico'])) {
            $stmt->bindValue(':tecnico', (int)$filtros['tecnico'], PDO::PARAM_INT);
        }

        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    /**
     * Registrar auditoría de cambios
     */
    private function registrarAuditoria($tipo, $id_activo) {
        $query = "INSERT INTO activos_auditoria 
                  (id_activo, usuario_modificacion, tipo_operacion)
                  VALUES (:id_activo, :usuario, :tipo)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_activo', $id_activo, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $this->usuario_creacion, PDO::PARAM_INT);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
    }

    /**
     * Obtener estadísticas de activos
     */
    public function obtenerEstadisticas() {
        $query = "SELECT 
                  categoria,
                  estado_actual,
                  COUNT(*) as cantidad
                  FROM " . $this->table . "
                  WHERE activo = 1
                  GROUP BY categoria, estado_actual";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

