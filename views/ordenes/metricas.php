<?php
$page_title = 'Métricas de Órdenes de Trabajo';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../models/Usuario.php';

$metricas = $metricas ?? [];
$informe_tiempos = $informe_tiempos ?? [];
$carga_operarios = $carga_operarios ?? [];
$estados = $estados ?? [];
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Métricas de Órdenes de Trabajo</h1>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
        <a href="<?php echo BASE_URL; ?>index.php?action=ordenes&subaction=cronograma" class="btn btn-info">
            <i class="fas fa-calendar-alt"></i> Cronograma
        </a>
    </div>
</div>

<!-- Tarjetas de indicadores principales -->
<div class="metrics-cards">
    <div class="metric-card metric-en-proceso">
        <div class="metric-icon"><i class="fas fa-spinner"></i></div>
        <div class="metric-body">
            <span class="metric-value"><?php echo (int)($metricas['en_proceso'] ?? 0); ?></span>
            <span class="metric-label">En Proceso</span>
        </div>
    </div>
    <div class="metric-card metric-finalizadas">
        <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
        <div class="metric-body">
            <span class="metric-value"><?php echo (int)($metricas['finalizadas'] ?? 0); ?></span>
            <span class="metric-label">Finalizadas</span>
        </div>
    </div>
    <div class="metric-card metric-criticas">
        <div class="metric-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="metric-body">
            <span class="metric-value"><?php echo (int)($metricas['criticas'] ?? 0); ?></span>
            <span class="metric-label">Críticas</span>
        </div>
    </div>
    <div class="metric-card metric-atrasadas">
        <div class="metric-icon"><i class="fas fa-clock"></i></div>
        <div class="metric-body">
            <span class="metric-value"><?php echo (int)($metricas['atrasadas'] ?? 0); ?></span>
            <span class="metric-label">Atrasadas</span>
        </div>
    </div>
</div>

