<?php
/**
 * API de Órdenes de Trabajo
 * Permite listar, consultar, crear, actualizar estado y reasignar órdenes.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ApiAuth.php';
require_once __DIR__ . '/../models/OrdenTrabajo.php';
require_once __DIR__ . '/../models/Activo.php';
require_once __DIR__ . '/../models/Usuario.php';

ApiAuth::allowCors();
ApiAuth::handlePreflight();

$segmentos = ApiAuth::getPathSegments();
$recurso = $segmentos[0] ?? 'orders';

if ($recurso !== 'orders') {
    ApiResponse::error('Endpoint no encontrado', 404);
}

$id = null;
$subrecurso = null;
$accion = null;

if (isset($segmentos[1])) {
    if (is_numeric($segmentos[1])) {
        $id = (int)$segmentos[1];
        $subrecurso = $segmentos[2] ?? null;
        $accion = $segmentos[3] ?? null;
    } else {
        $subrecurso = $segmentos[1];
        $accion = $segmentos[2] ?? null;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$ordenModel = new OrdenTrabajo();
$usuarioAutenticado = ApiAuth::requireUser();
$rol = $usuarioAutenticado['rol'];
$usuarioId = $usuarioAutenticado['id_usuario'];

/**
 * Verifica que el usuario tenga acceso a la orden proporcionada.
 */
function asegurarAccesoOrden(array $orden, array $usuario): void
{
    $rol = $usuario['rol'];
    $usuarioId = $usuario['id_usuario'];

    if ($rol === 'operario' && (int)$orden['id_usuario_asignado'] !== (int)$usuarioId) {
        ApiResponse::error('No tiene permisos para acceder a esta orden.', 403);
    }

    if (in_array($rol, ['jefe', 'director'], true) && (int)$orden['id_solicitante'] !== (int)$usuarioId) {
        ApiResponse::error('No tiene permisos para acceder a esta orden.', 403);
    }
}

/**
 * Guarda evidencia (imagen) de una orden.
 */
function procesarEvidenciaOrden($archivo, $base64 = null, $ordenId = null): ?array
{
    if (empty($archivo) && empty($base64)) {
        return null;
    }

    $uploadDir = UPLOAD_DIR . 'ordenes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!empty($archivo) && ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            ApiResponse::error('No se pudo subir la evidencia proporcionada.', 400);
        }

        if (($archivo['size'] ?? 0) > UPLOAD_MAX_SIZE) {
            ApiResponse::error('La evidencia supera el tamaño máximo permitido (10MB).', 400);
        }

        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionesPermitidas, true)) {
            ApiResponse::error('Formato de evidencia no permitido. Use JPG, PNG, GIF o WEBP.', 400);
        }

        if (!@getimagesize($archivo['tmp_name'])) {
            ApiResponse::error('La evidencia cargada no es una imagen válida.', 400);
        }

        $nombreArchivo = uniqid('ord_' . ($ordenId ?? 'tmp') . '_', true) . '.' . $extension;
        if (!move_uploaded_file($archivo['tmp_name'], $uploadDir . $nombreArchivo)) {
            ApiResponse::error('No se pudo guardar la evidencia.', 500);
        }

        return [
            'ruta' => 'ordenes/' . $nombreArchivo,
            'nombre_original' => $archivo['name'] ?? $nombreArchivo,
            'mime' => $archivo['type'] ?? null,
            'size' => $archivo['size'] ?? null,
        ];
    }

    if (!empty($base64)) {
        $contenido = base64_decode($base64, true);
        if ($contenido === false) {
            ApiResponse::error('La evidencia en base64 no es válida.', 400);
        }

        $nombreArchivo = uniqid('ord_' . ($ordenId ?? 'tmp') . '_', true) . '.png';
        if (!file_put_contents($uploadDir . $nombreArchivo, $contenido)) {
            ApiResponse::error('No se pudo guardar la evidencia en base64.', 500);
        }

        return [
            'ruta' => 'ordenes/' . $nombreArchivo,
            'nombre_original' => $nombreArchivo,
            'mime' => 'image/png',
            'size' => strlen($contenido),
        ];
    }

    return null;
}

/**
 * Construye filtros a partir de la petición y del rol del usuario.
 */
