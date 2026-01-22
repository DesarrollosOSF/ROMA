<?php
/**
 * API RESTful para Gestión de Activos
 * Endpoints orientados a la integración con clientes Flutter.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ApiAuth.php';
require_once __DIR__ . '/../models/Activo.php';
require_once __DIR__ . '/../models/OrdenTrabajo.php';
require_once __DIR__ . '/../models/Usuario.php';

ApiAuth::allowCors();
ApiAuth::handlePreflight();

/**
 * Gestiona la carga de la foto principal del activo.
 */
function procesarFotoPrincipalApi($archivo, $fotoActual = null)
{
    if (empty($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $fotoActual;
    }

    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        ApiResponse::error('No se pudo subir la imagen proporcionada.', 400);
    }

    if (($archivo['size'] ?? 0) > UPLOAD_MAX_SIZE) {
        ApiResponse::error('La imagen supera el tamaño máximo permitido (10MB).', 400);
    }

    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($archivo['name'] ?? '', PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas, true)) {
        ApiResponse::error('Formato de imagen no permitido. Utilice JPG, PNG, GIF o WEBP.', 400);
    }

    if (!@getimagesize($archivo['tmp_name'])) {
        ApiResponse::error('El archivo enviado no es una imagen válida.', 400);
    }

    $uploadDir = UPLOAD_DIR . 'activos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $nombreArchivo = uniqid('act_', true) . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        ApiResponse::error('No se pudo guardar la foto del activo.', 500);
    }

    if (!empty($fotoActual)) {
        $rutaAnterior = UPLOAD_DIR . $fotoActual;
        if (is_file($rutaAnterior)) {
            @unlink($rutaAnterior);
        }
    }

    return 'activos/' . $nombreArchivo;
}

$usuarioAutenticado = ApiAuth::requireUser();
$rol = $usuarioAutenticado['rol'];

$segmentos = ApiAuth::getPathSegments();
$recurso = $segmentos[0] ?? 'assets';

if ($recurso !== 'assets') {
    ApiResponse::error('Endpoint no encontrado', 404);
}

$id = null;
$subrecurso = null;

if (isset($segmentos[1])) {
    if (is_numeric($segmentos[1])) {
        $id = (int)$segmentos[1];
        $subrecurso = $segmentos[2] ?? null;
    } else {
        $subrecurso = $segmentos[1];
    }
}

