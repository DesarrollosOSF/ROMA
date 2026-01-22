<?php
$page_title = 'Carga Masiva de Activos';
require_once __DIR__ . '/../layout/header.php';
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
            <strong>Instrucciones:</strong>
            <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                <li>Descargue la plantilla CSV de ejemplo</li>
                <li>Complete los datos siguiendo el formato</li>
                <li>Suba el archivo CSV con los activos a importar</li>
                <li>El sistema validará y procesará cada registro</li>
            </ul>
        </div>

        <form method="POST" action="<?php echo BASE_URL; ?>index.php?action=activos&subaction=procesar_carga" 
              enctype="multipart/form-data">
            <div class="form-group form-group-full">
                <label>Seleccionar archivo CSV</label>
                <input type="file" name="archivo_csv" accept=".csv" required>
                <small>Formato: CSV separado por comas. Tamaño máximo: 10MB</small>
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

        <?php if (isset($_SESSION['detalle_carga'])): ?>
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Resultado de la Importación</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <?php foreach ($_SESSION['detalle_carga'] as $detalle): ?>
                            <li><?php echo htmlspecialchars($detalle); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php unset($_SESSION['detalle_carga']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

