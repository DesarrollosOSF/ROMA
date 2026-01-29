-- ============================================
-- Script para agregar 'cofres_cremacion' al ENUM de categoría en la tabla activos
-- ============================================

-- Actualizar el ENUM de la columna categoria en la tabla activos
ALTER TABLE activos 
MODIFY COLUMN categoria ENUM(
    'equipo_especial',
    'vehiculos',
    'maquinas',
    'muebles_enseres',
    'horno_crematorio',
    'planta_agua_residual',
    'cofres_cremacion'
) NOT NULL;
    