$activo = new Activo();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if ($subrecurso === 'categories') {
                ApiAuth::ensurePermission($rol, ['ver_activos', 'ver_lista_activos']);
                ApiResponse::success(array_values(ASSET_CATEGORIES), 'Categorías obtenidas correctamente');
            }

            if ($id && $subrecurso === 'history') {
                ApiAuth::ensurePermission($rol, ['ver_activos', 'ver_lista_activos']);

                $filtros = [
                    'tipo_mantenimiento' => $_GET['tipo'] ?? '',
                    'fecha_desde' => $_GET['fecha_desde'] ?? '',
                    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
                    'tecnico' => $_GET['tecnico'] ?? ''
                ];

                $historial = $activo->obtenerHojaVida($id, $filtros);

                $incluirOrdenes = ($_GET['incluir_ordenes'] ?? '0') === '1';
                if ($incluirOrdenes) {
                    $historial = array_merge(
                        $historial,
                        $activo->obtenerOrdenesAsociadas($id, $filtros)
                    );
                }

                ApiResponse::success([
                    'id_activo' => $id,
                    'registros' => $historial
                ], 'Historial obtenido correctamente');
            }

            if ($id && !$subrecurso) {
                ApiAuth::ensurePermission($rol, ['ver_activos', 'ver_lista_activos']);
                $detalle = $activo->obtenerPorId($id);

                if (!$detalle) {
                    ApiResponse::error('Activo no encontrado', 404);
                }

                if (isset($_GET['incluir']) && strpos($_GET['incluir'], 'historial') !== false) {
                    $detalle['historial'] = $activo->obtenerHojaVida($id);
                }

                ApiResponse::success($detalle, 'Activo obtenido correctamente');
            }

            if (!$id) {
                ApiAuth::ensurePermission($rol, ['ver_activos', 'ver_lista_activos']);

                $filtros = [
                    'categoria' => $_GET['categoria'] ?? '',
                    'estado' => $_GET['estado'] ?? '',
                    'ubicacion' => $_GET['ubicacion'] ?? '',
                    'busqueda' => $_GET['busqueda'] ?? '',
                    'limit' => (int)($_GET['limit'] ?? 50),
                    'offset' => (int)($_GET['offset'] ?? 0)
                ];

                $data = $activo->listar($filtros);

                ApiResponse::success($data, 'Activos obtenidos correctamente', 200, [
                    'count' => count($data)
                ]);
            }

            ApiResponse::error('Subrecurso no encontrado', 404);
            break;

        case 'POST':
            ApiAuth::ensurePermission($rol, ['crear_activos']);

            $input = $_POST;
            if (empty($input)) {
                $input = ApiAuth::parseJsonBody();
            }

            $activo->nombre_activo = $input['nombre_activo'] ?? '';
            $activo->codigo_interno = $input['codigo_interno'] ?? '';
            $activo->codigo_patrimonial = $input['codigo_patrimonial'] ?? '';
            $activo->descripcion_general = $input['descripcion_general'] ?? '';
            $activo->categoria = $input['categoria'] ?? 'equipo_especial';
            $activo->ubicacion = $input['ubicacion'] ?? '';
            $activo->ruta = $input['ruta'] ?? '';
            $activo->responsable = $input['responsable'] ?? '';
            $activo->area_asignada = $input['area_asignada'] ?? '';
            $activo->marca = $input['marca'] ?? '';
            $activo->modelo = $input['modelo'] ?? '';
            $activo->numero_serie = $input['numero_serie'] ?? '';
            $activo->fecha_adquisicion = $input['fecha_adquisicion'] ?? null;
            $activo->valor_adquisicion = $input['valor_adquisicion'] ?? 0;
            $activo->estado_actual = $input['estado_actual'] ?? 'operativo';
            $activo->vida_util_estimada = $input['vida_util_estimada'] ?? null;
            $activo->kilometraje = $input['kilometraje'] ?? 0;
            $activo->horas_uso = $input['horas_uso'] ?? 0;
            $activo->proximo_mantenimiento = $input['proximo_mantenimiento'] ?? null;
            $activo->observaciones = $input['observaciones'] ?? '';
            $activo->usuario_creacion = $usuarioAutenticado['id_usuario'];

            if (!empty($_FILES['foto_principal'])) {
                $activo->foto_principal = procesarFotoPrincipalApi($_FILES['foto_principal']);
            } elseif (!empty($input['foto_base64'])) {
                $imagenDecodificada = base64_decode($input['foto_base64'], true);
                if ($imagenDecodificada === false) {
                    ApiResponse::error('La imagen en base64 no es válida.', 400);
                }

                $uploadDir = UPLOAD_DIR . 'activos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $nombreArchivo = uniqid('act_', true) . '.png';
                if (!file_put_contents($uploadDir . $nombreArchivo, $imagenDecodificada)) {
                    ApiResponse::error('No se pudo guardar la foto enviada en base64.', 500);
                }
                $activo->foto_principal = 'activos/' . $nombreArchivo;
            }

            if ($activo->crear()) {
                ApiResponse::success([
                    'id_activo' => $activo->id_activo
                ], 'Activo creado correctamente', 201);
            }

            ApiResponse::error('No se pudo crear el activo.', 400);
            break;

        case 'PUT':
        case 'PATCH':
            if (!$id) {
                ApiResponse::error('Debe indicar el activo a actualizar.', 400);
            }

            ApiAuth::ensurePermission($rol, ['editar_activos']);

            $input = $_POST;
            if (empty($input)) {
                $input = ApiAuth::parseJsonBody();
            }

            $existente = $activo->obtenerPorId($id);
            if (!$existente) {
                ApiResponse::error('Activo no encontrado', 404);
            }

            $activo->id_activo = $id;
            $activo->nombre_activo = $input['nombre_activo'] ?? $existente['nombre_activo'];
            $activo->codigo_interno = $input['codigo_interno'] ?? $existente['codigo_interno'];
            $activo->codigo_patrimonial = $input['codigo_patrimonial'] ?? $existente['codigo_patrimonial'];
            $activo->descripcion_general = $input['descripcion_general'] ?? $existente['descripcion_general'];
            $activo->categoria = $input['categoria'] ?? $existente['categoria'];
            $activo->ubicacion = $input['ubicacion'] ?? $existente['ubicacion'];
            $activo->ruta = $input['ruta'] ?? $existente['ruta'];
            $activo->responsable = $input['responsable'] ?? $existente['responsable'];
            $activo->area_asignada = $input['area_asignada'] ?? $existente['area_asignada'];
            $activo->marca = $input['marca'] ?? $existente['marca'];
            $activo->modelo = $input['modelo'] ?? $existente['modelo'];
            $activo->numero_serie = $input['numero_serie'] ?? $existente['numero_serie'];
            $activo->fecha_adquisicion = $input['fecha_adquisicion'] ?? $existente['fecha_adquisicion'];
            $activo->valor_adquisicion = $input['valor_adquisicion'] ?? $existente['valor_adquisicion'];
            $activo->estado_actual = $input['estado_actual'] ?? $existente['estado_actual'];
            $activo->vida_util_estimada = $input['vida_util_estimada'] ?? $existente['vida_util_estimada'];
            $activo->kilometraje = $input['kilometraje'] ?? $existente['kilometraje'];
            $activo->horas_uso = $input['horas_uso'] ?? $existente['horas_uso'];
            $activo->proximo_mantenimiento = $input['proximo_mantenimiento'] ?? $existente['proximo_mantenimiento'];
            $activo->observaciones = $input['observaciones'] ?? $existente['observaciones'];
            $activo->foto_principal = $existente['foto_principal'];
            $activo->usuario_creacion = $usuarioAutenticado['id_usuario'];

            if (!empty($_FILES['foto_principal'])) {
                $activo->foto_principal = procesarFotoPrincipalApi($_FILES['foto_principal'], $existente['foto_principal']);
            } elseif (!empty($input['foto_base64'])) {
                $imagenDecodificada = base64_decode($input['foto_base64'], true);
                if ($imagenDecodificada === false) {
                    ApiResponse::error('La imagen en base64 no es válida.', 400);
                }

                $uploadDir = UPLOAD_DIR . 'activos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $nombreArchivo = uniqid('act_', true) . '.png';
                if (!file_put_contents($uploadDir . $nombreArchivo, $imagenDecodificada)) {
                    ApiResponse::error('No se pudo guardar la foto enviada en base64.', 500);
                }

                if (!empty($existente['foto_principal'])) {
                    $rutaAnterior = UPLOAD_DIR . $existente['foto_principal'];
                    if (is_file($rutaAnterior)) {
                        @unlink($rutaAnterior);
                    }
                }

                $activo->foto_principal = 'activos/' . $nombreArchivo;
            }

            if ($activo->actualizar()) {
                ApiResponse::success(['id_activo' => $id], 'Activo actualizado correctamente');
            }

            ApiResponse::error('No se pudo actualizar el activo.', 400);
            break;

        case 'DELETE':
            if (!$id) {
                ApiResponse::error('Debe indicar el activo a eliminar.', 400);
            }

            ApiAuth::ensurePermission($rol, ['eliminar_activos']);

            $activo->id_activo = $id;
            $activo->usuario_creacion = $usuarioAutenticado['id_usuario'];

            if ($activo->eliminar()) {
                ApiResponse::success(null, 'Activo eliminado correctamente');
            }

            ApiResponse::error('No se pudo eliminar el activo.', 400);
            break;

        default:
            ApiResponse::error('Método no permitido', 405);
    }
} catch (Exception $e) {
    error_log('API Activos: ' . $e->getMessage());
    ApiResponse::error('Error interno del servidor', 500);
}

