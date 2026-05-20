<?php
include_once '../../backend/registros/session_check.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="../../backend/vendor/datatables/dataTables.bs4.css" />
    <link href="../../backend/vendor/datatables/buttons.bs.css" rel="stylesheet" />
    
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <title>MEDIDATA - Pre-Clínica</title>
</head>
<body>
    <?php include_once '../admin/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>

        <main>
            <?php
            $hora_actual = (int) date('H');
            if ($hora_actual >= 6 && $hora_actual < 12) {
                $saludo = 'Buenos Días';
            } elseif ($hora_actual >= 12 && $hora_actual < 18) {
                $saludo = 'Buenas Tardes';
            } else {
                $saludo = 'Buenas Noches';
            }
            $display_name = htmlspecialchars((string) ($name ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . $display_name . '</strong>'; ?></h1>

            <div class="data preclinica-stack">
                <div class="content-data">
                    <div class="head">
                        <div>
                            <h3>Pre-Clínica</h3>
                        </div>
                    </div>
                    <hr class="preclinica-section-divider">

                    <form id="form-pre-clinica">
                    <div class="head"><h3>Tipo de Paciente</h3></div>
                    <div class="selection-group">
                        <div class="radio-options">
                            <label class="radio-item">
                                <input type="radio" name="tipo_paciente" value="paciente" checked>
                                <span>Hospitalario</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="tipo_paciente" value="ambulatorio">
                                <span>Ambulatorio</span>
                            </label>
                        </div>

                        <div id="wrapper_patients" class="form-group">
                            <label for="patients">Seleccionar Paciente:</label>
                            <div id="slot_patients" class="preclinica-select-slot is-loading">
                            <select name="id_paciente_hosp" id="patients">
                                <option value="">&nbsp;</option>
                            </select>
                            </div>
                        </div>

                        <div id="wrapper_outpatients" class="form-group" style="display: none;">
                            <label for="outpatients">Seleccionar Paciente:</label>
                            <div id="slot_outpatients" class="preclinica-select-slot is-loading">
                            <select name="id_paciente_amb" id="outpatients">
                                <option value="">&nbsp;</option>
                            </select>
                            </div>
                        </div>
                    </div>

                    <div class="preclinica-actions">
                        <button type="button" id="btn_fetch_vitals" class="button preclinica-main-btn">
                            Consultar signos vitales
                        </button>
                    </div>
                    </form>
                </div>

            <div id="vitals_display_area">
                <div class="preclinica-split">
                    <div class="content-data preclinica-col-form">
                        <div class="head"><h3>Registrar Nuevos Signos Vitales</h3></div>
                        <hr class="preclinica-section-divider">
                            <form id="vitals-form">
                                <div id="vitals-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                    <div class="form-group">
                                        <label for="weight_kg">Peso (KG)</label>
                                        <input type="number" step="0.01" id="weight_kg" class="form-control" placeholder="Ej: 70.5" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="stature_cm">Talla (CM)</label>
                                        <input type="number" step="0.01" id="stature_cm" class="form-control" placeholder="Ej: 175" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Presión Arterial (mmHg)</label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="number" id="bp_sys" class="form-control" placeholder="120" required style="width: 45%;">
                                            <span style="font-size: 1.5rem; font-weight: bold; color: #555;">/</span>
                                            <input type="number" id="bp_dia" class="form-control" placeholder="80" required style="width: 45%;">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Frec. Cardíaca (lpm)</label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="number" id="hr_1" class="form-control" placeholder="60" required style="width: 45%;">
                                            <span style="font-size: 1.5rem; font-weight: bold; color: #555;">/</span>
                                            <input type="number" id="hr_2" class="form-control" placeholder="100" required style="width: 45%;">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Frec. Respiratoria (rpm)</label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="number" id="rr_1" class="form-control" placeholder="14" required style="width: 45%;">
                                            <span style="font-size: 1.5rem; font-weight: bold; color: #555;">/</span>
                                            <input type="number" id="rr_2" class="form-control" placeholder="16" required style="width: 45%;">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Saturación (SatO2)</label>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="number" id="sat_1" class="form-control" placeholder="96" required style="width: 45%;">
                                            <span style="font-size: 1.5rem; font-weight: bold; color: #555;">/</span>
                                            <input type="number" id="sat_2" class="form-control" placeholder="80" required style="width: 45%;">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="temp_c">Temperatura (°C)</label>
                                        <input type="number" step="0.1" id="temp_c" class="form-control" placeholder="Ej: 36.5" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="map_pressure">P.A. Media (PAM)</label>
                                        <input type="text" id="map_pressure" class="form-control" value="N/A" required>
                                    </div>
                                    
                                    <div class="form-group" style="grid-column: span 2;">
                                        <label for="glucose_mg">Glucosa (mg/dL)</label>
                                        <input type="number" step="0.01" id="glucose_mg" class="form-control" placeholder="Ej: 110" required>
                                    </div>
                                </div>
                                <div class="preclinica-form-actions">
                                <button type="submit" id="btn_save_vitals" class="register-btn preclinica-save-vitals-btn" disabled>
                                    Guardar signos vitales
                                </button>
                                </div>
                            </form>
                    </div>

                </div>

                <div class="content-data preclinica-historial-full">
                    <div class="head">
                        <h3>Registro de Signos Vitales</h3>
                    </div>
                    <hr class="preclinica-section-divider">
                    <div class="table-responsive sv-dt-expediente-wrap">
                        <table id="table_vitals_historial" class="display table-striped preclinica-dt" style="width:100%">
                            <thead>
                                <tr>
                                    <th>FECHA</th>
                                    <th>HORA</th>
                                    <th>REALIZADO POR</th>
                                    <th>REVISADO POR</th>
                                    <th>PESO (kg/lb)</th>
                                    <th>TALLA (cm/in)</th>
                                    <th>PA (mmHg)</th>
                                    <th>PAM</th>
                                    <th>FC (lpm)</th>
                                    <th>FR (rpm)</th>
                                    <th>SAT (SatO2)</th>
                                    <th>TEMP (°C / °F)</th>
                                    <th>GLUCOSA (mg/dL / mmol/L)</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody id="vitals-historial-body">
                                <tr class="sv-dt-placeholder-row"><td colspan="14" style="text-align:center;">Seleccione un paciente y pulse consultar</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            </div>

        </main>
    </section>

    <!-- Scripts -->
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

    <!-- DataTables Scripts -->
    <script src="../../backend/vendor/datatables/dataTables.min.js"></script>
    <script src="../../backend/vendor/datatables/dataTables.bootstrap.min.js"></script>
    <script src="../../backend/vendor/datatables/buttons.min.js"></script>
    <script src="../../backend/vendor/datatables/jszip.min.js"></script>
    <!-- pdfmake + vfs deben ser el par del mismo sitio; vendor/datatables rompe con transformDictionaryWord si no coinciden -->
    <script src="../../backend/js/pdfmake.js"></script>
    <script src="../../backend/js/vfs_fonts.js"></script>
    <script src="../../backend/vendor/datatables/html5.min.js"></script>
    <script src="../../backend/vendor/datatables/buttons.print.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            const PRECLINICA_API = '../../backend/registros/';
            const FUNCIONES_PAC = '../../frontend/funciones/';
            let dataTableHistorial = null;
            let outpatientsSelect2Ready = false;
            let catalogsLoaded = false;

            const select2Opts = {
                placeholder: 'Seleccione un paciente',
                allowClear: false,
                width: '100%'
            };

            function resetHistoriaTablePlaceholder() {
                if (dataTableHistorial) {
                    dataTableHistorial.destroy();
                    dataTableHistorial = null;
                }
                $('#vitals-historial-body').html(
                    '<tr class="sv-dt-placeholder-row"><td colspan="14" style="text-align:center;">Seleccione un paciente y pulse consultar</td></tr>'
                );
            }

            function initOutpatientsSelect2IfNeeded() {
                if (!catalogsLoaded || outpatientsSelect2Ready) return;
                $('#outpatients').select2(select2Opts);
                outpatientsSelect2Ready = true;
                $('#slot_outpatients').removeClass('is-loading');
            }

            $.when($.post(FUNCIONES_PAC + 'cat_patients.php'), $.post(FUNCIONES_PAC + 'cat_outpatients.php'))
                .done(function (resPatients, resOut) {
                    $('#patients').html(resPatients[0]);
                    $('#outpatients').html(resOut[0]);
                    catalogsLoaded = true;

                    $('#patients').select2(select2Opts);
                    $('#slot_patients').removeClass('is-loading');

                    if ($('input[name="tipo_paciente"]:checked').val() === 'ambulatorio') {
                        initOutpatientsSelect2IfNeeded();
                    } else {
                        $('#slot_outpatients').removeClass('is-loading');
                    }
                })
                .fail(function () {
                    $('#slot_patients, #slot_outpatients').removeClass('is-loading');
                    swal('Error', 'No se pudieron cargar los listados de pacientes.', 'error');
                });

            // Cambio de tipo de paciente
            $('input[name="tipo_paciente"]').change(function() {
                const tipo = $(this).val();
                if (tipo === 'paciente') {
                    $('#wrapper_patients').show();
                    $('#wrapper_outpatients').hide();
                } else {
                    $('#wrapper_patients').hide();
                    $('#wrapper_outpatients').show();
                    initOutpatientsSelect2IfNeeded();
                }
                $('#btn_save_vitals').prop('disabled', true);
                resetHistoriaTablePlaceholder();
            });

            // Resetear estado al cambiar de paciente en el select
            $('#patients, #outpatients').on('change', function() {
                $('#btn_save_vitals').prop('disabled', true);
                resetHistoriaTablePlaceholder();
            });

            // Consultar datos
            $('#btn_fetch_vitals').click(function() {
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                if (!id || id === '0') {
                    swal('Aviso', 'Seleccione un paciente primero.', 'warning');
                    return;
                }

                $('#btn_save_vitals').prop('disabled', false);
                cargarVitals(tipo, id);
            });

            function cargarVitals(tipo, id) {
                if (dataTableHistorial) {
                    dataTableHistorial.destroy();
                    dataTableHistorial = null;
                }

                dataTableHistorial = $('#table_vitals_historial').DataTable({
                    ajax: {
                        url: PRECLINICA_API + 'pre_clinica_fetch_vitals.php',
                        data: { id, tipo },
                        dataSrc: function (json) {
                            if (json && typeof json === 'object' && !Array.isArray(json) && json.error) {
                                swal('Error', json.error, 'error');
                                return [];
                            }
                            return Array.isArray(json) ? json : [];
                        }
                    },
                    columns: [
                        { data: 'fecha' },
                        { data: 'hora' },
                        { data: 'processed_by' },
                        {
                            data: 'reviews_by',
                            render: function(data) {
                                return data
                                    ? data
                                    : '<span class="badge-pcv-warn">Pendiente</span>';
                            }
                        },
                        {
                            data: 'weight',
                            render: function(data) {
                                const val = parseFloat(data);
                                return val ? `${val}/${(val * 2.20462).toFixed(2)}` : data;
                            }
                        },
                        {
                            data: 'stature',
                            render: function(data) {
                                const val = parseFloat(data);
                                return val ? `${val}/${(val / 2.54).toFixed(2)}` : data;
                            }
                        },
                        { data: 'blood_pressure' },
                        { data: 'map_pressure' },
                        { data: 'heart_rate' },
                        { data: 'respiratory_rate' },
                        { data: 'oxygen_saturation' },
                        {
                            data: 'temperature',
                            render: function(data) {
                                const val = parseFloat(data);
                                return val ? `${val}/${((val * 9 / 5) + 32).toFixed(1)}` : data;
                            }
                        },
                        {
                            data: 'glucose',
                            render: function(data) {
                                const val = parseFloat(data);
                                return val ? `${val}/${(val / 18).toFixed(2)}` : data;
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                let stack = '<div class="sv-actions-cell-inner">';
                                stack += `<button type="button" class="register-btn btn-pdf-individual" data-id="${row.id}">PDF</button>`;
                                if (!row.reviews_by) {
                                    stack += `<button type="button" class="register-btn btn-aprobar" data-id="${row.id}">Aprobar</button>`;
                                }
                                stack += '</div>';
                                return stack;
                            }
                        }
                    ],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                    },
                    dom: '<"sv-dt-toolbar-row"Bf>irtip',
                    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    ordering: false,
                    /* Misma línea que partida_manual*.php: sólo scrollBody horizontal, toolbars quietas */
                    scrollX: true,
                    initComplete: function () {
                        this.api().columns.adjust();
                    }
                });
            }

            // Lógica de Descarga PDF Individual
            $(document).on('click', '.btn-pdf-individual', function() {
                const signoId = $(this).data('id');
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                if (tipo !== 'paciente') {
                    swal('Aviso', 'La generación de PDF con membrete actualmente solo está soportada para pacientes hospitalarios.', 'info');
                    return;
                }
                const url = `../pacientes/generate_signos_vitales_pdf.php?idpa=${id}&signo_id=${signoId}`;
                window.open(url, '_blank');
            });

            // Acción de Aprobar (Delegación de eventos)
            $(document).on('click', '.btn-aprobar', function() {
                const idRegistro = $(this).data('id');
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const idPaciente = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                swal({
                    title: "¿Confirmar Aprobación?",
                    text: "Se registrará su nombre como revisor de estos signos vitales.",
                    icon: "info",
                    buttons: ["Cancelar", "Aprobar"],
                }).then((willApprove) => {
                    if (willApprove) {
                        // Aquí se llamará al backend para actualizar reviews_by
                        $.post(PRECLINICA_API + 'pre_clinica_approve_vitals.php', { id: idRegistro, tipo: tipo }, function(resp) {
                            if (resp.success) {
                                swal("Éxito", resp.success, "success");
                                cargarVitals(tipo, idPaciente);
                            } else {
                                swal("Error", resp.error || "No se pudo aprobar", "error");
                            }
                        }, 'json');
                    }
                });
            });

            // Guardar Vitals
            $('#vitals-form').submit(function(e) {
                e.preventDefault();
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                // Construir valores compuestos solo para los que la BD espera como string "X/Y"
                // Para los numéricos (Peso, Talla, Temp, Glucosa) enviamos solo la unidad base (KG, CM, °C, mg/dL)
                const weight = $('#weight_kg').val();
                const stature = $('#stature_cm').val();
                const temperature = $('#temp_c').val();
                const glucose = $('#glucose_mg').val();
                
                const blood_pressure = $('#bp_sys').val() + '/' + $('#bp_dia').val();
                const heart_rate = $('#hr_1').val() + '/' + $('#hr_2').val();
                const respiratory_rate = $('#rr_1').val() + '/' + $('#rr_2').val();
                const oxygen_saturation = $('#sat_1').val() + '/' + $('#sat_2').val();

                const formData = {
                    tipo_paciente: tipo,
                    id_paciente: id,
                    weight: weight,
                    stature: stature,
                    blood_pressure: blood_pressure,
                    map_pressure: $('#map_pressure').val(),
                    heart_rate: heart_rate,
                    respiratory_rate: respiratory_rate,
                    oxygen_saturation: oxygen_saturation,
                    temperature: temperature,
                    glucose: glucose
                };

                $.post(PRECLINICA_API + 'pre_clinica_save_vitals.php', formData, function(resp) {
                    if (resp.success) {
                        swal('Éxito', resp.success, 'success');
                        $('#vitals-form')[0].reset();
                        $('#map_pressure').val('N/A');
                        cargarVitals(tipo, id);
                    } else {
                        swal('Error', resp.error || 'No se pudo guardar', 'error');
                    }
                }, 'json').fail(function() {
                    swal('Error', 'Error de red al intentar guardar.', 'error');
                });
            });
        });
    </script>
</body>
</html>