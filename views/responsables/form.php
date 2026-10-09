<?php
$isEdit = isset($trabajador) && is_array($trabajador);
$page_title = $isEdit ? 'Editar Responsable' : 'Nuevo Responsable';
require_once __DIR__ . '/../layout/header.php';
$dirSel = $isEdit ? ($trabajador['direccion'] ?? '') : '';
$areaSel = $isEdit ? ($trabajador['area'] ?? '') : '';
$catalogoDir = defined('DIRECCIONES_AREAS') ? DIRECCIONES_AREAS : [];
?>

<div class="page-header">
    <h1><i class="fas fa-<?php echo $isEdit ? 'edit' : 'user-plus'; ?>"></i> <?php echo $isEdit ? 'Editar Responsable' : 'Nuevo Responsable de Activos'; ?></h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=responsables" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="card">
    <div class="card-header"><h3>Datos del Trabajador</h3></div>
    <div class="card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=<?php echo $isEdit ? 'actualizar&id=' . (int)$trabajador['id_trabajador'] : 'guardar'; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre Completo <span class="required">*</span></label>
                    <input type="text" name="nombre_completo" required value="<?php echo htmlspecialchars($trabajador['nombre_completo'] ?? ''); ?>" placeholder="Ej: Juan Carlos Pérez">
                </div>
                <div class="form-group">
                    <label>Documento de Identidad <span class="required">*</span></label>
                    <input type="text" name="documento_identidad" required value="<?php echo htmlspecialchars($trabajador['documento_identidad'] ?? ''); ?>" placeholder="Ej: 1234567890">
                </div>
                <div class="form-group">
                    <label>Cargo</label>
                    <input type="text" name="cargo" value="<?php echo htmlspecialchars($trabajador['cargo'] ?? ''); ?>" placeholder="Ej: Auxiliar Administrativo">
                </div>
                <div class="form-group">
                    <label>Dirección a la que pertenece</label>
                    <select name="direccion" id="selDireccion" onchange="cargarAreas(true)">
                        <option value="">Seleccione...</option>
                        <?php foreach ($catalogoDir as $dir => $areas): ?>
                            <option value="<?php echo htmlspecialchars($dir); ?>" <?php echo ($dirSel === $dir) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dir); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Área a la que pertenece</label>
                    <select name="area" id="selArea">
                        <option value="">Seleccione primero la dirección...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de Vinculación</label>
                    <input type="text" name="tipo_vinculacion" value="<?php echo htmlspecialchars($trabajador['tipo_vinculacion'] ?? ''); ?>" placeholder="Ej: Término indefinido">
                </div>
                <div class="form-group">
                    <label>Centro de Costo</label>
                    <input type="text" name="centro_costo" value="<?php echo htmlspecialchars($trabajador['centro_costo'] ?? ''); ?>" placeholder="Ej: CC-001">
                </div>
                <div class="form-group">
                    <label>Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($trabajador['fecha_ingreso'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=responsables" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
const AREAS_POR_DIRECCION = <?php echo json_encode($catalogoDir, JSON_UNESCAPED_UNICODE); ?>;
const AREA_PRESEL = <?php echo json_encode($areaSel, JSON_UNESCAPED_UNICODE); ?>;
function cargarAreas(reset) {
    const dir = document.getElementById('selDireccion').value;
    const sel = document.getElementById('selArea');
    sel.innerHTML = '<option value="">Seleccione...</option>';
    (AREAS_POR_DIRECCION[dir] || []).forEach(function (a) {
        const op = document.createElement('option');
        op.value = a;
        op.textContent = a;
        if (a === AREA_PRESEL) op.selected = true;
        sel.appendChild(op);
    });
    if (reset) sel.selectedIndex = 0;
}
document.addEventListener('DOMContentLoaded', function () { cargarAreas(false); });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
