<?php
/**
 * Modelo de Almacenes (bodegas: San Rafael, COL, ...).
 * Los activos con estado 'almacen' pertenecen a una bodega.
 */

require_once __DIR__ . '/../config/database.php';

class Almacen {
    private $conn;
    private $table = 'almacenes';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!$this->conn) {
            throw new Exception("No se pudo establecer conexión con la base de datos");
        }
    }

    public static function slug($nombre) {
        $slug = strtolower(trim($nombre));
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        return trim($slug, '_');
    }

    public function listar($soloActivos = true) {
        try {
            $query = "SELECT a.*, COUNT(n.id_nuevo_activo) AS total_activos
                      FROM {$this->table} a
                      LEFT JOIN nuevos_activos n ON n.id_almacen = a.id_almacen AND n.activo = 1 AND n.estado = 'almacen'";
            if ($soloActivos) {
                $query .= " WHERE a.activo = 1";
            }
            $query .= " GROUP BY a.id_almacen ORDER BY a.nombre ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $res = $stmt->fetchAll();
            return is_array($res) ? $res : [];
        } catch (Exception $e) {
            error_log("Almacen::listar: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_almacen = :id LIMIT 1");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function contarActivos($idAlmacen) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM nuevos_activos WHERE id_almacen = :id AND activo = 1 AND estado = 'almacen'");
        $stmt->bindValue(':id', (int)$idAlmacen, PDO::PARAM_INT);
        $stmt->execute();
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public function crear($nombre, $descripcion, $responsable, $usuarioId) {
        $nombre = trim($nombre ?? '');
        if ($nombre === '') {
            throw new Exception('El nombre del almacén es obligatorio.');
        }
        $clave = self::slug($nombre);
        if ($clave === '') {
            throw new Exception('No se pudo generar la clave del almacén.');
        }
        $base = $clave;
        $i = 2;
        $stmt = $this->conn->prepare("SELECT id_almacen FROM {$this->table} WHERE clave = :c LIMIT 1");
        $stmt->bindValue(':c', $clave);
        $stmt->execute();
        while ($stmt->fetch()) {
            $clave = $base . '_' . $i;
            $i++;
            $stmt->bindValue(':c', $clave);
            $stmt->execute();
        }
        $q = "INSERT INTO {$this->table} (nombre, clave, descripcion, responsable, usuario_creacion)
              VALUES (:n, :c, :d, :r, :u)";
        $s = $this->conn->prepare($q);
        $s->bindValue(':n', $nombre);
        $s->bindValue(':c', $clave);
        $s->bindValue(':d', trim($descripcion ?? '') ?: null);
        $s->bindValue(':r', trim($responsable ?? '') ?: null);
        $s->bindValue(':u', $usuarioId ?: null, $usuarioId ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->execute();
        return (int)$this->conn->lastInsertId();
    }

    public function actualizar($id, $nombre, $descripcion, $responsable) {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            throw new Exception('Almacén no encontrado.');
        }
        $nombre = trim($nombre ?? '');
        if ($nombre === '') {
            throw new Exception('El nombre del almacén es obligatorio.');
        }
        $q = "UPDATE {$this->table} SET nombre = :n, descripcion = :d, responsable = :r WHERE id_almacen = :id";
        $s = $this->conn->prepare($q);
        $s->bindValue(':n', $nombre);
        $s->bindValue(':d', trim($descripcion ?? '') ?: null);
        $s->bindValue(':r', trim($responsable ?? '') ?: null);
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        return true;
    }

    public function eliminar($id) {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            throw new Exception('Almacén no encontrado.');
        }
        if ($this->contarActivos($id) > 0) {
            throw new Exception('No se puede eliminar: tiene activos en este almacén. Retírelos primero.');
        }
        $s = $this->conn->prepare("UPDATE {$this->table} SET activo = 0 WHERE id_almacen = :id");
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        return true;
    }
}
