<?php
/**
 * Punto de entrada principal del Sistema ROMA
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/ActivosController.php';
require_once __DIR__ . '/controllers/UsuariosController.php';
require_once __DIR__ . '/controllers/ConfiguracionController.php';
require_once __DIR__ . '/controllers/OrdenesController.php';
require_once __DIR__ . '/controllers/BusquedaController.php';
require_once __DIR__ . '/controllers/NotificacionesController.php';
require_once __DIR__ . '/controllers/PerfilController.php';
require_once __DIR__ . '/controllers/ResponsablesController.php';
require_once __DIR__ . '/controllers/NuevosActivosController.php';

// Obtener acción
$action = $_GET['action'] ?? 'dashboard';
$subaction = $_GET['subaction'] ?? 'index';
$id = $_GET['id'] ?? null;

// Si no está autenticado y no está en login, redirigir a login
if (!isset($_SESSION['usuario_autenticado']) && $action !== 'auth') {
    header('Location: index.php?action=auth&subaction=login');
    exit;
}

// Si está autenticado y trata de acceder a login, redirigir a dashboard
if (isset($_SESSION['usuario_autenticado']) && $action === 'auth' && $subaction === 'login') {
    header('Location: index.php?action=dashboard');
    exit;
}

// Enrutamiento
try {
    switch ($action) {
        case 'auth':
            $controller = new AuthController();
            
            switch ($subaction) {
                case 'login':
                    $controller->login();
                    break;
                case 'procesar_login':
                    $controller->procesarLogin();
                    break;
                case 'logout':
                    $controller->logout();
                    break;
                default:
                    $controller->login();
                    break;
            }
            break;

        case 'dashboard':
            $controller = new DashboardController();
            $controller->index();
            break;

        case 'activos':
            $controller = new ActivosController();
            
            switch ($subaction) {
                case 'crear':
                    $controller->crear();
                    break;
                case 'guardar':
                    $controller->guardar();
                    break;
                case 'ver':
                    if ($id) {
                        $controller->ver($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'editar':
                    if ($id) {
                        $controller->editar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'actualizar':
                    if ($id) {
                        $controller->actualizar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar':
                    if ($id) {
                        $controller->eliminar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'carga_masiva':
                    $controller->cargaMasiva();
                    break;
                case 'procesar_carga':
                    $controller->procesarCargaMasiva();
                    break;
                case 'auditoria':
                    $controller->auditoria();
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'usuarios':
            $controller = new UsuariosController();
            
            switch ($subaction) {
                case 'crear':
                    $controller->crear();
                    break;
                case 'guardar':
                    $controller->guardar();
                    break;
                case 'editar':
                    if ($id) {
                        $controller->editar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'actualizar':
                    if ($id) {
                        $controller->actualizar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar':
                    if ($id) {
                        $controller->eliminar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'nuevos_activos':
            $controller = new NuevosActivosController();

            switch ($subaction) {
                case 'categoria':
                    if ($id) {
                        $controller->categoria($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'crear':
                    $controller->crear();
                    break;
                case 'guardar':
                    $controller->guardar();
                    break;
                case 'ver':
                    if ($id) {
                        $controller->ver($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'editar':
                    if ($id) {
                        $controller->editar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'actualizar':
                    if ($id) {
                        $controller->actualizar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar':
                    if ($id) {
                        $controller->eliminar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'crear_categoria':
                    $controller->crearCategoria();
                    break;
                case 'guardar_categoria':
                    $controller->guardarCategoria();
                    break;
                case 'editar_categoria':
                    if ($id) {
                        $controller->editarCategoria($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'actualizar_categoria':
                    if ($id) {
                        $controller->actualizarCategoria($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar_categoria':
                    if ($id) {
                        $controller->eliminarCategoria($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'carga_masiva':
                    $controller->cargaMasiva();
                    break;
                case 'procesar_carga':
                    $controller->procesarCargaMasiva();
                    break;
                case 'auditoria':
                    $controller->auditoria();
                    break;
                case 'formato':
                    $controller->formato();
                    break;
                case 'generar_formato':
                    $controller->generarFormato();
                    break;
                case 'datos_trabajador':
                    if ($id) {
                        $controller->datosTrabajador($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'subir_adjunto':
                    if ($id) {
                        $controller->subirAdjunto($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar_adjunto':
                    if ($id) {
                        $controller->eliminarAdjunto($id);
                    } else {
                        $controller->index();
                    }
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'responsables':
            $controller = new ResponsablesController();

            switch ($subaction) {
                case 'crear':
                    $controller->crear();
                    break;
                case 'guardar':
                    $controller->guardar();
                    break;
                case 'ver':
                    if ($id) {
                        $controller->ver($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'editar':
                    if ($id) {
                        $controller->editar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'actualizar':
                    if ($id) {
                        $controller->actualizar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar':
                    if ($id) {
                        $controller->eliminar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'guardar_movimiento':
                    if ($id) {
                        $controller->guardarMovimiento($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar_movimiento':
                    if ($id) {
                        $controller->eliminarMovimiento($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'subir_formato':
                    if ($id) {
                        $controller->subirFormato($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar_formato':
                    if ($id) {
                        $controller->eliminarFormato($id);
                    } else {
                        $controller->index();
                    }
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'configuracion':
            $controller = new ConfiguracionController();
            $controller->index();
            break;

        case 'perfil':
            $controller = new PerfilController();
            
            switch ($subaction) {
                case 'actualizar':
                    $controller->actualizar();
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;

        case 'buscar':
            $controller = new BusquedaController();
            $controller->buscar();
            break;

        case 'notificaciones':
            $controller = new NotificacionesController();
            
            switch ($subaction) {
                case 'obtener':
                    $controller->obtener();
                    break;
                case 'marcar_leida':
                    $controller->marcarLeida();
                    break;
                case 'marcar_todas_leidas':
                    $controller->marcarTodasLeidas();
                    break;
                default:
                    $controller->obtener();
                    break;
            }
            break;

        case 'ordenes':
            $controller = new OrdenesController();
            
            switch ($subaction) {
                case 'crear':
                    $controller->crear();
                    break;
                case 'guardar':
                    $controller->guardar();
                    break;
                case 'ver':
                    if ($id) {
                        $controller->ver($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'editar':
                    if ($id) {
                        $controller->editar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'actualizar':
                    if ($id) {
                        $controller->actualizar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'eliminar':
                    if ($id) {
                        $controller->eliminar($id);
                    } else {
                        $controller->index();
                    }
                    break;
                case 'cronograma':
                    $controller->cronograma();
                    break;
                case 'metricas':
                    $controller->metricas();
                    break;
                case 'informe':
                    $controller->informe();
                    break;
                case 'actualizar_asignacion':
                    $controller->actualizarAsignacion();
                    break;
                case 'agregar_comentario':
                    $controller->agregarComentario();
                    break;
                case 'cambiar_estado':
                    $controller->cambiarEstado();
                    break;
                case 'reasignar':
                    $controller->reasignar();
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;

        default:
            // Redirigir a dashboard por defecto
            header('Location: index.php?action=dashboard');
            break;
    }
} catch (Exception $e) {
    error_log("Error en index.php: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}

