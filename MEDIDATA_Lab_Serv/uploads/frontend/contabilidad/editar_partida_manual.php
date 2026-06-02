<?php
include_once '../../backend/registros/session_check.php';
$rol = $_SESSION['rol'] ?? '';
// Solo Contabilidad y Administrador pueden editar partidas manuales
if ($rol === 'Auxiliar Contable') {
    header('Location: ../auxcontable/partida_manual_user.php');
    exit;
}
$numeroPartida = isset($_GET['numero_partida']) ? trim($_GET['numero_partida']) : '';
$returnTo = isset($_GET['return_to']) ? $_GET['return_to'] : '';
if (empty($numeroPartida)) {
    $dest = ($rol === 'Auxiliar Contable') ? '../auxcontable/partida_manual_user.php' : (($rol === 'Contabilidad') ? 'partida_manual_user.php' : 'partida_manual.php');
    header('Location: ' . $dest);
    exit;
}
$esAuxContable = ($rol === 'Auxiliar Contable');
$esContabilidad = ($rol === 'Contabilidad');
$menuFile = $esAuxContable ? '../auxcontable/menu.php' : ($esContabilidad ? '../contabilidad/menu.php' : '../admin/menu.php');
$perfilFile = $esAuxContable ? '../auxcontable/perfil.php' : ($esContabilidad ? '../contabilidad/perfil.php' : '../admin/perfil.php');
$retUrl = $returnTo ?: ($esAuxContable ? '../auxcontable/partida_manual_user.php' : ($esContabilidad ? 'partida_manual_user.php' : 'partida_manual.php'));
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
    <title>MEDIDATA - Editar Partida Manual</title>
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
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>
        <button class="button" onclick="cambiarColor(this, '<?php echo $esAuxContable ? '../auxcontable/catalogo_user.php' : ($esContabilidad ? 'catalogo_user.php' : 'catalogo.php'); ?>')">Catálogo de Cuentas</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo $esAuxContable ? '../auxcontable/diariogeneral_user.php' : ($esContabilidad ? 'diariogeneral_user.php' : 'diariogeneral.php'); ?>')">Diario General</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo $esAuxContable ? '../auxcontable/partida_manual_user.php' : ($esContabilidad ? 'partida_manual_user.php' : 'partida_manual.php'); ?>')">Partida Manual</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo $esAuxContable ? '../auxcontable/transacciones_user.php' : ($esContabilidad ? 'transacciones_user.php' : 'transacciones.php'); ?>')">Transacciones Capturadas</button>
        <br>

        <div class="form-partida">
            <h2>Editar Partida Manual <?php echo htmlspecialchars($numeroPartida); ?></h2>
            <form id="formPartidaManual">
                <input type="hidden" id="numero_partida" name="numero_partida" value="<?php echo htmlspecialchars($numeroPartida); ?>">
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
let cuentasCatalogo = [];
let contadorLineas = 0;

function parseJsonResponse(response) {
    return response.text().then(function(text) {
        var data = null;
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            // Fallback: algunos endpoints inyectan texto antes/después del JSON válido.
            var raw = String(text || '').trim();
            var firstBrace = raw.indexOf('{');
            var lastBrace = raw.lastIndexOf('}');
            if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
                try {
                    data = JSON.parse(raw.slice(firstBrace, lastBrace + 1));
                } catch (e2) {
                    var snippet2 = raw.replace(/\s+/g, ' ').substring(0, 260);
                    throw new Error(
                        response.status >= 400
                            ? ('Error del servidor (' + response.status + '). ' + (snippet2 || 'Respuesta no JSON.'))
                            : ('Respuesta inválida del servidor. ' + (snippet2 || ''))
                    );
                }
            } else {
                var snippet = raw.replace(/\s+/g, ' ').substring(0, 260);
                throw new Error(
                    response.status >= 400
                        ? ('Error del servidor (' + response.status + '). ' + (snippet || 'Respuesta no JSON.'))
                        : ('Respuesta inválida del servidor. ' + (snippet || ''))
                );
            }
        }
        // Compatibilidad: si el backend devuelve JSON doblemente serializado.
        if (typeof data === 'string') {
            try {
                data = JSON.parse(data);
            } catch (e3) {
                // Se mantiene string y seguirá al flujo de error manejado abajo.
            }
        }
        if (!response.ok && (!data || typeof data.success === 'undefined')) {
            throw new Error('Error HTTP ' + response.status);
        }
        return data;
    });
}

