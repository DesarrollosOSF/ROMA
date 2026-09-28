<?php
$page_title = 'Auditoría de Activos';
require_once __DIR__ . '/../layout/header.php';

function badgeOperacion($tipo) {
    $tipo = strtoupper($tipo ?? '');
    $map = ['INSERT' => 'success', 'UPDATE' => 'warning', 'DELETE' => 'danger'];
    $label = ['INSERT' => 'Creación', 'UPDATE' => 'Edición', 'DELETE' => 'Eliminación'][$tipo] ?? $tipo;
    $color = $map[$tipo] ?? 'secondary';
    return '<span class="badge badge-' . $color . '">' . htmlspecialchars($label) . '</span>';
}

function etiquetaCampo($campo, $map) {
    if (empty($campo) || $campo === 'registro') return '<span class="text-muted">Registro</span>';
    $label = $map[$campo] ?? $campo;
    return htmlspecialchars($label);
}

function recortar($v, $max = 120) {
    $v = (string)($v ?? '');
    if ($v === '') return '<span class="text-muted">—</span>';
    $corto = mb_strlen($v) > $max ? mb_substr($v, 0, $max) . '…' : $v;
    return '<span title="' . htmlspecialchars($v) . '">' . htmlspecialchars($corto) . '</span>';
}

$queryBase = function ($sobrescribir = []) {
    $p = [
        'action' => 'activos',
        'subaction' => 'auditoria',
        'tipo' => $_GET['tipo'] ?? '',
        'usuario' => $_GET['usuario'] ?? '',
        'busqueda' => $_GET['busqueda'] ?? '',
        'desde' => $_GET['desde'] ?? '',
        'hasta' => $_GET['hasta'] ?? '',
        'id_activo' => $_GET['id_activo'] ?? '',
    ];
    $p = array_merge($p, $sobrescribir);
    return BASE_URL . 'index.php?' . http_build_query($p);
};
?>

<div class="page-header">
    <h1><i class="fas fa-shield-alt"></i> Auditoría de Activos</h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Activos
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Cada creación, edición (campo por campo) y eliminación de activos queda registrada con
    <strong>quién</strong> lo hizo y <strong>fecha/hora</strong>. Sección visible solo para administradores.
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-filter"></i> Filtros</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>index.php" class="filters-form">
            <input type="hidden" name="action" value="activos">
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
                            <option value="<?php echo $u['id_usuario']; ?>" <?php echo ((string)($_GET['usuario'] ?? '') === (string)$u['id_usuario']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nombre'] . ' (' . $u['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Búsqueda (activo, código, campo, usuario)</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($_GET['busqueda'] ?? ''); ?>" placeholder="Ej: VEH-001, frenos, nombre...">
                </div>
                <div class="form-group">
                    <label>Desde</label>
                    <input type="date" name="desde" value="<?php echo htmlspecialchars($_GET['desde'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Hasta</label>
                    <input type="date" name="hasta" value="<?php echo htmlspecialchars($_GET['hasta'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=auditoria" class="btn btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-history"></i> Movimientos (<?php echo number_format($total ?? 0); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($registros)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No hay movimientos de auditoría con los filtros seleccionados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Usuario</th>
                            <th>Activo</th>
                            <th>Operación</th>
                            <th>Campo</th>
                            <th>Antes → Después</th>
                            <th>Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                            <tr>
                                <td style="white-space:nowrap;">
                                    <?php echo !empty($r['fecha_modificacion']) ? date('d/m/Y H:i', strtotime($r['fecha_modificacion'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['usuario_nombre'] ?? 'Sistema'); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($r['usuario_email'] ?? ''); ?></small>
                                    <?php if (!empty($r['usuario_rol'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($r['usuario_rol']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['nombre_activo'])): ?>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo (int)$r['id_activo']; ?>">
                                            <strong><?php echo htmlspecialchars($r['nombre_activo']); ?></strong>
                                        </a><br>
                                        <small class="text-muted">#<?php echo (int)$r['id_activo']; ?> · <?php echo htmlspecialchars($r['codigo_interno'] ?: ($r['codigo_patrimonial'] ?? '')); ?></small>
                                        <?php if (isset($r['activo_vigente']) && (int)$r['activo_vigente'] === 0): ?>
                                            <br><span class="badge badge-danger">Eliminado</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Activo #<?php echo (int)$r['id_activo']; ?> (no disponible)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo badgeOperacion($r['tipo_operacion']); ?></td>
                                <td><?php echo etiquetaCampo($r['campo_modificado'] ?? '', $etiquetasCampos ?? []); ?></td>
                                <td style="max-width:340px;">
                                    <div><span class="text-muted">Antes:</span> <?php echo recortar($r['valor_anterior'] ?? ''); ?></div>
                                    <div><span class="text-muted">Después:</span> <?php echo recortar($r['valor_nuevo'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-category"><?php echo htmlspecialchars($r['origen'] ?? 'web'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (($totalPaginas ?? 1) > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> · <?php echo number_format($total); ?> registros</div>
                    <nav class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a class="pagination-link" href="<?php echo $queryBase(['pagina' => $pagina - 1]); ?>"><i class="fas fa-chevron-left"></i> Anterior</a>
                        <?php endif; ?>
                        <span class="pagination-link active"><?php echo $pagina; ?></span>
                        <?php if ($pagina < $totalPaginas): ?>
                            <a class="pagination-link" href="<?php echo $queryBase(['pagina' => $pagina + 1]); ?>">Siguiente <i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
