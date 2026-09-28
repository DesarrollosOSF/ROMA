<?php
/**
 * Controlador de Notificaciones
 * Sistema ROMA - Sistema de Notificaciones
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class NotificacionesController {
    private $conn;

    public function __construct() {
        AuthController::verificarAutenticacion();
        
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtener notificaciones del usuario
     */
    public function obtener() {
        header('Content-Type: application/json');
        
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        if (!$usuario_id) {
            echo json_encode(['notificaciones' => []]);
            exit;
        }

        // Obtener notificaciones no leídas
        $query = "SELECT id_notificacion, tipo_notificacion, titulo, mensaje, id_referencia, fecha_creacion 
                  FROM notificaciones 
                  WHERE id_usuario = :usuario_id 
                  AND leida = 0 
                  ORDER BY fecha_creacion DESC 
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $notificaciones = $stmt->fetchAll();
        
        // Formatear notificaciones
        foreach ($notificaciones as &$notif) {
            $notif['tiempo'] = $this->tiempoRelativo($notif['fecha_creacion']);
            $notif['url'] = $this->generarUrl($notif['tipo_notificacion'], $notif['id_referencia']);
            $notif['icono'] = $this->obtenerIcono($notif['tipo_notificacion']);
        }

        echo json_encode(['notificaciones' => $notificaciones]);
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarLeida() {
        header('Content-Type: application/json');
        
        $id_notificacion = $_POST['id_notificacion'] ?? null;
        if (!$id_notificacion) {
            echo json_encode(['success' => false]);
            exit;
        }

        $query = "UPDATE notificaciones SET leida = 1, fecha_leida = NOW() WHERE id_notificacion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_notificacion, PDO::PARAM_INT);
        
        echo json_encode(['success' => $stmt->execute()]);
    }

    /**
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas() {
        header('Content-Type: application/json');
        
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        if (!$usuario_id) {
            echo json_encode(['success' => false]);
            exit;
        }

        $query = "UPDATE notificaciones SET leida = 1, fecha_leida = NOW() WHERE id_usuario = :usuario_id AND leida = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        
        echo json_encode(['success' => $stmt->execute()]);
    }

    /**
     * Crear notificación
     */
    public static function crear($id_usuario, $titulo, $mensaje, $tipo_notificacion = 'orden_actualizada', $id_referencia = null) {
        try {
            $database = new Database();
            $conn = $database->getConnection();

            $query = "INSERT INTO notificaciones (id_usuario, tipo_notificacion, titulo, mensaje, id_referencia) 
                      VALUES (:id_usuario, :tipo_notificacion, :titulo, :mensaje, :id_referencia)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_notificacion', $tipo_notificacion);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':mensaje', $mensaje);
            $stmt->bindParam(':id_referencia', $id_referencia, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al crear notificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar URL según tipo de notificación
     */
    private function generarUrl($tipo_notificacion, $id_referencia) {
        if (!$id_referencia) return null;
        
        $urls = [
            'orden_asignada' => BASE_URL . 'index.php?action=ordenes&subaction=ver&id=' . $id_referencia,
            'orden_actualizada' => BASE_URL . 'index.php?action=ordenes&subaction=ver&id=' . $id_referencia,
            'solicitud_creada' => BASE_URL . 'index.php?action=solicitudes&subaction=ver&id=' . $id_referencia,
            'stock_minimo' => BASE_URL . 'index.php?action=repuestos',
            'mantenimiento_programado' => BASE_URL . 'index.php?action=activos&subaction=ver&id=' . $id_referencia
        ];
        
        return $urls[$tipo_notificacion] ?? null;
    }

    /**
     * Obtener icono según tipo de notificación
     */
    private function obtenerIcono($tipo_notificacion) {
        $iconos = [
            'orden_asignada' => 'fas fa-clipboard-check',
            'orden_actualizada' => 'fas fa-clipboard-list',
            'solicitud_creada' => 'fas fa-file-alt',
            'stock_minimo' => 'fas fa-exclamation-triangle',
            'mantenimiento_programado' => 'fas fa-calendar-check'
        ];
        
        return $iconos[$tipo_notificacion] ?? 'fas fa-bell';
    }

    /**
     * Calcular tiempo relativo
     */
    private function tiempoRelativo($fecha) {
        $ahora = new DateTime();
        $fecha_notif = new DateTime($fecha);
        $diff = $ahora->diff($fecha_notif);

        if ($diff->days > 7) {
            return $fecha_notif->format('d/m/Y');
        } elseif ($diff->days > 0) {
            return "Hace {$diff->days} día" . ($diff->days > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return "Hace {$diff->h} hora" . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return "Hace {$diff->i} minuto" . ($diff->i > 1 ? 's' : '');
        } else {
            return "Ahora";
        }
    }
}

