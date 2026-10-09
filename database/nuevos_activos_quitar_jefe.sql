-- ============================================
-- ROMA: Quitar jefe inmediato (ya no se usa, solo dirección y área)
-- - trabajadores_responsables.jefe_inmediato
-- - nuevos_activos.responsable_general
-- Ejecutar una sola vez en la BD (SEGURO: solo toca esas columnas,
-- el resto de la información no se modifica).
-- NOTA: esto elimina los datos guardados en esas dos columnas.
-- ============================================

SET @tiene_ji := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores_responsables' AND COLUMN_NAME = 'jefe_inmediato');
SET @sql_ji := IF(@tiene_ji > 0,
  'ALTER TABLE trabajadores_responsables DROP COLUMN jefe_inmediato',
  'SELECT ''Columna jefe_inmediato ya no existe''');
PREPARE stmt FROM @sql_ji;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @tiene_rg := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nuevos_activos' AND COLUMN_NAME = 'responsable_general');
SET @sql_rg := IF(@tiene_rg > 0,
  'ALTER TABLE nuevos_activos DROP COLUMN responsable_general',
  'SELECT ''Columna responsable_general ya no existe''');
PREPARE stmt FROM @sql_rg;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
