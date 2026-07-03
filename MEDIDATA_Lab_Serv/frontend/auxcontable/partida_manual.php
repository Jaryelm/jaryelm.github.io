<?php
include_once '../../backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <title>MEDIDATA - Partida Manual</title>
</head>
<body>
<?php include_once '../admin/menu.php'; ?>
<section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group">
                </div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>
        <button class="button" onclick="cambiarColor(this, 'catalogo.php')">Catálogo de Cuentas</button>
        <button class="button" onclick="cambiarColor(this, 'diariogeneral.php')">Diario General</button>
        <button class="button" onclick="cambiarColor(this, 'partida_manual.php')">Partida Manual</button>
        <button class="button" onclick="cambiarColor(this, 'transacciones.php')">Transacciones Capturadas</button>
        <button class="button" onclick="cambiarColor(this, '../contabilidad/cuentas_por_pagar.php')">Cuentas por Pagar</button>
        <br>

        <div class="form-partida">
            <h2>Nueva Partida Manual</h2>
            <form id="formPartidaManual">
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Ocurrencia <span class="required">*</span></label>
                        <input type="date" id="fecha_ocurrencia" name="fecha_ocurrencia" required>
                    </div>
                    <div class="form-group">
                        <label>Referencia <span class="required">*</span></label>
                        <input type="text" id="referencia" name="referencia" placeholder="Ej: AJ-2026-001" required>
                    </div>
                    <div class="form-group">
                        <label>Unidad de Servicio</label>
                        <select id="unidad_servicio" name="unidad_servicio" class="select2-unidad">
                            <option value="RRHH">RRHH</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Descripción General <span class="required">*</span></label>
                        <input type="text" id="descripcion_general" name="descripcion_general" placeholder="Ej: Ajuste por depreciación mensual" required>
                        <small style="color:#666;">Se replicará automáticamente en la descripción de cada línea.</small>
                    </div>
                </div>

                <h3 style="margin-top: 25px;">Líneas de la partida</h3>
                <div class="totales" id="totales">Total Debe: L. 0.00 | Total Haber: L. 0.00</div>
                <table class="lineas-table">
                    <thead>
                        <tr>
                            <th>Cuenta</th>
                            <th>Nombre Cuenta</th>
                            <th>Debe</th>
                            <th>Haber</th>
                            <th>Descripción</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyLineas">
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="agregarLinea()">+ Agregar línea</button>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-save">Registrar Partida</button>
                </div>
            </form>
        </div>

        <div class="catalog-container" style="margin-top: 30px;">
            <h2 class="catalog-title">Partidas Manuales Registradas</h2>
            <div class="filters-container">
                <div class="filter-group">
                    <label for="filtroDesde">Desde:</label>
                    <input type="date" id="filtroDesde" class="filter-input">
                </div>
                <div class="filter-group">
                    <label for="filtroHasta">Hasta:</label>
                    <input type="date" id="filtroHasta" class="filter-input">
                </div>
                <button type="button" class="btn-filter" onclick="aplicarFiltrosPartidas()">Buscar</button>
                <button type="button" class="btn-filter btn-reset" onclick="limpiarFiltrosPartidas()">Limpiar</button>
            </div>
            <div class="table-container">
                <div class="table-responsive">
                <table id="tablaPartidasManuales" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Partida #</th>
                            <th>Fecha</th>
                            <th>Referencia</th>
                            <th>Descripción</th>
                            <th>Total Debe</th>
                            <th>Total Haber</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script>
let cuentasCatalogo = [];
let contadorLineas = 0;

$(document).ready(function() {
    document.getElementById('fecha_ocurrencia').valueAsDate = new Date();
    $('#descripcion_general').on('input change', function() {
        var val = $(this).val();
        $('#tbodyLineas .inp-desc').val(val);
    });
    cargarCuentas().then(function() {
        var tb = document.getElementById('tbodyLineas');
        if (tb && tb.rows.length === 0) {
            agregarLinea();
            agregarLinea();
        }
    });
    cargarUnidadesServicio();
    initTablaPartidasManuales();
});

