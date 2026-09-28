<?php
$page_title = 'Responsable: ' . ($trabajador['nombre_completo'] ?? '');
require_once __DIR__ . '/../layout/header.php';
$hoy = date('Y-m-d');
?>

<div class="page-header">
    <h1><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($trabajador['nombre_completo']); ?></h1>
    <div class="header-actions">
        <?php if ($puedeGestionar): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=editar&id=<?php echo (int)$trabajador['id_trabajador']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Editar</a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=formato&trabajador=<?php echo (int)$trabajador['id_trabajador']; ?>" class="btn btn-success"><i class="fas fa-file-pdf"></i> Generar Formato</a>
        <a href="<?php echo BASE_URL; ?>index.php?action=responsables" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</div>

<div class="detail-grid">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user"></i> Datos del Trabajador</h3></div>
        <div class="card-body">
            <dl class="detail-list">
                <dt>Nombre Completo:</dt><dd><?php echo htmlspecialchars($trabajador['nombre_completo']); ?></dd>
                <dt>Documento:</dt><dd><?php echo htmlspecialchars($trabajador['documento_identidad']); ?></dd>
                <dt>Cargo:</dt><dd><?php echo htmlspecialchars($trabajador['cargo'] ?: 'N/A'); ?></dd>
                <dt>Dependencia:</dt><dd><?php echo htmlspecialchars($trabajador['dependencia'] ?: 'N/A'); ?></dd>
                <dt>Tipo de Vinculación:</dt><dd><?php echo htmlspecialchars($trabajador['tipo_vinculacion'] ?: 'N/A'); ?></dd>
                <dt>Centro de Costo:</dt><dd><?php echo htmlspecialchars($trabajador['centro_costo'] ?: 'N/A'); ?></dd>
                <dt>Jefe Inmediato:</dt><dd><?php echo htmlspecialchars($trabajador['jefe_inmediato'] ?: 'N/A'); ?></dd>
                <dt>Fecha de Ingreso:</dt><dd><?php echo !empty($trabajador['fecha_ingreso']) ? date('d/m/Y', strtotime($trabajador['fecha_ingreso'])) : 'N/A'; ?></dd>
            </dl>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-file-pdf"></i> Formato de Inventario Adjunto</h3></div>
        <div class="card-body">
            <?php if (!empty($trabajador['formato_ruta'])): ?>
                <p><i class="fas fa-file-pdf"></i> <strong><?php echo htmlspecialchars($trabajador['formato_nombre'] ?? 'Formato'); ?></strong></p>
                <div class="form-actions" style="justify-content:flex-start;">
                    <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($trabajador['formato_ruta']); ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Consultar</a>
                    <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($trabajador['formato_ruta']); ?>" download class="btn btn-sm btn-secondary"><i class="fas fa-download"></i> Descargar</a>
                    <?php if ($puedeGestionar): ?>
                        <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=eliminar_formato&id=<?php echo (int)$trabajador['id_trabajador']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('¿Eliminar el formato adjunto?')"><i class="fas fa-trash"></i> Quitar</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-file-pdf"></i><p>Sin formato adjunto</p></div>
            <?php endif; ?>
            <?php if ($puedeGestionar): ?>
                <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=subir_formato&id=<?php echo (int)$trabajador['id_trabajador']; ?>" enctype="multipart/form-data" style="margin-top:1rem;">
                    <div class="form-group">
                        <label><?php echo !empty($trabajador['formato_ruta']) ? 'Reemplazar formato (PDF/Word/Excel/imagen)' : 'Adjuntar formato (PDF/Word/Excel/imagen)'; ?></label>
                        <input type="file" name="formato" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp">
                        <small>Máx. 10MB. Genérelo desde el botón "Generar Formato" e imprímalo a PDF.</small>
                    </div>
                    <div class="form-actions" style="justify-content:flex-start;">
                        <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Subir Formato</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card card-full">
    <div class="card-header"><h3><i class="fas fa-boxes"></i> Activos Asociados (<?php echo count($activos); ?>)</h3></div>
    <div class="card-body">
        <?php if (empty($activos)): ?>
            <div class="empty-state"><i class="fas fa-box"></i><p>Sin activos asociados. Asócielos desde el formulario del activo (campo Trabajador responsable).</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Código</th><th>Serial</th><th>Nombre</th><th>Grupo</th><th>Sede</th><th>Estado</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach ($activos as $a): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($a['codigo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($a['codigo_serial'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($a['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($a['categoria_nombre'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($a['sede'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($a['estado']); ?></td>
                                <td><a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=ver&id=<?php echo (int)$a['id_nuevo_activo']; ?>" class="btn-icon" title="Ver"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-full">
    <div class="card-header"><h3><i class="fas fa-exchange-alt"></i> Control de Devolución / Cambio / Traslado</h3></div>
    <div class="card-body">
        <?php if (empty($trabajador['movimientos'])): ?>
            <div class="empty-state"><i class="fas fa-exchange-alt"></i><p>Sin movimientos registrados</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Activo</th><th>Estado Anterior</th><th>Estado Nuevo</th><th>Responsable</th><th>Observación</th><?php if ($puedeGestionar): ?><th>Acciones</th><?php endif; ?></tr></thead>
                    <tbody>
                        <?php foreach ($trabajador['movimientos'] as $m): ?>
                            <tr>
                                <td style="white-space:nowrap;"><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($tiposMov[$m['tipo_movimiento']] ?? $m['tipo_movimiento']); ?></span></td>
                                <td>
                                    <?php if (!empty($m['id_nuevo_activo'])): ?>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=ver&id=<?php echo (int)$m['id_nuevo_activo']; ?>"><strong><?php echo htmlspecialchars($m['activo_codigo'] ?? $m['codigo_activo']); ?></strong></a>
                                        <?php if (!empty($m['activo_nombre'])): ?><br><small class="text-muted"><?php echo htmlspecialchars($m['activo_nombre']); ?></small><?php endif; ?>
                                    <?php else: ?>
                                        <strong><?php echo htmlspecialchars($m['codigo_activo'] ?: '—'); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($m['estado_anterior'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($m['estado_nuevo'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($m['responsable'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($m['observacion'] ?: '—'); ?></td>
                                <?php if ($puedeGestionar): ?>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon" title="Editar"
                                                onclick='editarMovimiento(<?php echo json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=eliminar_movimiento&id=<?php echo (int)$trabajador['id_trabajador']; ?>&id_movimiento=<?php echo (int)$m['id_movimiento']; ?>"
                                               class="btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este movimiento?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($puedeGestionar): ?>
            <h4 id="tituloMovForm" style="margin-top:1.5rem;"><i class="fas fa-plus"></i> Registrar Movimiento</h4>
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=guardar_movimiento&id=<?php echo (int)$trabajador['id_trabajador']; ?>">
                <input type="hidden" name="id_movimiento" id="mov_id" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Fecha <span class="required">*</span></label>
                        <input type="date" name="fecha" id="mov_fecha" value="<?php echo $hoy; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo <span class="required">*</span></label>
                        <select name="tipo_movimiento" id="mov_tipo" required>
                            <?php foreach ($tiposMov as $k => $v): ?><option value="<?php echo $k; ?>"><?php echo $v; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Activo (del trabajador)</label>
                        <select name="id_nuevo_activo" id="mov_activo">
                            <option value="">— Otro / manual —</option>
                            <?php foreach ($activos as $a): ?>
                                <option value="<?php echo (int)$a['id_nuevo_activo']; ?>" data-codigo="<?php echo htmlspecialchars($a['codigo']); ?>"><?php echo htmlspecialchars($a['codigo'] . ' — ' . $a['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Código del Activo</label>
                        <input type="text" name="codigo_activo" id="mov_codigo" placeholder="Ej: PC-001">
                    </div>
                    <div class="form-group">
                        <label>Estado Anterior</label>
                        <input type="text" name="estado_anterior" id="mov_est_ant" placeholder="Ej: Bueno">
                    </div>
                    <div class="form-group">
                        <label>Estado Nuevo</label>
                        <input type="text" name="estado_nuevo" id="mov_est_nuevo" placeholder="Ej: Regular">
                    </div>
                    <div class="form-group">
                        <label>Responsable</label>
                        <input type="text" name="responsable" id="mov_resp" placeholder="Quién recibe/entrega">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Observación</label>
                        <input type="text" name="observacion" id="mov_obs" placeholder="Detalle del movimiento">
                    </div>
                </div>
                <div class="form-actions" style="justify-content:flex-start;">
                    <button type="submit" class="btn btn-primary" id="btnMovGuardar"><i class="fas fa-save"></i> Guardar Movimiento</button>
                    <button type="button" class="btn btn-secondary" id="btnMovCancelar" style="display:none;" onclick="cancelarEdicionMov()">Cancelar edición</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($puedeGestionar): ?>
<script>
function editarMovimiento(m) {
    document.getElementById('mov_id').value = m.id_movimiento;
    document.getElementById('mov_fecha').value = m.fecha || '';
    document.getElementById('mov_tipo').value = m.tipo_movimiento || 'devolucion';
    document.getElementById('mov_activo').value = m.id_nuevo_activo || '';
    document.getElementById('mov_codigo').value = m.codigo_activo || '';
    document.getElementById('mov_est_ant').value = m.estado_anterior || '';
    document.getElementById('mov_est_nuevo').value = m.estado_nuevo || '';
    document.getElementById('mov_resp').value = m.responsable || '';
    document.getElementById('mov_obs').value = m.observacion || '';
    document.getElementById('tituloMovForm').innerHTML = '<i class="fas fa-edit"></i> Editar Movimiento';
    document.getElementById('btnMovCancelar').style.display = '';
    document.getElementById('tituloMovForm').scrollIntoView({behavior: 'smooth'});
}
function cancelarEdicionMov() {
    document.getElementById('mov_id').value = '';
    document.getElementById('tituloMovForm').innerHTML = '<i class="fas fa-plus"></i> Registrar Movimiento';
    document.getElementById('btnMovCancelar').style.display = 'none';
}
document.getElementById('mov_activo').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.codigo) {
        document.getElementById('mov_codigo').value = opt.dataset.codigo;
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
