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
        $this->activo->nombre_activo = $_POST['nombre_activo'] ?? '';
        $this->activo->codigo_interno = $_POST['codigo_interno'] ?? '';
        $this->activo->codigo_patrimonial = $_POST['codigo_patrimonial'] ?? '';
        $this->activo->descripcion_general = $_POST['descripcion_general'] ?? '';
        $this->activo->categoria = $_POST['categoria'] ?? '';
        $this->activo->ubicacion = $_POST['ubicacion'] ?? '';
        $this->activo->ruta = $_POST['ruta'] ?? '';
        $this->activo->responsable = $_POST['responsable'] ?? '';
        $this->activo->area_asignada = $_POST['area_asignada'] ?? '';
        $this->activo->marca = $_POST['marca'] ?? '';
        $this->activo->modelo = $_POST['modelo'] ?? '';
        $this->activo->numero_serie = $_POST['numero_serie'] ?? '';
        $this->activo->fecha_adquisicion = $_POST['fecha_adquisicion'] ?? null;
        $this->activo->valor_adquisicion = $_POST['valor_adquisicion'] ?? 0;
        $this->activo->estado_actual = $_POST['estado_actual'] ?? 'operativo';
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
        $this->activo->id_activo = $id;
        $this->activo->nombre_activo = $_POST['nombre_activo'] ?? '';
        $this->activo->codigo_interno = $_POST['codigo_interno'] ?? '';
        $this->activo->codigo_patrimonial = $_POST['codigo_patrimonial'] ?? '';
        $this->activo->descripcion_general = $_POST['descripcion_general'] ?? '';
        $this->activo->categoria = $_POST['categoria'] ?? '';
        $this->activo->ubicacion = $_POST['ubicacion'] ?? '';
        $this->activo->ruta = $_POST['ruta'] ?? '';
        $this->activo->responsable = $_POST['responsable'] ?? '';
        $this->activo->area_asignada = $_POST['area_asignada'] ?? '';
        $this->activo->marca = $_POST['marca'] ?? '';
        $this->activo->modelo = $_POST['modelo'] ?? '';
        $this->activo->numero_serie = $_POST['numero_serie'] ?? '';
        $this->activo->fecha_adquisicion = $_POST['fecha_adquisicion'] ?? null;
        $this->activo->valor_adquisicion = $_POST['valor_adquisicion'] ?? 0;
        $this->activo->estado_actual = $_POST['estado_actual'] ?? 'operativo';
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
     * Mostrar formulario de carga masiva
     */
    public function cargaMasiva() {
        require_once __DIR__ . '/../views/activos/carga_masiva.php';
    }

    /**
     * Procesar carga masiva CSV
     */
    public function procesarCargaMasiva() {
        if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['mensaje'] = 'Error al subir el archivo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=activos&subaction=carga_masiva');
            return;
        }

        $archivo = $_FILES['archivo_csv']['tmp_name'];
        $resultado = $this->importarCSV($archivo);

        $_SESSION['mensaje'] = "Procesados: {$resultado['exitosos']} exitosos, {$resultado['errores']} con errores";
        $_SESSION['tipo_mensaje'] = $resultado['errores'] > 0 ? 'warning' : 'success';
        $_SESSION['detalle_carga'] = $resultado['detalles'];

        header('Location: index.php?action=activos&subaction=carga_masiva');
    }

    /**
     * Importar activos desde CSV
     */
    private function importarCSV($archivo) {
        $resultado = [
            'exitosos' => 0,
            'errores' => 0,
            'detalles' => []
        ];

        if (($handle = fopen($archivo, 'r')) !== false) {
            // Leer encabezados
            $encabezados = fgetcsv($handle, 1000, ',');
            
            // Mapeo de columnas
            $mapeo = [
                'nombre_activo' => array_search('nombre_activo', $encabezados) !== false ? array_search('nombre_activo', $encabezados) : null,
                'codigo_interno' => array_search('codigo_interno', $encabezados) !== false ? array_search('codigo_interno', $encabezados) : null,
                'codigo_patrimonial' => array_search('codigo_patrimonial', $encabezados) !== false ? array_search('codigo_patrimonial', $encabezados) : null,
                'categoria' => array_search('categoria', $encabezados) !== false ? array_search('categoria', $encabezados) : null,
                'ubicacion' => array_search('ubicacion', $encabezados) !== false ? array_search('ubicacion', $encabezados) : null,
                'marca' => array_search('marca', $encabezados) !== false ? array_search('marca', $encabezados) : null,
                'modelo' => array_search('modelo', $encabezados) !== false ? array_search('modelo', $encabezados) : null,
                'estado_actual' => array_search('estado_actual', $encabezados) !== false ? array_search('estado_actual', $encabezados) : null,
            ];

            $linea = 1;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $linea++;
                
                if (count($data) < 2) continue;

                try {
                    $this->activo->nombre_activo = $data[$mapeo['nombre_activo']] ?? '';
                    $this->activo->codigo_interno = $data[$mapeo['codigo_interno']] ?? '';
                    $this->activo->codigo_patrimonial = $data[$mapeo['codigo_patrimonial']] ?? '';
                    $this->activo->categoria = $data[$mapeo['categoria']] ?? 'equipo_especial';
                    $this->activo->ubicacion = $data[$mapeo['ubicacion']] ?? '';
                    $this->activo->marca = $data[$mapeo['marca']] ?? '';
                    $this->activo->modelo = $data[$mapeo['modelo']] ?? '';
                    $this->activo->estado_actual = $data[$mapeo['estado_actual']] ?? 'operativo';
                    $this->activo->usuario_creacion = $_SESSION['usuario_id'] ?? 1;

                    if ($this->activo->crear()) {
                        $resultado['exitosos']++;
                        $resultado['detalles'][] = "Línea $linea: Activo creado correctamente";
                    } else {
                        $resultado['errores']++;
                        $resultado['detalles'][] = "Línea $linea: Error al crear activo";
                    }
                } catch (Exception $e) {
                    $resultado['errores']++;
                    $resultado['detalles'][] = "Línea $linea: " . $e->getMessage();
                }
            }
            fclose($handle);
        }

        return $resultado;
    }
}