function cargarUnidadesServicio() {
    fetch('../../backend/registros/listar_unidades_servicio.php?t=' + Date.now())
        .then(r => r.json())
        .then(data => {
            var sel = $('#unidad_servicio');
            if (sel.data('select2')) sel.select2('destroy');
            sel.empty();
            if (data.success && data.unidades && data.unidades.length) {
                data.unidades.forEach(function(u) {
                    sel.append($('<option>').val(u.id).text(u.nombre));
                });
                sel.val('RRHH').trigger('change');
            } else {
                sel.append($('<option>').val('RRHH').text('RRHH'));
            }
            sel.select2({ width: '100%', placeholder: 'Seleccione departamento' });
        })
        .catch(() => {
            var sel = $('#unidad_servicio');
            if (sel.data('select2')) sel.select2('destroy');
            sel.select2({ width: '100%', placeholder: 'Seleccione departamento' });
        });
}

function cargarCuentas() {
    return fetch('../../backend/registros/lista_catalogo.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) cuentasCatalogo = data.cuentas || [];
        })
        .catch(() => { cuentasCatalogo = []; });
}

function agregarLinea() {
    contadorLineas++;
    const tr = document.createElement('tr');
    tr.dataset.idx = contadorLineas;
    tr.innerHTML = `
        <td class="col-cuenta">
            <select class="sel-cuenta select2-cuenta">
                <option value="">Seleccione...</option>
                ${cuentasCatalogo.map(c => `<option value="${(c.cuenta||'')}" data-nombre="${(c.nombre || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;')}">${(c.cuenta||'')} - ${(c.nombre || '').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</option>`).join('')}
            </select>
        </td>
        <td class="col-nombre"><input type="text" class="inp-nombre" readonly placeholder="Seleccione cuenta"></td>
        <td class="col-debe"><input type="text" class="inp-debe" placeholder="0.00" oninput="calcularTotales()"></td>
        <td class="col-haber"><input type="text" class="inp-haber" placeholder="0.00" oninput="calcularTotales()"></td>
        <td class="col-desc"><input type="text" class="inp-desc" placeholder="Se toma de Descripción General"></td>
        <td class="col-del"><button type="button" class="btn-del" onclick="eliminarLinea(this)">×</button></td>
    `;
    document.getElementById('tbodyLineas').appendChild(tr);
    tr.querySelector('.inp-desc').value = document.getElementById('descripcion_general').value;
    const sel = tr.querySelector('.sel-cuenta');
    $(sel).select2({ width: '100%', placeholder: 'Buscar cuenta...', language: { noResults: function() { return "No se encontraron resultados"; }, searching: function() { return "Buscando..."; } } })
        .on('change', function() { actualizarNombreCuenta(this); });
    calcularTotales();
}

function actualizarNombreCuenta(sel) {
    const opt = sel.options[sel.selectedIndex];
    const nombre = opt ? opt.getAttribute('data-nombre') || '' : '';
    const tr = sel.closest('tr');
    tr.querySelector('.inp-nombre').value = nombre;
    tr.querySelector('.inp-nombre').dataset.cuenta = sel.value;
}

function eliminarLinea(btn) {
    const tbody = document.getElementById('tbodyLineas');
    if (tbody.rows.length < 1) return;
    btn.closest('tr').remove();
    calcularTotales();
}

function resolverNombreCuentaLinea(cuenta, sel, nombreInput) {
    var n = (nombreInput || '').trim();
    if (n) return n;
    if (!cuenta) return '';
    var c = cuentasCatalogo.find(function(x) { return String(x.cuenta) === String(cuenta); });
    if (c && c.nombre) return String(c.nombre).trim();
    if (sel && sel.selectedIndex >= 0) {
        var opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            var t = (opt.textContent || '').trim();
            var i = t.indexOf(' - ');
            if (i !== -1) return t.substring(i + 3).trim();
        }
    }
    return '';
}