function fetchJsonWithRetry(url, options, retries) {
    return fetch(url, options).then(parseJsonResponse).catch(function(err) {
        if (retries > 0) {
            return fetchJsonWithRetry(url, options, retries - 1);
        }
        throw err;
    });
}

$(document).ready(function() {
    cargarUnidadesServicio();
    cargarCuentas().then(function() { cargarPartida(); });
});

function cargarPartida() {
    var url = '../../backend/registros/obtener_partida_manual.php?numero_partida=' + encodeURIComponent(document.getElementById('numero_partida').value);
    fetchJsonWithRetry(url, { cache: 'no-store' }, 1)
        .then(data => {
            // Compatibilidad: algunos backends responden sin bandera success.
            if (typeof data.success === 'undefined' && data && data.numero_partida && Array.isArray(data.lineas)) {
                data.success = true;
            }
            if (!data.success) {
                Swal.fire('Error', data.message || 'Partida no encontrada', 'error').then(() => location.href = 'partida_manual.php');
                return;
            }
            document.getElementById('fecha_ocurrencia').value = data.fecha_ocurrencia;
            document.getElementById('referencia').value = data.referencia || '';
            document.getElementById('descripcion_general').value = data.descripcion_general || '';
            $('#unidad_servicio').val(data.unidad_servicio || '').trigger('change');
            (data.lineas || []).forEach(function(l) {
                agregarLineaConDatos(l);
            });
            calcularTotales();
        })
        .catch(function(err) {
            var msg = (err && err.message) ? err.message : 'Error al cargar partida';
            Swal.fire('Error', msg, 'error').then(() => location.href = 'partida_manual.php');
        });
}

