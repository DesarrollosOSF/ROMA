<?php
$page_title = 'Dashboard - Sistema ROMA';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';

$rol_actual = $_SESSION['usuario_rol'] ?? '';
$puede_ver_activos = $puede_ver_activos ?? (Usuario::tienePermiso($rol_actual, 'ver_activos') || Usuario::tienePermiso($rol_actual, 'ver_lista_activos'));
$puede_ver_ordenes = $puede_ver_ordenes ?? (Usuario::tienePermiso($rol_actual, 'ver_ordenes') || Usuario::tienePermiso($rol_actual, 'ver_mis_ordenes'));
$es_admin = Usuario::esAdminGeneral($rol_actual);
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <div class="header-actions">
        <?php if (isset($_SESSION['usuario_rol']) && Usuario::esAdminGeneral($_SESSION['usuario_rol'])): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=usuarios&subaction=crear" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </a>
        <?php endif; ?>
        <?php if ($puede_ver_activos && Usuario::tienePermiso($_SESSION['usuario_rol'] ?? '', 'crear_activos')): ?>
            <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=crear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Activo
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="welcome-message">
    <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></strong> 
    (<?php echo USER_ROLES[$_SESSION['usuario_rol']] ?? $_SESSION['usuario_rol']; ?>)</p>
</div>

<!-- Tarjetas de Estadísticas -->
<div class="stats-grid">
    <?php if ($puede_ver_activos): ?>
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($estadisticas['total_activos']); ?></h3>
            <p>Total de Activos</p>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($estadisticas['activos_operativos']); ?></h3>
            <p>Activos Operativos</p>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($estadisticas['activos_reparacion']); ?></h3>
            <p>En Reparación</p>
        </div>
    </div>

    <div class="stat-card stat-danger">
        <div class="stat-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($estadisticas['activos_vencidos']); ?></h3>
            <p>Mantenimientos Vencidos</p>
        </div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($estadisticas['mantenimientos_proximos']); ?></h3>
            <p>Mantenimientos Próximos (30 días)</p>
        </div>
    </div>

    <div class="stat-card stat-secondary">
        <div class="stat-icon">
            <i class="fas fa-history"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($estadisticas['total_mantenimientos']); ?></h3>
            <p>Total Mantenimientos</p>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($estadisticas['activos_sin_plan']); ?></h3>
            <p>Activos Sin Plan</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card insights-card">
    <div class="card-header">
        <h3><i class="fas fa-lightbulb"></i> Insights Clave</h3>
    </div>
    <div class="card-body">
    <ul class="insights-list">
        <?php if ($estadisticas['activos_vencidos'] > 0): ?>
            <li>
                <span class="insight-badge badge-danger"><?php echo $estadisticas['activos_vencidos']; ?></span>
                activos tienen mantenimiento vencido. Priorizar programación inmediata.
            </li>
        <?php endif; ?>
        <?php if ($estadisticas['activos_sin_plan'] > 0): ?>
            <li>
                <span class="insight-badge badge-warning"><?php echo $estadisticas['activos_sin_plan']; ?></span>
                activos aún no cuentan con plan de mantenimiento definido.
            </li>
        <?php endif; ?>
        <?php if ($estadisticas['ordenes_criticas'] > 0): ?>
            <li>
                <span class="insight-badge badge-danger"><?php echo $estadisticas['ordenes_criticas']; ?></span>
                órdenes críticas/altas siguen abiertas; revise asignación y recursos.
            </li>
        <?php endif; ?>
        <?php if ($estadisticas['ordenes_urgentes'] > 0): ?>
            <li>
                <span class="insight-badge badge-warning"><?php echo $estadisticas['ordenes_urgentes']; ?></span>
                órdenes vencen en los próximos 3 días.
            </li>
        <?php endif; ?>
        <?php if ($estadisticas['cierre_promedio'] !== null): ?>
            <li>
                El cierre promedio de órdenes finalizadas en los últimos 30 días es de
                <strong><?php echo round($estadisticas['cierre_promedio'], 1); ?> horas</strong>.
            </li>
        <?php endif; ?>
        <?php if (
            $estadisticas['activos_vencidos'] == 0 &&
            $estadisticas['activos_sin_plan'] == 0 &&
            $estadisticas['ordenes_criticas'] == 0 &&
            $estadisticas['ordenes_urgentes'] == 0 &&
            $estadisticas['cierre_promedio'] !== null
        ): ?>
            <li>
                Operación bajo control. Continúe monitoreando el desempeño semanalmente.
            </li>
        <?php endif; ?>
    </ul>
    </div>
