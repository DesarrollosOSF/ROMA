<?php
$page_title = 'Detalle: ' . ($activo['nombre'] ?? '');
require_once __DIR__ . '/../layout/header.php';
$puedeGestionar = Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'crear_nuevos_activos');

$estadoColoresDetalle = [
    'operativo' => ['#10b981', '#d1fae5'],
    'en_reparacion' => ['#f59e0b', '#fef3c7'],
    'fuera_servicio' => ['#ef4444', '#fee2e2'],
    'en_baja' => ['#6b7280', '#f1f5f9'],
];
[$colorEst, $fondoEst] = $estadoColoresDetalle[$activo['estado']] ?? ['#64748b', '#f1f5f9'];

function fichaFila($icono, $etiqueta, $valorHtml) {
    return '<div class="ficha-fila"><div class="ficha-icono"><i class="fas fa-' . $icono . '"></i></div>'
        . '<div class="ficha-texto"><small>' . $etiqueta . '</small><div>' . $valorHtml . '</div></div></div>';
}
?>

<style>
.activo-hero { background: linear-gradient(120deg, #0f172a 0%, #1e3a8a 60%, #2563eb 100%); border-radius: 14px; padding: 1.5rem 1.75rem; color: #fff; display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; margin-bottom: 1.25rem; box-shadow: 0 8px 24px rgba(15,23,42,.3); }
.activo-hero-icon { width: 68px; height: 68px; border-radius: 18px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.25); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0; }
.activo-hero h1 { margin: 0; font-size: 1.45rem; }
.activo-chips { display: flex; gap: .45rem; flex-wrap: wrap; margin-top: .55rem; }
.chip { display: inline-flex; align-items: center; gap: .35rem; font-size: .75rem; font-weight: 600; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.25); border-radius: 999px; padding: .25rem .7rem; }
.chip-mono { font-family: Consolas, monospace; }
.activo-hero-actions { margin-left: auto; display: flex; gap: .5rem; flex-wrap: wrap; }
.activo-hero-actions .btn { border: 1px solid rgba(255,255,255,.35); }
.ficha-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem; }
.ficha-fila { display: flex; gap: .8rem; align-items: flex-start; padding: .6rem 0; border-bottom: 1px dashed #e2e8f0; }
.ficha-fila:last-child { border-bottom: none; }
.ficha-icono { width: 36px; height: 36px; border-radius: 10px; background: #eef2ff; color: #4338ca; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .9rem; }
.ficha-texto small { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; font-weight: 600; }
.ficha-texto div { font-weight: 600; color: #0f172a; }
.valor-hero { background: linear-gradient(120deg, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0; border-radius: 12px; padding: .9rem 1.1rem; text-align: center; margin-bottom: .9rem; }
.valor-hero small { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #047857; font-weight: 700; }
.valor-hero strong { font-size: 1.7rem; color: #065f46; }
.estado-banner { border-radius: 12px; padding: .7rem 1rem; display: flex; align-items: center; gap: .6rem; font-weight: 700; margin-bottom: .9rem; }
.estado-dot-lg { width: 12px; height: 12px; border-radius: 50%; }
.adjuntos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin-top: .75rem; }
.adjunto-item { border: 1px solid #e2e8f0; border-radius: 12px; padding: .6rem; display: flex; flex-direction: column; gap: .3rem; background: #fff; transition: transform .15s, box-shadow .15s; }
.adjunto-item:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(15,23,42,.1); }
.adjunto-thumb { width: 100%; height: 130px; object-fit: cover; border-radius: 8px; }
.adjunto-nombre { font-size: .78rem; font-weight: 600; word-break: break-word; color: #0f172a; }
.adjunto-acciones { display: flex; gap: .4rem; margin-top: auto; }
.timeline { position: relative; margin: .5rem 0 0 8px; padding-left: 1.5rem; border-left: 2px solid #e2e8f0; display: flex; flex-direction: column; gap: 1rem; }
.timeline-item { position: relative; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: .7rem .9rem; }
.timeline-item::before { content: ''; position: absolute; left: calc(-1.5rem - 6px); top: .9rem; width: 10px; height: 10px; border-radius: 50%; background: var(--tl-color, #2563eb); box-shadow: 0 0 0 3px #fff, 0 0 0 5px var(--tl-color, #2563eb); }
.timeline-meta { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; font-size: .78rem; color: #64748b; margin-bottom: .3rem; }
.timeline-campo { font-weight: 700; color: #0f172a; }
.timeline-cambio { font-size: .82rem; }
.timeline-cambio del { color: #b91c1c; text-decoration-color: #fca5a5; }
.timeline-cambio ins { color: #047857; text-decoration: none; font-weight: 600; }
.doc-row { display: flex; align-items: center; gap: .7rem; padding: .6rem .2rem; border-bottom: 1px dashed #e2e8f0; }
.doc-row:last-child { border-bottom: none; }
.doc-icon { width: 38px; height: 38px; border-radius: 10px; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
</style>

<div class="activo-hero">
    <div class="activo-hero-icon"><i class="fas fa-box"></i></div>
    <div>
        <h1><?php echo htmlspecialchars($activo['nombre']); ?></h1>
        <div class="activo-chips">
            <span class="chip chip-mono"><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($activo['codigo']); ?></span>
            <?php if (!empty($activo['codigo_placa'])): ?><span class="chip chip-mono"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($activo['codigo_placa']); ?></span><?php endif; ?>
            <span class="chip"><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($activo['categoria_nombre'] ?? ''); ?></span>
            <span class="chip"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($activo['sede'] ?: 'Sin sede'); ?></span>
        </div>
    </div>
    <div class="activo-hero-actions">
        <?php if ($puedeGestionar): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=editar&id=<?php echo (int)$activo['id_nuevo_activo']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Editar</a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=categoria&id=<?php echo (int)$activo['id_categoria']; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</div>

<div class="estado-banner" style="background:<?php echo $fondoEst; ?>; color:<?php echo $colorEst; ?>;">
    <span class="estado-dot-lg" style="background:<?php echo $colorEst; ?>;"></span>
    Estado: <?php echo $estados[$activo['estado']] ?? $activo['estado']; ?>
    <?php if (!empty($activo['fecha_ingreso']) && $activo['fecha_ingreso'] !== '0000-00-00'): ?>
        <span style="margin-left:auto; font-weight:600;">Ingreso: <?php echo date('d/m/Y', strtotime($activo['fecha_ingreso'])); ?></span>
    <?php endif; ?>
</div>

<div class="ficha-grid">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-fingerprint"></i> Identificación</h3></div>
        <div class="card-body">
            <?php echo fichaFila('layer-group', 'Grupo de activos', htmlspecialchars($activo['categoria_nombre'] ?? '—')); ?>
            <?php echo fichaFila('barcode', 'Código activo fijo', '<span class="codigo-chip" style="font-family:Consolas,monospace;font-weight:700;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;border-radius:6px;padding:.15rem .5rem;">' . htmlspecialchars($activo['codigo']) . '</span>'); ?>
            <?php echo fichaFila('hashtag', 'Código serial', htmlspecialchars($activo['codigo_serial'] ?: 'N/A')); ?>
            <?php echo fichaFila('tag', 'Código de placa', htmlspecialchars($activo['codigo_placa'] ?? '' ?: 'N/A')); ?>
            <?php echo fichaFila('file-invoice-dollar', 'Cuenta contable', htmlspecialchars($activo['codigo_cuenta_contable'] ?? '' ?: 'N/A')); ?>
            <?php echo fichaFila('boxes', 'Grupo activo fijo', htmlspecialchars($activo['codigo_grupo_activo_fijo'] ?? '' ?: 'N/A')); ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-tie"></i> Responsables y Valor</h3></div>
        <div class="card-body">
            <div class="valor-hero">
                <small>Valor del activo</small>
                <strong><?php echo ($activo['valor'] ?? null) !== null && $activo['valor'] !== '' ? '$' . number_format((float)$activo['valor'], 2, ',', '.') : 'N/A'; ?></strong>
            </div>
            <?php echo fichaFila('map-marker-alt', 'Sede', htmlspecialchars($activo['sede'] ?: 'N/A')); ?>
            <?php echo fichaFila('user-tie', 'Jefe inmediato', htmlspecialchars($activo['responsable_general'] ?: 'N/A')); ?>
            <?php
            $trabHtml = 'N/A';
            if (!empty($activo['id_trabajador'])) {
                $trabHtml = '<a href="' . BASE_URL . 'index.php?action=responsables&subaction=ver&id=' . (int)$activo['id_trabajador'] . '">'
                    . htmlspecialchars(($activo['trabajador_nombre'] ?? 'Ver trabajador') . ' (' . ($activo['trabajador_documento'] ?? '') . ')') . '</a>';
            }
            echo fichaFila('id-card', 'Trabajador asociado', $trabHtml);
            ?>
        </div>
    </div>
</div>

<div class="card card-full" style="margin-bottom:1.25rem;">
    <div class="card-header"><h3><i class="fas fa-align-left"></i> Descripción</h3></div>
    <div class="card-body">
        <p style="margin:0;"><?php echo nl2br(htmlspecialchars($activo['descripcion'] ?? '' ?: 'Sin descripción')); ?></p>
        <p class="text-muted" style="margin:.75rem 0 0; font-size:.8rem;">
            Creado: <?php echo !empty($activo['fecha_creacion']) ? date('d/m/Y H:i', strtotime($activo['fecha_creacion'])) : 'N/A'; ?>
            · Actualizado: <?php echo !empty($activo['fecha_actualizacion']) ? date('d/m/Y H:i', strtotime($activo['fecha_actualizacion'])) : 'N/A'; ?>
        </p>
    </div>
</div>

<div class="card card-full" style="margin-bottom:1.25rem;">
    <div class="card-header">
        <h3><i class="fas fa-paperclip"></i> Imágenes y Archivos Adjuntos (<?php echo count($activo['adjuntos'] ?? []); ?>)</h3>
    </div>
    <div class="card-body">
        <?php $adjuntos = $activo['adjuntos'] ?? []; ?>
        <?php if (empty($adjuntos)): ?>
            <div class="empty-state"><i class="fas fa-paperclip"></i><p>Este activo aún no tiene imágenes ni archivos</p></div>
        <?php else: ?>
            <?php
            $imagenes = array_filter($adjuntos, function ($a) {
                return NuevoActivo::esImagen($a['nombre_archivo'] ?? '');
            });
            $archivos = array_filter($adjuntos, function ($a) {
                return !NuevoActivo::esImagen($a['nombre_archivo'] ?? '');
            });
            ?>
            <?php if (!empty($imagenes)): ?>
                <h4><i class="fas fa-images"></i> Imágenes</h4>
                <div class="adjuntos-grid">
                    <?php foreach ($imagenes as $img): ?>
                        <div class="adjunto-item">
                            <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($img['ruta_archivo']); ?>" target="_blank" title="<?php echo htmlspecialchars($img['nombre_archivo']); ?>">
                                <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($img['ruta_archivo']); ?>" alt="<?php echo htmlspecialchars($img['nombre_archivo']); ?>" class="adjunto-thumb" loading="lazy">
                            </a>
                            <span class="adjunto-nombre"><?php echo htmlspecialchars($img['nombre_archivo']); ?></span>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($img['fecha_subida'])); ?><?php echo !empty($img['usuario_nombre']) ? ' · ' . htmlspecialchars($img['usuario_nombre']) : ''; ?></small>
                            <?php if (!empty($img['descripcion'])): ?><small><?php echo htmlspecialchars($img['descripcion']); ?></small><?php endif; ?>
                            <div class="adjunto-acciones">
                                <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($img['ruta_archivo']); ?>" target="_blank" class="btn-icon" title="Ver"><i class="fas fa-eye"></i></a>
                                <?php if ($puedeGestionar): ?>
                                    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=eliminar_adjunto&id=<?php echo (int)$activo['id_nuevo_activo']; ?>&id_adjunto=<?php echo (int)$img['id_adjunto']; ?>"
                                       class="btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este archivo?')"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($archivos)): ?>
                <h4 style="margin-top:1.25rem;"><i class="fas fa-file-alt"></i> Archivos</h4>
                <?php foreach ($archivos as $doc): ?>
                    <div class="doc-row">
                        <div class="doc-icon"><i class="fas fa-file"></i></div>
                        <div style="flex:1;">
                            <strong><?php echo htmlspecialchars($doc['nombre_archivo']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($doc['descripcion'] ?: 'Sin descripción'); ?> · <?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?><?php echo !empty($doc['usuario_nombre']) ? ' · ' . htmlspecialchars($doc['usuario_nombre']) : ''; ?></small>
                        </div>
                        <div class="action-buttons">
                            <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($doc['ruta_archivo']); ?>" target="_blank" class="btn-icon" title="Descargar"><i class="fas fa-download"></i></a>
                            <?php if ($puedeGestionar): ?>
                                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=eliminar_adjunto&id=<?php echo (int)$activo['id_nuevo_activo']; ?>&id_adjunto=<?php echo (int)$doc['id_adjunto']; ?>"
                                   class="btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este archivo?')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($puedeGestionar): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=subir_adjunto&id=<?php echo (int)$activo['id_nuevo_activo']; ?>" enctype="multipart/form-data" style="margin-top:1.25rem; border-top:1px solid #e5e7eb; padding-top:1rem;">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Seleccionar imágenes / archivos (múltiple)</label>
                        <input type="file" name="adjuntos[]" multiple required accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                        <small>JPG, PNG, GIF, WEBP, PDF, DOC(X), XLS(X). Máx. 10MB c/u.</small>
                    </div>
                    <div class="form-group">
                        <label>Descripción (opcional, aplica a todos)</label>
                        <input type="text" name="descripcion_adjunto" maxlength="255" placeholder="Ej: Factura, foto frontal...">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Cargar Adjuntos</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (Usuario::esAdminGeneral($_SESSION['usuario_rol'] ?? '')): ?>
<div class="card card-full">
    <div class="card-header"><h3><i class="fas fa-shield-alt"></i> Historial de Cambios (Auditoría)</h3></div>
    <div class="card-body">
        <?php if (empty($historial)): ?>
            <div class="empty-state"><i class="fas fa-shield-alt"></i><p>Sin movimientos</p></div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($historial as $h): ?>
                    <?php
                    $tipoH = strtoupper($h['tipo_operacion'] ?? '');
                    $colorH = $tipoH === 'INSERT' ? '#10b981' : ($tipoH === 'DELETE' ? '#ef4444' : '#f59e0b');
                    $labelH = $tipoH === 'INSERT' ? 'Creación' : ($tipoH === 'DELETE' ? 'Eliminación' : 'Edición');
                    ?>
                    <div class="timeline-item" style="--tl-color:<?php echo $colorH; ?>;">
                        <div class="timeline-meta">
                            <span class="estado-pill" style="background:<?php echo $colorH; ?>1a; color:<?php echo $colorH; ?>;"><?php echo $labelH; ?></span>
                            <strong><?php echo htmlspecialchars($etiquetas[$h['campo_modificado']] ?? ($h['campo_modificado'] ?: 'Registro')); ?></strong>
                            <span>· <?php echo date('d/m/Y H:i', strtotime($h['fecha_modificacion'])); ?></span>
                            <span>· <?php echo htmlspecialchars($h['usuario_nombre'] ?? 'Sistema'); ?></span>
                        </div>
                        <div class="timeline-cambio">
                            <del><?php echo htmlspecialchars((string)($h['valor_anterior'] ?? '—')); ?></del>
                            <i class="fas fa-arrow-right" style="margin:0 .4rem; color:#94a3b8;"></i>
                            <ins><?php echo htmlspecialchars((string)($h['valor_nuevo'] ?? '—')); ?></ins>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