function calcularTotales() {
    let totalDebe = 0, totalHaber = 0;
    document.querySelectorAll('#tbodyLineas tr').forEach(tr => {
        const debe = parseFloat(String(tr.querySelector('.inp-debe').value).replace(/,/g, '')) || 0;
        const haber = parseFloat(String(tr.querySelector('.inp-haber').value).replace(/,/g, '')) || 0;
        totalDebe += debe;
        totalHaber += haber;
    });
    const diferencia = totalDebe - totalHaber;
    const diffCentavos = Math.round(diferencia * 100) / 100;
    const balanceado = diffCentavos === 0;
    const el = document.getElementById('totales');
    const estado = balanceado ? 'BALANCEADA' : 'DESBALANCEADA';
    el.textContent = `Total Debe: L. ${totalDebe.toFixed(2)} | Total Haber: L. ${totalHaber.toFixed(2)} | ${estado}`;
    el.className = 'totales ' + (balanceado ? 'balance-ok' : 'balance-error');
}

$('#formPartidaManual').on('submit', function(e) {
    e.preventDefault();
    var btnSubmit = $(this).find('button[type="submit"]');
    if (btnSubmit.prop('disabled')) return;
    btnSubmit.prop('disabled', true).data('texto-orig', btnSubmit.text()).text('Guardando...');
    const lineas = [];
    const numFilas = document.querySelectorAll('#tbodyLineas tr').length;
    document.querySelectorAll('#tbodyLineas tr').forEach(tr => {
        const sel = tr.querySelector('.sel-cuenta');
        const cuenta = sel ? String(sel.value || '').trim() : '';
        const nombre = resolverNombreCuentaLinea(cuenta, sel, tr.querySelector('.inp-nombre').value);
        const debe = parseFloat(String(tr.querySelector('.inp-debe').value).replace(/,/g, '')) || 0;
        const haber = parseFloat(String(tr.querySelector('.inp-haber').value).replace(/,/g, '')) || 0;
        const desc = tr.querySelector('.inp-desc').value || document.getElementById('descripcion_general').value;
        if (cuenta && nombre && (debe > 0 || haber > 0)) {
            lineas.push({ cuenta, nombre_cuenta: nombre, debe, haber, descripcion: desc });
        }
    });
    if (lineas.length < 2) {
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Registrar Partida');
        var msg = 'En una partida manual el diario debe cuadrar: agregue al menos 2 líneas con cuenta y monto, de modo que la suma del Debe sea igual a la suma del Haber (un mismo asiento con varios movimientos).';
        msg += ' Ahora hay ' + lineas.length + ' línea(s) válida(s)' + (numFilas ? ' (' + numFilas + ' filas en la tabla).' : '.');
        msg += ' Use "+ Agregar línea" si hace falta.';
        Swal.fire('Error', msg, 'error');
        return;
    }
    const totalDebe = lineas.reduce((s, l) => s + l.debe, 0);
    const totalHaber = lineas.reduce((s, l) => s + l.haber, 0);
    const diffCentavos = Math.round((totalDebe - totalHaber) * 100) / 100;
    if (diffCentavos !== 0) {
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Registrar Partida');
        Swal.fire('Error', 'La partida debe estar balanceada (Total Debe = Total Haber). Diferencia: L. ' + diffCentavos.toFixed(2), 'error');
        return;
    }
    const payload = {
        fecha_ocurrencia: document.getElementById('fecha_ocurrencia').value,
        referencia: document.getElementById('referencia').value.trim(),
        descripcion_general: document.getElementById('descripcion_general').value.trim(),
        unidad_servicio: ($('#unidad_servicio').val() || '').trim() || 'RRHH',
        lineas: lineas
    };
    fetch('../../backend/registros/registrar_partida_manual.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r) {
        return r.text().then(function(text) {
            var data = null;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                throw new Error(r.status >= 400 ? ('Error del servidor (' + r.status + '). Revise el log de PHP en XAMPP si la respuesta no es JSON.') : 'Respuesta inválida del servidor.');
            }
            if (!r.ok && (!data || typeof data.success === 'undefined')) {
                throw new Error('Error HTTP ' + r.status + (text ? (': ' + text.substring(0, 180)) : ''));
            }
            return data;
        });
    })
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', 'Partida ' + data.numero_partida + ' registrada correctamente', 'success');
            document.getElementById('formPartidaManual').reset();
            document.getElementById('fecha_ocurrencia').valueAsDate = new Date();
            $('#unidad_servicio').val('RRHH').trigger('change');
            $('#tbodyLineas .sel-cuenta').each(function() { if ($(this).data('select2')) $(this).select2('destroy'); });
            document.getElementById('tbodyLineas').innerHTML = '';
            agregarLinea();
            agregarLinea();
            cargarTablaPartidasManuales();
        } else {
            Swal.fire('Error', data.message || 'Error al registrar', 'error');
        }
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Registrar Partida');
    })
    .catch(err => {
        Swal.fire('Error', (err && err.message) ? err.message : 'No se pudo completar la petición (red o servidor).', 'error');
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Registrar Partida');
    });
});

