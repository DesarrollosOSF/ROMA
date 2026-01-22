<?php
/**
 * Controlador de Órdenes de Trabajo
 * Sistema ROMA - Módulo de Órdenes de Trabajo
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/OrdenTrabajo.php';
require_once __DIR__ . '/../models/Activo.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class OrdenesController {
    private $orden;
    private $activo;
    private $usuario;

    public function __construct() {
        AuthController::verificarAutenticacion();
        
        try {
            $this->orden = new OrdenTrabajo();
            $this->activo = new Activo();
            $this->usuario = new Usuario();
        } catch (Exception $e) {
            error_log("Error al inicializar modelos en OrdenesController: " . $e->getMessage());
            $_SESSION['mensaje'] = 'Error al inicializar el sistema. Por favor, contacte al administrador.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    /**
     * Listar órdenes de trabajo
     */
    public function index() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        $usuario_id = $_SESSION['usuario_id'] ?? null;

        $filtros = [
            'estado' => $_GET['estado'] ?? '',
            'tipo_mantenimiento' => $_GET['tipo'] ?? '',
            'criticidad' => $_GET['criticidad'] ?? '',
            'asignado_a' => $_GET['asignado'] ?? '',
            'activo' => $_GET['activo'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];

        // Si es operario, solo ver sus órdenes asignadas
        if ($rol === 'operario') {
            $filtros['asignado_a'] = $usuario_id;
        }

        // Si es jefe o director, solo ver órdenes de sus solicitudes
        if (in_array($rol, ['jefe', 'director'])) {
            $filtros['solicitante'] = $usuario_id;
        }

        $ordenes = $this->orden->listar($filtros);
        $activos = $this->activo->listar();
        $operarios = $this->usuario->obtenerOperarios();
        
        $tipos_mantenimiento = MAINTENANCE_TYPES;
        $estados = ORDER_STATES;
        $criticidades = CRITICITY_LEVELS;

        require_once __DIR__ . '/../views/ordenes/index.php';
    }

    /**
     * Mostrar formulario de nueva orden
     */
    public function crear() {
        try {
            $orden = []; // Inicializar orden vacío para nueva orden
            
            // Cargar datos necesarios
            $activos = [];
            $operarios = [];
            $usuarios = [];
            
            try {
                $activos = $this->activo->listar();
                if (!is_array($activos)) {
                    $activos = [];
                }
            } catch (Exception $e) {
                error_log("Error al cargar activos: " . $e->getMessage());
                $activos = [];
            }
            
            try {
                $operarios = $this->usuario->obtenerOperarios();
                if (!is_array($operarios)) {
                    $operarios = [];
                }
            } catch (Exception $e) {
                error_log("Error al cargar operarios: " . $e->getMessage());
                $operarios = [];
            }
            
            try {
                $usuarios = $this->usuario->listar(['activo' => 1]);
                if (!is_array($usuarios)) {
                    $usuarios = [];
                }
            } catch (Exception $e) {
                error_log("Error al cargar usuarios: " . $e->getMessage());
                $usuarios = [];
            }
            
            // Cargar constantes con validación
            $tipos_mantenimiento = defined('MAINTENANCE_TYPES') ? MAINTENANCE_TYPES : [
                'instalacion' => 'Instalación',
                'correctivo' => 'Correctivo',
                'preventivo' => 'Preventivo'
            ];
            
            $criticidades = defined('CRITICITY_LEVELS') ? CRITICITY_LEVELS : [
                'critica' => 'Crítica',
                'alta' => 'Alta',
                'normal' => 'Normal',
                'baja' => 'Baja'
            ];
            
            $estados = defined('ORDER_STATES') ? ORDER_STATES : [
                'recibido' => 'Recibido',
                'en_proceso' => 'En Proceso',
                'rechazado' => 'Rechazado',
                'finalizado' => 'Finalizado'
            ];

            // Cargar rutas (mismas que las categorías)
            $rutas = defined('ASSET_CATEGORIES') ? ASSET_CATEGORIES : [];

            require_once __DIR__ . '/../views/ordenes/form.php';
        } catch (Exception $e) {
            error_log("Error en OrdenesController::crear(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['mensaje'] = 'Error al cargar el formulario: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
    }

    /**
     * Procesar creación de orden
     */
    public function guardar() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        
        // Verificar permiso para crear órdenes
        if (!Usuario::tienePermiso($rol, 'crear_ordenes')) {
            $_SESSION['mensaje'] = 'No tiene permisos para crear órdenes de trabajo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
        
        $this->orden->id_activo = $_POST['id_activo'] ?? null;
        $this->orden->id_solicitud = $_POST['id_solicitud'] ?? null;
        $this->orden->id_solicitante = $_SESSION['usuario_id'] ?? ($_POST['id_solicitante'] ?? null);
        $this->orden->id_usuario_asignado = $_POST['id_usuario_asignado'] ?? null;
        $this->orden->tipo_mantenimiento = $_POST['tipo_mantenimiento'] ?? 'correctivo';
        $this->orden->nivel_criticidad = $_POST['nivel_criticidad'] ?? 'normal';
        $this->orden->descripcion_corta = $_POST['descripcion_corta'] ?? '';
        $this->orden->descripcion_detallada = $_POST['descripcion_detallada'] ?? '';
        $this->orden->estado_proceso = 'recibido';
        $this->orden->fecha_limite_ejecucion = $_POST['fecha_limite_ejecucion'] ?? null;
        $this->orden->usuario_creacion = $_SESSION['usuario_id'] ?? 1;

        if ($this->orden->crear()) {
            $id_orden = $this->orden->id_orden;
            $id_usuario = $_SESSION['usuario_id'] ?? 1;
            
            // Registrar en historial
            $this->orden->registrarHistorial(
                $id_orden,
                $id_usuario,
                'creacion',
                null,
                null,
                null,
                null,
                'Orden de trabajo creada'
            );
            
            // Procesar adjuntos si existen
            if (!empty($_FILES['adjuntos']['name'][0])) {
                $this->procesarAdjuntos($id_orden);
            }
            
            $_SESSION['mensaje'] = 'Orden de trabajo creada correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=ordenes');
        } else {
            $_SESSION['mensaje'] = 'Error al crear la orden de trabajo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes&subaction=crear');
        }
    }

    /**
     * Procesar adjuntos subidos
     */
    private function procesarAdjuntos($id_orden) {
        if (!isset($_FILES['adjuntos']) || empty($_FILES['adjuntos']['name'][0])) {
            return;
        }

        $upload_dir = UPLOAD_DIR . 'ordenes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $files = $_FILES['adjuntos'];
        $file_count = count($files['name']);
        $id_usuario = $_SESSION['usuario_id'] ?? 1;

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['tmp_name'][$i];
                $original_name = $files['name'][$i];
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];

                // Validar tamaño
                if ($file_size > UPLOAD_MAX_SIZE) {
                    continue;
                }

                // Validar extensión
                $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                    continue;
                }

                // Generar nombre único
                $new_name = uniqid('ord_' . $id_orden . '_', true) . '.' . $extension;
                $destination = $upload_dir . $new_name;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $ruta_relativa = 'ordenes/' . $new_name;
                    $this->orden->agregarAdjunto(
                        $id_orden,
                        $original_name,
                        $ruta_relativa,
                        $file_type,
                        $file_size,
                        $id_usuario
                    );
                }
            }
        }
    }

    /**
     * Ver detalle de orden
     */
    public function ver($id) {
        $rol = $_SESSION['usuario_rol'] ?? '';
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        
        $orden = $this->orden->obtenerPorId($id);
        
        if (!$orden) {
            $_SESSION['mensaje'] = 'Orden de trabajo no encontrada';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }

        // Verificar acceso según rol
        if ($rol === 'operario') {
            // Operario solo puede ver órdenes asignadas a él
            if ($orden['id_usuario_asignado'] != $usuario_id) {
                $_SESSION['mensaje'] = 'No tiene acceso a esta orden';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes');
                exit;
            }
        } elseif (in_array($rol, ['jefe', 'director'])) {
            // Jefe/Director solo puede ver órdenes que creó
            if ($orden['id_solicitante'] != $usuario_id) {
                $_SESSION['mensaje'] = 'No tiene acceso a esta orden';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes');
                exit;
            }
        }

        $activo = $this->activo->obtenerPorId($orden['id_activo']);
        $operarios = $this->usuario->obtenerOperarios();
        $historial = $this->orden->obtenerHistorial($id);
        
        $tipos_mantenimiento = MAINTENANCE_TYPES;
        $estados = ORDER_STATES;
        $criticidades = CRITICITY_LEVELS;

        require_once __DIR__ . '/../views/ordenes/detalle.php';
    }

    /**
     * Mostrar formulario de edición
     */
    public function editar($id) {
        $rol = $_SESSION['usuario_rol'] ?? '';
        
        // Verificar permiso para editar
        if (!Usuario::tienePermiso($rol, 'editar_ordenes')) {
            $_SESSION['mensaje'] = 'No tiene permisos para editar órdenes de trabajo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
        
        $orden = $this->orden->obtenerPorId($id);
        
        if (!$orden) {
            $_SESSION['mensaje'] = 'Orden de trabajo no encontrada';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }

        $activos = $this->activo->listar();
        $operarios = $this->usuario->obtenerOperarios();
        $usuarios = $this->usuario->listar(['activo' => 1]);
        
        $tipos_mantenimiento = MAINTENANCE_TYPES;
        $criticidades = CRITICITY_LEVELS;
        $estados = ORDER_STATES;
        
        // Cargar rutas (mismas que las categorías)
        $rutas = defined('ASSET_CATEGORIES') ? ASSET_CATEGORIES : [];

        require_once __DIR__ . '/../views/ordenes/form.php';
    }

    /**
     * Procesar actualización
     */
    public function actualizar($id) {
        $rol = $_SESSION['usuario_rol'] ?? '';
        
        // Verificar permiso para editar
        if (!Usuario::tienePermiso($rol, 'editar_ordenes')) {
            $_SESSION['mensaje'] = 'No tiene permisos para editar órdenes de trabajo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
        
        // Obtener orden actual para comparar cambios
        $orden_actual = $this->orden->obtenerPorId($id);
        if (!$orden_actual) {
            $_SESSION['mensaje'] = 'Orden no encontrada';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
        
        $id_usuario = $_SESSION['usuario_id'] ?? 1;
        
        $this->orden->id_orden = $id;
        $this->orden->id_activo = $_POST['id_activo'] ?? null;
        $this->orden->id_usuario_asignado = $_POST['id_usuario_asignado'] ?? null;
        $this->orden->tipo_mantenimiento = $_POST['tipo_mantenimiento'] ?? 'correctivo';
        $this->orden->nivel_criticidad = $_POST['nivel_criticidad'] ?? 'normal';
        $this->orden->descripcion_corta = $_POST['descripcion_corta'] ?? '';
        $this->orden->descripcion_detallada = $_POST['descripcion_detallada'] ?? '';
        $this->orden->estado_proceso = $_POST['estado_proceso'] ?? 'recibido';
        $this->orden->fecha_limite_ejecucion = $_POST['fecha_limite_ejecucion'] ?? null;

        if ($this->orden->actualizar()) {
            // Registrar cambios en historial
            $campos_cambiados = [];
            
            if ($orden_actual['id_activo'] != $this->orden->id_activo) {
                $this->orden->registrarHistorial($id, $id_usuario, 'edicion', 'id_activo', $orden_actual['id_activo'], 'id_activo', $this->orden->id_activo, 'Activo cambiado');
            }
            
            if ($orden_actual['id_usuario_asignado'] != $this->orden->id_usuario_asignado) {
                $this->orden->registrarHistorial($id, $id_usuario, 'asignacion', 'id_usuario_asignado', $orden_actual['id_usuario_asignado'], 'id_usuario_asignado', $this->orden->id_usuario_asignado, 'Operario asignado');
            }
            
            if ($orden_actual['estado_proceso'] != $this->orden->estado_proceso) {
                $this->orden->registrarHistorial($id, $id_usuario, 'estado', 'estado_proceso', $orden_actual['estado_proceso'], 'estado_proceso', $this->orden->estado_proceso, 'Estado actualizado');
            }
            
            if ($orden_actual['nivel_criticidad'] != $this->orden->nivel_criticidad) {
                $this->orden->registrarHistorial($id, $id_usuario, 'edicion', 'nivel_criticidad', $orden_actual['nivel_criticidad'], 'nivel_criticidad', $this->orden->nivel_criticidad, 'Criticidad actualizada');
            }
            
            // Procesar nuevos adjuntos si existen
            if (!empty($_FILES['adjuntos']['name'][0])) {
                $this->procesarAdjuntos($id);
                $this->orden->registrarHistorial($id, $id_usuario, 'adjunto', null, null, null, null, 'Archivos adjuntos agregados');
            }
            
            $_SESSION['mensaje'] = 'Orden de trabajo actualizada correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=ordenes&subaction=ver&id=' . $id);
        } else {
            $_SESSION['mensaje'] = 'Error al actualizar la orden de trabajo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes&subaction=editar&id=' . $id);
        }
    }

    /**
     * Eliminar orden
     */
    public function eliminar($id) {
        $rol = $_SESSION['usuario_rol'] ?? '';
        
        // Verificar permiso para eliminar
        if (!Usuario::tienePermiso($rol, 'eliminar_ordenes')) {
            $_SESSION['mensaje'] = 'No tiene permisos para eliminar órdenes de trabajo';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
        
        $this->orden->id_orden = $id;

        if ($this->orden->eliminar()) {
            $_SESSION['mensaje'] = 'Orden de trabajo eliminada correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar la orden de trabajo';
            $_SESSION['tipo_mensaje'] = 'error';
        }

        header('Location: index.php?action=ordenes');
    }

    /**
     * Cambiar estado de orden (para operarios)
     */
    public function cambiarEstado() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        $id_usuario = $_SESSION['usuario_id'] ?? null;
        
        // Verificar permiso
        if (!Usuario::tienePermiso($rol, 'cambiar_estado_orden')) {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No tiene permisos para cambiar el estado de órdenes']);
                exit;
            }
            $_SESSION['mensaje'] = 'No tiene permisos para cambiar el estado de órdenes';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
        
        $id_orden = $_POST['id_orden'] ?? null;
        $nuevo_estado = $_POST['nuevo_estado'] ?? '';
        $comentario = $_POST['comentario'] ?? '';
        
        if (!$id_orden || !$nuevo_estado) {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }
            $_SESSION['mensaje'] = 'Datos incompletos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
            exit;
        }
        
        // Verificar que el operario tenga acceso a esta orden
        if ($rol === 'operario') {
            $orden = $this->orden->obtenerPorId($id_orden);
            if (!$orden || $orden['id_usuario_asignado'] != $id_usuario) {
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'No tiene acceso a esta orden']);
                    exit;
                }
                $_SESSION['mensaje'] = 'No tiene acceso a esta orden';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes');
                exit;
            }
        }
        
        // Validar comentario
        if (empty(trim($comentario)) || strlen(trim($comentario)) < 10) {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'El comentario debe tener al menos 10 caracteres']);
                exit;
            }
            $_SESSION['mensaje'] = 'El comentario debe tener al menos 10 caracteres';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
            exit;
        }

        $requiereEvidencia = ($nuevo_estado === 'finalizado');
        $rutaEvidencia = null;
        $nombreEvidencia = null;
        $tipoEvidencia = null;
        $tamanoEvidencia = null;

        if ($requiereEvidencia) {
            $archivo = $_FILES['evidencia_finalizacion'] ?? null;

            if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                $mensaje = 'Debe adjuntar una evidencia fotográfica para finalizar la orden.';
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $mensaje]);
                    exit;
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
                exit;
            }

            if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $mensaje = 'No se pudo procesar la evidencia cargada. Inténtelo nuevamente.';
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $mensaje]);
                    exit;
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
                exit;
            }

            if ($archivo['size'] > UPLOAD_MAX_SIZE) {
                $mensaje = 'La evidencia supera el tamaño máximo permitido (10MB).';
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $mensaje]);
                    exit;
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
                exit;
            }

            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $extensionesPermitidas, true)) {
                $mensaje = 'Formato de evidencia no permitido. Use JPG, PNG, GIF o WEBP.';
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $mensaje]);
                    exit;
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
                exit;
            }

            if (!@getimagesize($archivo['tmp_name'])) {
                $mensaje = 'El archivo seleccionado no es una imagen válida.';
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $mensaje]);
                    exit;
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
                exit;
            }

            $uploadDir = UPLOAD_DIR . 'ordenes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $nombreArchivo = uniqid('evidencia_final_' . $id_orden . '_', true) . '.' . $extension;
            $rutaDestino = $uploadDir . $nombreArchivo;

            if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                $mensaje = 'No se pudo guardar la evidencia subida.';
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $mensaje]);
                    exit;
                }
                $_SESSION['mensaje'] = $mensaje;
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
                exit;
            }

            $rutaEvidencia = 'ordenes/' . $nombreArchivo;
            $nombreEvidencia = $archivo['name'] ?? $nombreArchivo;
            $tipoEvidencia = $archivo['type'] ?? null;
            $tamanoEvidencia = $archivo['size'] ?? null;
        }
        
        if ($this->orden->cambiarEstado($id_orden, $nuevo_estado, $id_usuario, $comentario, $rutaEvidencia)) {
            if ($rutaEvidencia) {
                $this->orden->agregarAdjunto(
                    $id_orden,
                    $nombreEvidencia,
                    $rutaEvidencia,
                    $tipoEvidencia,
                    $tamanoEvidencia,
                    $id_usuario,
                    'Evidencia finalización'
                );
            }

            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
                exit;
            }
            $_SESSION['mensaje'] = 'Estado de la orden actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            if ($rutaEvidencia) {
                $rutaCompleta = UPLOAD_DIR . $rutaEvidencia;
                if (is_file($rutaCompleta)) {
                    @unlink($rutaCompleta);
                }
            }

            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
                exit;
            }
            $_SESSION['mensaje'] = 'Error al actualizar el estado';
            $_SESSION['tipo_mensaje'] = 'error';
        }
        
        if (!$this->esAjax()) {
            header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
        }
    }

    /**
     * Reasignar orden a otro operario
     */
    public function reasignar() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        $id_usuario = $_SESSION['usuario_id'] ?? null;
        
        // Verificar permiso
        if (!Usuario::tienePermiso($rol, 'reasignar_orden')) {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No tiene permisos para reasignar órdenes']);
                exit;
            }
            $_SESSION['mensaje'] = 'No tiene permisos para reasignar órdenes';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes');
            exit;
        }
        
        $id_orden = $_POST['id_orden'] ?? null;
        $id_nuevo_operario = $_POST['id_nuevo_operario'] ?? null;
        $comentario = $_POST['comentario'] ?? '';
        
        if (!$id_orden || !$id_nuevo_operario) {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
                exit;
            }
            $_SESSION['mensaje'] = 'Datos incompletos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
            exit;
        }
        
        // Verificar que el operario tenga acceso a esta orden
        if ($rol === 'operario') {
            $orden = $this->orden->obtenerPorId($id_orden);
            if (!$orden || $orden['id_usuario_asignado'] != $id_usuario) {
                if ($this->esAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'No tiene acceso a esta orden']);
                    exit;
                }
                $_SESSION['mensaje'] = 'No tiene acceso a esta orden';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: index.php?action=ordenes');
                exit;
            }
        }
        
        if ($this->orden->reasignar($id_orden, $id_nuevo_operario, $id_usuario, $comentario)) {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Orden reasignada correctamente']);
                exit;
            }
            $_SESSION['mensaje'] = 'Orden reasignada correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error al reasignar la orden']);
                exit;
            }
            $_SESSION['mensaje'] = 'Error al reasignar la orden';
            $_SESSION['tipo_mensaje'] = 'error';
        }
        
        if (!$this->esAjax()) {
            header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
        }
    }
    
    /**
     * Verificar si la petición es AJAX
     */
    private function esAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Mostrar cronograma de trabajo
     */
    public function cronograma() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        $usuario_id = $_SESSION['usuario_id'] ?? null;

        // Obtener rango de fechas (por defecto semana actual)
        $vista = $_GET['vista'] ?? 'semanal'; // diaria, semanal, mensual
        
        // Calcular fechas según la vista
        if ($vista === 'semanal') {
            $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('monday this week'));
            $fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d', strtotime('sunday this week'));
        } elseif ($vista === 'mensual') {
            $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
            $fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
        } else { // diaria
            $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d');
            $fecha_hasta = $fecha_desde;
        }

        // Filtros adicionales
        $filtro_operario = $_GET['filtro_operario'] ?? null;
        $filtro_estado = $_GET['filtro_estado'] ?? null;
        $filtro_criticidad = $_GET['filtro_criticidad'] ?? null;
        $filtro_solicitante = null;

        // Para operarios: solo pueden ver órdenes asignadas a ellos mismos
        if ($rol === 'operario') {
            $filtro_operario = $usuario_id;
        }

        // Para jefes/directores: solo las órdenes que ellos crearon
        if (in_array($rol, ['jefe', 'director'])) {
            $filtro_operario = null; // Ignorar filtros externos
            $filtro_solicitante = $usuario_id;
        }

        $ordenes = $this->orden->obtenerParaCronograma($fecha_desde, $fecha_hasta, $filtro_operario, $filtro_estado, $filtro_criticidad, $filtro_solicitante);
        $operarios = $this->usuario->obtenerOperarios();
        
        // Calcular estadísticas de operarios
        $estadisticas_operarios = $this->calcularEstadisticasOperarios($ordenes, $fecha_desde, $fecha_hasta);
        
        $estados = ORDER_STATES;
        $criticidades = CRITICITY_LEVELS;

        require_once __DIR__ . '/../views/ordenes/cronograma.php';
    }
    
    /**
     * Calcular estadísticas de operarios para el cronograma
     */
    private function calcularEstadisticasOperarios($ordenes, $fecha_desde, $fecha_hasta) {
        $estadisticas = [];
        
        foreach ($ordenes as $orden) {
            $operario_id = $orden['id_usuario_asignado'] ?? 'sin_asignar';
            $operario_nombre = $orden['nombre_asignado'] ?? 'Sin Asignar';
            
            if (!isset($estadisticas[$operario_id])) {
                $estadisticas[$operario_id] = [
                    'nombre' => $operario_nombre,
                    'total' => 0,
                    'recibido' => 0,
                    'en_proceso' => 0,
                    'finalizado' => 0,
                    'rechazado' => 0,
                    'critica' => 0,
                    'alta' => 0,
                    'normal' => 0,
                    'baja' => 0,
                    'atrasadas' => 0
                ];
            }
            
            $estadisticas[$operario_id]['total']++;
            
            // Contar por estado
            $estado = $orden['estado_proceso'] ?? '';
            if (isset($estadisticas[$operario_id][$estado])) {
                $estadisticas[$operario_id][$estado]++;
            }
            
            // Contar por criticidad
            $criticidad = $orden['nivel_criticidad'] ?? '';
            if (isset($estadisticas[$operario_id][$criticidad])) {
                $estadisticas[$operario_id][$criticidad]++;
            }
            
            // Contar atrasadas
            $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
            $hoy = new DateTime();
            if ($fecha_limite < $hoy && $orden['estado_proceso'] !== 'finalizado') {
                $estadisticas[$operario_id]['atrasadas']++;
            }
        }
        
        return $estadisticas;
    }

    /**
     * Actualizar asignación desde cronograma (drag & drop)
     */
    public function actualizarAsignacion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Payload inválido',
                'detalle' => json_last_error_msg()
            ]);
            exit;
        }
        
        $id_orden = $data['id_orden'] ?? null;
        $id_usuario_asignado = $data['id_usuario_asignado'] ?? null;
        $fecha_limite = $data['fecha_limite'] ?? null;
        $id_usuario = $_SESSION['usuario_id'] ?? null;

        if (!empty($id_usuario_asignado) && !is_numeric($id_usuario_asignado)) {
            $id_usuario_asignado = null;
        } elseif (!empty($id_usuario_asignado)) {
            $id_usuario_asignado = (int)$id_usuario_asignado;
        } else {
            $id_usuario_asignado = null;
        }

        if (!$id_orden || !$fecha_limite || !$id_usuario) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        // Obtener orden actual para comparar cambios
        $orden_actual = $this->orden->obtenerPorId($id_orden);
        if (!$orden_actual) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Orden no encontrada']);
            exit;
        }

        $cambios = [];
        
        // Verificar si cambió el operario
        if ($orden_actual['id_usuario_asignado'] != $id_usuario_asignado) {
            $cambios['operario'] = [
                'anterior' => $orden_actual['id_usuario_asignado'],
                'nuevo' => $id_usuario_asignado
            ];
        }
        
        // Verificar si cambió la fecha
        if ($orden_actual['fecha_limite_ejecucion'] != $fecha_limite) {
            $cambios['fecha'] = [
                'anterior' => $orden_actual['fecha_limite_ejecucion'],
                'nuevo' => $fecha_limite
            ];
        }

        if (empty($cambios)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Sin cambios']);
            exit;
        }

        try {
            $actualizado = $this->orden->actualizarAsignacion($id_orden, $id_usuario_asignado, $fecha_limite);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar',
                'detalle' => $e->getMessage()
            ]);
            return;
        }

        if ($actualizado) {
            // Registrar cambios en historial
            if (isset($cambios['operario'])) {
                $this->orden->registrarHistorial(
                    $id_orden,
                    $id_usuario,
                    'reasignacion',
                    'id_usuario_asignado',
                    $cambios['operario']['anterior'],
                    'id_usuario_asignado',
                    $cambios['operario']['nuevo'],
                    'Reasignación desde cronograma'
                );
            }
            
            if (isset($cambios['fecha'])) {
                $this->orden->registrarHistorial(
                    $id_orden,
                    $id_usuario,
                    'edicion',
                    'fecha_limite_ejecucion',
                    $cambios['fecha']['anterior'],
                    'fecha_limite_ejecucion',
                    $cambios['fecha']['nuevo'],
                    'Fecha actualizada desde cronograma'
                );
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Asignación actualizada']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    }

    /**
     * Agregar comentario a una orden
     */
    public function agregarComentario() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $id_orden = $_POST['id_orden'] ?? null;
        $comentario = $_POST['comentario'] ?? '';

        if (!$id_orden || empty($comentario)) {
            $_SESSION['mensaje'] = 'Comentario vacío';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
            exit;
        }

        $id_usuario = $_SESSION['usuario_id'] ?? 1;

        if ($this->orden->agregarComentario($id_orden, $id_usuario, $comentario)) {
            // Registrar en historial
            $this->orden->registrarHistorial(
                $id_orden,
                $id_usuario,
                'comentario',
                null,
                null,
                null,
                null,
                $comentario
            );
            
            $_SESSION['mensaje'] = 'Comentario agregado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al agregar comentario';
            $_SESSION['tipo_mensaje'] = 'error';
        }

        header('Location: index.php?action=ordenes&subaction=ver&id=' . $id_orden);
    }
}


