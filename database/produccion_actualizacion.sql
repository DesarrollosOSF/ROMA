-- ============================================
-- ROMA: ACTUALIZACIÓN PARA PRODUCCIÓN (todo en un solo archivo)
-- ============================================
-- ✅ SEGURO: este archivo NO contiene DROP DATABASE, DROP TABLE,
--    DELETE, TRUNCATE ni inserta usuarios. NO borra información.
--    Todas las tablas se crean con IF NOT EXISTS y las columnas
--    e índices solo se agregan si no existen: se puede ejecutar
--    más de una vez sin riesgo.
--
-- PASOS:
--  1) Respalde la BD de producción (phpMyAdmin → Exportar).
--  2) En phpMyAdmin seleccione la base de datos del proyecto
--     (normalmente osfcomco_roma) y abra la pestaña SQL.
--  3) Pegue TODO este archivo y pulse Continuar.
--  4) Verifique que no haya errores en rojo.
--  5) Asigne el super administrador (ver PASO FINAL abajo).
--
-- INCLUYE (en orden seguro por llaves foráneas):
--  A) Rol 'superadmin' en usuarios (sin tocar usuarios existentes)
--  B) Tabla categorias_inventario + 7 grupos de activos
--  C) Tabla trabajadores_responsables
--  D) Tabla nuevos_activos (esquema final: sin observaciones ni
--     responsable_secundario, con fecha_ingreso, códigos contables,
--     descripcion, valor, codigo_placa e id_trabajador)
--  E) Tablas nuevos_activos_auditoria, nuevos_activos_adjuntos,
--     trabajadores_movimientos
--  F) Columna origen en activos_auditoria + índices
-- ============================================

-- ============ A) Rol superadmin (no modifica usuarios existentes) ============
SET @tiene_super := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'rol' AND COLUMN_TYPE LIKE '%superadmin%');
SET @sql_rol := IF(@tiene_super = 0,
  'ALTER TABLE usuarios MODIFY COLUMN rol ENUM(''administrador'',''superadmin'',''jefe'',''director'',''operario'') DEFAULT ''operario''',
  'SELECT ''Rol superadmin ya existe''');
PREPARE stmt FROM @sql_rol;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============ B) Grupos de activos (categorías dinámicas) ============
CREATE TABLE IF NOT EXISTS categorias_inventario (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    clave VARCHAR(100) NOT NULL UNIQUE COMMENT 'slug sin espacios, ej: maquinaria_y_equipo',
    descripcion TEXT NULL,
    icono VARCHAR(50) DEFAULT 'box' COMMENT 'icono FontAwesome sin prefijo fa-',
    color VARCHAR(20) DEFAULT 'primary' COMMENT 'primary|success|warning|danger|info|secondary',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_cat_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ C) Trabajadores responsables de activos ============
CREATE TABLE IF NOT EXISTS trabajadores_responsables (
    id_trabajador INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    documento_identidad VARCHAR(50) NOT NULL,
    cargo VARCHAR(150) NULL,
    dependencia VARCHAR(150) NULL,
    tipo_vinculacion VARCHAR(150) NULL,
    centro_costo VARCHAR(100) NULL,
    jefe_inmediato VARCHAR(255) NULL,
    fecha_ingreso DATE NULL,
    formato_ruta VARCHAR(500) NULL COMMENT 'Ruta relativa del formato de inventario adjunto',
    formato_nombre VARCHAR(255) NULL COMMENT 'Nombre original del formato adjunto',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    UNIQUE KEY uq_trab_documento (documento_identidad),
    INDEX idx_trab_nombre (nombre_completo),
    INDEX idx_trab_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ D) Nuevos activos (esquema final) ============
