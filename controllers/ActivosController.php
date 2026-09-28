<?php
/**
 * Controlador de Activos
 * Sistema ROMA - Interfaz Web
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Activo.php';
require_once __DIR__ . '/AuthController.php';

class ActivosController {
    private $activo;

    public function __construct() {
        // Verificar autenticación
        AuthController::verificarAutenticacion();
        
        $this->activo = new Activo();
    }

    /**
     * Mostrar lista de activos
     */
    public function index() {
        $filtros = [
            'categoria' => $_GET['categoria'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'ubicacion' => $_GET['ubicacion'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];

        $activos = $this->activo->listar($filtros);
        $categorias = ASSET_CATEGORIES;
        $estados = ASSET_STATES;

        require_once __DIR__ . '/../views/activos/index.php';
    }

    /**
     * Mostrar formulario de nuevo activo
     */
    public function crear() {
        require_once __DIR__ . '/../models/Usuario.php';
        
        // Verificar permiso
        $rol = $_SESSION['usuario_rol'] ?? '';
        if (!Usuario::tienePermiso($rol, 'crear_activos')) {
            $_SESSION['mensaje'] = 'No tiene permisos para crear activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        $categorias = ASSET_CATEGORIES;
        $estados = ASSET_STATES;
        $usuarioModel = new Usuario();
        $operarios = $usuarioModel->obtenerOperarios();
        $rutas = ASSET_CATEGORIES; // Las rutas son las mismas categorías
        $areas = ASSIGNED_AREAS;
        $ubicaciones = ASSET_LOCATIONS;
        
        require_once __DIR__ . '/../views/activos/form.php';
    }

    /**
     * Procesar creación de activo
     */
    public function guardar() {
        $nombre_activo = trim($_POST['nombre_activo'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        if ($nombre_activo === '') {
            $_SESSION['mensaje'] = 'El nombre del activo es obligatorio.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=crear');
            exit;
        }
        if ($categoria === '' || !array_key_exists($categoria, ASSET_CATEGORIES)) {
            $_SESSION['mensaje'] = 'Debe seleccionar una categoría válida.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=crear');
            exit;
        }
        $this->activo->nombre_activo = $nombre_activo;
        $this->activo->codigo_interno = $_POST['codigo_interno'] ?? '';
        $this->activo->codigo_patrimonial = $_POST['codigo_patrimonial'] ?? '';
        $this->activo->descripcion_general = $_POST['descripcion_general'] ?? '';
        $this->activo->categoria = $categoria;
        $this->activo->ubicacion = $_POST['ubicacion'] ?? '';
        $this->activo->ruta = $_POST['ruta'] ?? '';
        $this->activo->responsable = $_POST['responsable'] ?? '';
        $this->activo->area_asignada = $_POST['area_asignada'] ?? '';
        $this->activo->marca = $_POST['marca'] ?? '';
        $this->activo->modelo = $_POST['modelo'] ?? '';
        $this->activo->numero_serie = $_POST['numero_serie'] ?? '';
        $this->activo->fecha_adquisicion = $_POST['fecha_adquisicion'] ?? null;
        $this->activo->valor_adquisicion = $_POST['valor_adquisicion'] ?? 0;
        $estado_actual = $_POST['estado_actual'] ?? 'operativo';
        if (!array_key_exists($estado_actual, ASSET_STATES)) {
            $estado_actual = 'operativo';
        }
        $this->activo->estado_actual = $estado_actual;
        $this->activo->vida_util_estimada = $_POST['vida_util_estimada'] ?? null;
        $this->activo->kilometraje = $_POST['kilometraje'] ?? 0;
        $this->activo->horas_uso = $_POST['horas_uso'] ?? 0;
        $this->activo->proximo_mantenimiento = $_POST['proximo_mantenimiento'] ?? null;
        $this->activo->observaciones = $_POST['observaciones'] ?? '';
        $this->activo->usuario_creacion = $_SESSION['usuario_id'] ?? 1;
        $fotoProcesada = $this->procesarFotoPrincipal($_FILES['foto_principal'] ?? null);
        if ($fotoProcesada === false) {
            header('Location: index.php?action=activos&subaction=crear');
            return;
        }
        $this->activo->foto_principal = $fotoProcesada;

        if ($this->activo->crear()) {
            $_SESSION['mensaje'] = 'Activo creado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=activos');
        } else {
            $_SESSION['mensaje'] = 'Error al crear el activo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=crear');
        }
    }

    /**
     * Mostrar detalles de activo y hoja de vida
     */
    public function ver($id) {
        require_once __DIR__ . '/../models/Usuario.php';
        
        $activo = $this->activo->obtenerPorId($id);
        
        if (!$activo) {
            $_SESSION['mensaje'] = 'Activo no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos');
            exit;
        }

        // Obtener nombre del responsable si es un ID de usuario
        $responsable_nombre = $activo['responsable'];
        if (!empty($activo['responsable']) && is_numeric($activo['responsable'])) {
            $usuarioModel = new Usuario();
            $usuario = $usuarioModel->obtenerPorId($activo['responsable']);
            if ($usuario) {
                $responsable_nombre = $usuario['nombre'] . ' (' . $usuario['email'] . ')';
            }
        }

        // Paginación
        $pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $registros_por_pagina = 10;
        $offset = ($pagina_actual - 1) * $registros_por_pagina;

        $filtros = [
            'tipo_mantenimiento' => $_GET['tipo'] ?? '',
            'tecnico' => $_GET['tecnico'] ?? '',
            'limit' => $registros_por_pagina,
            'offset' => $offset
        ];

        // Obtener totales para paginación
        // Para hoja_vida, no aplicamos filtro de técnico porque es VARCHAR con nombre
        $filtros_hoja_vida = [
            'tipo_mantenimiento' => $filtros['tipo_mantenimiento']
        ];
        $total_mantenimientos = $this->activo->contarHojaVida($id, $filtros_hoja_vida);
        $total_ordenes = $this->activo->contarOrdenesAsociadas($id, $filtros);
        $total_registros = $total_mantenimientos + $total_ordenes;
        $total_paginas = max(1, ceil($total_registros / $registros_por_pagina));

        // Para hoja_vida, no aplicamos filtro de técnico porque es VARCHAR con nombre
        $hoja_vida_mantenimiento = $this->activo->obtenerHojaVida($id, $filtros_hoja_vida + ['limit' => $filtros['limit'], 'offset' => $filtros['offset']]);
        $ordenes_asociadas = $this->activo->obtenerOrdenesAsociadas($id, $filtros);

        $hoja_vida = array_merge($hoja_vida_mantenimiento, $ordenes_asociadas);

        usort($hoja_vida, function ($a, $b) {
            $fechaA = $a['fecha_intervencion'] ?? '';
            $fechaB = $b['fecha_intervencion'] ?? '';
            return strcmp($fechaB, $fechaA);
        });

        // Obtener todos los registros sin paginación para estadísticas
        $filtros_sin_paginacion_hoja_vida = [
            'tipo_mantenimiento' => $filtros['tipo_mantenimiento']
        ];
        $filtros_sin_paginacion_ordenes = [
            'tipo_mantenimiento' => $filtros['tipo_mantenimiento'],
            'tecnico' => $filtros['tecnico']
        ];
        $hoja_vida_completa = array_merge(
            $this->activo->obtenerHojaVida($id, $filtros_sin_paginacion_hoja_vida),
            $this->activo->obtenerOrdenesAsociadas($id, $filtros_sin_paginacion_ordenes)
        );

        $estadisticasOrdenes = $this->calcularEstadisticasOrdenes($ordenes_asociadas);
        $estadisticasMantenimientos = $this->calcularEstadisticasMantenimiento($hoja_vida_mantenimiento);
        $tendenciaOrdenes = $this->calcularTendenciaMensual($ordenes_asociadas);

        $metricasDecision = $this->construirMetricasDecision($estadisticasOrdenes, $estadisticasMantenimientos, $hoja_vida_completa);

        $tipos_mantenimiento = MAINTENANCE_TYPES;
        $categorias = ASSET_CATEGORIES;

        // Cargar operarios para el select de técnico
        $usuarioModel = new Usuario();
        $operarios = $usuarioModel->obtenerOperarios();

        // Historial de auditoría del activo (quién/qué/cuándo) — visible según permiso
        $historialAuditoria = [];
        try {
            $historialAuditoria = $this->activo->obtenerAuditoria([
                'id_activo' => $id,
                'limit' => 50,
                'offset' => 0
            ]);
        } catch (Exception $e) {
            $historialAuditoria = [];
        }
        $etiquetasCampos = Activo::camposAuditables();

        require_once __DIR__ . '/../views/activos/detalle.php';
    }

    private function procesarFotoPrincipal($archivo, $fotoActual = null)
    {
        if (empty($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $fotoActual;
        }

        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $_SESSION['mensaje'] = 'Error al subir la foto del activo. Inténtelo nuevamente.';
            $_SESSION['tipo_mensaje'] = 'error';
            return false;
        }

        if ($archivo['size'] > UPLOAD_MAX_SIZE) {
            $_SESSION['mensaje'] = 'La foto supera el tamaño máximo permitido (10MB).';
            $_SESSION['tipo_mensaje'] = 'error';
            return false;
        }

        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionesPermitidas)) {
            $_SESSION['mensaje'] = 'Formato de imagen no permitido. Use JPG, PNG, GIF o WEBP.';
            $_SESSION['tipo_mensaje'] = 'error';
            return false;
        }

        if (!@getimagesize($archivo['tmp_name'])) {
            $_SESSION['mensaje'] = 'El archivo subido no es una imagen válida.';
            $_SESSION['tipo_mensaje'] = 'error';
            return false;
        }

        $uploadDir = UPLOAD_DIR . 'activos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $nombreArchivo = uniqid('act_', true) . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $_SESSION['mensaje'] = 'No se pudo guardar la foto del activo.';
            $_SESSION['tipo_mensaje'] = 'error';
            return false;
        }

        if (!empty($fotoActual)) {
            $rutaAnterior = UPLOAD_DIR . $fotoActual;
            if (is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        return 'activos/' . $nombreArchivo;
    }

    private function calcularEstadisticasOrdenes($ordenes)
    {
        $estadisticas = [
            'total' => count($ordenes),
            'abiertas' => 0,
            'cerradas' => 0,
            'por_estado' => [],
            'por_criticidad' => [],
            'ultima' => null
        ];

        $ultimaFecha = null;

        foreach ($ordenes as $orden) {
            if (($orden['estado_orden'] ?? '') !== 'finalizado') {
                $estadisticas['abiertas']++;
            }

            $estado = $orden['estado_orden'] ?? 'desconocido';
            $estadisticas['por_estado'][$estado] = ($estadisticas['por_estado'][$estado] ?? 0) + 1;

            $criticidad = $orden['nivel_criticidad'] ?? 'sin_dato';
            $estadisticas['por_criticidad'][$criticidad] = ($estadisticas['por_criticidad'][$criticidad] ?? 0) + 1;

            if (!empty($orden['fecha_intervencion'])) {
                $fecha = $orden['fecha_intervencion'];
                if (!$ultimaFecha || $fecha > $ultimaFecha) {
                    $ultimaFecha = $fecha;
                    $estadisticas['ultima'] = $orden;
                }
            }
        }

        $estadisticas['cerradas'] = max($estadisticas['total'] - $estadisticas['abiertas'], 0);

        return $estadisticas;
    }

    private function calcularEstadisticasMantenimiento($registros)
    {
        $estadisticas = [
            'total' => count($registros),
            'costo_total' => 0,
            'costo_promedio' => 0,
            'ultima_fecha' => null
        ];

        foreach ($registros as $registro) {
            if (!empty($registro['costo_total'])) {
                $estadisticas['costo_total'] += (float) $registro['costo_total'];
            }

            if (!empty($registro['fecha_intervencion'])) {
                $fecha = $registro['fecha_intervencion'];
                if (!$estadisticas['ultima_fecha'] || $fecha > $estadisticas['ultima_fecha']) {
                    $estadisticas['ultima_fecha'] = $fecha;
                }
            }
        }

        if ($estadisticas['total'] > 0 && $estadisticas['costo_total'] > 0) {
            $estadisticas['costo_promedio'] = $estadisticas['costo_total'] / $estadisticas['total'];
        }

        return $estadisticas;
    }

    private function calcularTendenciaMensual($ordenes)
    {
        $resultado = [
            'labels' => [],
            'data' => []
        ];

        $conteo = [];
        $mesActual = new DateTime('first day of this month');

        for ($i = 5; $i >= 0; $i--) {
            $mes = clone $mesActual;
            $mes->modify("-$i month");
            $key = $mes->format('Y-m');
            $conteo[$key] = 0;
            $resultado['labels'][] = $mes->format('M Y');
        }

        foreach ($ordenes as $orden) {
            if (!empty($orden['fecha_intervencion'])) {
                $key = (new DateTime($orden['fecha_intervencion']))->format('Y-m');
                if (array_key_exists($key, $conteo)) {
                    $conteo[$key]++;
                }
            }
        }

        $resultado['data'] = array_values($conteo);

        return $resultado;
    }

    private function construirMetricasDecision($estadisticasOrdenes, $estadisticasMantenimientos, $registros)
    {
        $ultimaOrden = $estadisticasOrdenes['ultima'] ?? null;
        $ultimaIntervencion = null;
        foreach ($registros as $registro) {
            if (empty($registro['es_orden']) && !empty($registro['fecha_intervencion'])) {
                if (!$ultimaIntervencion || $registro['fecha_intervencion'] > $ultimaIntervencion['fecha_intervencion']) {
                    $ultimaIntervencion = $registro;
                }
            }
        }

        $criticidadAlta = $estadisticasOrdenes['por_criticidad']['critica'] ?? 0;
        $criticidadAlta += $estadisticasOrdenes['por_criticidad']['alta'] ?? 0;

        return [
            'resumen' => [
                ['label' => 'Órdenes Totales', 'value' => $estadisticasOrdenes['total'] ?? 0, 'icon' => 'clipboard-list', 'color' => 'info'],
                ['label' => 'Órdenes Abiertas', 'value' => $estadisticasOrdenes['abiertas'] ?? 0, 'icon' => 'exclamation-triangle', 'color' => 'warning'],
                ['label' => 'Intervenciones Registradas', 'value' => $estadisticasMantenimientos['total'] ?? 0, 'icon' => 'wrench', 'color' => 'success'],
                ['label' => 'Costo Total Histórico', 'value' => '$' . number_format($estadisticasMantenimientos['costo_total'] ?? 0, 2, ',', '.'), 'icon' => 'dollar-sign', 'color' => 'secondary']
            ],
            'insights' => [
                'ultima_orden' => !empty($ultimaOrden) ? [
                    'fecha' => date('d/m/Y', strtotime($ultimaOrden['fecha_intervencion'])),
                    'numero' => $ultimaOrden['numero_orden'],
                    'estado' => ORDER_STATES[$ultimaOrden['estado_orden']] ?? $ultimaOrden['estado_orden'],
                    'criticidad' => CRITICITY_LEVELS[$ultimaOrden['nivel_criticidad']] ?? $ultimaOrden['nivel_criticidad'],
                    'tecnico' => $ultimaOrden['tecnico_responsable'] ?? 'N/A'
                ] : null,
                'criticidad_alta' => $criticidadAlta,
                'ultima_intervencion' => !empty($estadisticasMantenimientos['ultima_fecha'])
                    ? date('d/m/Y', strtotime($estadisticasMantenimientos['ultima_fecha']))
                    : null
            ]
        ];
    }

    /**
     * Mostrar formulario de edición
     */
    public function editar($id) {
        require_once __DIR__ . '/../models/Usuario.php';
        
        // Verificar permiso
        $rol = $_SESSION['usuario_rol'] ?? '';
        if (!Usuario::tienePermiso($rol, 'editar_activos')) {
            $_SESSION['mensaje'] = 'No tiene permisos para editar activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }
        
        $activo = $this->activo->obtenerPorId($id);
        
        if (!$activo) {
            $_SESSION['mensaje'] = 'Activo no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos');
            exit;
        }

        $categorias = ASSET_CATEGORIES;
        $estados = ASSET_STATES;
        $usuarioModel = new Usuario();
        $operarios = $usuarioModel->obtenerOperarios();
        $rutas = ASSET_CATEGORIES; // Las rutas son las mismas categorías
        $areas = ASSIGNED_AREAS;
        $ubicaciones = ASSET_LOCATIONS;

        require_once __DIR__ . '/../views/activos/form.php';
    }

    /**
     * Procesar actualización
     */
    public function actualizar($id) {
        $nombre_activo = trim($_POST['nombre_activo'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        if ($nombre_activo === '') {
            $_SESSION['mensaje'] = 'El nombre del activo es obligatorio.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=editar&id=' . $id);
            exit;
        }
        if ($categoria === '' || !array_key_exists($categoria, ASSET_CATEGORIES)) {
            $_SESSION['mensaje'] = 'Debe seleccionar una categoría válida.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=editar&id=' . $id);
            exit;
        }
        $this->activo->id_activo = $id;
        $this->activo->nombre_activo = $nombre_activo;
        $this->activo->codigo_interno = $_POST['codigo_interno'] ?? '';
        $this->activo->codigo_patrimonial = $_POST['codigo_patrimonial'] ?? '';
        $this->activo->descripcion_general = $_POST['descripcion_general'] ?? '';
        $this->activo->categoria = $categoria;
        $this->activo->ubicacion = $_POST['ubicacion'] ?? '';
        $this->activo->ruta = $_POST['ruta'] ?? '';
        $this->activo->responsable = $_POST['responsable'] ?? '';
        $this->activo->area_asignada = $_POST['area_asignada'] ?? '';
        $this->activo->marca = $_POST['marca'] ?? '';
        $this->activo->modelo = $_POST['modelo'] ?? '';
        $this->activo->numero_serie = $_POST['numero_serie'] ?? '';
        $this->activo->fecha_adquisicion = $_POST['fecha_adquisicion'] ?? null;
        $this->activo->valor_adquisicion = $_POST['valor_adquisicion'] ?? 0;
        $estado_actual = $_POST['estado_actual'] ?? 'operativo';
        if (!array_key_exists($estado_actual, ASSET_STATES)) {
            $estado_actual = 'operativo';
        }
        $this->activo->estado_actual = $estado_actual;
        $this->activo->vida_util_estimada = $_POST['vida_util_estimada'] ?? null;
        $this->activo->kilometraje = $_POST['kilometraje'] ?? 0;
        $this->activo->horas_uso = $_POST['horas_uso'] ?? 0;
        $this->activo->proximo_mantenimiento = $_POST['proximo_mantenimiento'] ?? null;
        $this->activo->observaciones = $_POST['observaciones'] ?? '';
        $this->activo->usuario_creacion = $_SESSION['usuario_id'] ?? 1;
        $activoExistente = $this->activo->obtenerPorId($id);
        $fotoActual = $activoExistente['foto_principal'] ?? null;
        $fotoProcesada = $this->procesarFotoPrincipal($_FILES['foto_principal'] ?? null, $fotoActual);
        if ($fotoProcesada === false) {
            header('Location: index.php?action=activos&subaction=editar&id=' . $id);
            return;
        }
        $this->activo->foto_principal = $fotoProcesada;

        if ($this->activo->actualizar()) {
            $_SESSION['mensaje'] = 'Activo actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=activos&subaction=ver&id=' . $id);
        } else {
            $_SESSION['mensaje'] = 'Error al actualizar el activo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=editar&id=' . $id);
        }
    }

    /**
     * Eliminar activo
     */
    public function eliminar($id) {
        require_once __DIR__ . '/../models/Usuario.php';
        
        // Verificar permiso
        $rol = $_SESSION['usuario_rol'] ?? '';
        if (!Usuario::tienePermiso($rol, 'eliminar_activos')) {
            $_SESSION['mensaje'] = 'No tiene permisos para eliminar activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos');
            exit;
        }
        
        $this->activo->id_activo = $id;
        $this->activo->usuario_creacion = $_SESSION['usuario_id'] ?? 1;

        if ($this->activo->eliminar()) {
            $_SESSION['mensaje'] = 'Activo eliminado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar el activo';
            $_SESSION['tipo_mensaje'] = 'error';
        }

        header('Location: index.php?action=activos');
    }

    /**
     * Sección de auditoría (solo administradores).
     * Muestra quién creó/editó/eliminó cada activo y cuándo, con detalle por campo.
     */
    public function auditoria() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        if (!Usuario::esAdminGeneral($rol)) {
            $_SESSION['mensaje'] = 'Acceso restringido: la auditoría es solo para administradores.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }

        require_once __DIR__ . '/../models/Usuario.php';
        $usuarioModel = new Usuario();

        $filtros = [
            'tipo_operacion' => strtoupper(trim($_GET['tipo'] ?? '')),
            'id_usuario' => (int)($_GET['usuario'] ?? 0),
            'busqueda' => trim($_GET['busqueda'] ?? ''),
            'fecha_desde' => trim($_GET['desde'] ?? ''),
            'fecha_hasta' => trim($_GET['hasta'] ?? ''),
            'id_activo' => (int)($_GET['id_activo'] ?? 0),
        ];
        if (!in_array($filtros['tipo_operacion'], ['INSERT', 'UPDATE', 'DELETE'], true)) {
            $filtros['tipo_operacion'] = '';
        }
        if ($filtros['id_usuario'] <= 0) $filtros['id_usuario'] = '';
        if ($filtros['id_activo'] <= 0) $filtros['id_activo'] = '';
        // Validar fechas simples YYYY-MM-DD
        foreach (['fecha_desde', 'fecha_hasta'] as $k) {
            if (!empty($filtros[$k]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros[$k])) {
                $filtros[$k] = '';
            }
        }

        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 30;
        $offset = ($pagina - 1) * $porPagina;

        $total = $this->activo->contarAuditoria($filtros);
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
            $offset = ($pagina - 1) * $porPagina;
        }

        $registros = $this->activo->obtenerAuditoria($filtros + ['limit' => $porPagina, 'offset' => $offset]);
        $etiquetasCampos = Activo::camposAuditables();
        $usuariosLista = $usuarioModel->listar();

        require_once __DIR__ . '/../views/activos/auditoria.php';
    }

    /**
     * Mostrar formulario de carga masiva
     */
    public function cargaMasiva() {
        require_once __DIR__ . '/../models/Usuario.php';
        $rol = $_SESSION['usuario_rol'] ?? '';
        if (!Usuario::tienePermiso($rol, 'crear_activos')) {
            $_SESSION['mensaje'] = 'No tiene permisos para carga masiva de activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos');
            exit;
        }
        require_once __DIR__ . '/../views/activos/carga_masiva.php';
    }

    /**
     * Cómo funciona la carga masiva (resumen para la vista):
     * 1) Se sube un CSV UTF-8 con encabezados (ver plantilla).
     * 2) Se detecta el delimitador (, ; TAB) y se normalizan encabezados.
     * 3) Cada fila se valida (nombre, categoría, estado, fechas, duplicados).
     * 4) Las filas válidas se insertan una por una y cada una deja auditoría INSERT origen=carga_masiva.
     * 5) Las filas con error NO se insertan y se reportan por número de línea para corregir y re-subir.
     */
    public function procesarCargaMasiva() {
        require_once __DIR__ . '/../models/Usuario.php';
        $rol = $_SESSION['usuario_rol'] ?? '';
        if (!Usuario::tienePermiso($rol, 'crear_activos')) {
            $_SESSION['mensaje'] = 'No tiene permisos para carga masiva de activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos');
            return;
        }

        if (!isset($_FILES['archivo_csv']) || ($_FILES['archivo_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['mensaje'] = 'Error al subir el archivo. Verifique que sea un CSV válido de máximo 10MB.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=carga_masiva');
            return;
        }

        if (($_FILES['archivo_csv']['size'] ?? 0) > UPLOAD_MAX_SIZE) {
            $_SESSION['mensaje'] = 'El archivo supera el tamaño máximo permitido (10MB).';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=carga_masiva');
            return;
        }

        $nombreOriginal = $_FILES['archivo_csv']['name'] ?? '';
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            $_SESSION['mensaje'] = 'Formato no permitido. Suba un archivo .csv (se acepta .txt con formato CSV).';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=carga_masiva');
            return;
        }

        $archivo = $_FILES['archivo_csv']['tmp_name'];
        try {
            $resultado = $this->importarCSV($archivo);
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error procesando el archivo: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=carga_masiva');
            return;
        }

        $_SESSION['mensaje'] = "Carga masiva: {$resultado['exitosos']} creados, {$resultado['errores']} con errores, {$resultado['omitidos']} omitidos (de {$resultado['total_filas']} filas).";
        $_SESSION['tipo_mensaje'] = $resultado['errores'] > 0 ? 'warning' : 'success';
        // Guardar solo primeros 300 detalles para no saturar la sesión
        $_SESSION['detalle_carga'] = array_slice($resultado['detalles'], 0, 300);
        $_SESSION['resumen_carga'] = [
            'exitosos' => $resultado['exitosos'],
            'errores' => $resultado['errores'],
            'omitidos' => $resultado['omitidos'],
            'total' => $resultado['total_filas'],
        ];

        header('Location: index.php?action=activos&subaction=carga_masiva');
    }

    private function detectarDelimitador($lineaEncabezado) {
        $candidatos = [',', ';', "\t"];
        $mejor = ',';
        $max = 0;
        foreach ($candidatos as $d) {
            $n = count(str_getcsv($lineaEncabezado, $d));
            if ($n > $max) {
                $max = $n;
                $mejor = $d;
            }
        }
        return $mejor;
    }

    private function normalizarFechaCSV($valor) {
        $valor = trim((string)($valor ?? ''));
        if ($valor === '' || $valor === '0000-00-00') return null;
        // Acepta YYYY-MM-DD o DD/MM/YYYY o DD-MM-YYYY
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $m)) {
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $valor : false;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $valor, $m)) {
            $d = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            if (!checkdate((int)$mo, (int)$d, (int)$m[3])) return false;
            return "{$m[3]}-{$mo}-{$d}";
        }
        return false;
    }

    /**
     * Importar activos desde CSV (robusto).
     * Soporta todas las columnas del activo, valida y evita duplicados.
     */
    private function importarCSV($archivo) {
        $resultado = [
            'exitosos' => 0,
            'errores' => 0,
            'omitidos' => 0,
            'total_filas' => 0,
            'detalles' => []
        ];

        $contenido = @file_get_contents($archivo);
        if ($contenido === false || $contenido === '') {
            throw new Exception('El archivo está vacío o no se pudo leer.');
        }
        // Quitar BOM UTF-8 si existe (típico de Excel)
        if (substr($contenido, 0, 3) === "\xEF\xBB\xBF") {
            $contenido = substr($contenido, 3);
        }
        // Convertir a UTF-8 si viene en Latin1/Windows-1252
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }
        $tmpNormalizado = tempnam(sys_get_temp_dir(), 'roma_csv_');
        file_put_contents($tmpNormalizado, $contenido);

        $handle = fopen($tmpNormalizado, 'r');
        if ($handle === false) {
            throw new Exception('No se pudo abrir el archivo para lectura.');
        }

        $primeraLinea = fgets($handle);
        if ($primeraLinea === false) {
            fclose($handle);
            @unlink($tmpNormalizado);
            throw new Exception('El archivo no tiene encabezados.');
        }
        $delimitador = $this->detectarDelimitador($primeraLinea);
        rewind($handle);

        $encabezadosRaw = fgetcsv($handle, 0, $delimitador);
        if (!$encabezadosRaw) {
            fclose($handle);
            @unlink($tmpNormalizado);
            throw new Exception('No se pudieron leer los encabezados del CSV.');
        }
        $encabezados = array_map(function ($h) {
            return strtolower(trim((string)$h));
        }, $encabezadosRaw);

        $columnasSoportadas = ['nombre_activo','codigo_interno','codigo_patrimonial','descripcion_general','categoria','ubicacion','ruta','responsable','area_asignada','marca','modelo','numero_serie','fecha_adquisicion','valor_adquisicion','estado_actual','vida_util_estimada','kilometraje','horas_uso','proximo_mantenimiento','observaciones'];
        $mapeo = [];
        foreach ($columnasSoportadas as $col) {
            $idx = array_search($col, $encabezados, true);
            $mapeo[$col] = ($idx !== false) ? $idx : null;
        }

        if ($mapeo['nombre_activo'] === null) {
            fclose($handle);
            @unlink($tmpNormalizado);
            throw new Exception('Falta la columna obligatoria "nombre_activo". Descargue la plantilla.');
        }
        if ($mapeo['categoria'] === null) {
            fclose($handle);
            @unlink($tmpNormalizado);
            throw new Exception('Falta la columna obligatoria "categoria". Descargue la plantilla.');
        }

        $categoriasValidas = array_keys(ASSET_CATEGORIES);
        $estadosValidos = array_keys(ASSET_STATES);
        $codigosExistentes = $this->activo->obtenerCodigosInternosExistentes();
        $vistosEnArchivo = [];
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        $linea = 1; // encabezado
        $MAX_FILAS = 5000;
        while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
            $linea++;
            if ($linea > $MAX_FILAS + 1) {
                $resultado['omitidos']++;
                $resultado['detalles'][] = "Línea $linea: omitida, supera el máximo de $MAX_FILAS filas por carga.";
                continue;
            }
            // Fila totalmente vacía
            $todosVacios = true;
            foreach ($data as $c) {
                if (trim((string)$c) !== '') {
                    $todosVacios = false;
                    break;
                }
            }
            if ($todosVacios) {
                $resultado['omitidos']++;
                continue;
            }
            $resultado['total_filas']++;

            $get = function ($col) use ($data, $mapeo) {
                $i = $mapeo[$col];
                if ($i === null || !array_key_exists($i, $data)) return '';
                return trim((string)$data[$i]);
            };

            $nombre = $get('nombre_activo');
            $categoria = strtolower($get('categoria'));
            $estado = strtolower($get('estado_actual'));
            if ($estado === '') $estado = 'operativo';
            $codigoInterno = $get('codigo_interno');

            // Validaciones
            if ($nombre === '') {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: el nombre_activo es obligatorio.";
                continue;
            }
            if (!in_array($categoria, $categoriasValidas, true)) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: categoría inválida '$categoria'. Válidas: " . implode(', ', $categoriasValidas) . ".";
                continue;
            }
            if (!in_array($estado, $estadosValidos, true)) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: estado inválido '$estado'. Válidos: " . implode(', ', $estadosValidos) . ".";
                continue;
            }
            if ($codigoInterno !== '') {
                $clave = mb_strtolower($codigoInterno);
                if (isset($codigosExistentes[$clave]) || isset($vistosEnArchivo[$clave])) {
                    $resultado['errores']++;
                    $resultado['detalles'][] = "Línea $linea: código_interno '$codigoInterno' duplicado (ya existe).";
                    continue;
                }
            }
            $fechaAdq = $this->normalizarFechaCSV($get('fecha_adquisicion'));
            if ($fechaAdq === false) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: fecha_adquisicion inválida. Use YYYY-MM-DD o DD/MM/YYYY.";
                continue;
            }
            $fechaProx = $this->normalizarFechaCSV($get('proximo_mantenimiento'));
            if ($fechaProx === false) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: proximo_mantenimiento inválido. Use YYYY-MM-DD o DD/MM/YYYY.";
                continue;
            }
            foreach (['valor_adquisicion' => $get('valor_adquisicion'), 'kilometraje' => $get('kilometraje'), 'horas_uso' => $get('horas_uso')] as $campoNum => $valNum) {
                if ($valNum !== '' && !is_numeric(str_replace(',', '.', $valNum))) {
                    $resultado['errores']++;
                    $resultado['detalles'][] = "Línea $linea: $campoNum debe ser numérico.";
                    continue 2;
                }
            }
            $vidaUtil = $get('vida_util_estimada');
            if ($vidaUtil !== '' && !ctype_digit($vidaUtil)) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: vida_util_estimada debe ser un número entero de meses.";
                continue;
            }

            try {
                $this->activo->limpiarParaImportacion();
                $this->activo->nombre_activo = mb_strtoupper($nombre, 'UTF-8');
                $this->activo->codigo_interno = $codigoInterno;
                $this->activo->codigo_patrimonial = $get('codigo_patrimonial');
                $this->activo->descripcion_general = $get('descripcion_general');
                $this->activo->categoria = $categoria;
                $this->activo->ubicacion = $get('ubicacion');
                $ruta = strtolower($get('ruta'));
                $this->activo->ruta = in_array($ruta, $categoriasValidas, true) ? $ruta : $categoria;
                $this->activo->responsable = $get('responsable');
                $this->activo->area_asignada = strtolower($get('area_asignada'));
                $this->activo->marca = $get('marca');
                $this->activo->modelo = $get('modelo');
                $this->activo->numero_serie = $get('numero_serie');
                $this->activo->fecha_adquisicion = $fechaAdq;
                $val = str_replace(',', '.', $get('valor_adquisicion'));
                $this->activo->valor_adquisicion = $val === '' ? 0 : $val;
                $this->activo->estado_actual = $estado;
                $this->activo->vida_util_estimada = $vidaUtil === '' ? null : (int)$vidaUtil;
                $km = str_replace(',', '.', $get('kilometraje'));
                $this->activo->kilometraje = $km === '' ? 0 : $km;
                $hu = str_replace(',', '.', $get('horas_uso'));
                $this->activo->horas_uso = $hu === '' ? 0 : $hu;
                $this->activo->proximo_mantenimiento = $fechaProx;
                $this->activo->observaciones = $get('observaciones');
                $this->activo->usuario_creacion = $usuarioId;
                $this->activo->origen_auditoria = 'carga_masiva';

                if ($this->activo->crear()) {
                    $resultado['exitosos']++;
                    if ($codigoInterno !== '') {
                        $vistosEnArchivo[mb_strtolower($codigoInterno)] = true;
                        $codigosExistentes[mb_strtolower($codigoInterno)] = true;
                    }
                } else {
                    $resultado['errores']++;
                    $resultado['detalles'][] = "Línea $linea ($nombre): no se pudo guardar (posible duplicado o error DB).";
                }
            } catch (Exception $e) {
                $resultado['errores']++;
                $msg = $e->getMessage();
                // Traducir duplicado MySQL a mensaje claro
                if (stripos($msg, 'Duplicate') !== false && stripos($msg, 'codigo_interno') !== false) {
                    $msg = "código_interno '$codigoInterno' duplicado.";
                }
                $resultado['detalles'][] = "Línea $linea ($nombre): $msg";
            }
        }
        fclose($handle);
        @unlink($tmpNormalizado);

        if ($resultado['total_filas'] === 0) {
            throw new Exception('El archivo no contiene filas de datos.');
        }

        return $resultado;
    }
}
