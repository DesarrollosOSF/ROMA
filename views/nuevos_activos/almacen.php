<?php
$page_title = 'Almacén - Bodegas';
require_once __DIR__ . '/../layout/header.php';

$qBase = function ($over = []) {
    $p = ['action' => 'nuevos_activos', 'subaction' => 'almacen',
        'almacen' => $_GET['almacen'] ?? '', 'busqueda' => $_GET['busqueda'] ?? ''];
    return BASE_URL . 'index.php?' . http_build_query(array_merge($p, $over));
};
?>

<style>
.alm-hero { background: linear-gradient(120deg, #78350f 0%, #b45309 55%, #f59e0b 100%); border-radius: 14px; padding: 1.5rem 1.75rem; color: #fff; display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; margin-bottom: 1.25rem; box-shadow: 0 8px 24px rgba(180,83,9,.28); }
.alm-hero-icon { width: 64px; height: 64px; border-radius: 16px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; font-size: 1.7rem; flex-shrink: 0; }
.alm-hero h1 { margin: 0; font-size: 1.5rem; }
.alm-hero p { margin: .25rem 0 0; color: #fef3c7; font-size: .9rem; }
.alm-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; width: 100%; }
.alm-hero-actions .btn { border: 1px solid rgba(255,255,255,.35); }
.bodega-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
.bodega-card { display: block; background: #fff; border: 1px solid #e5e7eb; border-top: 4px solid #f59e0b; border-radius: 10px; padding: 1.1rem 1.25rem; text-decoration: none; color: inherit; transition: transform .15s, box-shadow .15s; position: relative; }
.bodega-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
.bodega-card.activa { border-color: #f59e0b; box-shadow: 0 0 0 2px #f59e0b55; }
.bodega-card h3 { margin: .4rem 0 0; font-size: 1.6rem; }
.bodega-card p { margin: 0; font-weight: 600; }
.bodega-icon { font-size: 1.6rem; color: #b45309; }
.bodega-link { display: inline-block; margin-top: .4rem; font-size: .82rem; color: #b45309; font-weight: 600; }
.bodega-acciones { display: flex; gap: .4rem; margin-top: .7rem; padding-top: .7rem; border-top: 1px solid #e5e7eb; }
</style>

<div class="alm-hero">
    <div class="alm-hero-icon"><i class="fas fa-warehouse"></i></div>
    <div>
        <h1>Almacén</h1>
        <p>Bodegas de la empresa y activos con estado <strong>En almacén</strong> (<?php echo number_format($total); ?>).</p>
    </div>
    <div class="alm-hero-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panel</a>
        <?php if ($puedeGestionar): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=crear_almacen" class="btn btn-secondary"><i class="fas fa-plus"></i> Agregar Bodega</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($listaAlmacenes)): ?>
    <div class="bodega-cards">
        <?php foreach ($listaAlmacenes as $b): ?>
            <div class="bodega-card <?php echo ((int)($_GET['almacen'] ?? 0) === (int)$b['id_almacen']) ? 'activa' : ''; ?>">
                <a href="<?php echo $qBase(['almacen' => (int)$b['id_almacen']]); ?>" style="text-decoration:none; color:inherit;">
                    <div class="bodega-icon"><i class="fas fa-warehouse"></i></div>
                    <h3><?php echo number_format($b['total_activos']); ?></h3>
                    <p><?php echo htmlspecialchars($b['nombre']); ?></p>
                    <span class="bodega-link">Ver activos <i class="fas fa-arrow-right"></i></span>
                </a>
                <?php if ($puedeGestionar): ?>
                    <div class="bodega-acciones">
                        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=editar_almacen&id=<?php echo (int)$b['id_almacen']; ?>" class="btn-icon" title="Editar bodega"><i class="fas fa-edit"></i></a>
                        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=eliminar_almacen&id=<?php echo (int)$b['id_almacen']; ?>" class="btn-icon btn-danger" title="Eliminar bodega" onclick="return confirm('¿Eliminar esta bodega? Solo es posible si no tiene activos.')"><i class="fas fa-trash"></i></a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="bodega-card">
            <a href="<?php echo $qBase(['almacen' => '']); ?>" style="text-decoration:none; color:inherit;">
                <div class="bodega-icon"><i class="fas fa-boxes"></i></div>
                <h3><?php echo number_format($total); ?></h3>
                <p>Todas las bodegas</p>
                <span class="bodega-link">Ver todo <i class="fas fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
<?php elseif ($puedeGestionar): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay bodegas registradas. <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=crear_almacen">Agregue la primera</a> (ej: San Rafael, COL).</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-filter"></i> Filtros</h3></div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="nuevos_activos">
            <input type="hidden" name="subaction" value="almacen">
            <div class="form-grid">
                <div class="form-group">
                    <label>Bodega</label>
                    <select name="almacen">
                        <option value="">Todas</option>
                        <?php foreach (($listaAlmacenes ?? []) as $b): ?>
                            <option value="<?php echo (int)$b['id_almacen']; ?>" <?php echo ((string)($_GET['almacen'] ?? '') === (string)$b['id_almacen']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Búsqueda (código, serial, placa, nombre)</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=almacen" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-list"></i> Activos en Almacén (<?php echo number_format(count($activos)); ?>)</h3></div>
    <div class="card-body">
        <?php if (empty($activos)): ?>
            <div class="empty-state"><i class="fas fa-warehouse"></i><p>No hay activos en almacén con los filtros actuales</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Bodega</th><th>Sede</th><th>Trabajador</th><th>Valor</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($activos as $a): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($a['codigo']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($a['codigo_placa'] ?? '' ?: ($a['codigo_serial'] ?? '')); ?></small></td>
                                <td><?php echo htmlspecialchars($a['nombre']); ?></td>
                                <td><span class="badge badge-warning"><i class="fas fa-warehouse"></i> <?php echo htmlspecialchars($a['almacen_nombre'] ?? '—'); ?></span></td>
                                <td><?php echo htmlspecialchars($a['sede'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($a['trabajador_nombre'] ?? '' ?: '—'); ?></td>
                                <td style="white-space:nowrap;"><?php echo isset($a['valor']) && $a['valor'] !== null && $a['valor'] !== '' ? '$' . number_format((float)$a['valor'], 0, ',', '.') : '—'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=ver&id=<?php echo (int)$a['id_nuevo_activo']; ?>" class="btn-icon" title="Ver"><i class="fas fa-eye"></i></a>
                                        <?php if ($puedeGestionar): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=retirar_almacen&id=<?php echo (int)$a['id_nuevo_activo']; ?>" class="btn-icon" title="Retirar del almacén (vuelve a operativo)" onclick="return confirm('¿Retirar este activo del almacén? Volverá a estado operativo.')"><i class="fas fa-sign-out-alt"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