function construirFiltrosListado(array $usuario): array
{
    $filtros = [
        'estado' => $_GET['estado'] ?? '',
        'tipo_mantenimiento' => $_GET['tipo'] ?? '',
        'criticidad' => $_GET['criticidad'] ?? '',
        'asignado_a' => $_GET['asignado'] ?? '',
        'activo' => $_GET['activo'] ?? '',
        'busqueda' => $_GET['busqueda'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    ];

    if ($usuario['rol'] === 'operario') {
        $filtros['asignado_a'] = $usuario['id_usuario'];
    }

    if (in_array($usuario['rol'], ['jefe', 'director'], true)) {
        $filtros['solicitante'] = $usuario['id_usuario'];
    }

    return $filtros;
}

try {
    switch ($method) {
        case 'GET':
            if (!$id) {
                ApiAuth::ensurePermission($rol, ['ver_ordenes', 'ver_mis_ordenes']);
                $ordenes = $ordenModel->listar(construirFiltrosListado($usuarioAutenticado));
                ApiResponse::success($ordenes, 'Órdenes obtenidas correctamente', 200, [
                    'count' => count($ordenes),
                ]);
            }

            if ($id && $subrecurso === 'history') {
                ApiAuth::ensurePermission($rol, ['ver_ordenes', 'ver_mis_ordenes']);
                $orden = $ordenModel->obtenerPorId($id);
                if (!$orden) {
                    ApiResponse::error('Orden no encontrada', 404);
                }
                asegurarAccesoOrden($orden, $usuarioAutenticado);

                $historial = $ordenModel->obtenerHistorial($id);
                ApiResponse::success([
                    'id_orden' => $id,
                    'numero_radicado' => $orden['numero_radicado'],
                    'historial' => $historial,
                ], 'Historial obtenido correctamente');
            }

            if ($id && !$subrecurso) {
                ApiAuth::ensurePermission($rol, ['ver_ordenes', 'ver_mis_ordenes']);
                $orden = $ordenModel->obtenerPorId($id);
                if (!$orden) {
                    ApiResponse::error('Orden no encontrada', 404);
                }

                asegurarAccesoOrden($orden, $usuarioAutenticado);

                $orden['historial'] = $ordenModel->obtenerHistorial($id);
                ApiResponse::success($orden, 'Orden obtenida correctamente');
            }

            ApiResponse::error('Subrecurso no encontrado', 404);
            break;

        case 'POST':
            if (!$id) {
                ApiAuth::ensurePermission($rol, ['crear_ordenes']);

                $input = $_POST;
                if (empty($input)) {
                    $input = ApiAuth::parseJsonBody();
                }

                $ordenModel->id_activo = $input['id_activo'] ?? null;
                if (empty($ordenModel->id_activo)) {
                    ApiResponse::validationError(['id_activo' => 'Debe indicar el activo asociado.']);
                }

                $ordenModel->id_solicitud = $input['id_solicitud'] ?? null;
                $ordenModel->id_solicitante = $usuarioId;
                $ordenModel->id_usuario_asignado = $input['id_usuario_asignado'] ?? null;
                $ordenModel->tipo_mantenimiento = $input['tipo_mantenimiento'] ?? 'correctivo';
                $ordenModel->nivel_criticidad = $input['nivel_criticidad'] ?? 'normal';
                $ordenModel->descripcion_corta = $input['descripcion_corta'] ?? '';
                $ordenModel->descripcion_detallada = $input['descripcion_detallada'] ?? '';
                $ordenModel->estado_proceso = 'recibido';
                $ordenModel->fecha_limite_ejecucion = $input['fecha_limite_ejecucion'] ?? null;
                $ordenModel->usuario_creacion = $usuarioId;

                if ($ordenModel->crear()) {
                    $ordenModel->registrarHistorial(
                        $ordenModel->id_orden,
                        $usuarioId,
                        'creacion',
                        null,
                        null,
                        null,
                        null,
                        'Orden de trabajo creada desde API'
                    );

                    ApiResponse::success([
                        'id_orden' => $ordenModel->id_orden,
                        'numero_radicado' => $ordenModel->numero_radicado,
                    ], 'Orden creada correctamente', 201);
                }

                ApiResponse::error('No se pudo crear la orden.', 400);
            }

            if ($id && $subrecurso === 'status') {
                ApiAuth::ensurePermission($rol, ['cambiar_estado_orden']);

                $orden = $ordenModel->obtenerPorId($id);
                if (!$orden) {
                    ApiResponse::error('Orden no encontrada', 404);
                }
                asegurarAccesoOrden($orden, $usuarioAutenticado);

                $input = $_POST;
                if (empty($input)) {
                    $input = ApiAuth::parseJsonBody();
                }

                $nuevoEstado = $input['nuevo_estado'] ?? '';
                $comentario = $input['comentario'] ?? '';

                if (empty($nuevoEstado)) {
                    ApiResponse::validationError(['nuevo_estado' => 'Debe indicar el nuevo estado.']);
                }

                if (strlen(trim($comentario)) < 5) {
                    ApiResponse::validationError(['comentario' => 'El comentario debe tener al menos 5 caracteres.']);
                }

                $requiereEvidencia = ($nuevoEstado === 'finalizado');
                $evidencia = procesarEvidenciaOrden(
                    $_FILES['evidencia'] ?? null,
                    $input['evidencia_base64'] ?? null,
                    $id
                );

                if ($requiereEvidencia && !$evidencia) {
                    ApiResponse::validationError(['evidencia' => 'Debe adjuntar una evidencia para finalizar la orden.']);
                }

                $resultado = $ordenModel->cambiarEstado(
                    $id,
                    $nuevoEstado,
                    $usuarioId,
                    $comentario,
                    $evidencia['ruta'] ?? null
                );

                if ($resultado) {
                    if ($evidencia) {
                        $ordenModel->agregarAdjunto(
                            $id,
                            $evidencia['nombre_original'],
                            $evidencia['ruta'],
                            $evidencia['mime'],
                            $evidencia['size'],
                            $usuarioId,
                            'Evidencia cambio de estado'
                        );
                    }

                    ApiResponse::success(null, 'Estado actualizado correctamente');
                }

                ApiResponse::error('No se pudo actualizar el estado.', 400);
            }

            if ($id && $subrecurso === 'reassign') {
                ApiAuth::ensurePermission($rol, ['reasignar_orden']);

                $orden = $ordenModel->obtenerPorId($id);
                if (!$orden) {
                    ApiResponse::error('Orden no encontrada', 404);
                }

                if ($rol === 'operario') {
                    asegurarAccesoOrden($orden, $usuarioAutenticado);
                }

                $input = $_POST;
                if (empty($input)) {
                    $input = ApiAuth::parseJsonBody();
                }

                $nuevoOperario = $input['id_usuario_asignado'] ?? null;
                if (empty($nuevoOperario)) {
                    ApiResponse::validationError(['id_usuario_asignado' => 'Debe indicar el nuevo operario.']);
                }

                $comentario = $input['comentario'] ?? 'Reasignación desde API';

                if ($ordenModel->reasignar($id, $nuevoOperario, $usuarioId, $comentario)) {
                    ApiResponse::success(null, 'Orden reasignada correctamente');
                }

                ApiResponse::error('No se pudo reasignar la orden.', 400);
            }

            if ($id && $subrecurso === 'comments') {
                ApiAuth::ensurePermission($rol, ['ver_ordenes', 'ver_mis_ordenes']);

                $orden = $ordenModel->obtenerPorId($id);
                if (!$orden) {
                    ApiResponse::error('Orden no encontrada', 404);
                }
                asegurarAccesoOrden($orden, $usuarioAutenticado);

                $input = $_POST;
                if (empty($input)) {
                    $input = ApiAuth::parseJsonBody();
                }

                $comentario = trim($input['comentario'] ?? '');
                if (strlen($comentario) < 5) {
                    ApiResponse::validationError(['comentario' => 'El comentario debe tener al menos 5 caracteres.']);
                }

                if ($ordenModel->agregarComentario($id, $usuarioId, $comentario)) {
                    $ordenModel->registrarHistorial($id, $usuarioId, 'comentario', null, null, null, null, $comentario);
                    ApiResponse::success(null, 'Comentario agregado correctamente');
                }

                ApiResponse::error('No se pudo agregar el comentario.', 400);
            }

            ApiResponse::error('Acción no permitida', 404);
            break;

        case 'DELETE':
            if (!$id) {
                ApiResponse::error('Debe indicar la orden a eliminar.', 400);
            }

            ApiAuth::ensurePermission($rol, ['eliminar_ordenes']);

            $ordenModel->id_orden = $id;
            if ($ordenModel->eliminar()) {
                ApiResponse::success(null, 'Orden eliminada correctamente');
            }

            ApiResponse::error('No se pudo eliminar la orden.', 400);
            break;

        default:
            ApiResponse::error('Método no permitido', 405);
    }
} catch (Exception $e) {
    error_log('API Órdenes: ' . $e->getMessage());
    ApiResponse::error('Error interno del servidor', 500);
}

