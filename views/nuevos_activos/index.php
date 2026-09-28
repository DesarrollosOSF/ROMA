<?php
$page_title = 'Nuevos Activos - Panel General';
require_once __DIR__ . '/../layout/header.php';

$labelsCat = [];
$dataCat = [];
foreach (($reporte['porCategoria'] ?? []) as $c) {
    $labelsCat[] = $c['nombre'];
    $dataCat[] = (int)$c['total'];
}
$labelsEst = [];
$dataEst = [];
foreach (($reporte['porEstado'] ?? []) as $e) {
    $labelsEst[] = $estados[$e['estado']] ?? $e['estado'];
    $dataEst[] = (int)$e['total'];
}
$labelsSede = [];
$dataSede = [];
foreach (($reporte['porSede'] ?? []) as $s) {
    $labelsSede[] = $s['sede'];
    $dataSede[] = (int)$s['total'];
}
?>

<div class="page-header">
    <h1><i class="fas fa-boxes"></i> Nuevos Activos</h1>
    <div class="header-actions">
        <?php if ($puedeGestionar): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=crear_categoria" class="btn btn-secondary">
                <i class="fas fa-folder-plus"></i> Agregar Grupo
            </a>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Activo
            </a>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=carga_masiva" class="btn btn-success">
                <i class="fas fa-upload"></i> Carga Masiva
            </a>
        <?php endif; ?>
        <?php if ($puedeGenerarFormato ?? false): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=formato" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Formato Inventario / Asignación
            </a>
        <?php endif; ?>
        <?php if ($esAdminGeneral ?? false): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=auditoria" class="btn btn-secondary">
                <i class="fas fa-shield-alt"></i> Auditoría
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Reporte general del inventario por grupo de activos. Pulse una tarjeta para ver el listado de ese grupo.
    Los grupos se pueden crear con el botón <strong>Agregar Grupo</strong>.
</div>

<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-boxes"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($reporte['total'] ?? 0); ?></h3>
            <p>Total Activos</p>
        </div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-folder"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format(count($reporte['porCategoria'] ?? [])); ?></h3>
            <p>Grupos de activos</p>
        </div>
    </div>
    <?php foreach (($reporte['porEstado'] ?? []) as $e): ?>
        <div class="stat-card stat-info">
            <div class="stat-content">
                <h3><?php echo number_format($e['total']); ?></h3>
                <p><?php echo htmlspecialchars($estados[$e['estado']] ?? $e['estado']); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-th-large"></i> Activos por Grupo</h3>
    </div>
    <div class="card-body">
        <?php if (empty($reporte['porCategoria'])): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No hay grupos registrados</p>
                <?php if ($puedeGestionar): ?>
                    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=crear_categoria" class="btn btn-sm btn-primary">Crear el primer grupo</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="category-cards">
                <?php foreach ($reporte['porCategoria'] as $c): ?>
                    <div class="category-card stat-<?php echo htmlspecialchars($c['color'] ?? 'primary'); ?>">
                        <a class="category-main" href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=categoria&id=<?php echo (int)$c['id_categoria']; ?>">
                            <div class="category-icon"><i class="fas fa-<?php echo htmlspecialchars($c['icono'] ?? 'box'); ?>"></i></div>
                            <div class="category-info">
                                <h3><?php echo number_format($c['total']); ?></h3>
                                <p><?php echo htmlspecialchars($c['nombre']); ?></p>
                            </div>
                        </a>
                        <?php if ($puedeGestionar): ?>
                            <div class="category-actions">
                                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=editar_categoria&id=<?php echo (int)$c['id_categoria']; ?>"
                                   class="btn-icon" title="Editar grupo"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=eliminar_categoria&id=<?php echo (int)$c['id_categoria']; ?>"
                                   class="btn-icon btn-danger" title="Eliminar grupo"
                                   onclick="return confirm('¿Eliminar el grupo «<?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>»? Solo es posible si no tiene activos asociados.')"><i class="fas fa-trash"></i></a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-pie"></i> Distribución por Grupo</h3></div>
        <div class="card-body">
            <?php if (empty($dataCat)): ?>
                <div class="empty-state"><i class="fas fa-chart-pie"></i><p>Sin datos</p></div>
            <?php else: ?>
                <canvas id="chartNuevosCategoria" height="260"></canvas>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-bar"></i> Por Estado</h3></div>
        <div class="card-body">
            <?php if (empty($dataEst)): ?>
                <div class="empty-state"><i class="fas fa-chart-bar"></i><p>Sin datos</p></div>
            <?php else: ?>
                <canvas id="chartNuevosEstado" height="260"></canvas>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-map-marker-alt"></i> Top Sedes</h3></div>
        <div class="card-body">
            <?php if (empty($dataSede)): ?>
                <div class="empty-state"><i class="fas fa-map-marker-alt"></i><p>Sin datos</p></div>
            <?php else: ?>
                <canvas id="chartNuevosSede" height="260"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.category-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
.category-card { display: block; padding: 1.25rem; border-radius: 10px; background: #fff; border: 1px solid #e5e7eb; text-decoration: none; color: inherit; border-top: 4px solid #3b82f6; transition: transform .15s, box-shadow .15s; }
.category-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
.category-card .category-icon { font-size: 1.8rem; color: #3b82f6; }
.category-card.stat-success { border-top-color: #10b981; } .category-card.stat-success .category-icon { color: #10b981; }
.category-card.stat-warning { border-top-color: #f59e0b; } .category-card.stat-warning .category-icon { color: #f59e0b; }
.category-card.stat-danger { border-top-color: #ef4444; } .category-card.stat-danger .category-icon { color: #ef4444; }
.category-card.stat-info { border-top-color: #0ea5e9; } .category-card.stat-info .category-icon { color: #0ea5e9; }
.category-card.stat-secondary { border-top-color: #6b7280; } .category-card.stat-secondary .category-icon { color: #6b7280; }
.category-card h3 { margin: .5rem 0 0; font-size: 1.8rem; }
.category-card p { margin: 0; font-weight: 600; }
.category-link { display: inline-block; margin-top: .5rem; font-size: .85rem; color: #3b82f6; }
.category-main { display: block; text-decoration: none; color: inherit; }
.category-actions { display: flex; gap: .4rem; margin-top: .75rem; padding-top: .75rem; border-top: 1px solid #e5e7eb; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const c1 = document.getElementById('chartNuevosCategoria');
    if (c1) new Chart(c1, { type: 'doughnut',
        data: { labels: <?php echo json_encode($labelsCat); ?>, datasets: [{ data: <?php echo json_encode($dataCat); ?>,
            backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#0ea5e9','#6b7280','#ec4899'] }] },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
    const c2 = document.getElementById('chartNuevosEstado');
    if (c2) new Chart(c2, { type: 'bar',
        data: { labels: <?php echo json_encode($labelsEst); ?>, datasets: [{ data: <?php echo json_encode($dataEst); ?>,
            backgroundColor: ['#10b981','#f59e0b','#ef4444','#6b7280'] }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } } });
    const c3 = document.getElementById('chartNuevosSede');
    if (c3) new Chart(c3, { type: 'bar',
        data: { labels: <?php echo json_encode($labelsSede); ?>, datasets: [{ data: <?php echo json_encode($dataSede); ?>, backgroundColor: '#3b82f6' }] },
        options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } } });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