function cargarUnidadesServicio() {
    fetch('../../backend/registros/listar_unidades_servicio.php?t=' + Date.now())
        .then(r => r.json())
        .then(data => {
            var sel = $('#unidad_servicio');
            if (sel.data('select2')) sel.select2('destroy');
            sel.empty();
            sel.append($('<option>').val('').text('Seleccione unidad...'));
            if (data.success && data.unidades && data.unidades.length) {
                data.unidades.forEach(function(u) {
                    sel.append($('<option>').val(u.id).text(u.nombre));
                });
            }
            sel.select2({ width: '100%', placeholder: 'Seleccione unidad...' });
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
    agregarLineaConDatos({ cuenta: '', nombre_cuenta: '', debe: 0, haber: 0, descripcion: document.getElementById('descripcion_general').value });
}

function agregarLineaConDatos(datos) {
    contadorLineas++;
    const tr = document.createElement('tr');
    tr.dataset.idx = contadorLineas;
    const debeVal = (datos.debe || 0) > 0 ? parseFloat(datos.debe).toFixed(2) : '';
    const haberVal = (datos.haber || 0) > 0 ? parseFloat(datos.haber).toFixed(2) : '';
    var opts = cuentasCatalogo.map(c => {
        var sel = (c.cuenta || '') === (datos.cuenta || '') ? ' selected' : '';
        return '<option value="' + (c.cuenta||'') + '" data-nombre="' + (c.nombre || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;') + '"' + sel + '>' + (c.cuenta||'') + ' - ' + (c.nombre || '').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</option>';
    }).join('');
    if (datos.cuenta && !cuentasCatalogo.some(c => (c.cuenta||'') === (datos.cuenta||''))) {
        opts = '<option value="' + (datos.cuenta||'').replace(/"/g,'&quot;') + '" data-nombre="' + (datos.nombre_cuenta || '').replace(/"/g,'&quot;').replace(/</g,'&lt;') + '" selected>' + (datos.cuenta||'') + ' - ' + (datos.nombre_cuenta || '').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</option>' + opts;
    }
    tr.innerHTML = '<td class="col-cuenta"><select class="sel-cuenta select2-cuenta"><option value="">Seleccione...</option>' + opts + '</select></td><td class="col-nombre"><input type="text" class="inp-nombre" readonly value="' + (datos.nombre_cuenta || '').replace(/"/g,'&quot;') + '"></td><td class="col-debe"><input type="text" class="inp-debe" placeholder="0.00" value="' + debeVal + '" oninput="calcularTotales()"></td><td class="col-haber"><input type="text" class="inp-haber" placeholder="0.00" value="' + haberVal + '" oninput="calcularTotales()"></td><td class="col-desc"><input type="text" class="inp-desc" value="' + (datos.descripcion || '').replace(/"/g,'&quot;') + '"></td><td class="col-del"><button type="button" class="btn-del" onclick="eliminarLinea(this)">×</button></td>';
    document.getElementById('tbodyLineas').appendChild(tr);
    const sel = tr.querySelector('.sel-cuenta');
    $(sel).select2({ width: '100%', placeholder: 'Buscar cuenta...' })
        .on('change', function() { actualizarNombreCuenta(this); });
    if (datos.cuenta) { sel.value = datos.cuenta; $(sel).trigger('change'); }
}

function actualizarNombreCuenta(sel) {
    const opt = sel.options[sel.selectedIndex];
    const nombre = opt ? opt.getAttribute('data-nombre') || '' : '';
    const tr = sel.closest('tr');
    tr.querySelector('.inp-nombre').value = nombre;
}

function eliminarLinea(btn) {
    if (document.getElementById('tbodyLineas').rows.length < 1) return;
    btn.closest('tr').remove();
    calcularTotales();
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
    document.querySelectorAll('#tbodyLineas tr').forEach(tr => {
        const sel = tr.querySelector('.sel-cuenta');
        const cuenta = sel ? sel.value : '';
        const nombre = tr.querySelector('.inp-nombre').value || '';
        const debe = parseFloat(String(tr.querySelector('.inp-debe').value).replace(/,/g, '')) || 0;
        const haber = parseFloat(String(tr.querySelector('.inp-haber').value).replace(/,/g, '')) || 0;
        const desc = tr.querySelector('.inp-desc').value || document.getElementById('descripcion_general').value;
        if (cuenta && nombre && (debe > 0 || haber > 0)) {
            lineas.push({ cuenta, nombre_cuenta: nombre, debe, haber, descripcion: desc });
        }
    });
    if (lineas.length < 2) {
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Guardar Cambios');
        Swal.fire('Error', 'Debe agregar al menos 2 líneas válidas', 'error');
        return;
    }
    const totalDebe = lineas.reduce((s, l) => s + l.debe, 0);
    const totalHaber = lineas.reduce((s, l) => s + l.haber, 0);
    const diffCentavos = Math.round((totalDebe - totalHaber) * 100) / 100;
    if (diffCentavos !== 0) {
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Guardar Cambios');
        Swal.fire('Error', 'La partida debe estar balanceada (Total Debe = Total Haber). Diferencia: L. ' + diffCentavos.toFixed(2), 'error');
        return;
    }
    const unidadServicio = ($('#unidad_servicio').val() || '').trim();
    if (!unidadServicio) {
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Guardar Cambios');
        Swal.fire('Error', 'Debe seleccionar una Unidad de Servicio', 'error');
        return;
    }
    const payload = {
        numero_partida: document.getElementById('numero_partida').value,
        fecha_ocurrencia: document.getElementById('fecha_ocurrencia').value,
        referencia: document.getElementById('referencia').value.trim(),
        descripcion_general: document.getElementById('descripcion_general').value.trim(),
        unidad_servicio: unidadServicio,
        lineas: lineas
    };
    fetch('../../backend/registros/actualizar_partida_manual.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(parseJsonResponse)
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', 'Partida actualizada correctamente', 'success').then(() => location.href = '<?php echo addslashes($retUrl); ?>');
        } else {
            Swal.fire('Error', data.message || 'Error al actualizar', 'error');
        }
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Guardar Cambios');
    })
    .catch(err => {
        var msg = (err && err.message) ? err.message : 'No se pudo completar la petición (red o servidor).';
        Swal.fire('Error', msg, 'error');
        btnSubmit.prop('disabled', false).text(btnSubmit.data('texto-orig') || 'Guardar Cambios');
    });
});
</script>
</body>
</html>
