<?php
$page_title = 'Responsables de Activos';
require_once __DIR__ . '/../layout/header.php';

$qBase = function ($over = []) {
    $p = ['action' => 'responsables', 'busqueda' => $_GET['busqueda'] ?? ''];
    return BASE_URL . 'index.php?' . http_build_query(array_merge($p, $over));
};
?>

<div class="page-header">
    <h1><i class="fas fa-id-card"></i> Responsables de Activos</h1>
    <div class="header-actions">
        <?php if ($puedeGestionar): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Responsable
            </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=formato" class="btn btn-success">
            <i class="fas fa-file-pdf"></i> Formato Inventario
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Trabajadores de la organización registrados para asociarles activos y autocompletar el formato de inventario.
    Solo el super administrador puede crearlos, editarlos o eliminarlos.
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-filter"></i> Filtros (<?php echo number_format($total); ?>)</h3></div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="responsables">
            <div class="form-grid">
                <div class="form-group">
                    <label>Búsqueda (nombre, documento, cargo, dependencia)</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=responsables" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-list"></i> Listado</h3></div>
    <div class="card-body">
        <?php if (empty($trabajadores)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No hay responsables registrados</p>
                <?php if ($puedeGestionar): ?>
                    <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=crear" class="btn btn-sm btn-primary">Crear el primero</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Documento</th>
                            <th>Cargo</th>
                            <th>Dependencia</th>
                            <th>Activos</th>
                            <th>Formato</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trabajadores as $t): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($t['nombre_completo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($t['documento_identidad']); ?></td>
                                <td><?php echo htmlspecialchars($t['cargo'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($t['dependencia'] ?: '—'); ?></td>
                                <td style="text-align:center;">
                                    <?php if ((int)$t['total_activos'] > 0): ?>
                                        <span class="badge badge-info"><i class="fas fa-box"></i> <?php echo (int)$t['total_activos']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if (!empty($t['formato_ruta'])): ?>
                                        <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($t['formato_ruta']); ?>" target="_blank" title="<?php echo htmlspecialchars($t['formato_nombre'] ?? 'Ver formato'); ?>">
                                            <span class="badge badge-success"><i class="fas fa-file-pdf"></i> Sí</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=ver&id=<?php echo (int)$t['id_trabajador']; ?>" class="btn-icon" title="Ver"><i class="fas fa-eye"></i></a>
                                        <?php if ($puedeGestionar): ?>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=editar&id=<?php echo (int)$t['id_trabajador']; ?>" class="btn-icon" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="<?php echo BASE_URL; ?>index.php?action=responsables&subaction=eliminar&id=<?php echo (int)$t['id_trabajador']; ?>" class="btn-icon btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar este responsable? Sus activos quedarán sin asociar.')"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></div>
                    <nav class="pagination">
                        <?php if ($pagina > 1): ?><a class="pagination-link" href="<?php echo $qBase(['pagina' => $pagina - 1]); ?>">Anterior</a><?php endif; ?>
                        <span class="pagination-link active"><?php echo $pagina; ?></span>
                        <?php if ($pagina < $totalPaginas): ?><a class="pagination-link" href="<?php echo $qBase(['pagina' => $pagina + 1]); ?>">Siguiente</a><?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
