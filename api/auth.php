<?php
/**
 * API de Autenticación
 * Permite iniciar sesión, cerrar sesión (stateless) y obtener el perfil del usuario conectado.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ApiAuth.php';

ApiAuth::allowCors();
ApiAuth::handlePreflight();

$segmentos = ApiAuth::getPathSegments();
$recurso = $segmentos[0] ?? 'auth';
$accion = $segmentos[1] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if ($recurso !== 'auth') {
    ApiResponse::error('Endpoint no encontrado', 404);
}

/**
 * Construye arreglo de permisos disponibles para el rol entregado.
 */
function construirPermisosPorRol(string $rol, ?string $email = null): array
{
    $accionesInteres = [
        'ver_activos',
        'ver_lista_activos',
        'crear_activos',
        'editar_activos',
        'eliminar_activos',
        'ver_ordenes',
        'ver_mis_ordenes',
        'crear_ordenes',
        'asignar_ordenes',
        'editar_ordenes',
        'eliminar_ordenes',
        'cambiar_estado_orden',
        'reasignar_orden',
        'subir_evidencias',
        'gestionar_usuarios',
        'configurar_sistema',
        'ver_reportes',
    ];

    $permisos = [];
    foreach ($accionesInteres as $accion) {
        $permisos[$accion] = Usuario::tienePermiso($rol, $accion, $email);
    }

    return $permisos;
}

try {
    switch ($method) {
        case 'POST':
            if ($accion === 'login') {
                $input = $_POST;
                if (empty($input)) {
                    $input = ApiAuth::parseJsonBody();
                }

                $email = trim($input['email'] ?? '');
                $password = $input['password'] ?? '';

                if (empty($email) || empty($password)) {
                    ApiResponse::validationError([
                        'email' => empty($email) ? 'El email es obligatorio.' : null,
                        'password' => empty($password) ? 'La contraseña es obligatoria.' : null,
                    ], 'Credenciales incompletas');
                }

                $usuarioModel = new Usuario();
                if (!$usuarioModel->autenticar($email, $password)) {
                    ApiResponse::error('Credenciales incorrectas.', 401);
                }

                $detalle = $usuarioModel->obtenerPorId($usuarioModel->id_usuario);
                if (!$detalle) {
                    ApiResponse::error('El usuario no se encuentra activo.', 401);
                }

                $token = ApiAuth::generateToken($detalle);

                ApiResponse::success([
                    'token' => $token,
                    'expira_en' => API_TOKEN_EXPIRATION,
                    'usuario' => [
                        'id_usuario' => (int)$detalle['id_usuario'],
                        'nombre' => $detalle['nombre'],
                        'email' => $detalle['email'],
                        'rol' => $detalle['rol'],
                        'telefono' => $detalle['telefono'] ?? null,
                        'cargo' => $detalle['cargo'] ?? null,
                        'area' => $detalle['area'] ?? null,
                        'fecha_creacion' => $detalle['fecha_creacion'] ?? null,
                        'permisos' => construirPermisosPorRol($detalle['rol'], $detalle['email'] ?? null),
                    ]
                ], 'Autenticación exitosa');
            }

            if ($accion === 'logout') {
                // La API es stateless; el logout sólo invalida el token en el cliente.
                ApiResponse::success(null, 'Sesión cerrada correctamente');
            }

            ApiResponse::error('Acción no permitida', 404);
            break;

        case 'GET':
            if ($accion === 'profile') {
                $usuario = ApiAuth::requireUser();

                ApiResponse::success([
                    'id_usuario' => (int)$usuario['id_usuario'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol'],
                    'telefono' => $usuario['telefono'] ?? null,
                    'cargo' => $usuario['cargo'] ?? null,
                    'area' => $usuario['area'] ?? null,
                    'fecha_creacion' => $usuario['fecha_creacion'] ?? null,
                    'permisos' => construirPermisosPorRol($usuario['rol'], $usuario['email'] ?? null),
                ], 'Perfil obtenido correctamente');
            }

            if ($accion === 'ping') {
                // Permite validar rápidamente un token activo.
                $usuario = ApiAuth::requireUser();
                ApiResponse::success([
                    'id_usuario' => (int)$usuario['id_usuario'],
                    'rol' => $usuario['rol'],
                ], 'Token válido');
            }

            ApiResponse::error('Acción no permitida', 404);
            break;

        default:
            ApiResponse::error('Método no permitido', 405);
    }
} catch (Exception $e) {
    error_log('API Auth: ' . $e->getMessage());
    ApiResponse::error('Error interno del servidor', 500);
}

