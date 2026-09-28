<?php
$isEditCat = isset($categoria) && is_array($categoria);
$page_title = $isEditCat ? 'Editar Grupo de activos' : 'Agregar Grupo de activos';
require_once __DIR__ . '/../layout/header.php';

$iconos = ['box' => 'Caja', 'laptop' => 'Portátil', 'print' => 'Impresora', 'desktop' => 'PC Escritorio', 'chair' => 'Silla', 'network-wired' => 'Red', 'server' => 'Servidor', 'phone' => 'Teléfono', 'tablet' => 'Tablet', 'couch' => 'Mueble'];
$colores = ['primary' => 'Azul', 'success' => 'Verde', 'warning' => 'Amarillo', 'danger' => 'Rojo', 'info' => 'Celeste', 'secondary' => 'Gris'];
$iconoSel = $isEditCat ? ($categoria['icono'] ?? 'box') : 'box';
$colorSel = $isEditCat ? ($categoria['color'] ?? 'primary') : 'primary';
?>

<div class="page-header">
    <h1><i class="fas fa-<?php echo $isEditCat ? 'edit' : 'folder-plus'; ?>"></i> <?php echo $isEditCat ? 'Editar Grupo de activos' : 'Agregar Grupo de activos'; ?></h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="card">
    <div class="card-header"><h3><?php echo $isEditCat ? 'Editar: ' . htmlspecialchars($categoria['nombre']) : 'Nuevo grupo de activos'; ?></h3></div>
    <div class="card-body">
        <div class="alert alert-info"><i class="fas fa-info-circle"></i> Ejemplos: Maquinaria y equipo, Equipo de oficina, Flota y equipo de transporte. La clave se genera sola y sirve para la carga masiva (columna <code>categoria</code>).<?php if ($isEditCat): ?> Si cambia el nombre, la clave se regenera automáticamente.<?php endif; ?></div>
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=<?php echo $isEditCat ? 'actualizar_categoria&id=' . (int)$categoria['id_categoria'] : 'guardar_categoria'; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" required placeholder="Ej: Maquinaria y equipo" value="<?php echo htmlspecialchars($categoria['nombre'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Icono (FontAwesome)</label>
                    <select name="icono">
                        <?php foreach ($iconos as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($iconoSel === $k) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <select name="color">
                        <?php foreach ($colores as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo ($colorSel === $k) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group-full">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Qué incluye este grupo..."><?php echo htmlspecialchars($categoria['descripcion'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $isEditCat ? 'Guardar Cambios' : 'Crear Grupo'; ?></button>
                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