CREATE TABLE IF NOT EXISTS nuevos_activos (
    id_nuevo_activo INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    id_trabajador INT NULL COMMENT 'Trabajador responsable asociado (opcional)',
    codigo VARCHAR(100) NOT NULL COMMENT 'Código interno / placa',
    codigo_serial VARCHAR(150) NULL COMMENT 'Código serial del fabricante',
    codigo_placa VARCHAR(100) NULL COMMENT 'Código de placa del activo (opcional)',
    nombre VARCHAR(255) NOT NULL,
    fecha_ingreso DATE NULL COMMENT 'Fecha de ingreso del activo',
    codigo_cuenta_contable VARCHAR(50) NULL,
    codigo_grupo_activo_fijo VARCHAR(50) NULL,
    descripcion TEXT NULL,
    valor DECIMAL(15,2) NULL DEFAULT NULL,
    sede VARCHAR(255) NULL COMMENT 'Sede donde se encuentra',
    estado ENUM('operativo','en_reparacion','fuera_servicio','en_baja') DEFAULT 'operativo',
    responsable_general VARCHAR(255) NULL COMMENT 'Jefe inmediato',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    usuario_actualizacion INT NULL,
    activo TINYINT(1) DEFAULT 1 COMMENT '1 vigente, 0 eliminado lógico',
    FOREIGN KEY (id_categoria) REFERENCES categorias_inventario(id_categoria) ON DELETE RESTRICT,
    FOREIGN KEY (id_trabajador) REFERENCES trabajadores_responsables(id_trabajador) ON DELETE SET NULL,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (usuario_actualizacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    UNIQUE KEY uq_nuevos_codigo (codigo),
    INDEX idx_nuevos_categoria (id_categoria),
    INDEX idx_nuevos_trabajador (id_trabajador),
    INDEX idx_nuevos_estado (estado),
    INDEX idx_nuevos_sede (sede),
    INDEX idx_nuevos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ E1) Auditoría de nuevos activos ============
CREATE TABLE IF NOT EXISTS nuevos_activos_auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_nuevo_activo INT NOT NULL,
    campo_modificado VARCHAR(100) NULL,
    valor_anterior TEXT NULL,
    valor_nuevo TEXT NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo_operacion ENUM('INSERT','UPDATE','DELETE') NULL,
    origen VARCHAR(20) DEFAULT 'web',
    FOREIGN KEY (id_nuevo_activo) REFERENCES nuevos_activos(id_nuevo_activo) ON DELETE CASCADE,
    FOREIGN KEY (usuario_modificacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_nva_activo (id_nuevo_activo),
    INDEX idx_nva_fecha (fecha_modificacion),
    INDEX idx_nva_tipo (tipo_operacion),
    INDEX idx_nva_usuario_fecha (usuario_modificacion, fecha_modificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ E2) Adjuntos de nuevos activos (imágenes y archivos) ============
CREATE TABLE IF NOT EXISTS nuevos_activos_adjuntos (
    id_adjunto INT AUTO_INCREMENT PRIMARY KEY,
    id_nuevo_activo INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL COMMENT 'Nombre original subido por el usuario',
    ruta_archivo VARCHAR(500) NOT NULL COMMENT 'Ruta relativa dentro de uploads/',
    tipo_archivo VARCHAR(100) NULL,
    tamano_archivo INT NULL COMMENT 'Tamaño en bytes',
    descripcion TEXT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_subida INT NULL,
    FOREIGN KEY (id_nuevo_activo) REFERENCES nuevos_activos(id_nuevo_activo) ON DELETE CASCADE,
    FOREIGN KEY (usuario_subida) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_nvaa_activo (id_nuevo_activo),
    INDEX idx_nvaa_fecha (fecha_subida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ E3) Control devolución / cambio / traslado por trabajador ============
CREATE TABLE IF NOT EXISTS trabajadores_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_trabajador INT NOT NULL,
    id_nuevo_activo INT NULL COMMENT 'Activo asociado (opcional)',
    fecha DATE NOT NULL,
    tipo_movimiento ENUM('devolucion','cambio','traslado') NOT NULL,
    codigo_activo VARCHAR(100) NULL COMMENT 'Código del activo (texto libre)',
    estado_anterior VARCHAR(100) NULL,
    estado_nuevo VARCHAR(100) NULL,
    responsable VARCHAR(255) NULL,
    observacion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    FOREIGN KEY (id_trabajador) REFERENCES trabajadores_responsables(id_trabajador) ON DELETE CASCADE,
    FOREIGN KEY (id_nuevo_activo) REFERENCES nuevos_activos(id_nuevo_activo) ON DELETE SET NULL,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_mov_trabajador (id_trabajador),
    INDEX idx_mov_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============ F1) Columna origen en auditoría de activos (solo si falta) ============
SET @tiene_origen := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activos_auditoria' AND COLUMN_NAME = 'origen');
SET @sql_origen := IF(@tiene_origen = 0,
  'ALTER TABLE activos_auditoria ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT ''web'' AFTER tipo_operacion',
  'SELECT ''Columna origen ya existe''');
PREPARE stmt FROM @sql_origen;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_idx1 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activos_auditoria' AND INDEX_NAME = 'idx_auditoria_tipo');
SET @sql_idx1 := IF(@tiene_idx1 = 0,
  'CREATE INDEX idx_auditoria_tipo ON activos_auditoria (tipo_operacion)',
  'SELECT ''Indice idx_auditoria_tipo ya existe''');
PREPARE stmt FROM @sql_idx1;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_idx2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activos_auditoria' AND INDEX_NAME = 'idx_auditoria_usuario_fecha');
SET @sql_idx2 := IF(@tiene_idx2 = 0,
  'CREATE INDEX idx_auditoria_usuario_fecha ON activos_auditoria (usuario_modificacion, fecha_modificacion)',
  'SELECT ''Indice idx_auditoria_usuario_fecha ya existe''');
PREPARE stmt FROM @sql_idx2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============ F2) Columnas adicionales en nuevos_activos (solo si faltan) ============
-- (Por si las tablas ya existían de una instalación parcial anterior)
SET @tiene_fing := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'fecha_ingreso');
SET @sql_fing := IF(@tiene_fing = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN fecha_ingreso DATE NULL AFTER codigo_serial',
  'SELECT ''Columna fecha_ingreso ya existe''');
