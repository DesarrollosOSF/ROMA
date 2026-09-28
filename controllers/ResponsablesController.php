<?php
/**
 * Controlador de Trabajadores Responsables de Activos
 * Solo el super administrador puede agregarlos/editarlos/eliminarlos.
 * Administrador y super administrador pueden consultarlos.
 * Incluye control de devolución/cambio/traslado (editable) y
 * formato de inventario adjunto por trabajador.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/TrabajadorResponsable.php';
require_once __DIR__ . '/../models/NuevoActivo.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class ResponsablesController {
    private $modelo;
    private $activoModel;

    public function __construct() {
        AuthController::verificarAutenticacion();
        $this->modelo = new TrabajadorResponsable();
        $this->activoModel = new NuevoActivo();
    }

    private function puedeVer() {
        return Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'ver_responsables');
    }

    private function puedeGestionar() {
        return Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'gestionar_responsables');
    }

    private function exigirVer() {
        if (!$this->puedeVer()) {
            $_SESSION['mensaje'] = 'No tiene permisos para ver responsables de activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    private function exigirGestion() {
        if (!$this->puedeGestionar()) {
            $_SESSION['mensaje'] = 'Solo el super administrador puede agregar responsables de activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables');
            exit;
        }
    }

    public function index() {
        $this->exigirVer();
        $busqueda = trim($_GET['busqueda'] ?? '');
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 20;
        $total = $this->modelo->contar(['busqueda' => $busqueda]);
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $trabajadores = $this->modelo->listar([
            'busqueda' => $busqueda,
            'limit' => $porPagina,
            'offset' => ($pagina - 1) * $porPagina,
        ]);
        $puedeGestionar = $this->puedeGestionar();
        require_once __DIR__ . '/../views/responsables/index.php';
    }

    public function crear() {
        $this->exigirGestion();
        require_once __DIR__ . '/../views/responsables/form.php';
    }

    public function guardar() {
        $this->exigirGestion();
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $doc = trim($_POST['documento_identidad'] ?? '');
        if ($nombre === '' || $doc === '') {
            $_SESSION['mensaje'] = 'Nombre completo y documento de identidad son obligatorios.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables&subaction=crear');
            exit;
        }
        if ($this->modelo->obtenerPorDocumento($doc)) {
            $_SESSION['mensaje'] = "El documento '$doc' ya está registrado.";
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables&subaction=crear');
            exit;
        }
        $this->cargarDesdePost();
        try {
            $this->modelo->crear();
            $_SESSION['mensaje'] = 'Responsable creado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=responsables&subaction=ver&id=' . $this->modelo->id_trabajador);
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al crear: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables&subaction=crear');
        }
    }

    public function ver($id) {
        $this->exigirVer();
        $trabajador = $this->modelo->obtenerPorId($id);
        if (!$trabajador) {
            $_SESSION['mensaje'] = 'Responsable no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables');
            exit;
        }
        $activos = $this->activoModel->listarPorTrabajador((int)$id);
        $tiposMov = TrabajadorResponsable::tiposMovimiento();
        $puedeGestionar = $this->puedeGestionar();
        require_once __DIR__ . '/../views/responsables/detalle.php';
    }

    public function editar($id) {
        $this->exigirGestion();
        $trabajador = $this->modelo->obtenerPorId($id);
        if (!$trabajador) {
            $_SESSION['mensaje'] = 'Responsable no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables');
            exit;
        }
        require_once __DIR__ . '/../views/responsables/form.php';
    }

    public function actualizar($id) {
        $this->exigirGestion();
        $prev = $this->modelo->obtenerPorId($id);
        if (!$prev) {
            $_SESSION['mensaje'] = 'Responsable no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables');
            exit;
        }
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $doc = trim($_POST['documento_identidad'] ?? '');
        if ($nombre === '' || $doc === '') {
            $_SESSION['mensaje'] = 'Nombre completo y documento de identidad son obligatorios.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=editar&id=$id");
            exit;
        }
        if ($this->modelo->obtenerPorDocumento($doc, $id)) {
            $_SESSION['mensaje'] = "El documento '$doc' ya está registrado en otro responsable.";
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=editar&id=$id");
            exit;
        }
        $this->modelo->id_trabajador = (int)$id;
        $this->cargarDesdePost();
        try {
            $this->modelo->actualizar();
            $_SESSION['mensaje'] = 'Responsable actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header("Location: index.php?action=responsables&subaction=ver&id=$id");
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al actualizar: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=editar&id=$id");
        }
    }

    public function eliminar($id) {
        $this->exigirGestion();
        $prev = $this->modelo->obtenerPorId($id);
        if (!$prev) {
            $_SESSION['mensaje'] = 'Responsable no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables');
            exit;
        }
        if (!empty($prev['formato_ruta'])) {
            @unlink(UPLOAD_DIR . $prev['formato_ruta']);
        }
        $this->modelo->id_trabajador = (int)$id;
        $this->modelo->eliminar();
        $_SESSION['mensaje'] = 'Responsable eliminado correctamente (sus activos quedaron sin asociar)';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: index.php?action=responsables');
    }

    private function cargarDesdePost() {
        $this->modelo->nombre_completo = mb_strtoupper(trim($_POST['nombre_completo'] ?? ''), 'UTF-8');
        $this->modelo->documento_identidad = trim($_POST['documento_identidad'] ?? '');
        $this->modelo->cargo = trim($_POST['cargo'] ?? '');
        $this->modelo->dependencia = trim($_POST['dependencia'] ?? '');
        $this->modelo->tipo_vinculacion = trim($_POST['tipo_vinculacion'] ?? '');
        $this->modelo->centro_costo = trim($_POST['centro_costo'] ?? '');
        $this->modelo->jefe_inmediato = trim($_POST['jefe_inmediato'] ?? '');
        $fing = trim($_POST['fecha_ingreso'] ?? '');
        $this->modelo->fecha_ingreso = ($fing !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fing)) ? $fing : null;
        $this->modelo->usuario_creacion = $_SESSION['usuario_id'] ?? null;
    }

    /** Crear o actualizar un movimiento de devolución/cambio/traslado */
    public function guardarMovimiento($id) {
        $this->exigirGestion();
        $trabajador = $this->modelo->obtenerPorId($id);
        if (!$trabajador) {
            $_SESSION['mensaje'] = 'Responsable no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables');
            exit;
        }
        $idMov = (int)($_POST['id_movimiento'] ?? 0);
        if ($idMov > 0 && !$this->modelo->obtenerMovimiento($idMov, $id)) {
            $_SESSION['mensaje'] = 'Movimiento no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=ver&id=$id");
            exit;
        }
        try {
            $this->modelo->guardarMovimiento([
                'id_movimiento' => $idMov ?: null,
                'id_trabajador' => (int)$id,
                'fecha' => trim($_POST['fecha'] ?? ''),
                'tipo_movimiento' => $_POST['tipo_movimiento'] ?? '',
                'id_nuevo_activo' => (int)($_POST['id_nuevo_activo'] ?? 0) ?: null,
                'codigo_activo' => trim($_POST['codigo_activo'] ?? ''),
                'estado_anterior' => trim($_POST['estado_anterior'] ?? ''),
                'estado_nuevo' => trim($_POST['estado_nuevo'] ?? ''),
                'responsable' => trim($_POST['responsable'] ?? ''),
                'observacion' => trim($_POST['observacion'] ?? ''),
                'usuario_creacion' => $_SESSION['usuario_id'] ?? null,
            ]);
            $_SESSION['mensaje'] = $idMov > 0 ? 'Movimiento actualizado correctamente' : 'Movimiento registrado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al guardar movimiento: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
        }
        header("Location: index.php?action=responsables&subaction=ver&id=$id");
    }

    public function eliminarMovimiento($id) {
        $this->exigirGestion();
        $idMov = (int)($_GET['id_movimiento'] ?? 0);
        if ($idMov > 0) {
            $this->modelo->eliminarMovimiento($idMov, (int)$id);
            $_SESSION['mensaje'] = 'Movimiento eliminado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        }
        header("Location: index.php?action=responsables&subaction=ver&id=$id");
    }

    /** Subir o reemplazar el formato de inventario adjunto (PDF/documento) */
    public function subirFormato($id) {
        $this->exigirGestion();
        $trabajador = $this->modelo->obtenerPorId($id);
        if (!$trabajador) {
            $_SESSION['mensaje'] = 'Responsable no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=responsables');
            exit;
        }
        if (!isset($_FILES['formato']) || ($_FILES['formato']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['mensaje'] = 'Seleccione el archivo del formato de inventario.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=ver&id=$id");
            exit;
        }
        $file = $_FILES['formato'];
        if (($file['size'] ?? 0) > UPLOAD_MAX_SIZE) {
            $_SESSION['mensaje'] = 'El archivo supera 10MB.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=ver&id=$id");
            exit;
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $permitidas = array_unique(array_merge(ALLOWED_EXTENSIONS, ['gif', 'webp']));
        if (!in_array($ext, $permitidas, true)) {
            $_SESSION['mensaje'] = 'Extensión no permitida. Use PDF, Word, Excel o imagen.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=ver&id=$id");
            exit;
        }
        $uploadDir = UPLOAD_DIR . 'formatos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $nuevo = uniqid('formato_' . (int)$id . '_', true) . '.' . $ext;
        if (!@move_uploaded_file($file['tmp_name'], $uploadDir . $nuevo)) {
            $_SESSION['mensaje'] = 'No se pudo guardar el archivo.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=responsables&subaction=ver&id=$id");
            exit;
        }
        if (!empty($trabajador['formato_ruta'])) {
            @unlink(UPLOAD_DIR . $trabajador['formato_ruta']);
        }
        $this->modelo->guardarFormatoAdjunto((int)$id, 'formatos/' . $nuevo, $file['name']);
        $_SESSION['mensaje'] = 'Formato de inventario adjuntado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header("Location: index.php?action=responsables&subaction=ver&id=$id");
    }

    public function eliminarFormato($id) {
        $this->exigirGestion();
        $t = $this->modelo->eliminarFormatoAdjunto((int)$id);
        if ($t && !empty($t['formato_ruta'])) {
            @unlink(UPLOAD_DIR . $t['formato_ruta']);
        }
        $_SESSION['mensaje'] = 'Formato adjunto eliminado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header("Location: index.php?action=responsables&subaction=ver&id=$id");
    }
}
