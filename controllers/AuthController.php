<?php
/**
 * Controlador de Autenticación
 * Sistema ROMA
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $usuario;

    public function __construct() {
        $this->usuario = new Usuario();
    }

    /**
     * Mostrar formulario de login
     */
    public function login() {
        // Si ya está autenticado, redirigir al dashboard
        if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_autenticado'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesar login
     */
    public function procesarLogin() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['mensaje'] = 'Por favor, complete todos los campos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=auth&subaction=login');
            exit;
        }

        if ($this->usuario->autenticar($email, $password)) {
            // Iniciar sesión
            $_SESSION['usuario_id'] = $this->usuario->id_usuario;
            $_SESSION['usuario_nombre'] = $this->usuario->nombre;
            $_SESSION['usuario_email'] = $this->usuario->email;
            $_SESSION['usuario_rol'] = $this->usuario->rol;
            $_SESSION['usuario_autenticado'] = true;

            $_SESSION['mensaje'] = 'Bienvenido, ' . $this->usuario->nombre;
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=dashboard');
            exit;
        } else {
            $_SESSION['mensaje'] = 'Email o contraseña incorrectos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=auth&subaction=login');
            exit;
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        // Destruir todas las variables de sesión
        $_SESSION = array();

        // Si se desea destruir la sesión completamente, también borrar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finalmente, destruir la sesión
        session_destroy();

        header('Location: index.php?action=auth&subaction=login');
        exit;
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function verificarAutenticacion() {
        if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
            header('Location: index.php?action=auth&subaction=login');
            exit;
        }
    }

    /**
     * Verificar si el usuario tiene uno de los roles permitidos.
     * Acepta un rol (string) o varios (array) por compatibilidad.
     */
    public static function verificarRol($rol) {
        self::verificarAutenticacion();

        $permitidos = is_array($rol) ? $rol : [$rol];
        if (!isset($_SESSION['usuario_rol']) || !in_array($_SESSION['usuario_rol'], $permitidos, true)) {
            $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function verificarPermiso($accion) {
        self::verificarAutenticacion();
        
        $rol = $_SESSION['usuario_rol'] ?? null;
        
        if (!$rol || !Usuario::tienePermiso($rol, $accion)) {
            $_SESSION['mensaje'] = 'No tiene permisos para realizar esta acción';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    /**
     * Obtener el rol del usuario actual
     */
    public static function obtenerRol() {
        return $_SESSION['usuario_rol'] ?? null;
    }

    /**
     * Obtener el ID del usuario actual
     */
    public static function obtenerUsuarioId() {
        return $_SESSION['usuario_id'] ?? null;
    }
}

