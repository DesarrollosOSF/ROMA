<?php
$page_title = 'Auditoría - Nuevos Activos';
require_once __DIR__ . '/../layout/header.php';
$qBase = function ($over = []) {
    $p = ['action' => 'nuevos_activos', 'subaction' => 'auditoria',
        'tipo' => $_GET['tipo'] ?? '', 'usuario' => $_GET['usuario'] ?? '',
        'busqueda' => $_GET['busqueda'] ?? '', 'desde' => $_GET['desde'] ?? '', 'hasta' => $_GET['hasta'] ?? ''];
    return BASE_URL . 'index.php?' . http_build_query(array_merge($p, $over));
};
?>

<div class="page-header">
    <h1><i class="fas fa-shield-alt"></i> Auditoría de Nuevos Activos</h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panel</a>
</div>

<div class="alert alert-info"><i class="fas fa-info-circle"></i> Quién creó, editó o eliminó cada activo y cuándo, con detalle por campo. Solo administradores.</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-filter"></i> Filtros</h3></div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="nuevos_activos">
            <input type="hidden" name="subaction" value="auditoria">
            <div class="form-grid">
                <div class="form-group">
                    <label>Operación</label>
                    <select name="tipo">
                        <option value="">Todas</option>
                        <option value="INSERT" <?php echo (($_GET['tipo'] ?? '') === 'INSERT') ? 'selected' : ''; ?>>Creación</option>
                        <option value="UPDATE" <?php echo (($_GET['tipo'] ?? '') === 'UPDATE') ? 'selected' : ''; ?>>Edición</option>
                        <option value="DELETE" <?php echo (($_GET['tipo'] ?? '') === 'DELETE') ? 'selected' : ''; ?>>Eliminación</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Usuario</label>
                    <select name="usuario">
                        <option value="">Todos</option>
                        <?php foreach (($usuariosLista ?? []) as $u): ?>
                            <option value="<?php echo $u['id_usuario']; ?>" <?php echo ((string)($_GET['usuario'] ?? '') === (string)$u['id_usuario']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nombre'] . ' (' . $u['email'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Búsqueda</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" placeholder="Código, nombre, usuario, campo">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=auditoria" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Movimientos (<?php echo number_format($total ?? 0); ?>)</h3></div>
    <div class="card-body">
        <?php if (empty($registros)): ?>
            <div class="empty-state"><i class="fas fa-inbox"></i><p>Sin movimientos</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Fecha/Hora</th><th>Usuario</th><th>Activo</th><th>Operación</th><th>Campo</th><th>Antes → Después</th><th>Origen</th></tr></thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td style="white-space:nowrap;"><?php echo date('d/m/Y H:i', strtotime($r['fecha_modificacion'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($r['usuario_nombre'] ?? 'Sistema'); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($r['usuario_email'] ?? ''); ?></small></td>
                                <td>
                                    <?php if (!empty($r['activo_nombre'])): ?>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=ver&id=<?php echo (int)$r['id_nuevo_activo']; ?>"><strong><?php echo htmlspecialchars($r['activo_nombre']); ?></strong></a><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($r['activo_codigo'] ?? ''); ?> · <?php echo htmlspecialchars($r['categoria_nombre'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Activo #<?php echo (int)$r['id_nuevo_activo']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?php echo $r['tipo_operacion'] === 'INSERT' ? 'success' : ($r['tipo_operacion'] === 'DELETE' ? 'danger' : 'warning'); ?>"><?php echo $r['tipo_operacion'] === 'INSERT' ? 'Creación' : ($r['tipo_operacion'] === 'DELETE' ? 'Eliminación' : 'Edición'); ?></span></td>
                                <td><?php echo htmlspecialchars($etiquetas[$r['campo_modificado']] ?? ($r['campo_modificado'] ?: 'Registro')); ?></td>
                                <td style="max-width:320px;"><div>Antes: <?php echo htmlspecialchars((string)($r['valor_anterior'] ?? '—')); ?></div><div>Después: <?php echo htmlspecialchars((string)($r['valor_nuevo'] ?? '—')); ?></div></td>
                                <td><span class="badge badge-category"><?php echo htmlspecialchars($r['origen'] ?? 'web'); ?></span></td>
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
