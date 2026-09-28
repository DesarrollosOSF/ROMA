<?php
/**
 * Modelo de Trabajadores Responsables de Activos
 * Registro de colaboradores para asociarles activos y autocompletar
 * el formato de inventario. Incluye control de devolución/cambio/
 * traslado (editable) y formato de inventario adjunto.
 */

require_once __DIR__ . '/../config/database.php';

class TrabajadorResponsable {
    private $conn;
    private $table = 'trabajadores_responsables';

    public $id_trabajador;
    public $nombre_completo;
    public $documento_identidad;
    public $cargo;
    public $dependencia;
    public $tipo_vinculacion;
    public $centro_costo;
    public $jefe_inmediato;
    public $fecha_ingreso;
    public $usuario_creacion;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!$this->conn) {
            throw new Exception("No se pudo establecer conexión con la base de datos");
        }
    }

    public static function tiposMovimiento() {
        return [
            'devolucion' => 'Devolución',
            'cambio' => 'Cambio',
            'traslado' => 'Traslado',
        ];
    }

    public function listar($filtros = []) {
        try {
            $query = "SELECT t.*, COUNT(DISTINCT n.id_nuevo_activo) AS total_activos,
                             COUNT(DISTINCT m.id_movimiento) AS total_movimientos
                      FROM {$this->table} t
                      LEFT JOIN nuevos_activos n ON n.id_trabajador = t.id_trabajador AND n.activo = 1
                      LEFT JOIN trabajadores_movimientos m ON m.id_trabajador = t.id_trabajador
                      WHERE t.activo = 1";
            $params = [];
            if (!empty($filtros['busqueda'])) {
                $query .= " AND (t.nombre_completo LIKE :b1 OR t.documento_identidad LIKE :b2
                            OR t.cargo LIKE :b3 OR t.dependencia LIKE :b4)";
                $b = '%' . $filtros['busqueda'] . '%';
                $params[':b1'] = $b;
                $params[':b2'] = $b;
                $params[':b3'] = $b;
                $params[':b4'] = $b;
            }
            $query .= " GROUP BY t.id_trabajador ORDER BY t.nombre_completo ASC";
            if (!empty($filtros['limit'])) {
                $query .= " LIMIT :limit";
                $params[':limit'] = (int)$filtros['limit'];
                if (isset($filtros['offset'])) {
                    $query .= " OFFSET :offset";
                    $params[':offset'] = (int)$filtros['offset'];
                }
            }
            $stmt = $this->conn->prepare($query);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, in_array($k, [':limit', ':offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $res = $stmt->fetchAll();
            return is_array($res) ? $res : [];
        } catch (Exception $e) {
            error_log("TrabajadorResponsable::listar: " . $e->getMessage());
            return [];
        }
    }

    public function contar($filtros = []) {
        try {
            $query = "SELECT COUNT(*) AS total FROM {$this->table} WHERE activo = 1";
            $params = [];
            if (!empty($filtros['busqueda'])) {
                $query .= " AND (nombre_completo LIKE :b1 OR documento_identidad LIKE :b2)";
                $b = '%' . $filtros['busqueda'] . '%';
                $params[':b1'] = $b;
                $params[':b2'] = $b;
            }
            $stmt = $this->conn->prepare($query);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            return (int)($stmt->fetch()['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function obtenerPorId($id) {
        $s = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_trabajador = :id AND activo = 1 LIMIT 1");
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        $row = $s->fetch();
        if ($row) {
            $row['movimientos'] = $this->obtenerMovimientos($id);
        }
        return $row;
    }

    public function obtenerPorDocumento($documento, $excluirId = null) {
        $q = "SELECT * FROM {$this->table} WHERE documento_identidad = :doc";
        if ($excluirId) $q .= " AND id_trabajador <> :ex";
        $q .= " LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->bindValue(':doc', trim($documento));
        if ($excluirId) $s->bindValue(':ex', (int)$excluirId, PDO::PARAM_INT);
        $s->execute();
        return $s->fetch();
    }

    public function crear() {
        $q = "INSERT INTO {$this->table}
              (nombre_completo, documento_identidad, cargo, dependencia, tipo_vinculacion,
               centro_costo, jefe_inmediato, fecha_ingreso, usuario_creacion)
              VALUES (:nom, :doc, :cargo, :dep, :vinc, :cc, :jefe, :fing, :u)";
        $s = $this->conn->prepare($q);
        $s->bindValue(':nom', trim($this->nombre_completo));
        $s->bindValue(':doc', trim($this->documento_identidad));
        $s->bindValue(':cargo', trim($this->cargo ?? '') ?: null);
        $s->bindValue(':dep', trim($this->dependencia ?? '') ?: null);
        $s->bindValue(':vinc', trim($this->tipo_vinculacion ?? '') ?: null);
        $s->bindValue(':cc', trim($this->centro_costo ?? '') ?: null);
        $s->bindValue(':jefe', trim($this->jefe_inmediato ?? '') ?: null);
        $s->bindValue(':fing', $this->fecha_ingreso ?: null);
        $s->bindValue(':u', $this->usuario_creacion ?: null, $this->usuario_creacion ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->execute();
        $this->id_trabajador = (int)$this->conn->lastInsertId();
        return true;
    }

    public function actualizar() {
        $q = "UPDATE {$this->table} SET nombre_completo = :nom, documento_identidad = :doc,
              cargo = :cargo, dependencia = :dep, tipo_vinculacion = :vinc, centro_costo = :cc,
              jefe_inmediato = :jefe, fecha_ingreso = :fing
              WHERE id_trabajador = :id";
        $s = $this->conn->prepare($q);
        $s->bindValue(':nom', trim($this->nombre_completo));
        $s->bindValue(':doc', trim($this->documento_identidad));
        $s->bindValue(':cargo', trim($this->cargo ?? '') ?: null);
        $s->bindValue(':dep', trim($this->dependencia ?? '') ?: null);
        $s->bindValue(':vinc', trim($this->tipo_vinculacion ?? '') ?: null);
        $s->bindValue(':cc', trim($this->centro_costo ?? '') ?: null);
        $s->bindValue(':jefe', trim($this->jefe_inmediato ?? '') ?: null);
        $s->bindValue(':fing', $this->fecha_ingreso ?: null);
        $s->bindValue(':id', (int)$this->id_trabajador, PDO::PARAM_INT);
        $s->execute();
        return true;
    }

    public function eliminar() {
        $s = $this->conn->prepare("UPDATE {$this->table} SET activo = 0 WHERE id_trabajador = :id");
        $s->bindValue(':id', (int)$this->id_trabajador, PDO::PARAM_INT);
        $s->execute();
        // Los activos asociados quedan sin trabajador (SET NULL no aplica aquí por ser lógico;
        // se desasocian explícitamente para no mostrar datos de un responsable inactivo)
        $d = $this->conn->prepare("UPDATE nuevos_activos SET id_trabajador = NULL WHERE id_trabajador = :id");
        $d->bindValue(':id', (int)$this->id_trabajador, PDO::PARAM_INT);
        $d->execute();
        return true;
    }

    // ---------- Movimientos (devolución / cambio / traslado) ----------

    public function obtenerMovimientos($idTrabajador) {
        try {
            $q = "SELECT m.*, n.codigo AS activo_codigo, n.nombre AS activo_nombre
                  FROM trabajadores_movimientos m
                  LEFT JOIN nuevos_activos n ON n.id_nuevo_activo = m.id_nuevo_activo
                  WHERE m.id_trabajador = :id
                  ORDER BY m.fecha DESC, m.id_movimiento DESC";
            $s = $this->conn->prepare($q);
            $s->bindValue(':id', (int)$idTrabajador, PDO::PARAM_INT);
            $s->execute();
            $res = $s->fetchAll();
            return is_array($res) ? $res : [];
        } catch (Exception $e) {
            error_log("TrabajadorResponsable::obtenerMovimientos: " . $e->getMessage());
            return [];
        }
    }

    public function guardarMovimiento($datos) {
        $tipos = array_keys(self::tiposMovimiento());
        if (empty($datos['fecha']) || !in_array($datos['tipo_movimiento'] ?? '', $tipos, true)) {
            throw new Exception('Fecha y tipo de movimiento son obligatorios.');
        }
        if (!empty($datos['id_movimiento'])) {
            $q = "UPDATE trabajadores_movimientos SET fecha = :f, tipo_movimiento = :t,
                  id_nuevo_activo = :na, codigo_activo = :cod, estado_anterior = :ea,
                  estado_nuevo = :en, responsable = :resp, observacion = :obs
                  WHERE id_movimiento = :id AND id_trabajador = :trab";
            $s = $this->conn->prepare($q);
            $s->bindValue(':id', (int)$datos['id_movimiento'], PDO::PARAM_INT);
            $s->bindValue(':trab', (int)$datos['id_trabajador'], PDO::PARAM_INT);
        } else {
            $q = "INSERT INTO trabajadores_movimientos
                  (id_trabajador, fecha, tipo_movimiento, id_nuevo_activo, codigo_activo,
                   estado_anterior, estado_nuevo, responsable, observacion, usuario_creacion)
                  VALUES (:trab, :f, :t, :na, :cod, :ea, :en, :resp, :obs, :u)";
            $s = $this->conn->prepare($q);
            $s->bindValue(':trab', (int)$datos['id_trabajador'], PDO::PARAM_INT);
            $s->bindValue(':u', $datos['usuario_creacion'] ?: null, !empty($datos['usuario_creacion']) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $s->bindValue(':f', $datos['fecha']);
        $s->bindValue(':t', $datos['tipo_movimiento']);
        $s->bindValue(':na', !empty($datos['id_nuevo_activo']) ? (int)$datos['id_nuevo_activo'] : null, !empty($datos['id_nuevo_activo']) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->bindValue(':cod', trim($datos['codigo_activo'] ?? '') ?: null);
        $s->bindValue(':ea', trim($datos['estado_anterior'] ?? '') ?: null);
        $s->bindValue(':en', trim($datos['estado_nuevo'] ?? '') ?: null);
        $s->bindValue(':resp', trim($datos['responsable'] ?? '') ?: null);
        $s->bindValue(':obs', trim($datos['observacion'] ?? '') ?: null);
        $s->execute();
        return true;
    }

    public function obtenerMovimiento($idMovimiento, $idTrabajador) {
        $s = $this->conn->prepare("SELECT * FROM trabajadores_movimientos WHERE id_movimiento = :id AND id_trabajador = :trab LIMIT 1");
        $s->bindValue(':id', (int)$idMovimiento, PDO::PARAM_INT);
        $s->bindValue(':trab', (int)$idTrabajador, PDO::PARAM_INT);
        $s->execute();
        return $s->fetch();
    }

    public function eliminarMovimiento($idMovimiento, $idTrabajador) {
        $s = $this->conn->prepare("DELETE FROM trabajadores_movimientos WHERE id_movimiento = :id AND id_trabajador = :trab");
        $s->bindValue(':id', (int)$idMovimiento, PDO::PARAM_INT);
        $s->bindValue(':trab', (int)$idTrabajador, PDO::PARAM_INT);
        $s->execute();
        return true;
    }

    // ---------- Formato de inventario adjunto ----------

    public function guardarFormatoAdjunto($id, $rutaRelativa, $nombreOriginal) {
        $s = $this->conn->prepare("UPDATE {$this->table} SET formato_ruta = :ruta, formato_nombre = :nom WHERE id_trabajador = :id");
        $s->bindValue(':ruta', $rutaRelativa);
        $s->bindValue(':nom', $nombreOriginal);
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        return true;
    }

    public function eliminarFormatoAdjunto($id) {
        $t = $this->obtenerPorId($id);
        if (!$t) return null;
        $s = $this->conn->prepare("UPDATE {$this->table} SET formato_ruta = NULL, formato_nombre = NULL WHERE id_trabajador = :id");
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        return $t;
    }
}