PREPARE stmt FROM @sql_fing;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_ccc := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'codigo_cuenta_contable');
SET @sql_ccc := IF(@tiene_ccc = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN codigo_cuenta_contable VARCHAR(50) NULL AFTER fecha_ingreso',
  'SELECT ''Columna codigo_cuenta_contable ya existe''');
PREPARE stmt FROM @sql_ccc;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_cgaf := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'codigo_grupo_activo_fijo');
SET @sql_cgaf := IF(@tiene_cgaf = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN codigo_grupo_activo_fijo VARCHAR(50) NULL AFTER codigo_cuenta_contable',
  'SELECT ''Columna codigo_grupo_activo_fijo ya existe''');
PREPARE stmt FROM @sql_cgaf;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_desc := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'descripcion');
SET @sql_desc := IF(@tiene_desc = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN descripcion TEXT NULL AFTER codigo_grupo_activo_fijo',
  'SELECT ''Columna descripcion ya existe''');
PREPARE stmt FROM @sql_desc;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_valor := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'valor');
SET @sql_valor := IF(@tiene_valor = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN valor DECIMAL(15,2) NULL DEFAULT NULL AFTER descripcion',
  'SELECT ''Columna valor ya existe''');
PREPARE stmt FROM @sql_valor;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_placa := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'codigo_placa');
SET @sql_placa := IF(@tiene_placa = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN codigo_placa VARCHAR(100) NULL AFTER codigo_serial',
  'SELECT ''Columna codigo_placa ya existe''');
PREPARE stmt FROM @sql_placa;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_idtrab := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'id_trabajador');
SET @sql_idtrab := IF(@tiene_idtrab = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN id_trabajador INT NULL AFTER id_categoria, ADD CONSTRAINT fk_nuevos_trabajador FOREIGN KEY (id_trabajador) REFERENCES trabajadores_responsables(id_trabajador) ON DELETE SET NULL',
  'SELECT ''Columna id_trabajador ya existe''');
PREPARE stmt FROM @sql_idtrab;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_idxtrab := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND INDEX_NAME = 'idx_nuevos_trabajador');
SET @sql_idxtrab := IF(@tiene_idxtrab = 0,
  'CREATE INDEX idx_nuevos_trabajador ON nuevos_activos (id_trabajador)',
  'SELECT ''Indice idx_nuevos_trabajador ya existe''');
PREPARE stmt FROM @sql_idxtrab;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============ G) 7 grupos de activos principales (no duplica si ya existen) ============
INSERT INTO categorias_inventario (nombre, clave, descripcion, icono, color) VALUES
('Edificios y construcciones', 'edificios_y_construcciones', 'Inmuebles, edificaciones y construcciones de la organización', 'building', 'primary'),
('Maquinaria y equipo', 'maquinaria_y_equipo', 'Maquinaria y equipos de operación', 'cogs', 'warning'),
('Equipo de oficina', 'equipo_de_oficina', 'Mobiliario y equipos de oficina', 'briefcase', 'info'),
('Equipo de computación y comunicación', 'equipo_de_computacion_y_comunicacion', 'Computadores, impresoras, redes y comunicaciones', 'laptop', 'success'),
('Equipo médico', 'equipo_medico', 'Equipos e instrumental médico', 'stethoscope', 'danger'),
('Sala de tanatopraxia', 'sala_de_tanatopraxia', 'Dotación y equipos de la sala de tanatopraxia', 'bed', 'secondary'),
('Flota y equipo de transporte', 'flota_y_equipo_de_transporte', 'Vehículos y equipos de transporte', 'truck', 'primary')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), icono = VALUES(icono), color = VALUES(color);

-- ============ PASO FINAL: asignar super administrador ============
-- Descomente y ajuste la siguiente línea con el correo del usuario que será
-- super administrador (el único que podrá crear/editar/eliminar activos nuevos):
UPDATE usuarios SET rol = 'superadmin' WHERE email = 'aprendiz.sistemas@osf.com.co';