</div>

<!-- Gráficos y Tablas -->
<div class="dashboard-grid">
    <?php if ($puede_ver_activos): ?>
    <!-- Activos Recientes -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Activos Recientes</h3>
            <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-sm btn-primary">
                Ver Todos
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($activos_recientes)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No hay activos recientes para mostrar</p>
                    <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=crear" class="btn btn-sm btn-outline-primary">
                        Registrar nuevo activo
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Fecha Alta</th>
                                <th>Próx. Mant.</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activos_recientes as $activo): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($activo['codigo_interno'] ?: 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($activo['nombre_activo']); ?></td>
                                    <td>
                                        <span class="badge badge-category">
                                            <?php echo ASSET_CATEGORIES[$activo['categoria']] ?? $activo['categoria']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $activo['estado_actual']; ?>">
                                            <?php echo ASSET_STATES[$activo['estado_actual']] ?? $activo['estado_actual']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($activo['fecha_creacion']) ? date('d/m/Y', strtotime($activo['fecha_creacion'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if (!empty($activo['proximo_mantenimiento'])): ?>
                                            <?php 
                                                $fechaProx = strtotime($activo['proximo_mantenimiento']);
                                                $isSoon = $fechaProx >= time() && $fechaProx <= strtotime('+7 days');
                                                $isPast = $fechaProx < time();
                                            ?>
                                            <span class="<?php echo $isPast ? 'text-danger' : ($isSoon ? 'text-warning' : 'text-muted'); ?>">
                                                <?php echo date('d/m/Y', $fechaProx); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-danger">Sin plan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo $activo['id_activo']; ?>" 
                                           class="btn-icon" title="Ver">
                                            <i class="fas fa-eye"></i>
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

    <!-- Mantenimientos Próximos -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-alt"></i> Mantenimientos Próximos (30 días)</h3>
            <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-sm btn-primary">
                Ver Todos
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($mantenimientos_proximos)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No hay mantenimientos programados en los próximos 30 días</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Activo</th>
                                <th>Fecha Programada</th>
                                <th>Días Restantes</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mantenimientos_proximos as $mantenimiento): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($mantenimiento['codigo_interno'] ?: 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($mantenimiento['nombre_activo']); ?></td>
                                    <td>
                                        <?php 
                                        $fecha = strtotime($mantenimiento['proximo_mantenimiento']);
                                        echo date('d/m/Y', $fecha);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $fecha = strtotime($mantenimiento['proximo_mantenimiento']);
                                        $dias = floor(($fecha - time()) / 86400);
                                        if ($dias < 0) {
                                            echo '<span class="badge badge-danger">Vencido (' . abs($dias) . ' días)</span>';
                                        } elseif ($dias == 0) {
                                            echo '<span class="badge badge-warning">Hoy</span>';
                                        } elseif ($dias <= 7) {
                                            echo '<span class="badge badge-warning">' . $dias . ' días</span>';
                                        } else {
                                            echo '<span class="text-muted">' . $dias . ' días</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $mantenimiento['estado_actual']; ?>">
                                            <?php echo ASSET_STATES[$mantenimiento['estado_actual']] ?? $mantenimiento['estado_actual']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo $mantenimiento['id_activo']; ?>" 
                                           class="btn-icon" title="Ver">
                                            <i class="fas fa-eye"></i>
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

    <?php if ($es_admin): ?>
    <!-- Activos sin Plan/Vencidos -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-circle"></i> Activos sin Plan/Vencidos</h3>
        </div>
        <div class="card-body">
            <?php if (empty($activos_sin_plan_mantenimiento)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>Todos los activos cuentan con plan vigente.</p>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>index.php?action=activos">
                        Revisar Activos
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Activo</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Próx. Mant.</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activos_sin_plan_mantenimiento as $activo): ?>
                                <?php
                                    $proximo = $activo['proximo_mantenimiento'] 
                                        ? date('d/m/Y', strtotime($activo['proximo_mantenimiento']))
                                        : 'Sin definir';
                                    $isVencido = $activo['proximo_mantenimiento'] && strtotime($activo['proximo_mantenimiento']) < time();
                                ?>
                                <tr class="<?php echo $isVencido ? 'table-warning' : ''; ?>">
                                    <td><strong><?php echo htmlspecialchars($activo['codigo_interno'] ?: 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($activo['nombre_activo']); ?></td>
                                    <td>
                                        <span class="badge badge-category">
                                            <?php echo ASSET_CATEGORIES[$activo['categoria']] ?? $activo['categoria']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $activo['estado_actual']; ?>">
                                            <?php echo ASSET_STATES[$activo['estado_actual']] ?? $activo['estado_actual']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isVencido): ?>
                                            <span class="text-danger"><?php echo $proximo; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo $proximo; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo $activo['id_activo']; ?>"
                                           class="btn-icon" title="Ver activo">
                                            <i class="fas fa-eye"></i>
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

    <!-- Activos con Criticidad Alta -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bolt"></i> Activos con Criticidad Alta</h3>
        </div>
        <div class="card-body">
            <?php if (empty($criticidad_por_activo)): ?>
                <div class="empty-state">
                    <i class="fas fa-shield-alt"></i>
                    <p>No se registran órdenes críticas recientes.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Activo</th>
                                <th>Críticas</th>
                                <th>Altas</th>
                                <th>Órdenes Abiertas</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($criticidad_por_activo as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['codigo_interno'] ?: 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nombre_activo']); ?></td>
                                    <td><span class="badge badge-danger"><?php echo $row['criticas']; ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo $row['altas']; ?></span></td>
                                    <td><span class="badge badge-info"><?php echo $row['abiertas']; ?></span></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=activos&subaction=ver&id=<?php echo $row['id_activo']; ?>"
                                           class="btn-icon" title="Ver activo">
                                            <i class="fas fa-eye"></i>
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
    <?php endif; ?>

    <!-- Activos por Categoría -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Activos por Categoría</h3>
        </div>
        <div class="card-body">
            <?php if (empty($activos_por_categoria)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-pie"></i>
                    <p>No hay datos disponibles</p>
                </div>
            <?php else: ?>
                <canvas id="chartActivosCategoria" height="250"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Activos por Estado -->
    <div class="card">
        <div  class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Activos por Estado</h3>
        </div>
        <div class="card-body">
            <?php if (empty($activos_por_estado)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <p>No hay datos disponibles</p>
                </div>
            <?php else: ?>
                <div class="chart-list">
                    <?php foreach ($activos_por_estado as $item): ?>
                        <div class="chart-item">
                            <div class="chart-label">
                                <span><?php echo ASSET_STATES[$item['estado_actual']] ?? $item['estado_actual']; ?></span>
                                <strong><?php echo $item['cantidad']; ?></strong>
                            </div>
                            <div class="chart-bar">
                                <div class="chart-bar-fill" 
                                     style="width: <?php echo ($item['cantidad'] / max($estadisticas['total_activos'], 1)) * 100; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($puede_ver_ordenes): ?>
    <!-- Órdenes Recientes -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-list"></i> Órdenes Recientes</h3>
            <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-sm btn-primary">
                Ver Todas
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($ordenes_recientes)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No hay órdenes registradas</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Radicado</th>
                                <th>Activo</th>
                                <th>Estado</th>
                                <th>Criticidad</th>
                                <th>Fecha Límite</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes_recientes as $orden): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($orden['numero_radicado']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($orden['nombre_activo'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $estado_class = [
                                            'recibido' => 'badge-recibido',
                                            'en_proceso' => 'badge-en_proceso',
                                            'rechazado' => 'badge-rechazado',
                                            'finalizado' => 'badge-finalizado'
                                        ];
                                        $class = $estado_class[$orden['estado_proceso']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo ORDER_STATES[$orden['estado_proceso']] ?? $orden['estado_proceso']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $criticidad_class = [
                                            'critica' => 'badge-danger',
                                            'alta' => 'badge-warning',
                                            'normal' => 'badge-info',
                                            'baja' => 'badge-secondary'
                                        ];
                                        $class = $criticidad_class[$orden['nivel_criticidad']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo CRITICITY_LEVELS[$orden['nivel_criticidad']] ?? $orden['nivel_criticidad']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($orden['fecha_limite_ejecucion']): ?>
                                            <?php 
                                            $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
                                            $hoy = new DateTime();
                                            $dias_restantes = $hoy->diff($fecha_limite)->days;
                                            if ($fecha_limite < $hoy && $orden['estado_proceso'] !== 'finalizado') {
                                                echo '<span class="text-danger">' . $fecha_limite->format('d/m/Y') . '</span>';
                                            } elseif ($dias_restantes <= 3 && $orden['estado_proceso'] !== 'finalizado') {
                                                echo '<span class="text-warning">' . $fecha_limite->format('d/m/Y') . '</span>';
                                            } else {
                                                echo $fecha_limite->format('d/m/Y');
                                            }
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin fecha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=ver&id=<?php echo $orden['id_orden']; ?>" 
                                           class="btn-icon" title="Ver">
                                            <i class="fas fa-eye"></i>
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

    <!-- Órdenes por Estado -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Órdenes por Estado</h3>
        </div>
        <div class="card-body">
            <?php if (empty($ordenes_por_estado)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-pie"></i>
                    <p>No hay datos disponibles</p>
                </div>
            <?php else: ?>
                <canvas id="chartOrdenesEstado" height="250"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($es_admin): ?>
    <!-- Órdenes Críticas Abiertas -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-fire-alt"></i> Órdenes Críticas Abiertas</h3>
        </div>
        <div class="card-body">
            <?php if (empty($ordenes_criticas)): ?>
                <div class="empty-state">
                    <i class="fas fa-shield-alt"></i>
                    <p>No hay órdenes críticas en curso.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Radicado</th>
                                <th>Activo</th>
                                <th>Criticidad</th>
                                <th>Estado</th>
                                <th>Fecha Límite</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes_criticas as $orden): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($orden['numero_radicado']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($orden['nombre_activo'] ?? 'N/A'); ?></td>
                                    <td><span class="badge badge-danger"><?php echo CRITICITY_LEVELS[$orden['nivel_criticidad']] ?? $orden['nivel_criticidad']; ?></span></td>
                                    <td>
                                        <?php
                                        $estado_class = [
                                            'recibido' => 'badge-recibido',
                                            'en_proceso' => 'badge-en_proceso',
                                            'rechazado' => 'badge-rechazado',
                                            'finalizado' => 'badge-finalizado'
                                        ];
                                        $class = $estado_class[$orden['estado_proceso']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo ORDER_STATES[$orden['estado_proceso']] ?? $orden['estado_proceso']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($orden['fecha_limite_ejecucion']): ?>
                                            <?php 
                                            $fecha_limite = new DateTime($orden['fecha_limite_ejecucion']);
                                            $hoy = new DateTime();
                                            if ($fecha_limite < $hoy) {
                                                echo '<span class="text-danger">' . $fecha_limite->format('d/m/Y') . '</span>';
                                            } else {
                                                echo $fecha_limite->format('d/m/Y');
                                            }
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin fecha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=ver&id=<?php echo $orden['id_orden']; ?>" 
                                           class="btn-icon" title="Ver orden">
                                            <i class="fas fa-eye"></i>
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

    <!-- Rendimiento de Operarios -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users-cog"></i> Rendimiento de Operarios</h3>
        </div>
        <div class="card-body">
            <?php if (empty($rendimiento_operarios)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-cog"></i>
                    <p>Aún no hay datos suficientes de desempeño.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Operario</th>
                                <th>Órdenes Finalizadas</th>
                                <th>Órdenes Abiertas</th>
                                <th>Tiempo Promedio (h)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendimiento_operarios as $operario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($operario['nombre']); ?></td>
                                    <td><span class="badge badge-success"><?php echo $operario['finalizadas']; ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo $operario['abiertas']; ?></span></td>
                                    <td>
                                        <?php 
                                            echo $operario['horas_promedio'] !== null 
                                                ? round($operario['horas_promedio'], 1)
                                                : '--';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tendencia de Tiempos de Cierre -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Tendencia Tiempos de Cierre (14 días)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($tiempos_cierre)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>No hay cierres recientes para analizar.</p>
                </div>
            <?php else: ?>
                <canvas id="chartTiemposCierre" height="260"></canvas>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Preparar datos para Chart.js
$chartActivosCategoria = [];
$chartOrdenesEstado = [];
$chartTiemposCierre = [];

if ($puede_ver_activos && !empty($activos_por_categoria)) {
    foreach ($activos_por_categoria as $item) {
        $chartActivosCategoria['labels'][] = ASSET_CATEGORIES[$item['categoria']] ?? $item['categoria'];
        $chartActivosCategoria['data'][] = $item['cantidad'];
    }
}

if ($puede_ver_ordenes && !empty($ordenes_por_estado)) {
    foreach ($ordenes_por_estado as $item) {
        $chartOrdenesEstado['labels'][] = ORDER_STATES[$item['estado_proceso']] ?? $item['estado_proceso'];
        $chartOrdenesEstado['data'][] = $item['cantidad'];
    }
}

if ($puede_ver_ordenes && !empty($tiempos_cierre)) {
    foreach ($tiempos_cierre as $fila) {
        $chartTiemposCierre['labels'][] = date('d/m', strtotime($fila['fecha']));
        $chartTiemposCierre['data'][] = round($fila['horas_promedio'], 2);
    }
}
?>

<style>
.insights-card .card-header h3 {
    font-size: 1.05rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.insights-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    color: var(--text-color);
}

.insights-list li {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.insight-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
}

@media (max-width: 768px) {
    .insights-card {
        margin-top: 1rem;
    }
}
</style>

<?php if ($puede_ver_activos || $puede_ver_ordenes): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($puede_ver_activos && !empty($chartActivosCategoria)): ?>
    // Gráfico de Activos por Categoría
    const ctxActivos = document.getElementById('chartActivosCategoria');
    if (ctxActivos) {
        new Chart(ctxActivos, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chartActivosCategoria['labels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($chartActivosCategoria['data']); ?>,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#ec4899'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' activos';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if ($puede_ver_ordenes && !empty($chartOrdenesEstado)): ?>
    // Gráfico de Órdenes por Estado
    const ctxOrdenes = document.getElementById('chartOrdenesEstado');
    if (ctxOrdenes) {
        new Chart(ctxOrdenes, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartOrdenesEstado['labels']); ?>,
                datasets: [{
                    label: 'Órdenes',
                    data: <?php echo json_encode($chartOrdenesEstado['data']); ?>,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Órdenes: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
    <?php endif; ?>

    <?php if ($puede_ver_ordenes && !empty($chartTiemposCierre)): ?>
    // Tendencia de tiempos de cierre
    const ctxTiempos = document.getElementById('chartTiemposCierre');
    if (ctxTiempos) {
        new Chart(ctxTiempos, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartTiemposCierre['labels']); ?>,
                datasets: [{
                    label: 'Horas promedio',
                    data: <?php echo json_encode($chartTiemposCierre['data']); ?>,
                    fill: false,
                    borderColor: '#3b82f6',
                    backgroundColor: '#3b82f6',
                    tension: 0.25,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' h';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Horas'
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

