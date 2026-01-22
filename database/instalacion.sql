-- ============================================
-- SISTEMA ROMA - INSTALACIÓN COMPLETA
-- Registro y Operación de Mantenimiento Automatizado
-- ============================================
-- Este script crea la base de datos completa desde cero
-- Ejecutar en phpMyAdmin o desde línea de comandos
-- ============================================

DROP DATABASE IF EXISTS osfcomco_roma;
CREATE DATABASE osfcomco_roma CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE osfcomco_roma;

-- ============================================
-- TABLA DE USUARIOS
-- ============================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'jefe', 'director', 'operario') DEFAULT 'operario',
    telefono VARCHAR(20),
    cargo VARCHAR(100),
    area VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE ACTIVOS
-- ============================================
CREATE TABLE activos (
    id_activo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_activo VARCHAR(255) NOT NULL,
    codigo_interno VARCHAR(100) UNIQUE,
    codigo_patrimonial VARCHAR(100),
    descripcion_general TEXT,
    categoria ENUM(
        'equipo_especial',
        'vehiculos',
        'maquinas',
        'muebles_enseres',
        'horno_crematorio',
        'planta_agua_residual',
        'cofres_cremacion'
    ) NOT NULL,
    ubicacion VARCHAR(255),
    ruta VARCHAR(255),
    foto_principal VARCHAR(255),
    responsable VARCHAR(255),
    area_asignada VARCHAR(255),
    marca VARCHAR(100),
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    fecha_adquisicion DATE,
    valor_adquisicion DECIMAL(15, 2),
    estado_actual ENUM(
        'operativo',
        'en_reparacion',
        'fuera_servicio',
        'en_baja'
    ) DEFAULT 'operativo',
    vida_util_estimada INT COMMENT 'En meses',
    kilometraje DECIMAL(12, 2) DEFAULT 0,
    horas_uso DECIMAL(12, 2) DEFAULT 0,
    ultimo_mantenimiento DATE,
    proximo_mantenimiento DATE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creacion INT,
    usuario_actualizacion INT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (usuario_actualizacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_categoria (categoria),
    INDEX idx_estado (estado_actual),
    INDEX idx_codigo_interno (codigo_interno),
    INDEX idx_codigo_patrimonial (codigo_patrimonial)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE ADJUNTOS DE ACTIVOS
-- ============================================
CREATE TABLE activos_adjuntos (
    id_adjunto INT AUTO_INCREMENT PRIMARY KEY,
    id_activo INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_archivo VARCHAR(50),
    tamaño_archivo INT,
    descripcion TEXT,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_subida INT,
    FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
    FOREIGN KEY (usuario_subida) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_activo (id_activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE HOJA DE VIDA (HISTORIAL DE MANTENIMIENTO)
-- ============================================
CREATE TABLE activos_hoja_vida (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_activo INT NOT NULL,
    id_orden_trabajo INT,
    numero_orden VARCHAR(100),
    tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo') NOT NULL,
    fecha_intervencion DATE NOT NULL,
    kilometraje_evento DECIMAL(12, 2),
    horas_uso_evento DECIMAL(12, 2),
    descripcion_corta VARCHAR(255),
    descripcion_detallada TEXT,
    tecnico_responsable VARCHAR(255),
    repuestos_utilizados TEXT,
    costo_total DECIMAL(12, 2) DEFAULT 0,
    estado_orden ENUM('recibido', 'en_proceso', 'rechazado', 'finalizado') DEFAULT 'recibido',
    fecha_limite_solicitud DATE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT,
    FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_activo (id_activo),
    INDEX idx_fecha (fecha_intervencion),
    INDEX idx_tipo (tipo_mantenimiento),
    INDEX idx_estado (estado_orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE EVIDENCIAS FOTOGRÁFICAS
-- ============================================
CREATE TABLE activos_evidencias (
    id_evidencia INT AUTO_INCREMENT PRIMARY KEY,
    id_registro_hoja_vida INT NOT NULL,
    ruta_imagen VARCHAR(500) NOT NULL,
    descripcion TEXT,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_registro_hoja_vida) REFERENCES activos_hoja_vida(id_registro) ON DELETE CASCADE,
    INDEX idx_registro (id_registro_hoja_vida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE AUDITORÍA DE CAMBIOS
-- ============================================
CREATE TABLE activos_auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_activo INT NOT NULL,
    campo_modificado VARCHAR(100),
    valor_anterior TEXT,
    valor_nuevo TEXT,
    usuario_modificacion INT,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo_operacion ENUM('INSERT', 'UPDATE', 'DELETE'),
    FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
    FOREIGN KEY (usuario_modificacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_activo (id_activo),
    INDEX idx_fecha (fecha_modificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE ÓRDENES DE TRABAJO
-- ============================================
CREATE TABLE ordenes_trabajo (
    id_orden INT AUTO_INCREMENT PRIMARY KEY,
    numero_radicado VARCHAR(100) UNIQUE NOT NULL,
    id_activo INT NOT NULL,
    id_solicitud INT,
    id_solicitante INT NOT NULL,
    id_usuario_asignado INT,
    tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo') NOT NULL,
    nivel_criticidad ENUM('critica', 'alta', 'normal', 'baja') DEFAULT 'normal',
    descripcion_corta VARCHAR(255) NOT NULL,
    descripcion_detallada TEXT,
    estado_proceso ENUM('recibido', 'en_proceso', 'rechazado', 'finalizado') DEFAULT 'recibido',
    fecha_limite_ejecucion DATE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creacion INT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE RESTRICT,
    FOREIGN KEY (id_solicitante) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario_asignado) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_activo (id_activo),
    INDEX idx_solicitante (id_solicitante),
    INDEX idx_asignado (id_usuario_asignado),
    INDEX idx_estado (estado_proceso),
    INDEX idx_criticidad (nivel_criticidad),
    INDEX idx_numero_radicado (numero_radicado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE ADJUNTOS DE ÓRDENES
-- ============================================
CREATE TABLE ordenes_adjuntos (
    id_adjunto INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_archivo VARCHAR(50),
    tamaño_archivo INT,
    descripcion TEXT,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_subida INT,
    FOREIGN KEY (id_orden) REFERENCES ordenes_trabajo(id_orden) ON DELETE CASCADE,
    FOREIGN KEY (usuario_subida) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_orden (id_orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE COMENTARIOS DE ÓRDENES
-- ============================================
CREATE TABLE ordenes_comentarios (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    id_usuario INT NOT NULL,
    comentario TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_orden) REFERENCES ordenes_trabajo(id_orden) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_orden (id_orden),
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE HISTORIAL DE CAMBIOS DE ÓRDENES
-- ============================================
CREATE TABLE ordenes_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    id_usuario INT NOT NULL,
    tipo_cambio ENUM('creacion', 'estado', 'asignacion', 'reasignacion', 'edicion', 'comentario', 'adjunto') NOT NULL,
    campo_anterior VARCHAR(255),
    valor_anterior TEXT,
    campo_nuevo VARCHAR(255),
    valor_nuevo TEXT,
    descripcion TEXT,
    ruta_evidencia VARCHAR(500),
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_orden) REFERENCES ordenes_trabajo(id_orden) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_orden (id_orden),
    INDEX idx_usuario (id_usuario),
    INDEX idx_fecha (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE SOLICITUDES DE MANTENIMIENTO
-- ============================================
CREATE TABLE solicitudes_mantenimiento (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    numero_radicado VARCHAR(100) UNIQUE NOT NULL,
    id_activo INT NOT NULL,
    id_solicitante INT NOT NULL,
    tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo') NOT NULL,
    nivel_criticidad ENUM('critica', 'alta', 'normal', 'baja') DEFAULT 'normal',
    descripcion_corta VARCHAR(255) NOT NULL,
    descripcion_detallada TEXT,
    estado_solicitud ENUM('pendiente', 'asignada', 'rechazada', 'finalizada') DEFAULT 'pendiente',
    fecha_limite DATE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_orden_asociada INT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE RESTRICT,
    FOREIGN KEY (id_solicitante) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_orden_asociada) REFERENCES ordenes_trabajo(id_orden) ON DELETE SET NULL,
    INDEX idx_activo (id_activo),
    INDEX idx_solicitante (id_solicitante),
    INDEX idx_estado (estado_solicitud),
    INDEX idx_numero_radicado (numero_radicado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE ADJUNTOS DE SOLICITUDES
-- ============================================
CREATE TABLE solicitudes_adjuntos (
    id_adjunto INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_archivo VARCHAR(50),
    tamaño_archivo INT,
    descripcion TEXT,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_subida INT,
    FOREIGN KEY (id_solicitud) REFERENCES solicitudes_mantenimiento(id_solicitud) ON DELETE CASCADE,
    FOREIGN KEY (usuario_subida) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_solicitud (id_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE REPUESTOS
-- ============================================
CREATE TABLE repuestos (
    id_repuesto INT AUTO_INCREMENT PRIMARY KEY,
    codigo_repuesto VARCHAR(100) UNIQUE,
    nombre_repuesto VARCHAR(255) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(100),
    unidad_medida VARCHAR(50) DEFAULT 'unidad',
    stock_actual DECIMAL(10, 2) DEFAULT 0,
    stock_minimo DECIMAL(10, 2) DEFAULT 0,
    precio_unitario DECIMAL(12, 2) DEFAULT 0,
    proveedor VARCHAR(255),
    ubicacion_almacen VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo_repuesto),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE MOVIMIENTOS DE REPUESTOS
-- ============================================
CREATE TABLE repuestos_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_repuesto INT NOT NULL,
    id_orden_trabajo INT,
    tipo_movimiento ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    cantidad DECIMAL(10, 2) NOT NULL,
    motivo TEXT,
    usuario_movimiento INT NOT NULL,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE RESTRICT,
    FOREIGN KEY (id_orden_trabajo) REFERENCES ordenes_trabajo(id_orden) ON DELETE SET NULL,
    FOREIGN KEY (usuario_movimiento) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_repuesto (id_repuesto),
    INDEX idx_orden (id_orden_trabajo),
    INDEX idx_fecha (fecha_movimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE REPUESTOS UTILIZADOS EN ÓRDENES
-- ============================================
CREATE TABLE ordenes_repuestos (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    id_repuesto INT NOT NULL,
    cantidad DECIMAL(10, 2) NOT NULL,
    precio_unitario DECIMAL(12, 2),
    subtotal DECIMAL(12, 2),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_orden) REFERENCES ordenes_trabajo(id_orden) ON DELETE CASCADE,
    FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto) ON DELETE RESTRICT,
    INDEX idx_orden (id_orden),
    INDEX idx_repuesto (id_repuesto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE PLANES DE MANTENIMIENTO PREVENTIVO
-- ============================================
CREATE TABLE planes_preventivo (
    id_plan INT AUTO_INCREMENT PRIMARY KEY,
    id_activo INT NOT NULL,
    nombre_plan VARCHAR(255) NOT NULL,
    tipo_frecuencia ENUM('calendario', 'kilometraje', 'horas_uso') NOT NULL,
    frecuencia_valor INT NOT NULL COMMENT 'Días, kilómetros o horas según tipo',
    tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo') DEFAULT 'preventivo',
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creacion INT,
    FOREIGN KEY (id_activo) REFERENCES activos(id_activo) ON DELETE CASCADE,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_activo (id_activo),
    INDEX idx_tipo_frecuencia (tipo_frecuencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE HISTORIAL DE PLANES PREVENTIVOS
-- ============================================
CREATE TABLE planes_preventivo_historial (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_plan INT NOT NULL,
    id_orden_generada INT,
    fecha_programada DATE NOT NULL,
    fecha_ejecutada DATE,
    estado ENUM('pendiente', 'ejecutada', 'vencida', 'cancelada') DEFAULT 'pendiente',
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_plan) REFERENCES planes_preventivo(id_plan) ON DELETE CASCADE,
    FOREIGN KEY (id_orden_generada) REFERENCES ordenes_trabajo(id_orden) ON DELETE SET NULL,
    INDEX idx_plan (id_plan),
    INDEX idx_fecha_programada (fecha_programada),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE NOTIFICACIONES
-- ============================================
CREATE TABLE notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    tipo_notificacion ENUM('orden_asignada', 'orden_actualizada', 'solicitud_creada', 'stock_minimo', 'mantenimiento_programado') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT,
    id_referencia INT COMMENT 'ID de orden, solicitud, etc.',
    leida BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_leida TIMESTAMP NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_leida (leida),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA DE FLUJOS AUTOMÁTICOS
-- ============================================
CREATE TABLE flujos_automaticos (
    id_flujo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_flujo VARCHAR(255) NOT NULL,
    tipo_trigger ENUM('criticidad', 'tipo_mantenimiento', 'activo') NOT NULL,
    valor_trigger VARCHAR(255) NOT NULL,
    accion ENUM('asignar_operario', 'notificar', 'crear_orden') NOT NULL,
    parametros TEXT COMMENT 'JSON con parámetros adicionales',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo_trigger (tipo_trigger)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES - USUARIOS
-- ============================================
-- Password para todos: admin123
-- Hash: $2y$10$Mk5apHi4oWSfSMMd9hmQjOELi7UWjBGkUjEJrmdseeTqC5wIO17FC

INSERT INTO usuarios (nombre, email, password_hash, rol, cargo, area) VALUES
('Administrador Sistema', 'admin@roma.com', '$2y$10$Mk5apHi4oWSfSMMd9hmQjOELi7UWjBGkUjEJrmdseeTqC5wIO17FC', 'administrador', 'Administrador del Sistema', 'Sistemas'),
('Juan Pérez', 'jefe@roma.com', '$2y$10$Mk5apHi4oWSfSMMd9hmQjOELi7UWjBGkUjEJrmdseeTqC5wIO17FC', 'jefe', 'Jefe de Mantenimiento', 'Mantenimiento'),
('María García', 'director@roma.com', '$2y$10$Mk5apHi4oWSfSMMd9hmQjOELi7UWjBGkUjEJrmdseeTqC5wIO17FC', 'director', 'Directora de Operaciones', 'Operaciones'),
('Carlos López', 'operario@roma.com', '$2y$10$Mk5apHi4oWSfSMMd9hmQjOELi7UWjBGkUjEJrmdseeTqC5wIO17FC', 'operario', 'Técnico de Mantenimiento', 'Mantenimiento'),
('Ana Martínez', 'operario2@roma.com', '$2y$10$Mk5apHi4oWSfSMMd9hmQjOELi7UWjBGkUjEJrmdseeTqC5wIO17FC', 'operario', 'Técnico de Mantenimiento', 'Mantenimiento');

-- ============================================
-- FIN DE INSTALACIÓN
-- ============================================
-- El sistema está listo para usar
-- Credenciales por defecto: admin@roma.com / admin123
-- ============================================

