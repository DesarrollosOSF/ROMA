<?php
/**
 * Generación de Reporte PDF - Hoja de Vida del Activo
 * Sistema ROMA
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Activo.php';

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de activo no válido');
}

$id_activo = (int)$_GET['id'];
$activo_model = new Activo();
$activo = $activo_model->obtenerPorId($id_activo);

if (!$activo) {
    die('Activo no encontrado');
}

$hoja_vida = $activo_model->obtenerHojaVida($id_activo);
$categorias = ASSET_CATEGORIES;
$estados = ASSET_STATES;
$tipos_mantenimiento = MAINTENANCE_TYPES;

// Generar HTML para el PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Vida - <?php echo htmlspecialchars($activo['nombre_activo']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h2 {
            background-color: #f0f0f0;
            padding: 8px;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 5px;
        }
        .timeline {
            margin-top: 20px;
        }
        .timeline-item {
            border-left: 3px solid #2563eb;
            padding-left: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .timeline-header {
            font-weight: bold;
            color: #2563eb;
        }
        .timeline-date {
            color: #666;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema ROMA - Hoja de Vida del Activo</h1>
        <p>Generado el: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="info-section">
        <h2>Información del Activo</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value"><?php echo htmlspecialchars($activo['nombre_activo']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Código Interno:</div>
                <div class="info-value"><?php echo htmlspecialchars($activo['codigo_interno'] ?: 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Código Patrimonial:</div>
                <div class="info-value"><?php echo htmlspecialchars($activo['codigo_patrimonial'] ?: 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Categoría:</div>
                <div class="info-value"><?php echo $categorias[$activo['categoria']] ?? $activo['categoria']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value"><?php echo $estados[$activo['estado_actual']] ?? $activo['estado_actual']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Ubicación:</div>
                <div class="info-value"><?php echo htmlspecialchars($activo['ubicacion'] ?: 'N/A'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Marca/Modelo:</div>
                <div class="info-value"><?php echo htmlspecialchars(($activo['marca'] ?: '') . ' ' . ($activo['modelo'] ?: '')); ?></div>
            </div>
        </div>
    </div>

    <div class="info-section">
        <h2>Historial de Mantenimiento</h2>
        <?php if (empty($hoja_vida)): ?>
            <p>No hay registros de mantenimiento para este activo.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($hoja_vida as $registro): ?>
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <?php echo $tipos_mantenimiento[$registro['tipo_mantenimiento']] ?? $registro['tipo_mantenimiento']; ?>
                            <span class="timeline-date"> - <?php echo date('d/m/Y', strtotime($registro['fecha_intervencion'])); ?></span>
                        </div>
                        <?php if ($registro['tecnico_responsable']): ?>
                            <p><strong>Técnico:</strong> <?php echo htmlspecialchars($registro['tecnico_responsable']); ?></p>
                        <?php endif; ?>
                        <?php if ($registro['descripcion']): ?>
                            <p><?php echo nl2br(htmlspecialchars($registro['descripcion'])); ?></p>
                        <?php endif; ?>
                        <?php if ($registro['repuestos_utilizados']): ?>
                            <p><strong>Repuestos:</strong> <?php echo htmlspecialchars($registro['repuestos_utilizados']); ?></p>
                        <?php endif; ?>
                        <?php if ($registro['costo_total'] > 0): ?>
                            <p><strong>Costo Total:</strong> $<?php echo number_format($registro['costo_total'], 2, ',', '.'); ?></p>
                        <?php endif; ?>
                        <?php if ($registro['observaciones']): ?>
                            <p><em><?php echo nl2br(htmlspecialchars($registro['observaciones'])); ?></em></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Sistema ROMA - Registro y Operación de Mantenimiento Automatizado</p>
        <p>Página {PAGENO} de {nb}</p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Nota: Para generar PDF real, se recomienda usar una librería como TCPDF, FPDF o DomPDF
// Por ahora, retornamos HTML que puede ser convertido a PDF por el navegador
// o usar un servicio de conversión

header('Content-Type: text/html; charset=UTF-8');
echo $html;

// Para usar con DomPDF (requiere instalación):
// require_once __DIR__ . '/../vendor/autoload.php';
// use Dompdf\Dompdf;
// $dompdf = new Dompdf();
// $dompdf->loadHtml($html);
// $dompdf->setPaper('A4', 'portrait');
// $dompdf->render();
// $dompdf->stream("hoja_vida_{$id_activo}.pdf", array("Attachment" => true));
?>

