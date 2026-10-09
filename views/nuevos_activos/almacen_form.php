<?php
$isEditAlm = isset($almacen) && is_array($almacen);
$page_title = $isEditAlm ? 'Editar Bodega' : 'Agregar Bodega';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-<?php echo $isEditAlm ? 'edit' : 'plus'; ?>"></i> <?php echo $isEditAlm ? 'Editar Bodega' : 'Agregar Bodega'; ?></h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=almacen" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Almacén</a>
</div>

<div class="card">
    <div class="card-header"><h3><?php echo $isEditAlm ? 'Editar: ' . htmlspecialchars($almacen['nombre']) : 'Nueva bodega del almacén'; ?></h3></div>
    <div class="card-body">
        <div class="alert alert-info"><i class="fas fa-info-circle"></i> Ejemplos: San Rafael, COL. La clave se genera sola.</div>
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=<?php echo $isEditAlm ? 'actualizar_almacen&id=' . (int)$almacen['id_almacen'] : 'guardar_almacen'; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" required placeholder="Ej: San Rafael" value="<?php echo htmlspecialchars($almacen['nombre'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Responsable de bodega</label>
                    <input type="text" name="responsable" value="<?php echo htmlspecialchars($almacen['responsable'] ?? ''); ?>" placeholder="Nombre del responsable">
                </div>
                <div class="form-group form-group-full">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Ubicación, observaciones..."><?php echo htmlspecialchars($almacen['descripcion'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $isEditAlm ? 'Guardar Cambios' : 'Crear Bodega'; ?></button>
                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=almacen" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
