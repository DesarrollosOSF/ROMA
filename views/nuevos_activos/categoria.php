<?php
$page_title = 'Grupo: ' . ($categoria['nombre'] ?? '');
require_once __DIR__ . '/../layout/header.php';

$qBase = function ($over = []) {
    $p = ['action' => 'nuevos_activos', 'subaction' => 'categoria', 'id' => $_GET['id'] ?? '',
        'estado' => $_GET['estado'] ?? '', 'sede' => $_GET['sede'] ?? '',
        'busqueda' => $_GET['busqueda'] ?? ''];
    return BASE_URL . 'index.php?' . http_build_query(array_merge($p, $over));
};

$estadoColores = [
    'operativo' => '#10b981',
    'en_reparacion' => '#f59e0b',
    'fuera_servicio' => '#ef4444',
    'en_baja' => '#6b7280',
];
?>

<style>
.grupo-hero { background: linear-gradient(120deg, #1e3a8a 0%, #2563eb 60%, #0ea5e9 100%); border-radius: 14px; padding: 1.5rem 1.75rem; color: #fff; display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; margin-bottom: 1.25rem; box-shadow: 0 8px 24px rgba(37,99,235,.25); }
.grupo-hero-icon { width: 64px; height: 64px; border-radius: 16px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; font-size: 1.7rem; flex-shrink: 0; }
.grupo-hero h1 { margin: 0; font-size: 1.5rem; }
.grupo-hero p { margin: .25rem 0 0; color: #dbeafe; font-size: .9rem; }
.grupo-hero-stats { margin-left: auto; display: flex; gap: .6rem; flex-wrap: wrap; }
.grupo-stat { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); border-radius: 10px; padding: .5rem .9rem; text-align: center; }
.grupo-stat strong { display: block; font-size: 1.3rem; }
.grupo-stat small { color: #dbeafe; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
.grupo-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; width: 100%; }
.grupo-hero-actions .btn { border: 1px solid rgba(255,255,255,.35); }
.tabla-activos { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .88rem; }
.tabla-activos thead th { background: #1e293b; color: #fff; text-align: left; padding: .7rem .8rem; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
.tabla-activos thead th:first-child { border-top-left-radius: 10px; }
.tabla-activos thead th:last-child { border-top-right-radius: 10px; }
.tabla-activos tbody td { padding: .7rem .8rem; border-bottom: 1px solid #eef2f7; vertical-align: middle; }
.tabla-activos tbody tr { transition: background .12s; }
.tabla-activos tbody tr:hover { background: #f0f7ff; }
.tabla-activos tbody tr:last-child td { border-bottom: none; }
.codigo-chip { display: inline-block; font-family: Consolas, monospace; font-weight: 700; background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; border-radius: 6px; padding: .15rem .5rem; font-size: .8rem; }
.activo-nombre { font-weight: 600; color: #0f172a; }
.activo-sub { display: block; font-size: .75rem; color: #64748b; font-weight: 400; }
.estado-pill { display: inline-flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 600; background: #f1f5f9; border-radius: 999px; padding: .25rem .7rem; white-space: nowrap; }
.estado-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.valor-destacado { font-weight: 700; color: #047857; white-space: nowrap; }
.adj-badge { display: inline-flex; align-items: center; gap: .3rem; background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: .78rem; border-radius: 999px; padding: .25rem .65rem; text-decoration: none; }
.adj-badge:hover { background: #bae6fd; }
.action-buttons { display: flex; gap: .35rem; }
.btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #f1f5f9; color: #334155; text-decoration: none; transition: all .15s; }
.btn-icon:hover { background: #2563eb; color: #fff; transform: translateY(-1px); }
.btn-icon.btn-danger:hover { background: #dc2626; }
.pagination-wrapper { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; padding: .75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; }
.pagination-info { color: #64748b; font-size: .85rem; }
.pagination { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.pagination-link { min-width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; padding: 0 10px; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; text-decoration: none; font-size: .85rem; font-weight: 500; transition: all .15s; }
a.pagination-link:hover { background: #eff6ff; border-color: #2563eb; color: #1d4ed8; transform: translateY(-1px); box-shadow: 0 2px 6px rgba(37,99,235,.15); }
.pagination-link.active { background: #2563eb; border-color: #2563eb; color: #fff; font-weight: 700; box-shadow: 0 2px 8px rgba(37,99,235,.35); }
.pagination-link.pagination-nav { font-weight: 600; }
.pagination-ellipsis { color: #94a3b8; padding: 0 2px; }
@media (max-width: 640px) {
    .grupo-hero-stats { margin-left: 0; }
    .tabla-activos thead { display: none; }
    .tabla-activos, .tabla-activos tbody, .tabla-activos tr, .tabla-activos td { display: block; width: 100%; }
    .tabla-activos tbody tr { border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: .75rem; padding: .25rem .5rem; }
    .tabla-activos tbody td { border: none; padding: .3rem .5rem; }
}
</style>

<div class="grupo-hero">
    <div class="grupo-hero-icon"><i class="fas fa-<?php echo htmlspecialchars($categoria['icono'] ?? 'box'); ?>"></i></div>
    <div>
        <h1><?php echo htmlspecialchars($categoria['nombre']); ?></h1>
        <?php if (!empty($categoria['descripcion'])): ?>
            <p><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
        <?php endif; ?>
    </div>
    <div class="grupo-hero-stats">
        <div class="grupo-stat"><strong><?php echo number_format($total); ?></strong><small>Activos</small></div>
        <div class="grupo-stat"><strong><?php echo $totalPaginas; ?></strong><small>Páginas</small></div>
    </div>
    <div class="grupo-hero-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panel</a>
        <?php if ($puedeGestionar): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=crear&categoria=<?php echo (int)$categoria['id_categoria']; ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Activo</a>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=carga_masiva" class="btn btn-success"><i class="fas fa-upload"></i> Carga Masiva</a>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=editar_categoria&id=<?php echo (int)$categoria['id_categoria']; ?>" class="btn btn-secondary"><i class="fas fa-edit"></i> Editar Grupo</a>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=eliminar_categoria&id=<?php echo (int)$categoria['id_categoria']; ?>" class="btn btn-secondary" onclick="return confirm('¿Eliminar este grupo? Solo es posible si no tiene activos asociados.')"><i class="fas fa-trash"></i> Eliminar</a>
        <?php endif; ?>
        <?php if ($puedeGenerarFormato ?? false): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=formato" class="btn btn-success"><i class="fas fa-file-pdf"></i> Formato</a>
        <?php endif; ?>
        <?php if ($esAdminGeneral ?? false): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=auditoria" class="btn btn-secondary"><i class="fas fa-shield-alt"></i> Auditoría</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-filter"></i> Filtros</h3></div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="nuevos_activos">
            <input type="hidden" name="subaction" value="categoria">
            <input type="hidden" name="id" value="<?php echo (int)$categoria['id_categoria']; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Búsqueda (código, serial, nombre, responsable)</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $k => $v): ?>
                            <option value="<?php echo $k; ?>" <?php echo (($_GET['estado'] ?? '') === $k) ? 'selected' : ''; ?>><?php echo $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sede</label>
                    <input type="text" name="sede" value="<?php echo htmlspecialchars($_GET['sede'] ?? ''); ?>" placeholder="Filtrar por sede">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=categoria&id=<?php echo (int)$categoria['id_categoria']; ?>" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-list"></i> Listado de Activos</h3></div>
    <div class="card-body">
        <?php if (empty($activos)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No hay activos en este grupo con los filtros actuales</p>
                <?php if ($puedeGestionar): ?>
                    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=crear&categoria=<?php echo (int)$categoria['id_categoria']; ?>" class="btn btn-sm btn-primary">Crear el primero</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="tabla-activos">
                    <thead>
                        <tr>
                            <th>Activo</th>
                            <th>Sede</th>
                            <th>Estado</th>
                            <th>Jefe Inmediato</th>
                            <th>Valor</th>
                            <th style="text-align:center;">Adjuntos</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activos as $a): ?>
                            <?php $colorEst = $estadoColores[$a['estado']] ?? '#64748b'; ?>
                            <tr>
                                <td>
                                    <span class="codigo-chip"><?php echo htmlspecialchars($a['codigo']); ?></span>
                                    <span class="activo-nombre"><?php echo htmlspecialchars($a['nombre']); ?></span>
                                    <span class="activo-sub">Serial: <?php echo htmlspecialchars($a['codigo_serial'] ?: '—'); ?><?php echo !empty($a['codigo_placa']) ? ' · Placa: ' . htmlspecialchars($a['codigo_placa']) : ''; ?></span>
                                </td>
                                <td><i class="fas fa-map-marker-alt" style="color:#94a3b8;"></i> <?php echo htmlspecialchars($a['sede'] ?: '—'); ?></td>
                                <td><span class="estado-pill"><span class="estado-dot" style="background:<?php echo $colorEst; ?>;"></span><?php echo $estados[$a['estado']] ?? $a['estado']; ?></span></td>
                                <td><?php echo htmlspecialchars($a['responsable_general'] ?: '—'); ?></td>
                                <td class="valor-destacado"><?php echo isset($a['valor']) && $a['valor'] !== null && $a['valor'] !== '' ? '$' . number_format((float)$a['valor'], 0, ',', '.') : '—'; ?></td>
                                <td style="text-align:center;">
                                    <?php $nAdj = (int)(($conteoAdjuntos ?? [])[(int)$a['id_nuevo_activo']] ?? 0); ?>
                                    <?php if ($nAdj > 0): ?>
                                        <a class="adj-badge" href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=ver&id=<?php echo (int)$a['id_nuevo_activo']; ?>" title="Ver adjuntos">
                                            <i class="fas fa-paperclip"></i> <?php echo $nAdj; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons" style="justify-content:center;">
                                        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=ver&id=<?php echo (int)$a['id_nuevo_activo']; ?>" class="btn-icon" title="Ver"><i class="fas fa-eye"></i></a>
                                        <?php if ($puedeGestionar): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=editar&id=<?php echo (int)$a['id_nuevo_activo']; ?>" class="btn-icon" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=eliminar&id=<?php echo (int)$a['id_nuevo_activo']; ?>" class="btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este activo?')"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $desde = $total ? (($pagina - 1) * $porPagina + 1) : 0;
            $hasta = min($pagina * $porPagina, $total);
            $ventana = [];
            for ($p = 1; $p <= $totalPaginas; $p++) {
                if ($p === 1 || $p === $totalPaginas || abs($p - $pagina) <= 2) $ventana[] = $p;
            }
            ?>
            <div class="pagination-wrapper">
                <div class="pagination-info">Mostrando <?php echo number_format($desde); ?>–<?php echo number_format($hasta); ?> de <?php echo number_format($total); ?> registros · Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></div>
                <?php if ($totalPaginas > 1): ?>
                    <nav class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a class="pagination-link pagination-nav" href="<?php echo $qBase(['pagina' => 1]); ?>" title="Primera página"><i class="fas fa-angle-double-left"></i></a>
                            <a class="pagination-link pagination-nav" href="<?php echo $qBase(['pagina' => $pagina - 1]); ?>" title="Anterior"><i class="fas fa-angle-left"></i> Anterior</a>
                        <?php endif; ?>
                        <?php $ant = 0; ?>
                        <?php foreach ($ventana as $p): ?>
                            <?php if ($p - $ant > 1): ?><span class="pagination-ellipsis">…</span><?php endif; ?>
                            <?php if ($p === $pagina): ?>
                                <span class="pagination-link active"><?php echo $p; ?></span>
                            <?php else: ?>
                                <a class="pagination-link" href="<?php echo $qBase(['pagina' => $p]); ?>"><?php echo $p; ?></a>
                            <?php endif; ?>
                            <?php $ant = $p; ?>
                        <?php endforeach; ?>
                        <?php if ($pagina < $totalPaginas): ?>
                            <a class="pagination-link pagination-nav" href="<?php echo $qBase(['pagina' => $pagina + 1]); ?>" title="Siguiente">Siguiente <i class="fas fa-angle-right"></i></a>
                            <a class="pagination-link pagination-nav" href="<?php echo $qBase(['pagina' => $totalPaginas]); ?>" title="Última página"><i class="fas fa-angle-double-right"></i></a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
