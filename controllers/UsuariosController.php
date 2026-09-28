<?php
/**
 * Controlador de Usuarios
 * Sistema ROMA - Gestión de Usuarios
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class UsuariosController {
    private $usuario;

    public function __construct() {
        // Administradores y super administradores pueden gestionar usuarios
        AuthController::verificarRol(['administrador', 'superadmin']);
        
        $this->usuario = new Usuario();
    }

    /**
     * Mostrar lista de usuarios
     */
    public function index() {
        $filtros = [
            'rol' => $_GET['rol'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];

        $usuarios = $this->usuario->listar($filtros);
        $roles = USER_ROLES;

        require_once __DIR__ . '/../views/usuarios/index.php';
    }

    /**
     * Mostrar formulario de nuevo usuario
     */
    public function crear() {
        $roles = USER_ROLES;
        require_once __DIR__ . '/../views/usuarios/form.php';
    }

    /**
     * Procesar creación de usuario
     */
    public function guardar() {
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $rol = $_POST['rol'] ?? 'operario';
        $telefono = $_POST['telefono'] ?? '';
        $cargo = $_POST['cargo'] ?? '';
        $area = $_POST['area'] ?? '';

        if (empty($nombre) || empty($email) || empty($password)) {
            $_SESSION['mensaje'] = 'Por favor, complete todos los campos requeridos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=usuarios&subaction=crear');
            exit;
        }

        $database = new Database();
        $conn = $database->getConnection();

        // Verificar si el email ya existe
        $query = "SELECT id_usuario FROM usuarios WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->fetch()) {
            $_SESSION['mensaje'] = 'El email ya está registrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=usuarios&subaction=crear');
            exit;
        }

        // Crear usuario
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        $query = "INSERT INTO usuarios (nombre, email, password_hash, rol, telefono, cargo, area) 
                  VALUES (:nombre, :email, :password_hash, :rol, :telefono, :cargo, :area)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':cargo', $cargo);
        $stmt->bindParam(':area', $area);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Usuario creado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=usuarios');
        } else {
            $_SESSION['mensaje'] = 'Error al crear el usuario';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=usuarios&subaction=crear');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function editar($id) {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT id_usuario, nombre, email, rol, telefono, cargo, area, activo 
                  FROM usuarios 
                  WHERE id_usuario = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            $_SESSION['mensaje'] = 'Usuario no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=usuarios');
            exit;
        }

        $roles = USER_ROLES;
        require_once __DIR__ . '/../views/usuarios/form.php';
    }

    /**
     * Procesar actualización
     */
    public function actualizar($id) {
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $rol = $_POST['rol'] ?? 'operario';
        $telefono = $_POST['telefono'] ?? '';
        $cargo = $_POST['cargo'] ?? '';
        $area = $_POST['area'] ?? '';
        /*$activo = isset($_POST['activo']) ? 1 : 0;*/
        $activo = $_POST['activo'];

        $database = new Database();
        $conn = $database->getConnection();

        // Verificar si el email ya existe en otro usuario
        $query = "SELECT id_usuario FROM usuarios WHERE email = :email AND id_usuario != :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetch()) {
            $_SESSION['mensaje'] = 'El email ya está registrado en otro usuario';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=usuarios&subaction=editar&id=' . $id);
            exit;
        }

        // Actualizar usuario
        $query = "UPDATE usuarios SET 
                  nombre = :nombre,
                  email = :email,
                  rol = :rol,
                  telefono = :telefono,
                  cargo = :cargo,
                  area = :area,
                  activo = :activo
                  WHERE id_usuario = :id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':cargo', $cargo);
        $stmt->bindParam(':area', $area);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);

        // Si se proporcionó nueva contraseña, actualizarla
        if (!empty($_POST['password'])) {
            $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $query = "UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id";
            $stmt2 = $conn->prepare($query);
            $stmt2->bindParam(':password_hash', $password_hash);
            $stmt2->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt2->execute();
        }

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Usuario actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=usuarios');
        } else {
            $_SESSION['mensaje'] = 'Error al actualizar el usuario';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=usuarios&subaction=editar&id=' . $id);
        }
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function eliminar($id) {
        // No permitir auto-eliminarse
        if ($id == $_SESSION['usuario_id']) {
            $_SESSION['mensaje'] = 'No puede eliminar su propio usuario';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=usuarios');
            exit;
        }

        $database = new Database();
        $conn = $database->getConnection();

        $query = "UPDATE usuarios SET activo = 0 WHERE id_usuario = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Usuario eliminado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar el usuario';
            $_SESSION['tipo_mensaje'] = 'error';
        }

        header('Location: index.php?action=usuarios');
    }
}

