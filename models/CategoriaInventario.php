<?php
/**
 * Modelo de Categorías de Inventario (sección Nuevos Activos)
 * Las categorías son dinámicas: se crean desde el panel.
 */

require_once __DIR__ . '/../config/database.php';

class CategoriaInventario {
    private $conn;
    private $table = 'categorias_inventario';

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

    public function listar($soloActivas = true) {
        try {
            $query = "SELECT c.*, COUNT(n.id_nuevo_activo) AS total_activos
                      FROM {$this->table} c
                      LEFT JOIN nuevos_activos n ON n.id_categoria = c.id_categoria AND n.activo = 1";
            if ($soloActivas) {
                $query .= " WHERE c.activo = 1";
            }
            $query .= " GROUP BY c.id_categoria ORDER BY c.nombre ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $res = $stmt->fetchAll();
            return is_array($res) ? $res : [];
        } catch (Exception $e) {
            error_log("CategoriaInventario::listar: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_categoria = :id LIMIT 1");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function obtenerPorClave($clave) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE clave = :c LIMIT 1");
        $stmt->bindValue(':c', $clave);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function contarActivos($idCategoria) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM nuevos_activos WHERE id_categoria = :id AND activo = 1");
        $stmt->bindValue(':id', (int)$idCategoria, PDO::PARAM_INT);
        $stmt->execute();
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public function actualizar($id, $nombre, $descripcion, $icono, $color) {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            throw new Exception('Grupo de activos no encontrado.');
        }
        $nombre = trim($nombre ?? '');
        if ($nombre === '') {
            throw new Exception('El nombre del grupo de activos es obligatorio.');
        }
        $colores = ['primary','success','warning','danger','info','secondary'];
        if (!in_array($color, $colores, true)) $color = 'primary';
        $icono = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($icono ?? 'box')));
        if ($icono === '') $icono = 'box';

        // Si cambió el nombre, regenerar clave evitando colisiones (excluyendo la propia)
        $nuevaClave = $actual['clave'];
        if (strcasecmp($actual['nombre'], $nombre) !== 0) {
            $nuevaClave = self::slug($nombre);
            if ($nuevaClave === '') {
                throw new Exception('No se pudo generar la clave de la categoría.');
            }
            $base = $nuevaClave;
            $i = 2;
            $existe = $this->obtenerPorClave($nuevaClave);
            while ($existe && (int)$existe['id_categoria'] !== (int)$id) {
                $nuevaClave = $base . '_' . $i;
                $i++;
                $existe = $this->obtenerPorClave($nuevaClave);
            }
        }

        $q = "UPDATE {$this->table} SET nombre = :n, clave = :c, descripcion = :d, icono = :i, color = :co WHERE id_categoria = :id";
        $s = $this->conn->prepare($q);
        $s->bindValue(':n', $nombre);
        $s->bindValue(':c', $nuevaClave);
        $s->bindValue(':d', trim($descripcion ?? '') ?: null);
        $s->bindValue(':i', $icono);
        $s->bindValue(':co', $color);
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        return true;
    }

    public function eliminar($id) {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            throw new Exception('Grupo de activos no encontrado.');
        }
        $total = $this->contarActivos($id);
        if ($total > 0) {
            throw new Exception("No se puede eliminar: tiene $total activo(s) asociado(s). Reasigne o elimine esos activos primero.");
        }
        // Borrado lógico para conservar historial de carga masiva/auditoría
        $s = $this->conn->prepare("UPDATE {$this->table} SET activo = 0 WHERE id_categoria = :id");
        $s->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $s->execute();
        return true;
    }

    public function crear($nombre, $descripcion, $icono, $color, $usuarioId) {
        $nombre = trim($nombre ?? '');
        if ($nombre === '') {
            throw new Exception('El nombre del grupo de activos es obligatorio.');
        }
        $clave = self::slug($nombre);
        if ($clave === '') {
            throw new Exception('No se pudo generar la clave del grupo de activos.');
        }
        // Si la clave existe, agregar sufijo
        $base = $clave;
        $i = 2;
        while ($this->obtenerPorClave($clave)) {
            $clave = $base . '_' . $i;
            $i++;
        }
        $colores = ['primary','success','warning','danger','info','secondary'];
        if (!in_array($color, $colores, true)) $color = 'primary';
        $icono = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($icono ?? 'box')));
        if ($icono === '') $icono = 'box';

        $q = "INSERT INTO {$this->table} (nombre, clave, descripcion, icono, color, usuario_creacion)
              VALUES (:n, :c, :d, :i, :co, :u)";
        $s = $this->conn->prepare($q);
        $s->bindValue(':n', $nombre);
        $s->bindValue(':c', $clave);
        $s->bindValue(':d', $descripcion ?: null);
        $s->bindValue(':i', $icono);
        $s->bindValue(':co', $color);
        $s->bindValue(':u', $usuarioId ?: null, $usuarioId ? PDO::PARAM_INT : PDO::PARAM_STR);
        $s->execute();
        return (int)$this->conn->lastInsertId();
    }
}
