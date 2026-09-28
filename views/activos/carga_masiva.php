<?php
$page_title = 'Carga Masiva de Activos';
require_once __DIR__ . '/../layout/header.php';

$resumen = $_SESSION['resumen_carga'] ?? null;
$detalles = $_SESSION['detalle_carga'] ?? null;
unset($_SESSION['resumen_carga'], $_SESSION['detalle_carga']);
?>

<div class="page-header">
    <h1><i class="fas fa-upload"></i> Carga Masiva de Activos</h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=activos" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-file-csv"></i> Importar desde CSV</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>¿Cómo funciona la carga masiva?</strong>
            <ol style="margin-top: 0.5rem; padding-left: 1.5rem;">
                <li><strong>Descargue la plantilla</strong> y complétela en Excel/LibreOffice. No cambie los nombres de los encabezados.</li>
                <li><strong>Guarde como CSV UTF-8</strong> (delimitado por comas o punto y coma; ambos se detectan automáticamente).</li>
                <li><strong>Suba el archivo aquí.</strong> El sistema valida fila por fila:
                    <code>nombre_activo</code> y <code>categoria</code> obligatorios,
                    categoría/estado contra listas permitidas, fechas <code>YYYY-MM-DD</code> o <code>DD/MM/YYYY</code>,
                    números válidos y <code>codigo_interno</code> sin duplicados (ni en BD ni dentro del archivo).</li>
                <li><strong>Se crean solo las filas válidas.</strong> Cada activo creado deja una auditoría <code>INSERT</code> con origen <code>carga_masiva</code>, usuario y fecha/hora.</li>
                <li><strong>Las filas con error NO se insertan</strong> y se listan abajo por número de línea para que las corrija y vuelva a subir solo esas.</li>
            </ol>
        </div>

        <div class="alert alert-info">
            <strong>Columnas soportadas:</strong>
            <code>nombre_activo*</code>, <code>codigo_interno</code>, <code>codigo_patrimonial</code>,
            <code>descripcion_general</code>, <code>categoria*</code> (equipo_especial, vehiculos, maquinas, muebles_enseres, horno_crematorio, planta_agua_residual, cofres_cremacion),
            <code>ubicacion</code>, <code>ruta</code>, <code>responsable</code>, <code>area_asignada</code>,
            <code>marca</code>, <code>modelo</code>, <code>numero_serie</code>, <code>fecha_adquisicion</code>,
            <code>valor_adquisicion</code>, <code>estado_actual</code> (operativo, en_reparacion, fuera_servicio, en_baja),
            <code>vida_util_estimada</code>, <code>kilometraje</code>, <code>horas_uso</code>,
            <code>proximo_mantenimiento</code>, <code>observaciones</code>
            <br><small>* obligatorias. Máximo 5.000 filas por archivo, 10MB.</small>
        </div>

        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=activos&subaction=procesar_carga"
              enctype="multipart/form-data">
            <div class="form-group form-group-full">
                <label>Seleccionar archivo CSV</label>
                <input type="file" name="archivo_csv" accept=".csv,.txt" required>
                <small>Formato: CSV UTF-8 separado por comas o punto y coma. Tamaño máximo: 10MB</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Importar Activos
                </button>
                <a href="<?php echo BASE_URL; ?>assets/templates/plantilla_activos.csv"
                   class="btn btn-success" download>
                    <i class="fas fa-download"></i> Descargar Plantilla
                </a>
            </div>
        </form>

        <?php if ($resumen): ?>
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Resultado de la Importación</h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card stat-success">
                            <div class="stat-content"><h3><?php echo (int)$resumen['exitosos']; ?></h3><p>Creados</p></div>
                        </div>
                        <div class="stat-card stat-warning">
                            <div class="stat-content"><h3><?php echo (int)$resumen['errores']; ?></h3><p>Con errores</p></div>
                        </div>
                        <div class="stat-card stat-secondary">
                            <div class="stat-content"><h3><?php echo (int)$resumen['omitidos']; ?></h3><p>Omitidos</p></div>
                        </div>
                        <div class="stat-card stat-info">
                            <div class="stat-content"><h3><?php echo (int)$resumen['total']; ?></h3><p>Filas procesadas</p></div>
                        </div>
                    </div>
                    <?php if (!empty($detalles)): ?>
                        <h4 style="margin-top:1rem;">Detalle por línea (máx. 300)</h4>
                        <div style="max-height:320px; overflow:auto; border:1px solid #dee2e6; border-radius:6px; padding:0.75rem;">
                            <ul style="margin:0; padding-left:1.25rem;">
                                <?php foreach ($detalles as $detalle): ?>
                                    <li><?php echo htmlspecialchars($detalle); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <p class="text-muted" style="margin-top:0.5rem;">
                            Corrija las filas con error en su CSV y vuelva a subirlas. Los creados ya quedaron auditados como <code>carga_masiva</code>.
                        </p>
                    <?php else: ?>
                        <p class="text-muted">Carga sin errores. Todos los activos quedaron registrados en auditoría.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
