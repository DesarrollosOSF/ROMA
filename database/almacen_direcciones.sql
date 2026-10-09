-- ============================================
-- ROMA: Almacenes + estado 'almacen' + dirección/área en responsables
-- Ejecutar una sola vez en la BD (SEGURO: no borra datos, todo es
-- condicional y re-ejecutable).
-- 1) Agrega el estado 'almacen' al ENUM de nuevos_activos
-- 2) Crea la tabla almacenes (bodegas: San Rafael y COL)
-- 3) Agrega nuevos_activos.id_almacen (bodega donde está el activo)
-- 4) Agrega trabajadores_responsables.direccion y .area
--    (se conserva la columna dependencia con sus datos)
-- ============================================

-- 1) Estado 'almacen'
SET @tiene_est_alm := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos'
    AND COLUMN_NAME = 'estado' AND COLUMN_TYPE LIKE '%almacen%');
SET @sql_est_alm := IF(@tiene_est_alm = 0,
  'ALTER TABLE nuevos_activos MODIFY COLUMN estado ENUM(''operativo'',''en_reparacion'',''fuera_servicio'',''en_baja'',''almacen'') DEFAULT ''operativo''',
  'SELECT ''Estado almacen ya existe''');
PREPARE stmt FROM @sql_est_alm;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Tabla de almacenes (bodegas)
CREATE TABLE IF NOT EXISTS almacenes (
    id_almacen INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    clave VARCHAR(100) NOT NULL UNIQUE COMMENT 'slug sin espacios: san_rafael, col...',
    descripcion TEXT NULL,
    responsable VARCHAR(255) NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_alm_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO almacenes (nombre, clave, descripcion) VALUES
('San Rafael', 'san_rafael', 'Bodega San Rafael'),
('COL', 'col', 'Bodega COL (Centro de Operaciones)')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion);

-- 3) Bodega del activo
SET @tiene_idalm := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'id_almacen');
SET @sql_idalm := IF(@tiene_idalm = 0,
  'ALTER TABLE nuevos_activos ADD COLUMN id_almacen INT NULL AFTER id_trabajador, ADD CONSTRAINT fk_nuevos_almacen FOREIGN KEY (id_almacen) REFERENCES almacenes(id_almacen) ON DELETE SET NULL',
  'SELECT ''Columna id_almacen ya existe''');
PREPARE stmt FROM @sql_idalm;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_idxalm := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND INDEX_NAME = 'idx_nuevos_almacen');
SET @sql_idxalm := IF(@tiene_idxalm = 0,
  'CREATE INDEX idx_nuevos_almacen ON nuevos_activos (id_almacen)',
  'SELECT ''Indice idx_nuevos_almacen ya existe''');
PREPARE stmt FROM @sql_idxalm;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) Dirección y área del trabajador (se conserva dependencia con sus datos)
SET @tiene_dir := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores_responsables' AND COLUMN_NAME = 'direccion');
SET @sql_dir := IF(@tiene_dir = 0,
  'ALTER TABLE trabajadores_responsables ADD COLUMN direccion VARCHAR(150) NULL AFTER dependencia',
  'SELECT ''Columna direccion ya existe''');
PREPARE stmt FROM @sql_dir;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_area := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores_responsables' AND COLUMN_NAME = 'area');
SET @sql_area := IF(@tiene_area = 0,
  'ALTER TABLE trabajadores_responsables ADD COLUMN area VARCHAR(150) NULL AFTER direccion',
  'SELECT ''Columna area ya existe''');
PREPARE stmt FROM @sql_area;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
