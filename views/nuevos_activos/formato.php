<?php
$page_title = 'Formato de Inventario y Asignación';
require_once __DIR__ . '/../layout/header.php';
$hoy = date('Y-m-d');
?>

<div class="page-header">
    <h1><i class="fas fa-clipboard-list"></i> Formato de Inventario y Asignación</h1>
    <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panel</a>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Complete los datos del trabajador, seleccione los activos a asignar y agregue los movimientos de devolución/cambio/traslado si aplica.
    Al generar se abrirá el formato listo para <strong>descargar en PDF</strong> (imprimir / guardar como PDF).
</div>

<style>
.pagination-wrapper { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; }
.pagination-info { color: #6c757d; font-size: .9rem; }
.pagination { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
.pagination-link { padding: 6px 11px; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; color: #495057; text-decoration: none; font-size: .85rem; }
.pagination-link.active { background: #2563eb; color: #fff; border-color: #2563eb; font-weight: 600; }
.pagination-ellipsis { padding: 6px 4px; color: #6c757d; }
</style>
<form method="POST" action="<?php echo BASE_URL; ?>index.php?action=nuevos_activos&subaction=generar_formato" target="_blank">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user"></i> 1. Información del Trabajador</h3></div>
        <div class="card-body">
            <?php if (!empty($trabajadores)): ?>
                <div class="form-group form-group-full" style="margin-bottom:1rem;">
                    <label>Seleccionar trabajador registrado (autocompleta los campos y sus activos)</label>
                    <select id="selTrabajador" onchange="cargarTrabajador(this.value)">
                        <option value="">— Llenado manual —</option>
                        <?php foreach ($trabajadores as $tr): ?>
                            <option value="<?php echo (int)$tr['id_trabajador']; ?>" <?php echo ((int)($preTrabajador ?? 0) === (int)$tr['id_trabajador']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tr['nombre_completo'] . ' (' . $tr['documento_identidad'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Solo el super administrador registra trabajadores (Usuarios → Responsables de Activos).</small>
                </div>
            <?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre Completo <span class="required">*</span></label>
                    <input type="text" name="trab_nombre" id="trab_nombre" required placeholder="Ej: Juan Carlos Pérez">
                </div>
                <div class="form-group">
                    <label>Documento de Identidad <span class="required">*</span></label>
                    <input type="text" name="trab_documento" id="trab_documento" required placeholder="Ej: 1234567890">
                </div>
                <div class="form-group">
                    <label>Cargo</label>
                    <input type="text" name="trab_cargo" id="trab_cargo" placeholder="Ej: Auxiliar Administrativo">
                </div>
                <div class="form-group">
                    <label>Área / Dependencia</label>
                    <input type="text" name="trab_area" id="trab_area" placeholder="Ej: Sistemas" list="areasList">
                    <datalist id="areasList">
                        <?php foreach (ASSIGNED_AREAS as $a): ?><option value="<?php echo htmlspecialchars($a); ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Tipo de Vinculación</label>
                    <input type="text" name="trab_vinculacion" id="trab_vinculacion" placeholder="Ej: Contrato a término indefinido">
                </div>
                <div class="form-group">
                    <label>Centro de Costo</label>
                    <input type="text" name="trab_centro_costo" id="trab_centro_costo" placeholder="Ej: CC-001">
                </div>
                <div class="form-group">
                    <label>Jefe Inmediato</label>
                    <input type="text" name="trab_jefe" id="trab_jefe" placeholder="Ej: María García">
                </div>
                <div class="form-group">
                    <label>Fecha de Ingreso</label>
                    <input type="date" name="trab_ingreso" id="trab_ingreso">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-boxes"></i> 2. Activos Asignados <span class="required">*</span></h3></div>
        <div class="card-body">
            <div class="form-group">
                <label>Buscar en el listado</label>
                <input type="text" id="filtroActivos" placeholder="Filtrar por código, nombre, sede..." onkeyup="filtrarActivos()">
            </div>
            <?php if (empty($todosActivos)): ?>
                <div class="empty-state"><i class="fas fa-inbox"></i><p>No hay activos registrados. Cree activos primero.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table" id="tablaActivosFormato">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selTodos" onclick="seleccionarTodos(this.checked)" title="Seleccionar todos"></th>
                                <th>Código</th>
                                <th>Serial</th>
                                <th>Nombre</th>
                                <th>Grupo</th>
                                <th>Sede</th>
                                <th>Estado actual</th>
                                <th>Fecha entrega</th>
                                <th>Estado entrega</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todosActivos as $a): ?>
                                <tr>
                                    <td><input type="checkbox" name="activos_sel[]" value="<?php echo (int)$a['id_nuevo_activo']; ?>" class="chk-activo"></td>
                                    <td><strong><?php echo htmlspecialchars($a['codigo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($a['codigo_serial'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($a['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($a['categoria_nombre'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($a['sede'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($estados[$a['estado']] ?? $a['estado']); ?></td>
                                    <td><input type="date" name="entrega_fecha[<?php echo (int)$a['id_nuevo_activo']; ?>]" value="<?php echo $hoy; ?>"></td>
                                    <td>
                                        <select name="entrega_estado[<?php echo (int)$a['id_nuevo_activo']; ?>]">
                                            <?php foreach ($estados as $k => $v): ?>
                                                <option value="<?php echo htmlspecialchars($v); ?>" <?php echo $k === 'operativo' ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                                            <?php endforeach; ?>
                                            <option value="Nuevo">Nuevo</option>
                                            <option value="Bueno">Bueno</option>
                                            <option value="Regular">Regular</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="entrega_obs[<?php echo (int)$a['id_nuevo_activo']; ?>]" placeholder="Obs. entrega"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrapper" id="paginacionActivos" style="margin-top:1rem;">
                    <div class="pagination-info" id="infoPaginacionActivos"></div>
                    <nav class="pagination" id="botonesPaginacionActivos"></nav>
                </div>
                <p class="text-muted" style="margin-top:.5rem;">Seleccionados: <strong id="contadorSeleccionados">0</strong> activo(s) (la selección se conserva al cambiar de página).</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-exchange-alt"></i> 3. Control de Devolución / Cambio / Traslado</h3></div>
        <div class="card-body">
            <p class="text-muted" id="infoMovimientos"><?php echo !empty($movimientosPrevios) ? 'Se precargaron ' . count($movimientosPrevios) . ' movimiento(s) registrados del trabajador. Puede editarlos, quitarlos o agregar más.' : 'Opcional. Agregue filas según necesite.'; ?></p>
            <div class="table-responsive">
                <table class="data-table" id="tablaMovimientos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo de movimiento</th>
                            <th>Código del activo</th>
                            <th>Estado anterior</th>
                            <th>Estado nuevo</th>
                            <th>Responsable</th>
                            <th>Observación</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="movBody">
                        <?php
                        $tiposMovFmt = ['devolucion' => 'Devolución', 'cambio' => 'Cambio', 'traslado' => 'Traslado'];
                        $filasMov = [];
                        if (!empty($movimientosPrevios)) {
                            foreach ($movimientosPrevios as $mp) {
                                $filasMov[] = [
                                    'fecha' => $mp['fecha'] ?? '',
                                    'tipo' => $tiposMovFmt[$mp['tipo_movimiento'] ?? ''] ?? ($mp['tipo_movimiento'] ?? ''),
                                    'codigo' => ($mp['activo_codigo'] ?? '') !== '' ? $mp['activo_codigo'] : ($mp['codigo_activo'] ?? ''),
                                    'est_ant' => $mp['estado_anterior'] ?? '',
                                    'est_nuevo' => $mp['estado_nuevo'] ?? '',
                                    'resp' => $mp['responsable'] ?? '',
                                    'obs' => $mp['observacion'] ?? '',
                                ];
                            }
                        }
                        if (empty($filasMov)) {
                            $filasMov[] = ['fecha' => $hoy, 'tipo' => '', 'codigo' => '', 'est_ant' => '', 'est_nuevo' => '', 'resp' => '', 'obs' => ''];
                        }
                        ?>
                        <?php foreach ($filasMov as $fm): ?>
                        <tr class="mov-row">
                            <td><input type="date" name="mov_fecha[]" value="<?php echo htmlspecialchars($fm['fecha']); ?>"></td>
                            <td>
                                <select name="mov_tipo[]">
                                    <option value="">—</option>
                                    <?php foreach (['Devolución', 'Cambio', 'Traslado'] as $op): ?>
                                        <option <?php echo ($fm['tipo'] === $op) ? 'selected' : ''; ?>><?php echo $op; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="mov_codigo[]" placeholder="Ej: PC-001" value="<?php echo htmlspecialchars($fm['codigo']); ?>"></td>
                            <td><input type="text" name="mov_est_ant[]" placeholder="Ej: Bueno" value="<?php echo htmlspecialchars($fm['est_ant']); ?>"></td>
                            <td><input type="text" name="mov_est_nuevo[]" placeholder="Ej: Regular" value="<?php echo htmlspecialchars($fm['est_nuevo']); ?>"></td>
                            <td><input type="text" name="mov_resp[]" placeholder="Responsable" value="<?php echo htmlspecialchars($fm['resp']); ?>"></td>
                            <td><input type="text" name="mov_obs[]" placeholder="Observación" value="<?php echo htmlspecialchars($fm['obs']); ?>"></td>
                            <td><button type="button" class="btn-icon btn-danger" onclick="this.closest('tr').remove()" title="Quitar"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="agregarMovimiento()"><i class="fas fa-plus"></i> Agregar fila</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><i class="fas fa-pen"></i> 4. Observaciones y Firmas</h3></div>
        <div class="card-body">
            <div class="form-group form-group-full">
                <label>Observaciones generales</label>
                <textarea name="obs_generales" rows="3" placeholder="Observaciones generales del formato..."></textarea>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Fecha firma trabajador</label>
                    <input type="date" name="firma_trab_fecha" value="<?php echo $hoy; ?>">
                </div>
                <div class="form-group">
                    <label>Responsable de inventarios (nombre)</label>
                    <input type="text" name="resp_nombre" value="<?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Responsable de inventarios (cargo)</label>
                    <input type="text" name="resp_cargo" placeholder="Ej: Almacenista">
                </div>
                <div class="form-group">
                    <label>Fecha firma responsable</label>
                    <input type="date" name="resp_fecha" value="<?php echo $hoy; ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success"><i class="fas fa-file-pdf"></i> Generar Formato (PDF)</button>
                <a href="<?php echo BASE_URL; ?>index.php?action=nuevos_activos" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<script>
const ACTIVOS_POR_PAGINA = 10;
let paginaActivos = 1;

function filasActivos() {
    const t = document.getElementById('tablaActivosFormato');
    return t ? Array.from(t.querySelectorAll('tbody tr')) : [];
}
function filtrarActivos() {
    paginaActivos = 1;
    paginarActivos();
}
function irPaginaActivos(p) {
    paginaActivos = p;
    paginarActivos();
}
function actualizarContadorSeleccionados() {
    const n = document.querySelectorAll('#tablaActivosFormato .chk-activo:checked').length;
    const el = document.getElementById('contadorSeleccionados');
    if (el) el.textContent = n;
}
function paginarActivos() {
    const tabla = document.getElementById('tablaActivosFormato');
    if (!tabla) return;
    const f = (document.getElementById('filtroActivos').value || '').toLowerCase();
    const todas = filasActivos();
    const filtradas = todas.filter(function (tr) {
        return tr.innerText.toLowerCase().includes(f);
    });
    const totalPag = Math.max(1, Math.ceil(filtradas.length / ACTIVOS_POR_PAGINA));
    if (paginaActivos > totalPag) paginaActivos = totalPag;
    todas.forEach(function (tr) { tr.style.display = 'none'; });
    const ini = (paginaActivos - 1) * ACTIVOS_POR_PAGINA;
    filtradas.slice(ini, ini + ACTIVOS_POR_PAGINA).forEach(function (tr) { tr.style.display = ''; });
    // Info
    const info = document.getElementById('infoPaginacionActivos');
    if (info) {
        const desde = filtradas.length ? ini + 1 : 0;
        const hasta = Math.min(ini + ACTIVOS_POR_PAGINA, filtradas.length);
        info.textContent = 'Mostrando ' + desde + '–' + hasta + ' de ' + filtradas.length + ' activos · Página ' + paginaActivos + ' de ' + totalPag;
    }
    // Botones
    const nav = document.getElementById('botonesPaginacionActivos');
    if (nav) {
        let html = '';
        if (paginaActivos > 1) html += '<a class="pagination-link" href="#" onclick="irPaginaActivos(' + (paginaActivos - 1) + ');return false;">Anterior</a>';
        const paginas = [];
        for (let p = 1; p <= totalPag; p++) {
            if (p === 1 || p === totalPag || Math.abs(p - paginaActivos) <= 2) paginas.push(p);
        }
        let anterior = 0;
        paginas.forEach(function (p) {
            if (p - anterior > 1) html += '<span class="pagination-ellipsis">...</span>';
            html += (p === paginaActivos)
                ? '<span class="pagination-link active">' + p + '</span>'
                : '<a class="pagination-link" href="#" onclick="irPaginaActivos(' + p + ');return false;">' + p + '</a>';
            anterior = p;
        });
        if (paginaActivos < totalPag) html += '<a class="pagination-link" href="#" onclick="irPaginaActivos(' + (paginaActivos + 1) + ');return false;">Siguiente</a>';
        nav.innerHTML = html;
    }
    const sel = document.getElementById('selTodos');
    if (sel) sel.checked = false;
    actualizarContadorSeleccionados();
}
function seleccionarTodos(checked) {
    document.querySelectorAll('#tablaActivosFormato tbody tr:not([style*="none"]) .chk-activo').forEach(function (c) {
        c.checked = checked;
    });
    actualizarContadorSeleccionados();
}
document.addEventListener('DOMContentLoaded', function () {
    paginarActivos();
    document.querySelectorAll('#tablaActivosFormato .chk-activo').forEach(function (c) {
        c.addEventListener('change', actualizarContadorSeleccionados);
    });
});
function agregarMovimiento() {
    const tr = document.querySelector('#movBody .mov-row').cloneNode(true);
    tr.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    tr.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
    document.getElementById('movBody').appendChild(tr);
}
function escHtml(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function filaMovimiento(m) {
    const tipos = ['Devolución', 'Cambio', 'Traslado'];
    let opts = '<option value="">—</option>';
    tipos.forEach(function (t) {
        opts += '<option' + (m.tipo === t ? ' selected' : '') + '>' + t + '</option>';
    });
    return '<tr class="mov-row">'
        + '<td><input type="date" name="mov_fecha[]" value="' + escHtml(m.fecha || '') + '"></td>'
        + '<td><select name="mov_tipo[]">' + opts + '</select></td>'
        + '<td><input type="text" name="mov_codigo[]" placeholder="Ej: PC-001" value="' + escHtml(m.codigo || '') + '"></td>'
        + '<td><input type="text" name="mov_est_ant[]" placeholder="Ej: Bueno" value="' + escHtml(m.estado_anterior || '') + '"></td>'
        + '<td><input type="text" name="mov_est_nuevo[]" placeholder="Ej: Regular" value="' + escHtml(m.estado_nuevo || '') + '"></td>'
        + '<td><input type="text" name="mov_resp[]" placeholder="Responsable" value="' + escHtml(m.responsable || '') + '"></td>'
        + '<td><input type="text" name="mov_obs[]" placeholder="Observación" value="' + escHtml(m.observacion || '') + '"></td>'
        + '<td><button type="button" class="btn-icon btn-danger" onclick="this.closest(\'tr\').remove()" title="Quitar"><i class="fas fa-trash"></i></button></td>'
        + '</tr>';
}
function pintarMovimientos(movs) {
    const body = document.getElementById('movBody');
    const info = document.getElementById('infoMovimientos');
    if (!movs || !movs.length) {
        if (info) info.textContent = 'Opcional. Agregue filas según necesite.';
        return;
    }
    body.innerHTML = movs.map(filaMovimiento).join('');
    if (info) info.textContent = 'Se precargaron ' + movs.length + ' movimiento(s) registrados del trabajador. Puede editarlos, quitarlos o agregar más.';
}
function cargarTrabajador(id) {
    if (!id) return;
    fetch(window.BASE_URL + 'index.php?action=nuevos_activos&subaction=datos_trabajador&id=' + encodeURIComponent(id))
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.error) return;
            document.getElementById('trab_nombre').value = d.nombre_completo || '';
            document.getElementById('trab_documento').value = d.documento_identidad || '';
            document.getElementById('trab_cargo').value = d.cargo || '';
            document.getElementById('trab_area').value = d.dependencia || '';
            document.getElementById('trab_vinculacion').value = d.tipo_vinculacion || '';
            document.getElementById('trab_centro_costo').value = d.centro_costo || '';
            document.getElementById('trab_jefe').value = d.jefe_inmediato || '';
            document.getElementById('trab_ingreso').value = d.fecha_ingreso || '';
            // Preseleccionar sus activos asociados
            const ids = (d.activos || []).map(String);
            document.querySelectorAll('#tablaActivosFormato .chk-activo').forEach(function (c) {
                c.checked = ids.includes(c.value);
            });
            // Precargar su control de devolución / cambio / traslado
            pintarMovimientos(d.movimientos || []);
        });
}
<?php if (!empty($preTrabajador)): ?>
document.addEventListener('DOMContentLoaded', function () { cargarTrabajador('<?php echo (int)$preTrabajador; ?>'); });
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
