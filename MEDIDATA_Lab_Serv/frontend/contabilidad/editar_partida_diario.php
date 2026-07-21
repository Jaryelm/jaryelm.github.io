<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/diario_edicion_lib.php';
require_once '../../backend/php/diario_tipo_etiqueta.php';

$rol = $_SESSION['rol'] ?? '';
if (!medidata_diario_puede_editar_partidas($rol)) {
    header('Location: diariogeneral_user.php');
    exit;
}

$numeroPartida = isset($_GET['numero_partida']) ? trim((string) $_GET['numero_partida']) : '';
$returnTo = isset($_GET['return_to']) ? (string) $_GET['return_to'] : '';
if ($numeroPartida === '') {
    $dest = ($rol === 'Contabilidad') ? 'diariogeneral_user.php' : 'diariogeneral.php';
    header('Location: ' . $dest);
    exit;
}

$esContabilidad = ($rol === 'Contabilidad');
$menuFile = $esContabilidad ? '../contabilidad/menu.php' : '../admin/menu.php';
$perfilFile = $esContabilidad ? '../contabilidad/perfil.php' : '../admin/perfil.php';
$retUrl = $returnTo !== ''
    ? $returnTo
    : ($esContabilidad ? 'diariogeneral_user.php' : 'diariogeneral.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <title>MEDIDATA - Editar Partida</title>
    <style>
        .form-partida .lineas-table .col-ref { min-width: 140px; }
        .aviso-auto {
            background: #fff3cd; color: #856404; padding: 10px 12px; border-radius: 6px;
            margin-bottom: 14px; font-size: 14px; border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
<?php include_once $menuFile; ?>
<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once $perfilFile; ?>
    </nav>
    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
        <button class="button" onclick="cambiarColor(this, '<?php echo $esContabilidad ? 'diariogeneral_user.php' : 'diariogeneral.php'; ?>')">Diario General</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo $esContabilidad ? 'partida_manual_user.php' : 'partida_manual.php'; ?>')">Partida Manual</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo $esContabilidad ? 'cuentas_por_pagar_user.php' : 'cuentas_por_pagar.php'; ?>')">Cuentas por Pagar</button>
        <br>

        <div class="form-partida">
            <h2>Editar partida <?php echo htmlspecialchars($numeroPartida); ?></h2>
            <div class="aviso-auto" id="avisoTipo">Cargando partida…</div>
            <form id="formPartidaDiario">
                <input type="hidden" id="numero_partida" name="numero_partida" value="<?php echo htmlspecialchars($numeroPartida); ?>">
                <input type="hidden" id="tipo_transaccion" name="tipo_transaccion" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo</label>
                        <input type="text" id="tipo_label" disabled>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Ocurrencia <span class="required">*</span></label>
                        <input type="date" id="fecha_ocurrencia" name="fecha_ocurrencia" required>
                    </div>
                    <div class="form-group">
                        <label>Referencia (cabecera)</label>
                        <input type="text" id="referencia" name="referencia" placeholder="Ej: COMP-729 o ref. bancaria">
                    </div>
                    <div class="form-group">
                        <label>Unidad de Servicio</label>
                        <select id="unidad_servicio" name="unidad_servicio" class="select2-unidad">
                            <option value="">Seleccione unidad...</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Descripción General <span class="required">*</span></label>
                        <input type="text" id="descripcion_general" name="descripcion_general" required>
                    </div>
                </div>
                <div class="form-row" id="grupoMotivo">
                    <div class="form-group" style="flex: 2;">
                        <label>Motivo de corrección <span class="required">*</span></label>
                        <textarea id="motivo" name="motivo" rows="2" maxlength="255" placeholder="Ej: Retención aplicada por error; proveedor con constancia PACTAS"></textarea>
                    </div>
                </div>
                <div class="form-group" id="grupoSyncCompra" style="display:none;margin-bottom:12px;">
                    <label style="display:flex;gap:8px;align-items:flex-start;font-weight:normal;">
                        <input type="checkbox" id="sync_compra_fecha_emision" value="1" checked>
                        <span>Sincronizar también la fecha de emisión en la compra vinculada (COMP-xxx).</span>
                    </label>
                </div>

                <h3 style="margin-top: 25px;">Líneas de la partida</h3>
                <p style="font-size:13px;color:#555;margin:0 0 8px;">En pagos a proveedor cada línea puede tener su propia referencia (ej. COMP-729 en el Debe y la ref. bancaria en el Haber/retención).</p>
                <div class="totales" id="totales">Total Debe: L. 0.00 | Total Haber: L. 0.00</div>
                <table class="lineas-table">
                    <thead>
                        <tr>
                            <th>Cuenta</th>
                            <th>Nombre Cuenta</th>
                            <th>Debe</th>
                            <th>Haber</th>
                            <th>Referencia</th>
                            <th>Descripción</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyLineas"></tbody>
                </table>
                <button type="button" class="btn-add" onclick="agregarLinea()">+ Agregar línea</button>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-save">Guardar Cambios</button>
                    <a href="<?php echo htmlspecialchars($retUrl); ?>" class="btn-cancel">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script>
var cuentasCatalogo = [];
var contadorLineas = 0;
var requiereMotivo = true;
var retUrl = <?php echo json_encode($retUrl); ?>;

function parseJsonResponse(response) {
    return response.text().then(function(text) {
        var data = null;
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            var raw = String(text || '').trim();
            var firstBrace = raw.indexOf('{');
            var lastBrace = raw.lastIndexOf('}');
            if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
                data = JSON.parse(raw.slice(firstBrace, lastBrace + 1));
            } else {
                throw new Error('Respuesta inválida del servidor (' + response.status + ')');
            }
        }
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e3) {}
        }
        if (!response.ok && (!data || typeof data.success === 'undefined')) {
            throw new Error('Error HTTP ' + response.status);
        }
        return data;
    });
}