<!-- Carga de trabajo por operario -->
<div class="card card-full mb-3">
    <div class="card-header">
        <h3><i class="fas fa-users-cog"></i> Carga de trabajo por operario</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">Distribución de órdenes asignadas por operario. Use este resumen para balancear la carga y priorizar apoyo a quienes tienen atrasos o órdenes críticas.</p>
        <?php if (empty($carga_operarios)): ?>
        <p class="text-muted">No hay operarios registrados o no hay órdenes asignadas.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table data-table-sm">
                <thead>
                    <tr>
                        <th>Operario</th>
                        <th>Activas</th>
                        <th>En proceso</th>
                        <th>Recibido</th>
                        <th>Finalizadas</th>
                        <th>Atrasadas</th>
                        <th>Críticas</th>
                        <th>Resultado / Nivel de carga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $promedio_activas = 0;
                    $n = count($carga_operarios);
                    foreach ($carga_operarios as $op) {
                        $promedio_activas += (int)($op['total_activas'] ?? 0);
                    }
                    $promedio_activas = $n > 0 ? round($promedio_activas / $n, 1) : 0;
                    foreach ($carga_operarios as $op):
                        $activas = (int)($op['total_activas'] ?? 0);
                        $en_proceso = (int)($op['en_proceso'] ?? 0);
                        $recibido = (int)($op['recibido'] ?? 0);
                        $finalizadas = (int)($op['finalizadas'] ?? 0);
                        $atrasadas = (int)($op['atrasadas'] ?? 0);
                        $criticas = (int)($op['criticas'] ?? 0);
                        $nombre = htmlspecialchars($op['nombre'] ?? 'Sin nombre');
                        $email = htmlspecialchars($op['email'] ?? '');
                        if ($activas === 0) {
                            $nivel = 'Sin asignación';
                            $clase = 'secondary';
                            $recomendacion = 'Sin órdenes activas. Puede asignar nuevas.';
                        } elseif ($atrasadas > 0 || $criticas > 0) {
                            $nivel = 'Riesgo';
                            $clase = 'danger';
                            $recomendacion = $atrasadas > 0 && $criticas > 0 ? 'Priorizar atrasos y órdenes críticas.' : ($atrasadas > 0 ? 'Priorizar órdenes atrasadas.' : 'Atender órdenes críticas.');
                        } elseif ($activas >= 10 || $activas > $promedio_activas * 1.5) {
                            $nivel = 'Alta carga';
                            $clase = 'warning';
                            $recomendacion = 'Valorar reasignar o dar apoyo para cumplir plazos.';
                        } elseif ($activas >= 4) {
                            $nivel = 'Carga normal';
                            $clase = 'info';
                            $recomendacion = 'Carga dentro del rango esperado.';
                        } else {
                            $nivel = 'Baja carga';
                            $clase = 'success';
                            $recomendacion = 'Puede asumir más órdenes si se requiere.';
                        }
                    ?>
                    <tr class="<?php echo $atrasadas > 0 || $criticas > 0 ? 'table-row-risk' : ''; ?>">
                        <td>
                            <strong><?php echo $nombre; ?></strong>
                            <?php if ($email): ?><br><small class="text-muted"><?php echo $email; ?></small><?php endif; ?>
                        </td>
                        <td><strong><?php echo $activas; ?></strong></td>
                        <td><?php echo $en_proceso; ?></td>
                        <td><?php echo $recibido; ?></td>
                        <td><?php echo $finalizadas; ?></td>
                        <td class="<?php echo $atrasadas > 0 ? 'text-danger font-weight-bold' : ''; ?>"><?php echo $atrasadas; ?></td>
                        <td class="<?php echo $criticas > 0 ? 'text-danger font-weight-bold' : ''; ?>"><?php echo $criticas; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $clase; ?>"><?php echo $nivel; ?></span>
                            <br><small class="text-muted"><?php echo $recomendacion; ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="carga-resumen mt-3">
            <strong>Resumen:</strong> Promedio de órdenes activas por operario: <strong><?php echo $promedio_activas; ?></strong>.
            Operarios con atrasos o críticas requieren prioridad. Use el cronograma para reasignar si es necesario.
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="metrics-grid">
    <!-- Informe de tiempo de finalización -->
    <div class="card card-full">
        <div class="card-header">
            <h3><i class="fas fa-stopwatch"></i> Informe de tiempo de finalización</h3>
        </div>
        <div class="card-body">
            <p class="text-muted">Comparación entre tiempo estimado (fecha creación → fecha límite) y tiempo real (fecha creación → fecha de cierre) de las órdenes finalizadas.</p>
            
            <div class="informe-tiempos-grid">
                <div class="informe-item">
                    <span class="informe-label">Órdenes finalizadas analizadas</span>
                    <span class="informe-value"><?php echo (int)($informe_tiempos['total_finalizadas'] ?? 0); ?></span>
                </div>
                <div class="informe-item">
                    <span class="informe-label">Cumplieron plazo</span>
                    <span class="informe-value"><?php echo (int)($informe_tiempos['cumplieron_plazo'] ?? 0); ?></span>
                </div>
                <div class="informe-item">
                    <span class="informe-label">% Cumplimiento de plazo</span>
                    <span class="informe-value informe-highlight"><?php echo number_format($informe_tiempos['porcentaje_cumplimiento'] ?? 0, 1); ?>%</span>
                </div>
                <div class="informe-item">
                    <span class="informe-label">Promedio días estimados (creación → límite)</span>
                    <span class="informe-value"><?php echo number_format($informe_tiempos['promedio_dias_estimados'] ?? 0, 1); ?> días</span>
                </div>
                <div class="informe-item">
                    <span class="informe-label">Promedio días reales (creación → cierre)</span>
                    <span class="informe-value"><?php echo number_format($informe_tiempos['promedio_dias_reales'] ?? 0, 1); ?> días</span>
                </div>
                <div class="informe-item">
                    <span class="informe-label">Promedio días de retraso (cuando hubo retraso)</span>
                    <span class="informe-value <?php echo ($informe_tiempos['promedio_dias_retraso'] ?? 0) > 0 ? 'text-danger' : ''; ?>"><?php echo number_format($informe_tiempos['promedio_dias_retraso'] ?? 0, 1); ?> días</span>
                </div>
            </div>

            <?php
            $resumen = $informe_tiempos['resumen_retraso'] ?? [];
            $total_con_plazo = ($resumen['a_tiempo'] ?? 0) + ($resumen['retraso_1_3'] ?? 0) + ($resumen['retraso_4_7'] ?? 0) + ($resumen['retraso_8_14'] ?? 0) + ($resumen['retraso_mas_14'] ?? 0);
            ?>
            <h4 class="mt-3 mb-2"><i class="fas fa-layer-group"></i> Resumen por tiempo de retraso</h4>
            <p class="text-muted small">Distribución de las <?php echo (int)$informe_tiempos['total_finalizadas']; ?> órdenes finalizadas según cumplimiento del plazo (días estimados vs reales).</p>
            <div class="resumen-retraso-grid">
                <div class="resumen-retraso-item resumen-a-tiempo">
                    <span class="resumen-retraso-valor"><?php echo (int)($resumen['a_tiempo'] ?? 0); ?></span>
                    <span class="resumen-retraso-label">A tiempo</span>
                    <span class="resumen-retraso-sub">(≤ 0 días retraso)</span>
                </div>
                <div class="resumen-retraso-item">
                    <span class="resumen-retraso-valor"><?php echo (int)($resumen['retraso_1_3'] ?? 0); ?></span>
                    <span class="resumen-retraso-label">Retraso 1-3 días</span>
                </div>
                <div class="resumen-retraso-item">
                    <span class="resumen-retraso-valor"><?php echo (int)($resumen['retraso_4_7'] ?? 0); ?></span>
                    <span class="resumen-retraso-label">Retraso 4-7 días</span>
                </div>
                <div class="resumen-retraso-item">
                    <span class="resumen-retraso-valor"><?php echo (int)($resumen['retraso_8_14'] ?? 0); ?></span>
                    <span class="resumen-retraso-label">Retraso 8-14 días</span>
                </div>
                <div class="resumen-retraso-item resumen-mas-14">
                    <span class="resumen-retraso-valor"><?php echo (int)($resumen['retraso_mas_14'] ?? 0); ?></span>
                    <span class="resumen-retraso-label">Retraso &gt; 14 días</span>
                </div>
            </div>

            <?php if (!empty($informe_tiempos['muestra'])): ?>
            <h4 class="mt-4 mb-2"><i class="fas fa-list"></i> Muestra (últimas 20 órdenes finalizadas)</h4>
            <p class="text-muted small">Vista previa. Con cientos o miles de órdenes el análisis se basa en el resumen anterior.</p>
            <div class="table-responsive mt-2">
                <table class="data-table data-table-sm">
                    <thead>
                        <tr>
                            <th>Radicado</th>
                            <th>Días estimados</th>
                            <th>Días reales</th>
                            <th>Retraso (días)</th>
                            <th>Cumplió plazo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($informe_tiempos['muestra'] as $d): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['numero_radicado']); ?></strong></td>
                            <td><?php echo $d['dias_estimados'] !== null ? $d['dias_estimados'] : '—'; ?></td>
                            <td><?php echo $d['dias_reales'] !== null ? $d['dias_reales'] : '—'; ?></td>
                            <td class="<?php echo ($d['dias_retraso'] ?? 0) > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $d['dias_retraso'] !== null ? $d['dias_retraso'] : '—'; ?></td>
                            <td><?php echo $d['cumplio_plazo'] === true ? '<span class="badge badge-success">Sí</span>' : ($d['cumplio_plazo'] === false ? '<span class="badge badge-danger">No</span>' : '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informe experto para toma de decisiones -->
    <div class="card card-full">
        <div class="card-header">
            <h3><i class="fas fa-user-tie"></i> Informe para toma de decisiones</h3>
        </div>
        <div class="card-body informe-experto">
            <?php
            $en_proceso = (int)($metricas['en_proceso'] ?? 0);
            $finalizadas = (int)($metricas['finalizadas'] ?? 0);
            $criticas = (int)($metricas['criticas'] ?? 0);
            $atrasadas = (int)($metricas['atrasadas'] ?? 0);
            $recibido = (int)($metricas['recibido'] ?? 0);
            $total_activas = (int)($metricas['total_activas'] ?? 0);
            $pct = $informe_tiempos['porcentaje_cumplimiento'] ?? 0;
            $prom_retraso = $informe_tiempos['promedio_dias_retraso'] ?? 0;
            $recomendaciones = [];
            $calificacion = 5;
            $resumen = '';

            if ($atrasadas > 0) {
                $recomendaciones[] = '<strong>Priorización urgente:</strong> Existen ' . $atrasadas . ' orden(es) atrasada(s). Se recomienda reasignar recursos o replantear fechas límite para evitar impactos operativos.';
                $calificacion = min($calificacion, 2);
            }
            if ($criticas > 0) {
                $recomendaciones[] = '<strong>Órdenes críticas activas:</strong> ' . $criticas . ' orden(es) con nivel crítico pendiente(s). Deben atenderse en el menor tiempo posible.';
                $calificacion = min($calificacion, 3);
            }
            if ($recibido > 0 && $en_proceso == 0 && $total_activas > 5) {
                $recomendaciones[] = '<strong>Cola de recepción:</strong> Hay ' . $recibido . ' orden(es) en estado Recibido sin iniciar. Valorar asignación o capacidad del equipo.';
            }
            if ($pct < 70 && $finalizadas > 0) {
                $recomendaciones[] = '<strong>Cumplimiento de plazos:</strong> El porcentaje de órdenes finalizadas dentro del plazo es del ' . number_format($pct, 0) . '%. Se sugiere revisar estimaciones de tiempo al crear órdenes o reforzar capacidad de ejecución.';
                $calificacion = min($calificacion, 3);
            }
            if ($prom_retraso > 3 && $finalizadas > 0) {
                $recomendaciones[] = '<strong>Retraso promedio:</strong> Las órdenes que se atrasan lo hacen en promedio ' . number_format($prom_retraso, 0) . ' días. Considere ampliar márgenes en fechas límite para tipos de trabajo más complejos.';
            }
            if ($total_activas == 0 && $finalizadas > 0) {
                $recomendaciones[] = '<strong>Estado del flujo:</strong> No hay órdenes activas en este momento. Buen cierre de ciclo; puede enfocarse en preventivos o nuevas solicitudes.';
                $calificacion = 5;
            }
            if ($total_activas > 0 && $atrasadas == 0 && $criticas == 0) {
                $recomendaciones[] = '<strong>Flujo saludable:</strong> Las órdenes activas no presentan atrasos ni criticidad abierta. Mantener seguimiento a fechas límite.';
                $calificacion = max($calificacion, 4);
            }
            if (empty($recomendaciones)) {
                $recomendaciones[] = 'No hay suficientes datos recientes para generar recomendaciones. Continúe registrando órdenes y cierres para obtener un informe más detallado.';
            }

            $etiqueta_calificacion = $calificacion >= 4.5 ? 'Muy bueno' : ($calificacion >= 3.5 ? 'Bueno' : ($calificacion >= 2.5 ? 'Regular' : ($calificacion >= 1.5 ? 'Mejorable' : 'Crítico')));
            $clase_calificacion = $calificacion >= 4 ? 'success' : ($calificacion >= 3 ? 'info' : ($calificacion >= 2 ? 'warning' : 'danger'));
            ?>
            <div class="calificacion-box">
                <span class="calificacion-label">Calificación del proceso (Órdenes de trabajo)</span>
                <span class="calificacion-valor badge badge-<?php echo $clase_calificacion; ?>"><?php echo $etiqueta_calificacion; ?></span>
            </div>
            <p class="informe-resumen"><?php echo $resumen ?: 'El siguiente informe resume el estado de las órdenes y sugiere acciones para mejorar la planificación y el cumplimiento de plazos.'; ?></p>
            <ul class="recomendaciones-list">
                <?php foreach ($recomendaciones as $rec): ?>
                <li><?php echo $rec; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<style>
.metrics-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.metric-card { display: flex; align-items: center; padding: 1.25rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.metric-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-size: 1.4rem; color: #fff; }
.metric-en-proceso .metric-icon { background: linear-gradient(135deg, #17a2b8, #138496); }
.metric-finalizadas .metric-icon { background: linear-gradient(135deg, #28a745, #1e7e34); }
.metric-criticas .metric-icon { background: linear-gradient(135deg, #dc3545, #c82333); }
.metric-atrasadas .metric-icon { background: linear-gradient(135deg, #fd7e14, #e8590c); }
.metric-body { display: flex; flex-direction: column; }
.metric-value { font-size: 1.75rem; font-weight: 700; color: #212529; }
.metric-label { font-size: 0.9rem; color: #6c757d; }
.metrics-grid { display: flex; flex-direction: column; gap: 1.5rem; }
.card-full { max-width: 100%; }
.informe-tiempos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin: 1rem 0; }
.informe-item { display: flex; flex-direction: column; padding: 0.75rem; background: #f8f9fa; border-radius: 8px; }
.informe-label { font-size: 0.85rem; color: #6c757d; }
.informe-value { font-size: 1.25rem; font-weight: 600; }
.informe-value.informe-highlight { color: var(--primary-color, #007bff); }
.data-table-sm { font-size: 0.9rem; }
.mt-3 { margin-top: 1rem; }
.calificacion-box { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem; background: #f8f9fa; border-radius: 8px; }
.calificacion-label { font-weight: 600; }
.calificacion-valor { font-size: 1rem; padding: 0.35rem 0.75rem; }
.informe-resumen { color: #495057; margin-bottom: 1rem; }
.recomendaciones-list { margin: 0; padding-left: 1.25rem; }
.recomendaciones-list li { margin-bottom: 0.5rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }
.mb-2 { margin-bottom: 0.5rem; }
.resumen-retraso-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin: 1rem 0; }
.resumen-retraso-item { padding: 1rem; background: #f8f9fa; border-radius: 8px; text-align: center; border-left: 4px solid #6c757d; }
.resumen-retraso-item.resumen-a-tiempo { border-left-color: #28a745; background: #f1f9f2; }
.resumen-retraso-item.resumen-mas-14 { border-left-color: #dc3545; background: #fdf2f2; }
.resumen-retraso-valor { display: block; font-size: 1.5rem; font-weight: 700; color: #212529; }
.resumen-retraso-label { font-size: 0.9rem; color: #495057; }
.resumen-retraso-sub { font-size: 0.75rem; color: #6c757d; display: block; margin-top: 2px; }
.mb-3 { margin-bottom: 1rem; }
.table-row-risk { background-color: #fff5f5 !important; }
.font-weight-bold { font-weight: 700; }
.carga-resumen { padding: 0.75rem; background: #f8f9fa; border-radius: 8px; font-size: 0.9rem; }
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
