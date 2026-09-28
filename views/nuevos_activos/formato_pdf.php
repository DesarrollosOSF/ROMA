<?php
// Vista imprimible del formato de inventario y asignación (se imprime/guarda como PDF desde el navegador)
$t = $formato['trabajador'];
$resp = $formato['responsable'];
$fechaGen = date('d/m/Y H:i');
$f = function ($v) {
    $v = trim((string)($v ?? ''));
    return $v === '' ? '—' : $v;
};
$fd = function ($v) {
    $v = trim((string)($v ?? ''));
    if ($v === '' || $v === '0000-00-00') return '—';
    $ts = strtotime($v);
    return $ts ? date('d/m/Y', $ts) : $v;
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formato de Inventario y Asignación - <?php echo htmlspecialchars($t['nombre'] ?: 'Trabajador'); ?></title>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 0; padding: 20px; background: #f1f5f9; }
        .toolbar { display: flex; gap: .5rem; margin-bottom: 1rem; }
        .toolbar button, .toolbar a { padding: .5rem 1rem; border-radius: 6px; border: 1px solid #ccc; background: #2563eb; color: #fff; text-decoration: none; cursor: pointer; font-size: 14px; }
        .toolbar a { background: #6b7280; }
        .doc { max-width: 1000px; margin: 0 auto; background: #fff; padding: 24px 28px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .doc-header { text-align: center; background: #1e3a8a; color: #fff; border-radius: 8px; padding: 14px 10px; margin-bottom: 12px; }
        .doc-header h1 { font-size: 18px; margin: 0; text-transform: uppercase; letter-spacing: .5px; }
        .doc-header p { margin: 4px 0 0; font-size: 11px; color: #dbeafe; }
        h2 { font-size: 13px; padding: 7px 10px; margin: 18px 0 8px; text-transform: uppercase; color: #0f0f0f; border-radius: 6px; }
        h2.sec-trabajador { background: #b8b9bb; }
        h2.sec-activos { background: #b8b9bb; }
        h2.sec-movimientos { background: #b8b9bb; }
        h2.sec-constancia { background: #b8b9bb; }
        h2.sec-obs { background: #b8b9bb; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #0f0f0f; padding: 5px 6px; text-align: left; font-size: 11.5px; vertical-align: top; }
        thead th { color: #070606; }
        .tabla-trabajador thead th { background: #d2d6df; }
        .tabla-activos thead th { background: #d2d6df; }
        .tabla-movimientos thead th { background: #d2d6df; }
        
        .grid-2 td { width: 50%; }
        .label { font-weight: bold; color: #080808; }
        .constancia { border: 2px solid #c4cdcf; background: #ecfeff; border-radius: 6px; padding: 10px 12px; text-align: justify; font-size: 11.5px; line-height: 1.55; }
        .firmas { display: table; width: 100%; margin-top: 26px; }
        .firma { display: table-cell; width: 50%; text-align: center; padding: 0 20px; font-size: 11.5px; }
        .firma .linea { border-top: 2px solid #0b0b0c; margin-top: 60px; padding-top: 6px; }
        .firma strong { color: #0c0c0c; }
        .small { font-size: 10.5px; color: #080808; }
        @media print {
            body { padding: 0; background: #fff; }
            .toolbar { display: none; }
            .doc { max-width: none; box-shadow: none; border-radius: 0; padding: 0; }
        }
    </style>
</head>
<body>
<div class="doc">
    <div class="toolbar">
        <button onclick="window.print()">Descargar / Imprimir PDF</button>
        <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=formato">Volver al formulario</a>
    </div>

    <div class="doc-header">
        <h1>Formato de Inventario y Asignación de Activos al Colaborador</h1>
        <p>Sistema ROMA · Generado: <?php echo $fechaGen; ?> · Generado por: <?php echo htmlspecialchars($formato['generado_por']); ?></p>
    </div>

    <h2 class="sec-trabajador">1. Información del Trabajador</h2>
    <table class="tabla-trabajador grid-2">
        <tr>
            <td><span class="label">Nombre Completo:</span> <?php echo htmlspecialchars($f($t['nombre'])); ?></td>
            <td><span class="label">Documento de Identidad:</span> <?php echo htmlspecialchars($f($t['documento'])); ?></td>
        </tr>
        <tr>
            <td><span class="label">Cargo:</span> <?php echo htmlspecialchars($f($t['cargo'])); ?></td>
            <td><span class="label">Área / Dependencia:</span> <?php echo htmlspecialchars($f($t['area'])); ?></td>
        </tr>
        <tr>
            <td><span class="label">Tipo de Vinculación:</span> <?php echo htmlspecialchars($f($t['vinculacion'])); ?></td>
            <td><span class="label">Centro de Costo:</span> <?php echo htmlspecialchars($f($t['centro_costo'])); ?></td>
        </tr>
        <tr>
            <td><span class="label">Jefe Inmediato:</span> <?php echo htmlspecialchars($f($t['jefe'])); ?></td>
            <td><span class="label">Fecha de Ingreso:</span> <?php echo htmlspecialchars($fd($t['ingreso'])); ?></td>
        </tr>
    </table>

    <h2 class="sec-activos">2. Información de los Activos Asignados</h2>
    <?php if (empty($formato['activos'])): ?>
        <p>Sin activos seleccionados.</p>
    <?php else: ?>
        <table class="tabla-activos">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Código Serial</th>
                    <th>Nombre</th>
                    <th>Grupo</th>
                    <th>Sede</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Fecha Entrega</th>
                    <th>Estado Entrega</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($formato['activos'] as $i => $a): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($a['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($f($a['codigo_serial'])); ?></td>
                        <td><?php echo htmlspecialchars($a['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($f($a['categoria'])); ?></td>
                        <td><?php echo htmlspecialchars($f($a['sede'])); ?></td>
                        <td><?php echo isset($a['valor']) && $a['valor'] !== null && $a['valor'] !== '' ? '$' . number_format((float)$a['valor'], 0, ',', '.') : '—'; ?></td>
                        <td><?php echo htmlspecialchars($f($a['estado'])); ?></td>
                        <td><?php echo htmlspecialchars($fd($a['fecha_entrega'])); ?></td>
                        <td><?php echo htmlspecialchars($f($a['estado_entrega'])); ?></td>
                        <td><?php echo htmlspecialchars($f($a['observacion'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 class="sec-movimientos">3. Control de Devolución, Cambio o Traslado</h2>
    <?php if (empty($formato['movimientos'])): ?>
        <p class="small">Sin movimientos registrados.</p>
    <?php else: ?>
        <table class="tabla-movimientos">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo de Movimiento</th>
                    <th>Código del Activo</th>
                    <th>Estado Anterior</th>
                    <th>Estado Nuevo</th>
                    <th>Responsable</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($formato['movimientos'] as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fd($m['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($f($m['tipo'])); ?></td>
                        <td><?php echo htmlspecialchars($f($m['codigo'])); ?></td>
                        <td><?php echo htmlspecialchars($f($m['estado_anterior'])); ?></td>
                        <td><?php echo htmlspecialchars($f($m['estado_nuevo'])); ?></td>
                        <td><?php echo htmlspecialchars($f($m['responsable'])); ?></td>
                        <td><?php echo htmlspecialchars($f($m['observacion'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 class="sec-constancia">Constancia de Entrega y Responsabilidad</h2>
    <div class="constancia">
        El trabajador declara haber recibido los elementos relacionados en el presente formato, en las condiciones aquí registradas,
        y se compromete a utilizarlos adecuadamente, conservarlos y reportar oportunamente cualquier pérdida, daño, deterioro o
        situación que afecte su integridad. Los elementos relacionados hacen parte del inventario de la Organización y permanecerán
        bajo responsabilidad del trabajador mientras se encuentren asignados a su cargo. En caso de retiro, traslado o cambio de
        funciones, deberá realizar la devolución de los elementos asignados y atender el procedimiento interno establecido.
    </div>

    <h2 class="sec-obs">Observaciones Generales</h2>
    <table>
        <tr><td style="min-height:60px; height:60px;"><?php echo nl2br(htmlspecialchars($formato['obs_generales'] ?: '—')); ?></td></tr>
    </table>

    <div class="firmas">
        <div class="firma">
            <div class="linea">
                <strong>Firma del Trabajador</strong><br>
                Nombre: <?php echo htmlspecialchars($f($t['nombre'])); ?><br>
                Documento: <?php echo htmlspecialchars($f($t['documento'])); ?><br>
                Fecha: <?php echo htmlspecialchars($fd($formato['firma_trab_fecha'])); ?>
            </div>
        </div>
        <div class="firma">
            <div class="linea">
                <strong>Firma del Responsable de Inventarios</strong><br>
                Nombre: <?php echo htmlspecialchars($f($resp['nombre'])); ?><br>
                Cargo: <?php echo htmlspecialchars($f($resp['cargo'])); ?><br>
                Fecha: <?php echo htmlspecialchars($fd($resp['fecha'])); ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