$(document).ready(function() {
    cargarUnidadesServicio();
    cargarCuentas().then(function() { cargarPartida(); });
});

function cargarPartida() {
    var url = '../../backend/registros/obtener_partida_diario.php?numero_partida=' +
        encodeURIComponent(document.getElementById('numero_partida').value);
    fetch(url, { cache: 'no-store' }).then(parseJsonResponse).then(function(data) {
        if (!data.success) {
            Swal.fire('Error', data.message || 'Partida no encontrada', 'error').then(function() {
                location.href = retUrl;
            });
            return;
        }
        document.getElementById('tipo_transaccion').value = data.tipo_transaccion || '';
        document.getElementById('tipo_label').value = data.tipo_transaccion || '';
        document.getElementById('fecha_ocurrencia').value = data.fecha_ocurrencia || '';
        document.getElementById('referencia').value = data.referencia || '';
        document.getElementById('descripcion_general').value = data.descripcion_general || '';
        $('#unidad_servicio').val(data.unidad_servicio || '').trigger('change');
        requiereMotivo = data.requiere_motivo !== false;
        document.getElementById('grupoMotivo').style.display = requiereMotivo ? '' : 'none';
        document.getElementById('grupoSyncCompra').style.display =
            (data.tipo_transaccion || '') === 'COMPRA_PROVEEDOR' ? '' : 'none';

        var etiqueta = data.tipo_transaccion || '';
        document.getElementById('avisoTipo').textContent =
            'Tipo: ' + etiqueta + '. Puede corregir cuentas, montos, referencias y fecha. La partida debe quedar balanceada.';

        (data.lineas || []).forEach(function(l) { agregarLineaConDatos(l); });
        calcularTotales();
    }).catch(function(err) {
        Swal.fire('Error', (err && err.message) ? err.message : 'Error al cargar partida', 'error')
            .then(function() { location.href = retUrl; });
    });
}

