<?php
/**
 * Controlador de Nuevos Activos (inventario general)
 * Panel con tarjetas por categoría + gráficas, listado por categoría,
 * crear/editar, carga masiva y auditoría.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/NuevoActivo.php';
require_once __DIR__ . '/../models/CategoriaInventario.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class NuevosActivosController {
    private $modelo;
    private $categorias;

    public function __construct() {
        AuthController::verificarAutenticacion();
        $this->modelo = new NuevoActivo();
        $this->categorias = new CategoriaInventario();
    }

    private function puedeVer() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        return Usuario::tienePermiso($rol, 'ver_nuevos_activos')
            || Usuario::tienePermiso($rol, 'ver_activos')
            || Usuario::esAdminGeneral($rol);
    }

    /**
     * Solo el super administrador puede crear, editar y eliminar nuevos activos
     * (categorías, activos y carga masiva incluidos).
     */
    private function puedeGestionar() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        return Usuario::tienePermiso($rol, 'crear_nuevos_activos');
    }

    /**
     * El formato de inventario lo generan administrador y super administrador.
     */
    private function puedeGenerarFormato() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        return Usuario::tienePermiso($rol, 'generar_formato_nuevos');
    }

    private function exigirVer() {
        if (!$this->puedeVer()) {
            $_SESSION['mensaje'] = 'No tiene permisos para ver Nuevos Activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=dashboard');
            exit;
        }
    }

    private function exigirGestion() {
        if (!$this->puedeGestionar()) {
            $_SESSION['mensaje'] = 'Solo el super administrador puede crear, editar o eliminar activos';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
    }

    private function exigirFormato() {
        if (!$this->puedeGenerarFormato()) {
            $_SESSION['mensaje'] = 'Solo el administrador o super administrador pueden generar el formato de inventario';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
    }

    /** Panel principal: reporte general con tarjetas por grupo + gráficas */
    public function index() {
        $this->exigirVer();
        $reporte = $this->modelo->reporteGeneral();
        $estados = NuevoActivo::estados();
        $puedeGestionar = $this->puedeGestionar();
        $puedeGenerarFormato = $this->puedeGenerarFormato();
        $esAdminGeneral = Usuario::esAdminGeneral($_SESSION['usuario_rol'] ?? '');
        require_once __DIR__ . '/../views/nuevos_activos/index.php';
    }

    /** Listado de activos de una categoría (tabla solicitada) */
    public function categoria($idCategoria) {
        $this->exigirVer();
        $categoria = $this->categorias->obtenerPorId($idCategoria);
        if (!$categoria) {
            $_SESSION['mensaje'] = 'Grupo de activos no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        $filtros = [
            'id_categoria' => (int)$idCategoria,
            'estado' => trim($_GET['estado'] ?? ''),
            'sede' => trim($_GET['sede'] ?? ''),
            'busqueda' => trim($_GET['busqueda'] ?? ''),
        ];
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 10;
        $total = $this->modelo->contar($filtros);
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $activos = $this->modelo->listar($filtros + ['limit' => $porPagina, 'offset' => ($pagina - 1) * $porPagina]);
        $conteoAdjuntos = $this->modelo->contarAdjuntosPorActivos(array_column($activos, 'id_nuevo_activo'));
        $estados = NuevoActivo::estados();
        $puedeGestionar = $this->puedeGestionar();
        $puedeGenerarFormato = $this->puedeGenerarFormato();
        $esAdminGeneral = Usuario::esAdminGeneral($_SESSION['usuario_rol'] ?? '');
        require_once __DIR__ . '/../views/nuevos_activos/categoria.php';
    }

    public function crear() {
        $this->exigirGestion();
        $listaCategorias = $this->categorias->listar();
        $estados = NuevoActivo::estados();
        $idCategoriaPre = (int)($_GET['categoria'] ?? 0);
        require_once __DIR__ . '/../models/TrabajadorResponsable.php';
        $listaTrabajadores = (new TrabajadorResponsable())->listar(['limit' => 2000]);
        require_once __DIR__ . '/../views/nuevos_activos/form.php';
    }

    public function guardar() {
        $this->exigirGestion();
        $idCat = (int)($_POST['id_categoria'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $estado = $_POST['estado'] ?? 'operativo';
        if ($idCat <= 0 || !$this->categorias->obtenerPorId($idCat)) {
            $_SESSION['mensaje'] = 'Seleccione un grupo de activos válido.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=crear');
            exit;
        }
        if ($codigo === '' || $nombre === '') {
            $_SESSION['mensaje'] = 'Código y nombre son obligatorios.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=crear&categoria=' . $idCat);
            exit;
        }
        if (!array_key_exists($estado, NuevoActivo::estados())) $estado = 'operativo';
        if ($this->modelo->existeCodigo($codigo)) {
            $_SESSION['mensaje'] = "El código '$codigo' ya existe.";
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=crear&categoria=' . $idCat);
            exit;
        }
        $fechaIngreso = $this->normalizarFecha(trim($_POST['fecha_ingreso'] ?? ''));
        if ($fechaIngreso === false) {
            $_SESSION['mensaje'] = 'Fecha de ingreso inválida. Use YYYY-MM-DD.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=crear&categoria=' . $idCat);
            exit;
        }
        $valor = str_replace(',', '.', trim($_POST['valor'] ?? ''));
        if ($valor !== '' && !is_numeric($valor)) {
            $_SESSION['mensaje'] = 'El valor debe ser numérico.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=crear&categoria=' . $idCat);
            exit;
        }
        $this->modelo->id_categoria = $idCat;
        $this->modelo->id_trabajador = $this->validarTrabajador((int)($_POST['id_trabajador'] ?? 0));
        $this->modelo->codigo = $codigo;
        $this->modelo->codigo_serial = trim($_POST['codigo_serial'] ?? '');
        $this->modelo->codigo_placa = trim($_POST['codigo_placa'] ?? '');
        $this->modelo->fecha_ingreso = $fechaIngreso;
        $this->modelo->codigo_cuenta_contable = trim($_POST['codigo_cuenta_contable'] ?? '');
        $this->modelo->codigo_grupo_activo_fijo = trim($_POST['codigo_grupo_activo_fijo'] ?? '');
        $this->modelo->descripcion = trim($_POST['descripcion'] ?? '');
        $this->modelo->valor = $valor === '' ? null : $valor;
        $this->modelo->nombre = mb_strtoupper($nombre, 'UTF-8');
        $this->modelo->sede = trim($_POST['sede'] ?? '');
        $this->modelo->estado = $estado;
        $this->modelo->responsable_general = trim($_POST['responsable_general'] ?? '');
        $this->modelo->usuario_creacion = $_SESSION['usuario_id'] ?? null;
        $this->modelo->origen_auditoria = 'web';
        try {
            $this->modelo->crear();
            $resAdj = $this->procesarAdjuntos($this->modelo->id_nuevo_activo);
            $_SESSION['mensaje'] = 'Activo creado correctamente' . ($resAdj['subidos'] > 0 ? " ({$resAdj['subidos']} adjunto(s) cargados)" : '');
            if (!empty($resAdj['errores'])) {
                $_SESSION['mensaje'] .= '. Adjuntos omitidos: ' . implode(' ', array_slice($resAdj['errores'], 0, 3));
            }
            $_SESSION['tipo_mensaje'] = !empty($resAdj['errores']) ? 'warning' : 'success';
            header('Location: index.php?action=nuevos_activos&subaction=ver&id=' . $this->modelo->id_nuevo_activo);
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al crear: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=crear&categoria=' . $idCat);
        }
    }

    public function ver($id) {
        $this->exigirVer();
        $activo = $this->modelo->obtenerPorId($id);
        if (!$activo) {
            $_SESSION['mensaje'] = 'Activo no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        $historial = $this->modelo->obtenerAuditoria(['id_nuevo_activo' => (int)$id, 'limit' => 50]);
        $etiquetas = NuevoActivo::camposAuditables();
        $estados = NuevoActivo::estados();
        require_once __DIR__ . '/../views/nuevos_activos/detalle.php';
    }

    public function editar($id) {
        $this->exigirGestion();
        $activo = $this->modelo->obtenerPorId($id);
        if (!$activo) {
            $_SESSION['mensaje'] = 'Activo no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        $listaCategorias = $this->categorias->listar();
        $estados = NuevoActivo::estados();
        require_once __DIR__ . '/../models/TrabajadorResponsable.php';
        $listaTrabajadores = (new TrabajadorResponsable())->listar(['limit' => 2000]);
        require_once __DIR__ . '/../views/nuevos_activos/form.php';
    }

    public function actualizar($id) {
        $this->exigirGestion();
        $prev = $this->modelo->obtenerPorId($id);
        if (!$prev) {
            $_SESSION['mensaje'] = 'Activo no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        $idCat = (int)($_POST['id_categoria'] ?? $prev['id_categoria']);
        $codigo = trim($_POST['codigo'] ?? $prev['codigo']);
        $nombre = trim($_POST['nombre'] ?? $prev['nombre']);
        $estado = $_POST['estado'] ?? $prev['estado'];
        if (!array_key_exists($estado, NuevoActivo::estados())) $estado = 'operativo';
        if ($codigo === '' || $nombre === '') {
            $_SESSION['mensaje'] = 'Código y nombre son obligatorios.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=nuevos_activos&subaction=editar&id=$id");
            exit;
        }
        if ($this->modelo->existeCodigo($codigo, $id)) {
            $_SESSION['mensaje'] = "El código '$codigo' ya existe en otro activo.";
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=nuevos_activos&subaction=editar&id=$id");
            exit;
        }
        $fechaIngreso = $this->normalizarFecha(trim($_POST['fecha_ingreso'] ?? ''));
        if ($fechaIngreso === false) {
            $_SESSION['mensaje'] = 'Fecha de ingreso inválida. Use YYYY-MM-DD.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=nuevos_activos&subaction=editar&id=$id");
            exit;
        }
        $valor = str_replace(',', '.', trim($_POST['valor'] ?? ''));
        if ($valor !== '' && !is_numeric($valor)) {
            $_SESSION['mensaje'] = 'El valor debe ser numérico.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=nuevos_activos&subaction=editar&id=$id");
            exit;
        }
        $this->modelo->id_nuevo_activo = (int)$id;
        $this->modelo->id_categoria = $idCat;
        $this->modelo->id_trabajador = $this->validarTrabajador((int)($_POST['id_trabajador'] ?? 0));
        $this->modelo->codigo = $codigo;
        $this->modelo->codigo_serial = trim($_POST['codigo_serial'] ?? '');
        $this->modelo->codigo_placa = trim($_POST['codigo_placa'] ?? '');
        $this->modelo->fecha_ingreso = $fechaIngreso;
        $this->modelo->codigo_cuenta_contable = trim($_POST['codigo_cuenta_contable'] ?? '');
        $this->modelo->codigo_grupo_activo_fijo = trim($_POST['codigo_grupo_activo_fijo'] ?? '');
        $this->modelo->descripcion = trim($_POST['descripcion'] ?? '');
        $this->modelo->valor = $valor === '' ? null : $valor;
        $this->modelo->nombre = mb_strtoupper($nombre, 'UTF-8');
        $this->modelo->sede = trim($_POST['sede'] ?? '');
        $this->modelo->estado = $estado;
        $this->modelo->responsable_general = trim($_POST['responsable_general'] ?? '');
        $this->modelo->usuario_creacion = $_SESSION['usuario_id'] ?? null;
        $this->modelo->origen_auditoria = 'web';
        try {
            $this->modelo->actualizar();
            $resAdj = $this->procesarAdjuntos((int)$id);
            $_SESSION['mensaje'] = 'Activo actualizado correctamente' . ($resAdj['subidos'] > 0 ? " ({$resAdj['subidos']} adjunto(s) cargados)" : '');
            if (!empty($resAdj['errores'])) {
                $_SESSION['mensaje'] .= '. Adjuntos omitidos: ' . implode(' ', array_slice($resAdj['errores'], 0, 3));
            }
            $_SESSION['tipo_mensaje'] = !empty($resAdj['errores']) ? 'warning' : 'success';
            header("Location: index.php?action=nuevos_activos&subaction=ver&id=$id");
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al actualizar: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=nuevos_activos&subaction=editar&id=$id");
        }
    }

    public function eliminar($id) {
        $this->exigirGestion();
        $prev = $this->modelo->obtenerPorId($id);
        if (!$prev) {
            $_SESSION['mensaje'] = 'Activo no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        $this->modelo->id_nuevo_activo = (int)$id;
        $this->modelo->usuario_creacion = $_SESSION['usuario_id'] ?? null;
        $this->modelo->origen_auditoria = 'web';
        $this->modelo->eliminar();
        $_SESSION['mensaje'] = 'Activo eliminado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
        header('Location: index.php?action=nuevos_activos&subaction=categoria&id=' . (int)$prev['id_categoria']);
    }

    /** Subir imágenes/archivos desde la ficha del activo */
    public function subirAdjunto($id) {
        $this->exigirGestion();
        $activo = $this->modelo->obtenerPorId($id);
        if (!$activo) {
            $_SESSION['mensaje'] = 'Activo no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        $res = $this->procesarAdjuntos((int)$id);
        if ($res['subidos'] === 0 && empty($res['errores'])) {
            $_SESSION['mensaje'] = 'Seleccione al menos un archivo para subir.';
            $_SESSION['tipo_mensaje'] = 'error';
        } else {
            $_SESSION['mensaje'] = $res['subidos'] > 0
                ? "Se cargaron {$res['subidos']} archivo(s) correctamente."
                : 'No se pudo cargar ningún archivo.';
            if (!empty($res['errores'])) {
                $_SESSION['mensaje'] .= ' ' . implode(' ', array_slice($res['errores'], 0, 3));
            }
            $_SESSION['tipo_mensaje'] = (!empty($res['errores']) || $res['subidos'] === 0) ? 'warning' : 'success';
        }
        header("Location: index.php?action=nuevos_activos&subaction=ver&id=$id");
    }

    public function eliminarAdjunto($id) {
        $this->exigirGestion();
        $idAdjunto = (int)($_GET['id_adjunto'] ?? 0);
        $activo = $this->modelo->obtenerPorId($id);
        if (!$activo || $idAdjunto <= 0) {
            $_SESSION['mensaje'] = 'Adjunto no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        try {
            $adj = $this->modelo->obtenerAdjuntoPorId($idAdjunto);
            if (!$adj || (int)$adj['id_nuevo_activo'] !== (int)$id) {
                throw new Exception('El adjunto no pertenece a este activo.');
            }
            $this->modelo->eliminarAdjunto($idAdjunto);
            if (!empty($adj['ruta_archivo'])) {
                @unlink(UPLOAD_DIR . $adj['ruta_archivo']);
            }
            $this->modelo->auditarAdjunto((int)$id, $_SESSION['usuario_id'] ?? null, 'eliminar', $adj['nombre_archivo'] ?? '');
            $_SESSION['mensaje'] = 'Adjunto eliminado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al eliminar adjunto: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
        }
        header("Location: index.php?action=nuevos_activos&subaction=ver&id=$id");
    }

    /**
     * Procesa $_FILES['adjuntos'] (múltiple). Devuelve ['subidos'=>int, 'errores'=>[]].
     * Acepta imágenes (jpg/jpeg/png/gif/webp) y documentos (pdf/doc/docx/xls/xlsx).
     */
    private function procesarAdjuntos($idNuevoActivo) {
        $res = ['subidos' => 0, 'errores' => []];
        if (!isset($_FILES['adjuntos']) || empty($_FILES['adjuntos']['name'][0])) {
            return $res;
        }
        $permitidas = array_unique(array_merge(ALLOWED_EXTENSIONS, ['gif', 'webp']));
        $uploadDir = UPLOAD_DIR . 'nuevos_activos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $files = $_FILES['adjuntos'];
        $total = count($files['name']);
        $descripcion = trim($_POST['descripcion_adjunto'] ?? '');
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        for ($i = 0; $i < $total; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $original = $files['name'][$i];
            $tmp = $files['tmp_name'][$i];
            $size = $files['size'][$i] ?? 0;
            $type = $files['type'][$i] ?? '';
            if ($size > UPLOAD_MAX_SIZE) {
                $res['errores'][] = "'$original' supera 10MB.";
                continue;
            }
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidas, true)) {
                $res['errores'][] = "'$original' tiene extensión no permitida.";
                continue;
            }
            $nuevo = uniqid('nva_' . (int)$idNuevoActivo . '_', true) . '.' . $ext;
            if (!@move_uploaded_file($tmp, $uploadDir . $nuevo)) {
                $res['errores'][] = "No se pudo guardar '$original'.";
                continue;
            }
            try {
                $this->modelo->agregarAdjunto(
                    (int)$idNuevoActivo, $original, 'nuevos_activos/' . $nuevo,
                    $type, $size, $usuarioId, $descripcion
                );
                $this->modelo->auditarAdjunto((int)$idNuevoActivo, $usuarioId, 'subir', $original);
                $res['subidos']++;
            } catch (Exception $e) {
                @unlink($uploadDir . $nuevo);
                $res['errores'][] = "Error en BD con '$original'.";
            }
        }
        return $res;
    }

    public function crearCategoria() {
        $this->exigirGestion();
        require_once __DIR__ . '/../views/nuevos_activos/categoria_form.php';
    }

    public function editarCategoria($id) {
        $this->exigirGestion();
        $categoria = $this->categorias->obtenerPorId($id);
        if (!$categoria) {
            $_SESSION['mensaje'] = 'Grupo de activos no encontrado';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        require_once __DIR__ . '/../views/nuevos_activos/categoria_form.php';
    }

    public function actualizarCategoria($id) {
        $this->exigirGestion();
        try {
            $this->categorias->actualizar(
                $id,
                $_POST['nombre'] ?? '',
                trim($_POST['descripcion'] ?? ''),
                $_POST['icono'] ?? 'box',
                $_POST['color'] ?? 'primary'
            );
            $_SESSION['mensaje'] = 'Grupo de activos actualizado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=nuevos_activos');
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al actualizar grupo: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: index.php?action=nuevos_activos&subaction=editar_categoria&id=$id");
        }
    }

    public function eliminarCategoria($id) {
        $this->exigirGestion();
        try {
            $this->categorias->eliminar($id);
            $_SESSION['mensaje'] = 'Grupo de activos eliminado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'No se pudo eliminar: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
        }
        header('Location: index.php?action=nuevos_activos');
    }

    public function guardarCategoria() {
        $this->exigirGestion();
        try {
            $id = $this->categorias->crear(
                $_POST['nombre'] ?? '',
                trim($_POST['descripcion'] ?? ''),
                $_POST['icono'] ?? 'box',
                $_POST['color'] ?? 'primary',
                $_SESSION['usuario_id'] ?? null
            );
            $_SESSION['mensaje'] = 'Grupo de activos creado correctamente';
            $_SESSION['tipo_mensaje'] = 'success';
            header('Location: index.php?action=nuevos_activos&subaction=categoria&id=' . $id);
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error al crear grupo: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=crear_categoria');
        }
    }

    public function cargaMasiva() {
        $this->exigirGestion();
        $listaCategorias = $this->categorias->listar();
        require_once __DIR__ . '/../views/nuevos_activos/carga_masiva.php';
    }

    public function procesarCargaMasiva() {
        $this->exigirGestion();
        if (!isset($_FILES['archivo_csv']) || ($_FILES['archivo_csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['mensaje'] = 'Error al subir el archivo CSV.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=carga_masiva');
            return;
        }
        if (($_FILES['archivo_csv']['size'] ?? 0) > UPLOAD_MAX_SIZE) {
            $_SESSION['mensaje'] = 'El archivo supera 10MB.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=carga_masiva');
            return;
        }
        try {
            $resultado = $this->importarCSV($_FILES['archivo_csv']['tmp_name']);
        } catch (Exception $e) {
            $_SESSION['mensaje'] = 'Error procesando el archivo: ' . $e->getMessage();
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=carga_masiva');
            return;
        }
        $_SESSION['mensaje'] = "Carga masiva: {$resultado['exitosos']} creados, {$resultado['errores']} con errores (de {$resultado['total_filas']} filas).";
        $_SESSION['tipo_mensaje'] = $resultado['errores'] > 0 ? 'warning' : 'success';
        $_SESSION['detalle_carga_nuevos'] = array_slice($resultado['detalles'], 0, 300);
        $_SESSION['resumen_carga_nuevos'] = $resultado;
        header('Location: index.php?action=nuevos_activos&subaction=carga_masiva');
    }

    /** Devuelve el id del trabajador si existe, o null (sin asociar) */
    private function validarTrabajador($id) {
        if ($id <= 0) return null;
        try {
            require_once __DIR__ . '/../models/TrabajadorResponsable.php';
            $t = (new TrabajadorResponsable())->obtenerPorId($id);
            return $t ? (int)$id : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /** Normaliza fecha YYYY-MM-DD o DD/MM/YYYY a YYYY-MM-DD; '' => null; inválida => false */
    private function normalizarFecha($valor) {
        $valor = trim((string)($valor ?? ''));
        if ($valor === '' || $valor === '0000-00-00') return null;
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

    private function importarCSV($archivo) {
        $resultado = ['exitosos' => 0, 'errores' => 0, 'omitidos' => 0, 'total_filas' => 0, 'detalles' => []];
        $contenido = @file_get_contents($archivo);
        if ($contenido === false || $contenido === '') throw new Exception('Archivo vacío.');
        if (substr($contenido, 0, 3) === "\xEF\xBB\xBF") $contenido = substr($contenido, 3);
        if (!mb_check_encoding($contenido, 'UTF-8')) $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        $tmp = tempnam(sys_get_temp_dir(), 'nvos_');
        file_put_contents($tmp, $contenido);
        $h = fopen($tmp, 'r');
        $first = fgets($h);
        $delim = ',';
        $max = 0;
        foreach ([',', ';', "\t"] as $d) {
            $n = count(str_getcsv($first, $d));
            if ($n > $max) {
                $max = $n;
                $delim = $d;
            }
        }
        rewind($h);
        $enc = array_map(function ($x) {
            return strtolower(trim($x));
        }, fgetcsv($h, 0, $delim));
        $cols = ['categoria','codigo','codigo_serial','codigo_placa','fecha_ingreso','codigo_cuenta_contable','codigo_grupo_activo_fijo','descripcion','valor','nombre','sede','estado','responsable_general','documento_responsable'];
        $map = [];
        foreach ($cols as $c) {
            $i = array_search($c, $enc, true);
            $map[$c] = ($i !== false) ? $i : null;
        }
        if ($map['nombre'] === null || $map['codigo'] === null || $map['categoria'] === null) {
            fclose($h);
            @unlink($tmp);
            throw new Exception('Faltan columnas obligatorias: categoria, codigo, nombre. Descargue la plantilla.');
        }
        // Mapa clave -> id de categorías
        $cats = $this->categorias->listar(false);
        $catMap = [];
        foreach ($cats as $c) {
            $catMap[strtolower($c['clave'])] = $c['id_categoria'];
            $catMap[strtolower($c['nombre'])] = $c['id_categoria'];
        }
        $estadosValidos = array_keys(NuevoActivo::estados());
        $vistos = [];
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $linea = 1;
        while (($data = fgetcsv($h, 0, $delim)) !== false) {
            $linea++;
            $vacio = true;
            foreach ($data as $c) {
                if (trim((string)$c) !== '') {
                    $vacio = false;
                    break;
                }
            }
            if ($vacio) {
                $resultado['omitidos']++;
                continue;
            }
            $resultado['total_filas']++;
            $get = function ($col) use ($data, $map) {
                $i = $map[$col];
                if ($i === null || !array_key_exists($i, $data)) return '';
                return trim((string)$data[$i]);
            };
            $catRaw = strtolower($get('categoria'));
            $catRaw = str_replace(' ', '_', $catRaw);
            $idCat = $catMap[$catRaw] ?? null;
            $codigo = $get('codigo');
            $nombre = $get('nombre');
            $estado = strtolower($get('estado'));
            if ($estado === '') $estado = 'operativo';
            if ($nombre === '' || $codigo === '') {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: código y nombre son obligatorios.";
                continue;
            }
            if ($idCat === null) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: grupo '$catRaw' no existe. Créelo primero en el panel.";
                continue;
            }
            if (!in_array($estado, $estadosValidos, true)) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: estado inválido '$estado'. Válidos: " . implode(', ', $estadosValidos);
                continue;
            }
            $claveCod = strtolower($codigo);
            if (isset($vistos[$claveCod]) || $this->modelo->existeCodigo($codigo)) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: código '$codigo' duplicado.";
                continue;
            }
            $fechaIng = $this->normalizarFecha($get('fecha_ingreso'));
            if ($fechaIng === false) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: fecha_ingreso inválida. Use YYYY-MM-DD o DD/MM/YYYY.";
                continue;
            }
            $valorCsv = str_replace(',', '.', $get('valor'));
            if ($valorCsv !== '' && !is_numeric($valorCsv)) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea: valor debe ser numérico.";
                continue;
            }
            $idTrabCsv = null;
            $docResp = $get('documento_responsable');
            if ($docResp !== '') {
                try {
                    require_once __DIR__ . '/../models/TrabajadorResponsable.php';
                    $tr = (new TrabajadorResponsable())->obtenerPorDocumento($docResp);
                    if ($tr) {
                        $idTrabCsv = (int)$tr['id_trabajador'];
                    } else {
                        $resultado['errores']++;
                        $resultado['detalles'][] = "Línea $linea: documento_responsable '$docResp' no existe en responsables.";
                        continue;
                    }
                } catch (Exception $e) {
                    $idTrabCsv = null;
                }
            }
            try {
                $this->modelo->limpiarParaImportacion();
                $this->modelo->id_categoria = $idCat;
                $this->modelo->id_trabajador = $idTrabCsv;
                $this->modelo->codigo = $codigo;
                $this->modelo->codigo_serial = $get('codigo_serial');
                $this->modelo->codigo_placa = $get('codigo_placa');
                $this->modelo->fecha_ingreso = $fechaIng;
                $this->modelo->codigo_cuenta_contable = $get('codigo_cuenta_contable');
                $this->modelo->codigo_grupo_activo_fijo = $get('codigo_grupo_activo_fijo');
                $this->modelo->descripcion = $get('descripcion');
                $this->modelo->valor = $valorCsv === '' ? null : $valorCsv;
                $this->modelo->nombre = mb_strtoupper($nombre, 'UTF-8');
                $this->modelo->sede = $get('sede');
                $this->modelo->estado = $estado;
                $this->modelo->responsable_general = $get('responsable_general');
                $this->modelo->usuario_creacion = $usuarioId;
                $this->modelo->origen_auditoria = 'carga_masiva';
                $this->modelo->crear();
                $vistos[$claveCod] = true;
                $resultado['exitosos']++;
            } catch (Exception $e) {
                $resultado['errores']++;
                $resultado['detalles'][] = "Línea $linea ($nombre): " . $e->getMessage();
            }
        }
        fclose($h);
        @unlink($tmp);
        if ($resultado['total_filas'] === 0) throw new Exception('Sin filas de datos.');
        return $resultado;
    }

    /** Formulario del formato de inventario y asignación al colaborador */
    public function formato() {
        $this->exigirFormato();
        $todosActivos = $this->modelo->listar(['limit' => 2000]);
        $estados = NuevoActivo::estados();
        require_once __DIR__ . '/../models/TrabajadorResponsable.php';
        $trabModel = new TrabajadorResponsable();
        $trabajadores = $trabModel->listar();
        $preTrabajador = (int)($_GET['trabajador'] ?? 0);
        // Si viene de la ficha del trabajador, precargar su control de movimientos
        $movimientosPrevios = [];
        if ($preTrabajador > 0) {
            $tPre = $trabModel->obtenerPorId($preTrabajador);
            if ($tPre) {
                $movimientosPrevios = $tPre['movimientos'] ?? [];
            } else {
                $preTrabajador = 0;
            }
        }
        require_once __DIR__ . '/../views/nuevos_activos/formato.php';
    }

    /** Datos de un trabajador en JSON para autocompletar el formato */
    public function datosTrabajador($id) {
        $this->exigirFormato();
        require_once __DIR__ . '/../models/TrabajadorResponsable.php';
        $trabModel = new TrabajadorResponsable();
        $t = $trabModel->obtenerPorId($id);
        if (!$t) {
            http_response_code(404);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Trabajador no encontrado']);
            exit;
        }
        $acts = $this->modelo->listarPorTrabajador((int)$id);
        $tiposMov = TrabajadorResponsable::tiposMovimiento();
        $movs = [];
        foreach (($t['movimientos'] ?? []) as $m) {
            $movs[] = [
                'fecha' => $m['fecha'] ?? '',
                'tipo' => $tiposMov[$m['tipo_movimiento'] ?? ''] ?? ($m['tipo_movimiento'] ?? ''),
                'codigo' => ($m['activo_codigo'] ?? '') !== '' ? $m['activo_codigo'] : ($m['codigo_activo'] ?? ''),
                'estado_anterior' => $m['estado_anterior'] ?? '',
                'estado_nuevo' => $m['estado_nuevo'] ?? '',
                'responsable' => $m['responsable'] ?? '',
                'observacion' => $m['observacion'] ?? '',
            ];
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'id_trabajador' => (int)$t['id_trabajador'],
            'nombre_completo' => $t['nombre_completo'],
            'documento_identidad' => $t['documento_identidad'],
            'cargo' => $t['cargo'],
            'dependencia' => $t['dependencia'],
            'tipo_vinculacion' => $t['tipo_vinculacion'],
            'centro_costo' => $t['centro_costo'],
            'jefe_inmediato' => $t['jefe_inmediato'],
            'fecha_ingreso' => $t['fecha_ingreso'],
            'activos' => array_map(function ($a) {
                return (int)$a['id_nuevo_activo'];
            }, $acts),
            'movimientos' => $movs,
        ]);
        exit;
    }

    /** Procesa el formulario y muestra el formato listo para descargar en PDF */
    public function generarFormato() {
        $this->exigirFormato();
        $sel = $_POST['activos_sel'] ?? [];
        if (!is_array($sel) || empty($sel)) {
            $_SESSION['mensaje'] = 'Seleccione al menos un activo para el formato.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=formato');
            exit;
        }
        $trabajador = [
            'nombre' => trim($_POST['trab_nombre'] ?? ''),
            'documento' => trim($_POST['trab_documento'] ?? ''),
            'cargo' => trim($_POST['trab_cargo'] ?? ''),
            'area' => trim($_POST['trab_area'] ?? ''),
            'vinculacion' => trim($_POST['trab_vinculacion'] ?? ''),
            'centro_costo' => trim($_POST['trab_centro_costo'] ?? ''),
            'jefe' => trim($_POST['trab_jefe'] ?? ''),
            'ingreso' => trim($_POST['trab_ingreso'] ?? ''),
        ];
        if ($trabajador['nombre'] === '' || $trabajador['documento'] === '') {
            $_SESSION['mensaje'] = 'Nombre completo y documento del trabajador son obligatorios.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=formato');
            exit;
        }
        $entregaFecha = $_POST['entrega_fecha'] ?? [];
        $entregaEstado = $_POST['entrega_estado'] ?? [];
        $entregaObs = $_POST['entrega_obs'] ?? [];
        $activos = [];
        foreach ($sel as $idRaw) {
            $id = (int)$idRaw;
            if ($id <= 0) continue;
            $a = $this->modelo->obtenerPorId($id);
            if (!$a) continue;
            $activos[] = [
                'codigo' => $a['codigo'] ?? '',
                'codigo_serial' => $a['codigo_serial'] ?? '',
                'nombre' => $a['nombre'] ?? '',
                'categoria' => $a['categoria_nombre'] ?? '',
                'sede' => $a['sede'] ?? '',
                'valor' => $a['valor'] ?? null,
                'estado' => NuevoActivo::estados()[$a['estado']] ?? ($a['estado'] ?? ''),
                'fecha_entrega' => $entregaFecha[$id] ?? date('Y-m-d'),
                'estado_entrega' => $entregaEstado[$id] ?? '',
                'observacion' => trim($entregaObs[$id] ?? ''),
            ];
        }
        if (empty($activos)) {
            $_SESSION['mensaje'] = 'Los activos seleccionados no existen.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos&subaction=formato');
            exit;
        }
        // Movimientos (filas paralelas; se omiten las totalmente vacías)
        $movimientos = [];
        $mf = $_POST['mov_fecha'] ?? [];
        $mt = $_POST['mov_tipo'] ?? [];
        $mc = $_POST['mov_codigo'] ?? [];
        $mea = $_POST['mov_est_ant'] ?? [];
        $men = $_POST['mov_est_nuevo'] ?? [];
        $mr = $_POST['mov_resp'] ?? [];
        $mo = $_POST['mov_obs'] ?? [];
        $n = max(count($mf), count($mt), count($mc), count($mea), count($men), count($mr), count($mo));
        for ($i = 0; $i < $n; $i++) {
            $fila = [
                'fecha' => trim($mf[$i] ?? ''),
                'tipo' => trim($mt[$i] ?? ''),
                'codigo' => trim($mc[$i] ?? ''),
                'estado_anterior' => trim($mea[$i] ?? ''),
                'estado_nuevo' => trim($men[$i] ?? ''),
                'responsable' => trim($mr[$i] ?? ''),
                'observacion' => trim($mo[$i] ?? ''),
            ];
            if (implode('', $fila) === '') continue;
            $movimientos[] = $fila;
        }
        $formato = [
            'trabajador' => $trabajador,
            'activos' => $activos,
            'movimientos' => $movimientos,
            'obs_generales' => trim($_POST['obs_generales'] ?? ''),
            'firma_trab_fecha' => trim($_POST['firma_trab_fecha'] ?? ''),
            'responsable' => [
                'nombre' => trim($_POST['resp_nombre'] ?? ''),
                'cargo' => trim($_POST['resp_cargo'] ?? ''),
                'fecha' => trim($_POST['resp_fecha'] ?? ''),
            ],
            'generado_por' => $_SESSION['usuario_nombre'] ?? '',
        ];
        require_once __DIR__ . '/../views/nuevos_activos/formato_pdf.php';
    }

    /** Auditoría de nuevos activos (quién/qué/cuándo) */
    public function auditoria() {
        $rol = $_SESSION['usuario_rol'] ?? '';
        if (!Usuario::esAdminGeneral($rol)) {
            $_SESSION['mensaje'] = 'La auditoría es solo para administradores.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php?action=nuevos_activos');
            exit;
        }
        require_once __DIR__ . '/../models/Usuario.php';
        $uModel = new Usuario();
        $filtros = [
            'tipo_operacion' => strtoupper(trim($_GET['tipo'] ?? '')),
            'id_usuario' => (int)($_GET['usuario'] ?? 0) ?: '',
            'busqueda' => trim($_GET['busqueda'] ?? ''),
            'fecha_desde' => trim($_GET['desde'] ?? ''),
            'fecha_hasta' => trim($_GET['hasta'] ?? ''),
        ];
        if (!in_array($filtros['tipo_operacion'], ['INSERT', 'UPDATE', 'DELETE'], true)) $filtros['tipo_operacion'] = '';
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 30;
        $total = $this->modelo->contarAuditoria($filtros);
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $registros = $this->modelo->obtenerAuditoria($filtros + ['limit' => $porPagina, 'offset' => ($pagina - 1) * $porPagina]);
        $etiquetas = NuevoActivo::camposAuditables();
        $usuariosLista = $uModel->listar();
        require_once __DIR__ . '/../views/nuevos_activos/auditoria.php';
    }
}
