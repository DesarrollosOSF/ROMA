<?php
/**
 * Modelo de Nuevos Activos (inventario general: impresoras, computadores, etc.)
 * Campos: código, código serial, nombre, sede, estado, responsable general/secundario.
 */

require_once __DIR__ . '/../config/database.php';

class NuevoActivo {
    private $conn;
    private $table = 'nuevos_activos';

    public $id_nuevo_activo;
    public $id_categoria;
    public $id_trabajador;
    public $codigo;
    public $codigo_serial;
    public $codigo_placa;
    public $fecha_ingreso;
    public $codigo_cuenta_contable;
    public $codigo_grupo_activo_fijo;
    public $descripcion;
    public $valor;
    public $nombre;
    public $sede;
    public $estado = 'operativo';
    public $responsable_general;
    public $usuario_creacion;
    public $origen_auditoria = 'web';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!$this->conn) {
            throw new Exception("No se pudo establecer conexión con la base de datos");
        }
    }

    public static function estados() {
        return [
            'operativo' => 'Operativo',
            'en_reparacion' => 'En reparación',
            'fuera_servicio' => 'Fuera de servicio',
            'en_baja' => 'En baja',
        ];
    }

    public static function camposAuditables() {
        return [
            'id_categoria' => 'Categoría',
            'id_trabajador' => 'Trabajador responsable',
            'codigo' => 'Código',
            'codigo_serial' => 'Código serial',
            'codigo_placa' => 'Código de placa',
            'fecha_ingreso' => 'Fecha de ingreso',
            'codigo_cuenta_contable' => 'Código cuenta contable',
            'codigo_grupo_activo_fijo' => 'Código grupo activo fijo',
            'descripcion' => 'Descripción',
            'valor' => 'Valor',
            'nombre' => 'Nombre',
            'sede' => 'Sede',
            'estado' => 'Estado',
            'responsable_general' => 'Jefe inmediato',
        ];
    }

    private $cacheTablaTrab = null;

    private function tablaTrabajadoresExiste() {
        try {
            if ($this->cacheTablaTrab === null) {
                $st = $this->conn->query("SHOW TABLES LIKE 'trabajadores_responsables'");
                $this->cacheTablaTrab = ($st && $st->fetch()) ? true : false;
            }
            return $this->cacheTablaTrab;
        } catch (Exception $e) {
            return false;
        }
    }

    public function listar($filtros = []) {
        try {
            $conTrab = $this->tablaTrabajadoresExiste();
            $selectTrab = $conTrab ? ", t.nombre_completo AS trabajador_nombre, t.documento_identidad AS trabajador_documento" : "";
            $joinTrab = $conTrab ? " LEFT JOIN trabajadores_responsables t ON t.id_trabajador = n.id_trabajador" : "";
            $query = "SELECT n.*, c.nombre AS categoria_nombre, c.clave AS categoria_clave, c.icono AS categoria_icono, c.color AS categoria_color
                      $selectTrab
                      FROM {$this->table} n
                      INNER JOIN categorias_inventario c ON c.id_categoria = n.id_categoria
                      $joinTrab
                      WHERE n.activo = 1";
            $params = [];
            if (!empty($filtros['id_categoria'])) {
                $query .= " AND n.id_categoria = :cat";
                $params[':cat'] = (int)$filtros['id_categoria'];
            }
            if (!empty($filtros['estado'])) {
                $query .= " AND n.estado = :est";
                $params[':est'] = $filtros['estado'];
            }
            if (!empty($filtros['sede'])) {
                $query .= " AND n.sede LIKE :sede";
                $params[':sede'] = '%' . $filtros['sede'] . '%';
            }
            if (!empty($filtros['id_trabajador']) && $conTrab) {
                $query .= " AND n.id_trabajador = :trab";
                $params[':trab'] = (int)$filtros['id_trabajador'];
            }
            if (!empty($filtros['busqueda'])) {
                $b = '%' . $filtros['busqueda'] . '%';
                if ($this->tieneColumna('codigo_placa')) {
                    $query .= " AND (n.nombre LIKE :b1 OR n.codigo LIKE :b2 OR n.codigo_serial LIKE :b3
                                OR n.responsable_general LIKE :b4 OR n.codigo_placa LIKE :b5)";
                    $params[':b5'] = $b;
                } else {
                    $query .= " AND (n.nombre LIKE :b1 OR n.codigo LIKE :b2 OR n.codigo_serial LIKE :b3
                                OR n.responsable_general LIKE :b4)";
                }
                $params[':b1'] = $b;
                $params[':b2'] = $b;
                $params[':b3'] = $b;
                $params[':b4'] = $b;
            }
            $query .= " ORDER BY n.fecha_creacion DESC";
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
                $stmt->bindValue($k, $v, in_array($k, [':limit', ':offset', ':cat', ':trab'], true) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $res = $stmt->fetchAll();
            return is_array($res) ? $res : [];
        } catch (Exception $e) {
            error_log("NuevoActivo::listar: " . $e->getMessage());
            return [];
        }
    }

    public function contar($filtros = []) {
        try {
            $query = "SELECT COUNT(*) AS total FROM {$this->table} n WHERE n.activo = 1";
            $params = [];
            if (!empty($filtros['id_categoria'])) {
                $query .= " AND n.id_categoria = :cat";
                $params[':cat'] = (int)$filtros['id_categoria'];
            }
            if (!empty($filtros['estado'])) {
                $query .= " AND n.estado = :est";
                $params[':est'] = $filtros['estado'];
            }
            if (!empty($filtros['busqueda'])) {
                $query .= " AND (n.nombre LIKE :b1 OR n.codigo LIKE :b2 OR n.codigo_serial LIKE :b3)";
                $b = '%' . $filtros['busqueda'] . '%';
                $params[':b1'] = $b;
                $params[':b2'] = $b;
                $params[':b3'] = $b;
            }
            $stmt = $this->conn->prepare($query);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            return (int)($stmt->fetch()['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function listarPorTrabajador($idTrabajador) {
        if (!$this->tieneColumna('id_trabajador')) return [];
        return $this->listar(['id_trabajador' => (int)$idTrabajador, 'limit' => 2000]);
    }

    public function obtenerPorId($id) {
        $conTrab = $this->tablaTrabajadoresExiste();
        $selectTrab = $conTrab ? ", t.nombre_completo AS trabajador_nombre, t.documento_identidad AS trabajador_documento" : "";
        $joinTrab = $conTrab ? " LEFT JOIN trabajadores_responsables t ON t.id_trabajador = n.id_trabajador" : "";
        $q = "SELECT n.*, c.nombre AS categoria_nombre, c.clave AS categoria_clave
              $selectTrab
              FROM {$this->table} n
              INNER JOIN categorias_inventario c ON c.id_categoria = n.id_categoria
              $joinTrab
              WHERE n.id_nuevo_activo = :id AND n.activo = 1 LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        $activo = $s->fetch();
        if ($activo) {
            $activo['adjuntos'] = $this->obtenerAdjuntos($id);
        }
        return $activo;
    }

    private $cacheColumnas = null;

    private function tieneColumna($col) {
        try {
            if ($this->cacheColumnas === null) {
                $st = $this->conn->query("SHOW COLUMNS FROM {$this->table}");
                $cols = $st ? $st->fetchAll(PDO::FETCH_COLUMN, 0) : [];
                $this->cacheColumnas = is_array($cols) ? $cols : [];
            }
            return in_array($col, $this->cacheColumnas, true);
        } catch (Exception $e) {
            return false;
        }
    }

    public function crear() {
        // Los 5 campos nuevos solo se incluyen si la migración ya fue aplicada
        $conNuevos = $this->tieneColumna('fecha_ingreso');
        $conTrab = $this->tieneColumna('id_trabajador');
        $conRespSec = $this->tieneColumna('responsable_secundario');
        $conPlaca = $this->tieneColumna('codigo_placa');
        $q = "INSERT INTO {$this->table}
              (id_categoria, " . ($conTrab ? "id_trabajador, " : "") . "codigo, codigo_serial, " . ($conPlaca ? "codigo_placa, " : "") . "nombre, "
              . ($conNuevos ? "fecha_ingreso, codigo_cuenta_contable, codigo_grupo_activo_fijo, descripcion, valor, " : "")
              . "sede, estado, responsable_general, " . ($conRespSec ? "responsable_secundario, " : "") . "usuario_creacion)
              VALUES (:cat, " . ($conTrab ? ":trab, " : "") . ":cod, :ser, " . ($conPlaca ? ":placa, " : "") . ":nom, "
              . ($conNuevos ? ":fing, :ccc, :cgaf, :desc, :valor, " : "")
              . ":sede, :est, :rg, " . ($conRespSec ? ":rs, " : "") . ":u)";
        $s = $this->conn->prepare($q);
        $s->bindValue(':cat', (int)$this->id_categoria, PDO::PARAM_INT);
        if ($conTrab) {
            $s->bindValue(':trab', !empty($this->id_trabajador) ? (int)$this->id_trabajador : null, !empty($this->id_trabajador) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $s->bindValue(':cod', trim($this->codigo));
        $s->bindValue(':ser', trim($this->codigo_serial ?? '') ?: null);
        if ($conPlaca) {
            $s->bindValue(':placa', trim($this->codigo_placa ?? '') ?: null);
        }
        $s->bindValue(':nom', trim($this->nombre));
        if ($conNuevos) {
            $s->bindValue(':fing', $this->fecha_ingreso ?: null);
            $s->bindValue(':ccc', trim($this->codigo_cuenta_contable ?? '') ?: null);
            $s->bindValue(':cgaf', trim($this->codigo_grupo_activo_fijo ?? '') ?: null);
            $s->bindValue(':desc', trim($this->descripcion ?? '') ?: null);
            $s->bindValue(':valor', ($this->valor === '' || $this->valor === null) ? null : $this->valor);
        }
        $s->bindValue(':sede', trim($this->sede ?? '') ?: null);
        $s->bindValue(':est', $this->estado ?: 'operativo');
        $s->bindValue(':rg', trim($this->responsable_general ?? '') ?: null);
        if ($conRespSec) {
            // Compatibilidad con BD sin migrar: la columna existe pero el campo ya no se usa
            $s->bindValue(':rs', null);
        }
        $s->bindValue(':u', $this->usuario_creacion ?: null, $this->usuario_creacion ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->execute();
        $this->id_nuevo_activo = (int)$this->conn->lastInsertId();
        $this->auditar('INSERT', $this->id_nuevo_activo, 'registro', null, trim($this->nombre) . ' | ' . trim($this->codigo));
        return true;
    }

    public function actualizar() {
        // Snapshot previo para auditoría por campo
        $prev = null;
        try {
            $sp = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_nuevo_activo = :id LIMIT 1");
            $sp->bindValue(':id', (int)$this->id_nuevo_activo, PDO::PARAM_INT);
            $sp->execute();
            $prev = $sp->fetch() ?: null;
        } catch (Exception $e) {
            $prev = null;
        }

        $conNuevos = $this->tieneColumna('fecha_ingreso');
        $conTrab = $this->tieneColumna('id_trabajador');
        $conRespSec = $this->tieneColumna('responsable_secundario');
        $conPlaca = $this->tieneColumna('codigo_placa');
        $q = "UPDATE {$this->table} SET id_categoria = :cat, "
              . ($conTrab ? "id_trabajador = :trab, " : "")
              . "codigo = :cod, codigo_serial = :ser, "
              . ($conPlaca ? "codigo_placa = :placa, " : "")
              . "nombre = :nom, "
              . ($conNuevos ? "fecha_ingreso = :fing, codigo_cuenta_contable = :ccc, codigo_grupo_activo_fijo = :cgaf, descripcion = :desc, valor = :valor, " : "")
              . "sede = :sede, estado = :est, responsable_general = :rg, "
              . ($conRespSec ? "responsable_secundario = :rs, " : "")
              . "usuario_actualizacion = :u
              WHERE id_nuevo_activo = :id";
        $s = $this->conn->prepare($q);
        $s->bindValue(':cat', (int)$this->id_categoria, PDO::PARAM_INT);
        if ($conTrab) {
            $s->bindValue(':trab', !empty($this->id_trabajador) ? (int)$this->id_trabajador : null, !empty($this->id_trabajador) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $s->bindValue(':cod', trim($this->codigo));
        $s->bindValue(':ser', trim($this->codigo_serial ?? '') ?: null);
        if ($conPlaca) {
            $s->bindValue(':placa', trim($this->codigo_placa ?? '') ?: null);
        }
        $s->bindValue(':nom', trim($this->nombre));
        if ($conNuevos) {
            $s->bindValue(':fing', $this->fecha_ingreso ?: null);
            $s->bindValue(':ccc', trim($this->codigo_cuenta_contable ?? '') ?: null);
            $s->bindValue(':cgaf', trim($this->codigo_grupo_activo_fijo ?? '') ?: null);
            $s->bindValue(':desc', trim($this->descripcion ?? '') ?: null);
            $s->bindValue(':valor', ($this->valor === '' || $this->valor === null) ? null : $this->valor);
        }
        $s->bindValue(':sede', trim($this->sede ?? '') ?: null);
        $s->bindValue(':est', $this->estado ?: 'operativo');
        $s->bindValue(':rg', trim($this->responsable_general ?? '') ?: null);
        if ($conRespSec) {
            // Compatibilidad con BD sin migrar: la columna existe pero el campo ya no se usa
            $s->bindValue(':rs', null);
        }
        $s->bindValue(':u', $this->usuario_creacion ?: null, $this->usuario_creacion ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->bindValue(':id', (int)$this->id_nuevo_activo, PDO::PARAM_INT);
        $s->execute();

        // Auditoría por campo
        $actuales = [
            'id_categoria' => $this->id_categoria,
            'id_trabajador' => !empty($this->id_trabajador) ? (int)$this->id_trabajador : '',
            'codigo' => trim($this->codigo),
            'codigo_serial' => trim($this->codigo_serial ?? ''),
            'codigo_placa' => trim($this->codigo_placa ?? ''),
            'fecha_ingreso' => trim($this->fecha_ingreso ?? ''),
            'codigo_cuenta_contable' => trim($this->codigo_cuenta_contable ?? ''),
            'codigo_grupo_activo_fijo' => trim($this->codigo_grupo_activo_fijo ?? ''),
            'descripcion' => trim($this->descripcion ?? ''),
            'valor' => trim((string)($this->valor ?? '')),
            'nombre' => trim($this->nombre),
            'sede' => trim($this->sede ?? ''),
            'estado' => $this->estado,
            'responsable_general' => trim($this->responsable_general ?? ''),
            // Nota: 'observaciones' y 'responsable_secundario' se eliminaron del módulo.
        ];
        // Campos que solo se auditan si la columna existe en BD
        $soloSiExiste = ['fecha_ingreso','codigo_cuenta_contable','codigo_grupo_activo_fijo','descripcion','valor','id_trabajador','responsable_secundario','codigo_placa'];
        $cambios = 0;
        if (is_array($prev)) {
            foreach ($actuales as $campo => $nuevo) {
                if (!array_key_exists($campo, $prev) && in_array($campo, $soloSiExiste, true)) {
                    continue;
                }
                $ant = trim((string)($prev[$campo] ?? ''));
                if ($ant !== trim((string)$nuevo)) {
                    $this->auditar('UPDATE', $this->id_nuevo_activo, $campo, $prev[$campo] ?? null, $nuevo);
                    $cambios++;
                }
            }
        }
        if ($cambios === 0) {
            $this->auditar('UPDATE', $this->id_nuevo_activo, 'registro', null, 'Guardado sin cambios');
        }
        return true;
    }

    public function eliminar() {
        $prev = $this->obtenerPorId($this->id_nuevo_activo);
        $snap = $prev ? trim(($prev['nombre'] ?? '') . ' | ' . ($prev['codigo'] ?? '')) : null;
        $q = "UPDATE {$this->table} SET activo = 0, usuario_actualizacion = :u WHERE id_nuevo_activo = :id";
        $s = $this->conn->prepare($q);
        $s->bindValue(':u', $this->usuario_creacion ?: null, $this->usuario_creacion ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->bindValue(':id', (int)$this->id_nuevo_activo, PDO::PARAM_INT);
        $s->execute();
        $this->auditar('DELETE', $this->id_nuevo_activo, 'registro', $snap, 'Activo dado de baja (eliminado lógico)');
        return true;
    }

    private function auditar($tipo, $id, $campo = null, $anterior = null, $nuevo = null) {
        try {
            $q = "INSERT INTO nuevos_activos_auditoria
                  (id_nuevo_activo, campo_modificado, valor_anterior, valor_nuevo, usuario_modificacion, tipo_operacion, origen)
                  VALUES (:id, :campo, :ant, :nue, :u, :tipo, :origen)";
            $s = $this->conn->prepare($q);
            $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $s->bindValue(':campo', $campo);
            $s->bindValue(':ant', $anterior !== null ? (string)$anterior : null);
            $s->bindValue(':nue', $nuevo !== null ? (string)$nuevo : null);
            $s->bindValue(':u', $this->usuario_creacion ?: null, $this->usuario_creacion ? PDO::PARAM_INT : PDO::PARAM_STR);
            $s->bindValue(':tipo', $tipo);
            $s->bindValue(':origen', $this->origen_auditoria ?: 'web');
            $s->execute();
        } catch (Exception $e) {
            error_log("NuevoActivo::auditar: " . $e->getMessage());
        }
    }

    public function obtenerAuditoria($filtros = []) {
        try {
            $q = "SELECT a.*, u.nombre AS usuario_nombre, u.email AS usuario_email,
                         n.nombre AS activo_nombre, n.codigo AS activo_codigo, n.activo AS activo_vigente,
                         c.nombre AS categoria_nombre
                  FROM nuevos_activos_auditoria a
                  LEFT JOIN usuarios u ON u.id_usuario = a.usuario_modificacion
                  LEFT JOIN nuevos_activos n ON n.id_nuevo_activo = a.id_nuevo_activo
                  LEFT JOIN categorias_inventario c ON c.id_categoria = n.id_categoria
                  WHERE 1=1";
            $p = [];
            if (!empty($filtros['id_nuevo_activo'])) {
                $q .= " AND a.id_nuevo_activo = :nid";
                $p[':nid'] = (int)$filtros['id_nuevo_activo'];
            }
            if (!empty($filtros['tipo_operacion'])) {
                $q .= " AND a.tipo_operacion = :tipo";
                $p[':tipo'] = $filtros['tipo_operacion'];
            }
            if (!empty($filtros['id_usuario'])) {
                $q .= " AND a.usuario_modificacion = :u";
                $p[':u'] = (int)$filtros['id_usuario'];
            }
            if (!empty($filtros['busqueda'])) {
                $q .= " AND (n.nombre LIKE :b1 OR n.codigo LIKE :b2 OR u.nombre LIKE :b3 OR a.campo_modificado LIKE :b4)";
                $b = '%' . $filtros['busqueda'] . '%';
                $p[':b1'] = $b;
                $p[':b2'] = $b;
                $p[':b3'] = $b;
                $p[':b4'] = $b;
            }
            if (!empty($filtros['fecha_desde'])) {
                $q .= " AND DATE(a.fecha_modificacion) >= :desde";
                $p[':desde'] = $filtros['fecha_desde'];
            }
            if (!empty($filtros['fecha_hasta'])) {
                $q .= " AND DATE(a.fecha_modificacion) <= :hasta";
                $p[':hasta'] = $filtros['fecha_hasta'];
            }
            $q .= " ORDER BY a.fecha_modificacion DESC, a.id_auditoria DESC";
            if (!empty($filtros['limit'])) {
                $q .= " LIMIT :limit";
                $p[':limit'] = (int)$filtros['limit'];
                if (isset($filtros['offset'])) {
                    $q .= " OFFSET :offset";
                    $p[':offset'] = (int)$filtros['offset'];
                }
            }
            $s = $this->conn->prepare($q);
            foreach ($p as $k => $v) {
                $s->bindValue($k, $v, in_array($k, [':limit', ':offset', ':nid', ':u'], true) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $s->execute();
            $res = $s->fetchAll();
            return is_array($res) ? $res : [];
        } catch (Exception $e) {
            error_log("NuevoActivo::obtenerAuditoria: " . $e->getMessage());
            return [];
        }
    }

    public function contarAuditoria($filtros = []) {
        try {
            $q = "SELECT COUNT(*) AS total FROM nuevos_activos_auditoria a
                  LEFT JOIN usuarios u ON u.id_usuario = a.usuario_modificacion
                  LEFT JOIN nuevos_activos n ON n.id_nuevo_activo = a.id_nuevo_activo
                  WHERE 1=1";
            $p = [];
            if (!empty($filtros['tipo_operacion'])) {
                $q .= " AND a.tipo_operacion = :tipo";
                $p[':tipo'] = $filtros['tipo_operacion'];
            }
            if (!empty($filtros['id_usuario'])) {
                $q .= " AND a.usuario_modificacion = :u";
                $p[':u'] = (int)$filtros['id_usuario'];
            }
            if (!empty($filtros['busqueda'])) {
                $q .= " AND (n.nombre LIKE :b1 OR n.codigo LIKE :b2 OR u.nombre LIKE :b3)";
                $b = '%' . $filtros['busqueda'] . '%';
                $p[':b1'] = $b;
                $p[':b2'] = $b;
                $p[':b3'] = $b;
            }
            $s = $this->conn->prepare($q);
            foreach ($p as $k => $v) $s->bindValue($k, $v);
            $s->execute();
            return (int)($s->fetch()['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    // Reporte general para el panel: conteos por categoría, estado y sede
    public function reporteGeneral() {
        try {
            $porCategoria = $this->conn->query(
                "SELECT c.id_categoria, c.nombre, c.clave, c.icono, c.color, COUNT(n.id_nuevo_activo) AS total
                 FROM categorias_inventario c
                 LEFT JOIN nuevos_activos n ON n.id_categoria = c.id_categoria AND n.activo = 1
                 WHERE c.activo = 1 GROUP BY c.id_categoria ORDER BY total DESC, c.nombre ASC"
            )->fetchAll();
            $porEstado = $this->conn->query(
                "SELECT estado, COUNT(*) AS total FROM nuevos_activos WHERE activo = 1 GROUP BY estado"
            )->fetchAll();
            $porSede = $this->conn->query(
                "SELECT COALESCE(NULLIF(sede,''),'(Sin sede)') AS sede, COUNT(*) AS total
                 FROM nuevos_activos WHERE activo = 1 GROUP BY sede ORDER BY total DESC LIMIT 10"
            )->fetchAll();
            $total = (int)$this->conn->query("SELECT COUNT(*) AS t FROM nuevos_activos WHERE activo = 1")->fetch()['t'];
            return [
                'total' => $total,
                'porCategoria' => is_array($porCategoria) ? $porCategoria : [],
                'porEstado' => is_array($porEstado) ? $porEstado : [],
                'porSede' => is_array($porSede) ? $porSede : [],
            ];
        } catch (Exception $e) {
            error_log("NuevoActivo::reporteGeneral: " . $e->getMessage());
            return ['total' => 0, 'porCategoria' => [], 'porEstado' => [], 'porSede' => []];
        }
    }

    public function existeCodigo($codigo, $excluirId = null) {
        try {
            $q = "SELECT COUNT(*) AS t FROM nuevos_activos WHERE codigo = :c";
            if ($excluirId) $q .= " AND id_nuevo_activo <> :ex";
            $s = $this->conn->prepare($q);
            $s->bindValue(':c', trim($codigo));
            if ($excluirId) $s->bindValue(':ex', (int)$excluirId, PDO::PARAM_INT);
            $s->execute();
            return ((int)$s->fetch()['t']) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function limpiarParaImportacion() {
        foreach (['id_categoria','id_trabajador','codigo','codigo_serial','codigo_placa','fecha_ingreso','codigo_cuenta_contable','codigo_grupo_activo_fijo','descripcion','valor','nombre','sede','estado','responsable_general'] as $p) {
            $this->$p = null;
        }
        $this->id_nuevo_activo = null;
        $this->estado = 'operativo';
    }

    // ---------- Adjuntos (imágenes y archivos) ----------

    public static function esImagen($nombreArchivo) {
        $ext = strtolower(pathinfo((string)$nombreArchivo, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function obtenerAdjuntos($idNuevoActivo) {
        try {
            $q = "SELECT a.*, u.nombre AS usuario_nombre
                  FROM nuevos_activos_adjuntos a
                  LEFT JOIN usuarios u ON u.id_usuario = a.usuario_subida
                  WHERE a.id_nuevo_activo = :id
                  ORDER BY a.fecha_subida DESC";
            $s = $this->conn->prepare($q);
            $s->bindValue(':id', (int)$idNuevoActivo, PDO::PARAM_INT);
            $s->execute();
            $res = $s->fetchAll();
            return is_array($res) ? $res : [];
        } catch (Exception $e) {
            // Tabla aún no creada: devolver vacío en lugar de romper la ficha
            error_log("NuevoActivo::obtenerAdjuntos: " . $e->getMessage());
            return [];
        }
    }

    public function contarAdjuntosPorActivos(array $ids) {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) return [];
        try {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $s = $this->conn->prepare(
                "SELECT id_nuevo_activo, COUNT(*) AS total FROM nuevos_activos_adjuntos WHERE id_nuevo_activo IN ($ph) GROUP BY id_nuevo_activo"
            );
            foreach ($ids as $i => $v) {
                $s->bindValue($i + 1, $v, PDO::PARAM_INT);
            }
            $s->execute();
            $out = [];
            foreach ($s->fetchAll() as $row) {
                $out[(int)$row['id_nuevo_activo']] = (int)$row['total'];
            }
            return $out;
        } catch (Exception $e) {
            return [];
        }
    }

    public function agregarAdjunto($idNuevoActivo, $nombreOriginal, $rutaRelativa, $tipo, $tamano, $usuarioId, $descripcion = null) {
        $q = "INSERT INTO nuevos_activos_adjuntos
              (id_nuevo_activo, nombre_archivo, ruta_archivo, tipo_archivo, tamano_archivo, descripcion, usuario_subida)
              VALUES (:id, :nom, :ruta, :tipo, :tam, :desc, :u)";
        $s = $this->conn->prepare($q);
        $s->bindValue(':id', (int)$idNuevoActivo, PDO::PARAM_INT);
        $s->bindValue(':nom', $nombreOriginal);
        $s->bindValue(':ruta', $rutaRelativa);
        $s->bindValue(':tipo', $tipo ?: null);
        $s->bindValue(':tam', $tamano ? (int)$tamano : null, $tamano ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->bindValue(':desc', trim((string)($descripcion ?? '')) ?: null);
        $s->bindValue(':u', $usuarioId ?: null, $usuarioId ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->execute();
        return (int)$this->conn->lastInsertId();
    }

    public function obtenerAdjuntoPorId($idAdjunto) {
        $s = $this->conn->prepare("SELECT * FROM nuevos_activos_adjuntos WHERE id_adjunto = :id LIMIT 1");
        $s->bindValue(':id', (int)$idAdjunto, PDO::PARAM_INT);
        $s->execute();
        return $s->fetch();
    }

    public function eliminarAdjunto($idAdjunto) {
        $adj = $this->obtenerAdjuntoPorId($idAdjunto);
        if (!$adj) return null;
        $s = $this->conn->prepare("DELETE FROM nuevos_activos_adjuntos WHERE id_adjunto = :id");
        $s->bindValue(':id', (int)$idAdjunto, PDO::PARAM_INT);
        $s->execute();
        return $adj;
    }

    /** Deja rastro en auditoría cuando se sube o elimina un adjunto */
    public function auditarAdjunto($idNuevoActivo, $usuarioId, $accion, $nombreArchivo) {
        $prev = $this->usuario_creacion;
        $prevOrigen = $this->origen_auditoria;
        $this->usuario_creacion = $usuarioId;
        $this->origen_auditoria = 'web';
        if ($accion === 'eliminar') {
            $this->auditar('UPDATE', $idNuevoActivo, 'adjunto', $nombreArchivo, 'Adjunto eliminado');
        } else {
            $this->auditar('UPDATE', $idNuevoActivo, 'adjunto', null, 'Adjunto agregado: ' . $nombreArchivo);
        }
        $this->usuario_creacion = $prev;
        $this->origen_auditoria = $prevOrigen;
    }
}
