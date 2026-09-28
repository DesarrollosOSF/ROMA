<?php
$page_title = $page_title ?? 'Informe de Órdenes por Creador';
require_once __DIR__ . '/../layout/header.php';

$informe = $informe ?? [];
$estados = $estados ?? [];
$fecha_desde_val = $_GET['fecha_desde'] ?? '';
$fecha_hasta_val = $_GET['fecha_hasta'] ?? '';
?>

<div class="page-header">
    <h1><i class="fas fa-file-alt"></i> Informe de Órdenes por Creador</h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=crear" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Orden
        </a>
    </div>
</div>

<p class="text-muted mb-3">
    Resumen de órdenes de trabajo creadas por cada usuario y su distribución por estado. Use el botón <strong>Ver órdenes</strong> para listar solo las creadas por ese usuario.
</p>

<!-- Filtro por fechas -->
<form method="GET" action="" class="card mb-3">
    <div class="card-body">
        <input type="hidden" name="action" value="ordenes">
        <input type="hidden" name="subaction" value="informe">
        <div class="form-row" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <div class="form-group">
                <label for="fecha_desde">Desde (fecha creación)</label>
                <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($fecha_desde_val); ?>">
            </div>
            <div class="form-group">
                <label for="fecha_hasta">Hasta (fecha creación)</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($fecha_hasta_val); ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=informe" class="btn btn-secondary">Limpiar</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> Órdenes creadas por usuario</h3>
    </div>
    <div class="card-body">
        <?php if (empty($informe)): ?>
            <p class="text-muted">No hay órdenes registradas en el período seleccionado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table data-table">
                    <thead>
                        <tr>
                            <th>Usuario (creador)</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th><?php echo $estados['recibido'] ?? 'Recibido'; ?></th>
                            <th><?php echo $estados['en_proceso'] ?? 'En Proceso'; ?></th>
                            <th><?php echo $estados['rechazado'] ?? 'Rechazado'; ?></th>
                            <th><?php echo $estados['finalizado'] ?? 'Finalizado'; ?></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($informe as $fila): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($fila['nombre']); ?></strong>
                                </td>
                                <td>
                                    <span class="text-muted"><?php echo htmlspecialchars($fila['email'] ?? ''); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo (int)$fila['total']; ?></span>
                                </td>
                                <td><?php echo (int)$fila['recibido']; ?></td>
                                <td><?php echo (int)$fila['en_proceso']; ?></td>
                                <td><?php echo (int)$fila['rechazado']; ?></td>
                                <td><?php echo (int)$fila['finalizado']; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&creado_por=<?php echo (int)$fila['id_usuario']; ?>" 
                                       class="btn btn-sm btn-primary" title="Ver órdenes creadas por este usuario">
                                        <i class="fas fa-list"></i> Ver órdenes
                                    </a>
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
