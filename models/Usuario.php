<?php
/**
 * Modelo de Usuario
 * Sistema ROMA - Autenticación
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class Usuario {
    private $conn;
    private $table = 'usuarios';

    public $id_usuario;
    public $nombre;
    public $email;
    public $password_hash;
    public $rol;
    public $activo;

    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            if (!$this->conn) {
                throw new Exception("No se pudo establecer conexión con la base de datos");
            }
        } catch (Exception $e) {
            error_log("Error en Usuario::__construct(): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Autenticar usuario por email y contraseña
     */
    public function autenticar($email, $password) {
        $query = "SELECT id_usuario, nombre, email, password_hash, rol, activo 
                  FROM " . $this->table . " 
                  WHERE email = :email AND activo = 1 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            $this->id_usuario = $usuario['id_usuario'];
            $this->nombre = $usuario['nombre'];
            $this->email = $usuario['email'];
            $this->rol = $usuario['rol'];
            $this->activo = $usuario['activo'];
            return true;
        }

        return false;
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT id_usuario, nombre, email, rol, activo, telefono, cargo, area, fecha_creacion
                  FROM " . $this->table . " 
                  WHERE id_usuario = :id 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }
    
    /**
     * Obtener usuario por email
     */
    public function obtenerPorEmail($email) {
        $query = "SELECT id_usuario, nombre, email, rol, activo 
                  FROM " . $this->table . " 
                  WHERE email = :email 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function tieneRol($rol) {
        return $this->rol === $rol;
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function esAdministrador() {
        return $this->rol === 'administrador';
    }

    /**
     * Obtener todos los usuarios
     */
    public function listar($filtros = []) {
        try {
            $query = "SELECT id_usuario, nombre, email, rol, telefono, cargo, area, activo, fecha_creacion 
                      FROM " . $this->table . " 
                      WHERE 1=1";
            
            $params = [];

            if (!empty($filtros['rol'])) {
                $query .= " AND rol = :rol";
                $params[':rol'] = $filtros['rol'];
            }

            if (!empty($filtros['activo'])) {
                $query .= " AND activo = :activo";
                $params[':activo'] = $filtros['activo'];
            } else {
                $query .= " AND activo = 1";
            }

            if (!empty($filtros['busqueda'])) {
                $busqueda = '%' . $filtros['busqueda'] . '%';
                $query .= " AND (nombre LIKE :busqueda1 OR email LIKE :busqueda2)";
                $params[':busqueda1'] = $busqueda;
                $params[':busqueda2'] = $busqueda;
            }

            $query .= " ORDER BY nombre";

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $result = $stmt->fetchAll();
            return is_array($result) ? $result : [];
        } catch (Exception $e) {
            error_log("Error en Usuario::listar(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios por rol
     */
    public function obtenerPorRol($rol) {
        try {
            $query = "SELECT id_usuario, nombre, email, rol, cargo, area 
                      FROM " . $this->table . " 
                      WHERE rol = :rol AND activo = 1 
                      ORDER BY nombre";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':rol', $rol);
            $stmt->execute();

            $result = $stmt->fetchAll();
            return is_array($result) ? $result : [];
        } catch (Exception $e) {
            error_log("Error en Usuario::obtenerPorRol(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener operarios disponibles
     */
    public function obtenerOperarios() {
        return $this->obtenerPorRol('operario');
    }

    /**
     * Verificar permisos según rol
     */
    public static function tienePermiso($rol, $accion) {
        $permisos = [
            'administrador' => [
                'ver_activos' => true,
                'crear_activos' => true,
                'editar_activos' => true,
                'eliminar_activos' => true,
                'ver_ordenes' => true,
                'crear_ordenes' => true,
                'asignar_ordenes' => true,
                'editar_ordenes' => true,
                'eliminar_ordenes' => true,
                'cambiar_estado_orden' => true,
                'reasignar_orden' => true,
                'ver_solicitudes' => true,
                'asignar_solicitudes' => true,
                'rechazar_solicitudes' => true,
                'ver_repuestos' => true,
                'gestionar_repuestos' => true,
                'ver_reportes' => true,
                'gestionar_usuarios' => true,
                'configurar_sistema' => true
            ],
            'jefe' => [
                'ver_activos' => false, // No puede ver lista, pero puede seleccionarlos al crear orden
                'ver_lista_activos' => false,
                'crear_activos' => false,
                'editar_activos' => false,
                'eliminar_activos' => false,
                'ver_ordenes' => true,
                'crear_ordenes' => true, // Puede crear órdenes
                'asignar_ordenes' => false,
                'editar_ordenes' => false, // No puede editar
                'eliminar_ordenes' => false, // No puede eliminar
                'ver_mis_ordenes' => true, // Solo las que creó
                'ver_solicitudes' => true,
                'crear_solicitudes' => true,
                'ver_mis_solicitudes' => true,
                'ver_repuestos' => false,
                'ver_reportes' => false,
                'gestionar_usuarios' => false,
                'configurar_sistema' => false
            ],
            'director' => [
                'ver_activos' => false, // No puede ver lista, pero puede seleccionarlos al crear orden
                'ver_lista_activos' => false,
                'crear_activos' => false,
                'editar_activos' => false,
                'eliminar_activos' => false,
                'ver_ordenes' => true,
                'crear_ordenes' => true, // Puede crear órdenes
                'asignar_ordenes' => false,
                'editar_ordenes' => false, // No puede editar
                'eliminar_ordenes' => false, // No puede eliminar
                'ver_mis_ordenes' => true, // Solo las que creó
                'ver_solicitudes' => true,
                'crear_solicitudes' => true,
                'ver_mis_solicitudes' => true,
                'ver_repuestos' => false,
                'ver_reportes' => false,
                'gestionar_usuarios' => false,
                'configurar_sistema' => false
            ],
            'operario' => [
                'ver_activos' => true, // Puede ver activos
                'ver_lista_activos' => true,
                'crear_activos' => false,
                'editar_activos' => false, // No puede editar
                'eliminar_activos' => false,
                'ver_ordenes' => true,
                'ver_mis_ordenes' => true, // Solo las asignadas a él
                'crear_ordenes' => true, // Puede crear órdenes
                'editar_ordenes' => false, // No puede editar
                'eliminar_ordenes' => false, // No puede eliminar
                'cambiar_estado_orden' => true, // Puede cambiar estado
                'reasignar_orden' => true, // Puede reasignar a otro operario
                'subir_evidencias' => true,
                'registrar_comentarios' => true,
                'ver_repuestos' => false,
                'gestionar_usuarios' => false,
                'configurar_sistema' => false
            ]
        ];

        return isset($permisos[$rol][$accion]) && $permisos[$rol][$accion] === true;
    }

    /**
     * Verificar si puede ver todas las órdenes o solo las propias
     */
    public static function puedeVerTodasOrdenes($rol) {
        return in_array($rol, ['administrador', 'jefe', 'director']);
    }

    /**
     * Verificar si puede crear solicitudes
     */
    public static function puedeCrearSolicitudes($rol) {
        return in_array($rol, ['jefe', 'director']);
    }

    /**
     * Verificar si puede asignar órdenes
     */
    public static function puedeAsignarOrdenes($rol) {
        return $rol === 'administrador';
    }
}