function cargarUnidadesServicio() {
    fetch('../../backend/registros/listar_unidades_servicio.php?t=' + Date.now())
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var sel = $('#unidad_servicio');
            if (sel.data('select2')) sel.select2('destroy');
            sel.empty().append($('<option>').val('').text('Seleccione unidad...'));
            if (data.success && data.unidades) {
                data.unidades.forEach(function(u) {
                    sel.append($('<option>').val(u.id).text(u.nombre));
                });
            }
            sel.select2({ width: '100%', placeholder: 'Seleccione unidad...' });
        });
}

function cargarCuentas() {
    return fetch('../../backend/registros/lista_catalogo.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) cuentasCatalogo = data.cuentas || [];
        })
        .catch(function() { cuentasCatalogo = []; });
}

function agregarLinea() {
    agregarLineaConDatos({
        cuenta: '',
        nombre_cuenta: '',
        debe: 0,
        haber: 0,
        referencia: document.getElementById('referencia').value || '',
        descripcion: document.getElementById('descripcion_general').value
    });
}

function escAttr(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

function agregarLineaConDatos(datos) {
    contadorLineas++;
    var tr = document.createElement('tr');
    tr.dataset.idx = String(contadorLineas);
    var debeVal = (datos.debe || 0) > 0 ? parseFloat(datos.debe).toFixed(2) : '';
    var haberVal = (datos.haber || 0) > 0 ? parseFloat(datos.haber).toFixed(2) : '';
    var opts = cuentasCatalogo.map(function(c) {
        var sel = (c.cuenta || '') === (datos.cuenta || '') ? ' selected' : '';
        return '<option value="' + escAttr(c.cuenta || '') + '" data-nombre="' + escAttr(c.nombre || '') + '"' + sel + '>' +
            escAttr(c.cuenta || '') + ' - ' + escAttr(c.nombre || '') + '</option>';
    }).join('');
    if (datos.cuenta && !cuentasCatalogo.some(function(c) { return (c.cuenta || '') === (datos.cuenta || ''); })) {
        opts = '<option value="' + escAttr(datos.cuenta) + '" data-nombre="' + escAttr(datos.nombre_cuenta || '') + '" selected>' +
            escAttr(datos.cuenta) + ' - ' + escAttr(datos.nombre_cuenta || '') + '</option>' + opts;
    }
    tr.innerHTML =
        '<td class="col-cuenta"><select class="sel-cuenta select2-cuenta"><option value="">Seleccione...</option>' + opts + '</select></td>' +
        '<td class="col-nombre"><input type="text" class="inp-nombre" readonly value="' + escAttr(datos.nombre_cuenta || '') + '"></td>' +
        '<td class="col-debe"><input type="text" class="inp-debe" placeholder="0.00" value="' + debeVal + '" oninput="calcularTotales()"></td>' +
        '<td class="col-haber"><input type="text" class="inp-haber" placeholder="0.00" value="' + haberVal + '" oninput="calcularTotales()"></td>' +
        '<td class="col-ref"><input type="text" class="inp-ref" value="' + escAttr(datos.referencia || '') + '"></td>' +
        '<td class="col-desc"><input type="text" class="inp-desc" value="' + escAttr(datos.descripcion || '') + '"></td>' +
        '<td class="col-del"><button type="button" class="btn-del" onclick="eliminarLinea(this)">×</button></td>';
    document.getElementById('tbodyLineas').appendChild(tr);
    var sel = tr.querySelector('.sel-cuenta');
    $(sel).select2({ width: '100%', placeholder: 'Buscar cuenta...' })
        .on('change', function() { actualizarNombreCuenta(this); });
    if (datos.cuenta) {
        sel.value = datos.cuenta;
        $(sel).trigger('change');
    }
}

function actualizarNombreCuenta(sel) {
    var opt = sel.options[sel.selectedIndex];
    var nombre = opt ? (opt.getAttribute('data-nombre') || '') : '';
    sel.closest('tr').querySelector('.inp-nombre').value = nombre;
}

function eliminarLinea(btn) {
    if (document.getElementById('tbodyLineas').rows.length <= 2) {
        Swal.fire('Aviso', 'La partida debe tener al menos 2 líneas.', 'warning');
        return;
    }
    btn.closest('tr').remove();
    calcularTotales();
}

function calcularTotales() {
    var totalDebe = 0, totalHaber = 0;
    document.querySelectorAll('#tbodyLineas tr').forEach(function(tr) {
        totalDebe += parseFloat(String(tr.querySelector('.inp-debe').value).replace(/,/g, '')) || 0;
        totalHaber += parseFloat(String(tr.querySelector('.inp-haber').value).replace(/,/g, '')) || 0;
    });
    var diffCentavos = Math.round((totalDebe - totalHaber) * 100) / 100;
    var balanceado = diffCentavos === 0;
    var el = document.getElementById('totales');
    el.textContent = 'Total Debe: L. ' + totalDebe.toFixed(2) + ' | Total Haber: L. ' + totalHaber.toFixed(2) +
        ' | ' + (balanceado ? 'BALANCEADA' : 'DESBALANCEADA');
    el.className = 'totales ' + (balanceado ? 'balance-ok' : 'balance-error');
}

$('#formPartidaDiario').on('submit', function(e) {
    e.preventDefault();
    var btnSubmit = $(this).find('button[type="submit"]');
    if (btnSubmit.prop('disabled')) return;

    var motivo = ($('#motivo').val() || '').trim();
    if (requiereMotivo && !motivo) {
        Swal.fire('Aviso', 'Indique el motivo de la corrección.', 'warning');
        return;
    }

    var lineas = [];
    document.querySelectorAll('#tbodyLineas tr').forEach(function(tr) {
        var sel = tr.querySelector('.sel-cuenta');
        var cuenta = sel ? sel.value : '';
        var nombre = tr.querySelector('.inp-nombre').value || '';
        var debe = parseFloat(String(tr.querySelector('.inp-debe').value).replace(/,/g, '')) || 0;
        var haber = parseFloat(String(tr.querySelector('.inp-haber').value).replace(/,/g, '')) || 0;
        var ref = (tr.querySelector('.inp-ref').value || '').trim() || ($('#referencia').val() || '').trim();
        var desc = tr.querySelector('.inp-desc').value || document.getElementById('descripcion_general').value;
        if (cuenta && nombre && (debe > 0 || haber > 0)) {
            lineas.push({
                cuenta: cuenta,
                nombre_cuenta: nombre,
                debe: debe,
                haber: haber,
                referencia: ref,
                descripcion: desc
            });
        }
    });

    if (lineas.length < 2) {
        Swal.fire('Aviso', 'Debe haber al menos 2 líneas válidas.', 'warning');
        return;
    }

    btnSubmit.prop('disabled', true).data('texto-orig', btnSubmit.text()).text('Guardando...');

    var payload = {
        numero_partida: document.getElementById('numero_partida').value,
        tipo_transaccion: document.getElementById('tipo_transaccion').value,
        fecha_ocurrencia: document.getElementById('fecha_ocurrencia').value,
        referencia: document.getElementById('referencia').value,
        descripcion_general: document.getElementById('descripcion_general').value,
        unidad_servicio: document.getElementById('unidad_servicio').value,
        motivo: motivo || 'Actualización de partida',
        sync_compra_fecha_emision: document.getElementById('sync_compra_fecha_emision').checked ? 1 : 0,
        lineas: lineas
    };

    fetch('../../backend/registros/actualizar_partida_diario.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(parseJsonResponse)
    .then(function(data) {
        if (data.success) {
            Swal.fire('Éxito', data.message || 'Partida actualizada', 'success').then(function() {
                location.href = retUrl + (retUrl.indexOf('?') >= 0 ? '&' : '?') + 'search=' +
                    encodeURIComponent(document.getElementById('numero_partida').value);
            });
        } else {
            Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
            btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Guardar Cambios');
        }
    })
    .catch(function(err) {
        Swal.fire('Error', (err && err.message) ? err.message : 'Error de comunicación', 'error');
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Guardar Cambios');
    });
});
</script>
</body>
</html>