var tablaPartidasManuales;

/** Una sola petición agrupada (simple=1) + DataTables en cliente: menos roundtrips al pool MySQL que serverSide por draw. */
function paramsPartidasManualesCarga() {
    return {
        simple: '1',
        limite: '3500',
        fechaDesde: $('#filtroDesde').val() || '',
        fechaHasta: $('#filtroHasta').val() || ''
    };
}

function cargarTablaPartidasManuales() {
    var $t = $('#tablaPartidasManuales');
    if (!$t.length) {
        return;
    }
    if ($.fn.DataTable.isDataTable($t)) {
        $t.DataTable().destroy();
    }
    tablaPartidasManuales = null;
    $t.find('tbody').empty();
    var $wrap = $t.closest('.table-responsive');
    $('#partidasManualCargando').remove();
    $('#partidasManualNotaLimite').remove();
    var $busy = $('<div id="partidasManualCargando" class="dt-medidata-processing" style="display:flex;align-items:center;gap:12px;padding:12px;"><div class="dt-medidata-spinner" aria-hidden="true"></div><p style="margin:0;">Cargando partidas…</p></div>');
    $wrap.before($busy);

    $.getJSON('../contabilidad/get_partidas_manuales.php', paramsPartidasManualesCarga())
        .done(function(resp) {
            $('#partidasManualCargando').remove();
            var rows = resp && $.isArray(resp.data) ? resp.data : [];

            tablaPartidasManuales = $t.DataTable({
                data: rows,
                processing: false,
                serverSide: false,
                dom: 'frtip',
                columns: [
                    { data: 'numero_partida' },
                    { data: 'fecha_ocurrencia' },
                    { data: 'referencia' },
                    { data: 'descripcion' },
                    {
                        data: 'total_debe',
                        className: 'text-right',
                        render: function(data) {
                            var v = parseFloat(data) || 0;
                            return 'L. ' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        }
                    },
                    {
                        data: 'total_haber',
                        className: 'text-right',
                        render: function(data) {
                            var v = parseFloat(data) || 0;
                            return 'L. ' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        }
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                language: {
                    lengthMenu: "Mostrar _MENU_ registros",
                    zeroRecords: "No hay partidas manuales registradas",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros locales)",
                    search: "Buscar:",
                    paginate: { first: "Primero", previous: "Anterior", next: "Siguiente", last: "Último" }
                },
                scrollX: true
            });

            if (rows.length >= 3500) {
                $('<p id="partidasManualNotaLimite" style="margin:8px 0 0;color:#666;font-size:13px;"></p>')
                    .text('Se muestran hasta 3500 partidas agrupadas. Use filtros por fecha si no aparece alguna esperada.')
                    .insertBefore($wrap);
            }
        })
        .fail(function(jqXHR) {
            $('#partidasManualCargando').remove();
            var msg = 'No se pudo cargar el listado.';
            if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.error) {
                msg = jqXHR.responseJSON.error;
            }
            Swal.fire('Error', msg, 'error');
        });
}

function initTablaPartidasManuales() {
    cargarTablaPartidasManuales();
}

function aplicarFiltrosPartidas() {
    cargarTablaPartidasManuales();
}

function limpiarFiltrosPartidas() {
    document.getElementById('filtroDesde').value = '';
    document.getElementById('filtroHasta').value = '';
    cargarTablaPartidasManuales();
}
</script>
</body>
</html>
