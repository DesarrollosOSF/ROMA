-- ============================================
-- AGREGAR OPCIÓN "Documentos y Trámites" AL ENUM DE TIPO_MANTENIMIENTO
-- Sistema ROMA - Actualización de Base de Datos
-- ============================================
-- Este script agrega la nueva opción 'documentos_tramites' a los campos ENUM
-- de tipo_mantenimiento en todas las tablas que lo utilizan
-- ============================================

USE osfcomco_roma;

-- Actualizar tabla activos_hoja_vida
ALTER TABLE activos_hoja_vida 
MODIFY COLUMN tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo', 'documentos_tramites') NOT NULL;

-- Actualizar tabla ordenes_trabajo
ALTER TABLE ordenes_trabajo 
MODIFY COLUMN tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo', 'documentos_tramites') NOT NULL;

-- Actualizar tabla solicitudes_mantenimiento
ALTER TABLE solicitudes_mantenimiento 
MODIFY COLUMN tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo', 'documentos_tramites') NOT NULL;

-- Actualizar tabla planes_preventivo
ALTER TABLE planes_preventivo 
MODIFY COLUMN tipo_mantenimiento ENUM('instalacion', 'correctivo', 'preventivo', 'documentos_tramites') DEFAULT 'preventivo';

-- ============================================
-- FIN DE ACTUALIZACIÓN
-- ============================================

