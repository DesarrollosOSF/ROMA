<?php
$page_title = 'Carga Masiva - Nuevos Activos';
require_once __DIR__ . '/../layout/header.php';
$resumen = $_SESSION['resumen_carga_nuevos'] ?? null;
$detalles = $_SESSION['detalle_carga_nuevos'] ?? null;
unset($_SESSION['resumen_carga_nuevos'], $_SESSION['detalle_carga_nuevos']);
?>

<div class="page-header">
    <h1><i class="fas fa-upload"></i> Carga Masiva</h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-file-csv"></i> Importar desde CSV</h3></div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>¿Cómo funciona?</strong>
            <ol style="margin-top:.5rem; padding-left:1.5rem;">
                <li>Descargue la plantilla y complétela. No cambie los encabezados.</li>
                <li>La columna <code>categoria</code> debe ser la <strong>clave o el nombre</strong> de un grupo ya creado en el panel (ej: <code>maquinaria_y_equipo</code>, <code>flota_y_equipo_de_transporte</code>).</li>
                <li>Guarde como CSV UTF-8 (comas o punto y coma) y súbalo aquí. Se validan código/nombre obligatorios, estado válido y códigos sin duplicar.</li>
                <li>Cada fila válida crea un activo y deja auditoría <code>INSERT origen=carga_masiva</code>. Las filas con error se listan abajo para corregir.</li>
            </ol>
        </div>
        <div class="alert alert-info">
            <strong>Columnas:</strong> <code>categoria*</code> (grupo), <code>codigo*</code>, <code>codigo_serial</code>, <code>codigo_placa</code>,
            <code>fecha_ingreso</code>, <code>codigo_cuenta_contable</code>, <code>codigo_grupo_activo_fijo</code>,
            <code>descripcion</code>, <code>valor</code>, <code>nombre*</code>,
            <code>sede</code>, <code>estado</code> (operativo, en_reparacion, fuera_servicio, en_baja),
            <code>responsable_general</code> (jefe inmediato), <code>documento_responsable</code> (documento del trabajador registrado; opcional)
        </div>
        <?php if (!empty($listaCategorias)): ?>
            <p><strong>Grupos disponibles (claves):</strong>
                <?php foreach ($listaCategorias as $c): ?>
                    <span class="badge badge-category"><?php echo htmlspecialchars($c['clave']); ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=procesar_carga" enctype="multipart/form-data">
            <div class="form-group form-group-full">
                <label>Archivo CSV</label>
                <input type="file" name="archivo_csv" accept=".csv,.txt" required>
                <small>Máximo 10MB</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Importar</button>
                <a href="<?php echo BASE_URL; ?>assets/templates/plantilla_nuevos_activos.csv" class="btn btn-success" download><i class="fas fa-download"></i> Descargar Plantilla</a>
            </div>
        </form>

        <?php if ($resumen): ?>
            <div class="card" style="margin-top:2rem;">
                <div class="card-header"><h3>Resultado: <?php echo (int)$resumen['exitosos']; ?> creados, <?php echo (int)$resumen['errores']; ?> con errores (<?php echo (int)$resumen['total_filas']; ?> filas)</h3></div>
                <div class="card-body">
                    <?php if (!empty($detalles)): ?>
                        <div style="max-height:300px; overflow:auto; border:1px solid #dee2e6; border-radius:6px; padding:.75rem;">
                            <ul style="margin:0; padding-left:1.25rem;">
                                <?php foreach ($detalles as $d): ?><li><?php echo htmlspecialchars($d); ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Sin errores.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
