<?php
$isEdit = isset($activo) && is_array($activo);
$page_title = $isEdit ? 'Editar Activo' : 'Nuevo Activo';
require_once __DIR__ . '/../layout/header.php';
$catSel = $isEdit ? ($activo['id_categoria'] ?? '') : ((int)($_GET['categoria'] ?? 0) ?: ($idCategoriaPre ?? ''));
?>

<div class="page-header">
    <h1><i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus'; ?>"></i> <?php echo $isEdit ? 'Editar Activo' : 'Nuevo Activo'; ?></h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="card">
    <div class="card-header"><h3>Información del Activo</h3></div>
    <div class="card-body">
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=<?php echo $isEdit ? 'actualizar&id=' . (int)$activo['id_nuevo_activo'] : 'guardar'; ?>" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Grupo de activos <span class="required">*</span></label>
                    <select name="id_categoria" required>
                        <option value="">Seleccione...</option>
                        <?php foreach (($listaCategorias ?? []) as $c): ?>
                            <option value="<?php echo (int)$c['id_categoria']; ?>" <?php echo ((string)$catSel === (string)$c['id_categoria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo (int)$c['total_activos']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado <span class="required">*</span></label>
                    <select name="estado" required>
                        <?php foreach ($estados as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo (($isEdit ? ($activo['estado'] ?? '') : 'operativo') === $k) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Código Activo Fijo <span class="required">*</span></label>
                    <input type="text" name="codigo" value="<?php echo htmlspecialchars($activo['codigo'] ?? ''); ?>" required placeholder="Ej: PC-001 o 00234">
                </div>
                <div class="form-group">
                    <label>Código Serial</label>
                    <input type="text" name="codigo_serial" value="<?php echo htmlspecialchars($activo['codigo_serial'] ?? ''); ?>" placeholder="Serial del fabricante">
                </div>
                <div class="form-group">
                    <label>Código de Placa</label>
                    <input type="text" name="codigo_placa" value="<?php echo htmlspecialchars($activo['codigo_placa'] ?? ''); ?>" placeholder="Ej: PLA-001 (opcional)">
                </div>

                <div class="form-group form-group-full">
                    <label>Nombre <span class="required">*</span></label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($activo['nombre'] ?? ''); ?>" required placeholder="Ej: Portátil HP ProBook 440">
                </div>
                <div class="form-group">
                    <label>Sede</label>
                    <input type="text" name="sede" value="<?php echo htmlspecialchars($activo['sede'] ?? ''); ?>" placeholder="Ej: Sede principal Tunja" list="sedesList">
                    <datalist id="sedesList">
                        <?php foreach (ASSET_LOCATIONS as $s): ?><option value="<?php echo htmlspecialchars($s); ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Trabajador Responsable (asocia el activo)</label>
                    <select name="id_trabajador">
                        <option value="">— Sin asociar —</option>
                        <?php foreach (($listaTrabajadores ?? []) as $tr): ?>
                            <option value="<?php echo (int)$tr['id_trabajador']; ?>" <?php echo ((string)($isEdit ? ($activo['id_trabajador'] ?? '') : '') === (string)$tr['id_trabajador']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tr['nombre_completo'] . ' (' . $tr['documento_identidad'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Solo super admin gestiona este listado (Usuarios → Responsables de Activos).</small>
                </div>
                <div class="form-group">
                    <label>Jefe Inmediato</label>
                    <input type="text" name="responsable_general" value="<?php echo htmlspecialchars($activo['responsable_general'] ?? ''); ?>" placeholder="Nombre del jefe inmediato">
                </div>
                <div class="form-group">
                    <label>Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($activo['fecha_ingreso'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Código Cuenta Contable</label>
                    <input type="text" name="codigo_cuenta_contable" value="<?php echo htmlspecialchars($activo['codigo_cuenta_contable'] ?? ''); ?>" placeholder="Ej: 152405">
                </div>
                <div class="form-group">
                    <label>Código Grupo Activo Fijo</label>
                    <input type="text" name="codigo_grupo_activo_fijo" value="<?php echo htmlspecialchars($activo['codigo_grupo_activo_fijo'] ?? ''); ?>" placeholder="Ej: 005">
                </div>
                <div class="form-group">
                    <label>Valor</label>
                    <input type="number" name="valor" step="0.01" min="0" value="<?php echo htmlspecialchars($activo['valor'] ?? ''); ?>" placeholder="Ej: 1500000">
                </div>
                <div class="form-group form-group-full">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Descripción del activo..."><?php echo htmlspecialchars($activo['descripcion'] ?? ''); ?></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label>Imágenes y archivos adjuntos</label>
                    <input type="file" name="adjuntos[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                    <small>Puede seleccionar varias imágenes o archivos (máx. 10MB c/u). En edición también puede cargarlos desde la ficha del activo.</small>
                </div>
            </div>
            <?php if ($isEdit && !empty($activo['adjuntos'])): ?>
                <div class="form-section">
                    <h4><i class="fas fa-paperclip"></i> Adjuntos actuales (<?php echo count($activo['adjuntos']); ?>)</h4>
                    <ul>
                        <?php foreach ($activo['adjuntos'] as $adj): ?>
                            <li><?php echo htmlspecialchars($adj['nombre_archivo']); ?> <small class="text-muted">(<?php echo date('d/m/Y', strtotime($adj['fecha_subida'])); ?>)</small></li>
                        <?php endforeach; ?>
                    </ul>
                    <small>Para eliminar un adjunto, hágalo desde la ficha del activo (Ver).</small>
                </div>
            <?php endif; ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
