<?php
/**
 * Controlador de Perfil de Usuario
 * Sistema ROMA - Gestión de Perfil Personal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class PerfilController {
    private $usuario;

    public function __construct() {
        AuthController::verificarAutenticacion();
        $this->usuario = new Usuario();
    }

    /**
     * Mostrar perfil del usuario actual
     */
    public function index() {
        $id_usuario = $_SESSION['usuario_id'] ?? null;
        
        if (!$id_usuario) {
            $_SESSION['mensaje'] = 'Error: Usuario no identificado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }

        $usuario = $this->usuario->obtenerPorId($id_usuario);
        
        if (!$usuario) {
            $_SESSION['mensaje'] = 'Error: Usuario no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }

        require_once __DIR__ . '/../views/perfil/index.php';
    }

    /**
     * Actualizar perfil del usuario
     */
    public function actualizar() {
        $id_usuario = $_SESSION['usuario_id'] ?? null;
        
        if (!$id_usuario) {
            $_SESSION['mensaje'] = 'Error: Usuario no identificado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=perfil');
            exit;
        }

        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $cargo = $_POST['cargo'] ?? '';
        $area = $_POST['area'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($nombre) || empty($email)) {
            $_SESSION['mensaje'] = 'Nombre y email son obligatorios';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=perfil');
            exit;
        }

        // Verificar si el email ya existe en otro usuario
        $usuario_actual = $this->usuario->obtenerPorId($id_usuario);
        if ($usuario_actual['email'] !== $email) {
            $usuario_existente = $this->usuario->obtenerPorEmail($email);
            if ($usuario_existente && $usuario_existente['id_usuario'] != $id_usuario) {
                $_SESSION['mensaje'] = 'El email ya está en uso por otro usuario';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=perfil');
                exit;
            }
        }

        // Actualizar contraseña si se proporcionó
        if (!empty($password)) {
            if ($password !== $password_confirm) {
                $_SESSION['mensaje'] = 'Las contraseñas no coinciden';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=perfil');
                exit;
            }
            
            if (strlen($password) < 6) {
                $_SESSION['mensaje'] = 'La contraseña debe tener al menos 6 caracteres';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=perfil');
                exit;
            }
        }

        // Actualizar datos
        $database = new Database();
        $conn = $database->getConnection();
        
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $query = "UPDATE usuarios 
                      SET nombre = :nombre, 
                          email = :email, 
                          telefono = :telefono,
                          cargo = :cargo,
                          area = :area,
                          password = :password
                      WHERE id_usuario = :id_usuario";
        } else {
            $query = "UPDATE usuarios 
                      SET nombre = :nombre, 
                          email = :email, 
                          telefono = :telefono,
                          cargo = :cargo,
                          area = :area
                      WHERE id_usuario = :id_usuario";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':cargo', $cargo);
        $stmt->bindParam(':area', $area);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        
        if (!empty($password)) {
            $stmt->bindParam(':password', $password_hash);
        }
        
        if ($stmt->execute()) {
            // Actualizar datos en sesión
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $email;
            
            $_SESSION['mensaje'] = 'Perfil actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al actualizar el perfil';
            $_SESSION['tipo_mensaje'] = 'error';
        }
        
        header('Location: index.php?action=perfil');
        exit;
    }
}

