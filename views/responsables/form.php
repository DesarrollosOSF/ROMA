<?php
$isEdit = isset($trabajador) && is_array($trabajador);
$page_title = $isEdit ? 'Editar Responsable' : 'Nuevo Responsable';
require_once __DIR__ . '/../layout/header.php';
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
                    <label>Dependencia</label>
                    <input type="text" name="dependencia" value="<?php echo htmlspecialchars($trabajador['dependencia'] ?? ''); ?>" placeholder="Ej: Sistemas" list="depList">
                    <datalist id="depList">
                        <?php foreach (ASSIGNED_AREAS as $a): ?><option value="<?php echo htmlspecialchars($a); ?>"><?php endforeach; ?>
                    </datalist>
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
                    <label>Jefe Inmediato</label>
                    <input type="text" name="jefe_inmediato" value="<?php echo htmlspecialchars($trabajador['jefe_inmediato'] ?? ''); ?>" placeholder="Ej: María García">
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
