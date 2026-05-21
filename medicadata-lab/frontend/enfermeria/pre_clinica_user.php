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
    <?php include_once '../enfermeria/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once '../enfermeria/perfil.php'; ?>
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
                            <p class="subtitle">Selección de paciente para triaje y signos vitales</p>
                        </div>
                    </div>
                    <hr class="preclinica-section-divider">

                    <form id="form-pre-clinica">
                    <div class="head"><h3>Paciente</h3></div>
                    <div class="selection-group">
                        <div id="wrapper_patient_combined" class="form-group">
                            <label for="preclinic_patient_select">Seleccionar paciente:</label>
                            <div id="slot_patient_combined" class="preclinica-select-slot is-loading">
                            <select name="paciente_sel" id="preclinic_patient_select">
                                <option value="">&nbsp;</option>
                            </select>
                            </div>
                        </div>
                    </div>

                    <div class="preclinica-actions">
                        <button type="button" id="btn_fetch_vitals" class="button preclinica-main-btn">
                            Consultar
                        </button>
                    </div>
                    </form>
                </div>

            <div id="vitals_display_area" style="display: none;">
                <div class="preclinica-vitals-stack">
                    <div class="content-data preclinica-col-form">
                        <div class="head"><h3>Registrar Nuevos Signos Vitales</h3></div>
                        <hr class="preclinica-section-divider">
                    <form id="vitals-form">
                        <div id="vitals-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div class="form-group">
                                <label for="weight">Peso (kg)</label>
                                <input type="text" id="weight" class="form-control" placeholder="Ej: 70.5" required>
                            </div>
                            <div class="form-group">
                                <label for="stature">Talla (cm)</label>
                                <input type="text" id="stature" class="form-control" placeholder="Ej: 175" required>
                            </div>
                            
                            <!-- PA Dividida para formato automático con "/" -->
                            <div class="form-group">
                                <label>Presión Arterial (PA)</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="number" id="bp_sys" class="form-control" placeholder="Sistólica (Ej: 120)" required style="width: 45%;">
                                    <span style="font-size: 1.5rem; font-weight: bold; color: #555;">/</span>
                                    <input type="number" id="bp_dia" class="form-control" placeholder="Diastólica (Ej: 80)" required style="width: 45%;">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="map_pressure">P.A. Media (PAM)</label>
                                <input type="text" id="map_pressure" class="form-control" value="N/A" required>
                            </div>
                            <div class="form-group">
                                <label for="heart_rate">Frec. Cardíaca (FC)</label>
                                <input type="text" id="heart_rate" class="form-control" placeholder="Ej: 80" required>
                            </div>
                            <div class="form-group">
                                <label for="respiratory_rate">Frec. Respiratoria (FR)</label>
                                <input type="text" id="respiratory_rate" class="form-control" placeholder="Ej: 18" required>
                            </div>
                            <div class="form-group">
                                <label for="oxygen_saturation">Saturación (SAT %)</label>
                                <input type="text" id="oxygen_saturation" class="form-control" placeholder="Ej: 98" required>
                            </div>
                            <div class="form-group">
                                <label for="temperature">Temperatura (°C)</label>
                                <input type="text" id="temperature" class="form-control" placeholder="Ej: 36.5" required>
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label for="glucose">Glucosa (mg/dL)</label>
                                <input type="text" id="glucose" class="form-control" placeholder="Ej: 110" required>
                            </div>
                        </div>
                        <div class="preclinica-form-actions">
                        <button type="submit" class="register-btn preclinica-save-vitals-btn">
                            Guardar Signos Vitales
                        </button>
                        </div>
                    </form>
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
            let catalogsLoaded = false;

            const select2Opts = {
                placeholder: 'Seleccionar paciente',
                allowClear: false,
                width: '100%'
            };

            function preclinicParsePatientValue(raw) {
                const s = raw != null ? String(raw).trim() : '';
                if (!s || s === '0') {
                    return { tipo: null, id: null };
                }
                if (s.indexOf('a:') === 0) {
                    return { tipo: 'ambulatorio', id: s.slice(2).trim() };
                }
                if (s.indexOf('p:') === 0) {
                    return { tipo: 'paciente', id: s.slice(2).trim() };
                }
                return { tipo: null, id: null };
            }

            /** Une cat_patients + cat_outpatients en un solo select (valor p:id | a:id). Lista plana sin etiquetas de tipo. */
            function preclinicPopulateUnifiedSelect($sel, patientsHtml, outpatientHtml) {
                $sel.empty().append($('<option>', { value: '', text: 'Seleccionar paciente' }));
                $('<select>' + patientsHtml + '</select>').find('option').each(function () {
                    const v = ($(this).val() || '').trim();
                    var txt = (($(this).text() || '').trim());
                    if (v !== '' && v !== '0' && txt !== '') {
                        $sel.append($('<option>', { value: 'p:' + v, text: txt }));
                    }
                });
                $('<select>' + outpatientHtml + '</select>').find('option').each(function () {
                    const v = ($(this).val() || '').trim();
                    var txt = (($(this).text() || '').trim());
                    if (v !== '' && v !== '0' && txt !== '') {
                        $sel.append($('<option>', { value: 'a:' + v, text: txt }));
                    }
                });
            }

            function resetHistoriaTablePlaceholder() {
                if (dataTableHistorial) {
                    dataTableHistorial.destroy();
                    dataTableHistorial = null;
                }
                $('#vitals-historial-body').html(
                    '<tr class="sv-dt-placeholder-row"><td colspan="14" style="text-align:center;">Seleccione un paciente y pulse consultar</td></tr>'
                );
            }

            $.when($.post(FUNCIONES_PAC + 'cat_patients.php'), $.post(FUNCIONES_PAC + 'cat_outpatients.php'))
                .done(function (resPatients, resOut) {
                    const $sel = $('#preclinic_patient_select');
                    if ($sel.hasClass('select2-hidden-accessible')) {
                        $sel.select2('destroy');
                    }
                    preclinicPopulateUnifiedSelect($sel, resPatients[0], resOut[0]);
                    catalogsLoaded = true;
                    $sel.select2(select2Opts);
                    $('#slot_patient_combined').removeClass('is-loading');
                })
                .fail(function () {
                    $('#slot_patient_combined').removeClass('is-loading');
                    swal('Error', 'No se pudieron cargar los listados de pacientes.', 'error');
                });

            $('#preclinic_patient_select').on('change', function() {
                $('#vitals_display_area').hide();
                resetHistoriaTablePlaceholder();
            });

            // Consultar datos
            $('#btn_fetch_vitals').click(function() {
                const parsed = preclinicParsePatientValue($('#preclinic_patient_select').val());
                const tipo = parsed.tipo;
                const id = parsed.id;

                if (!tipo || !id || id === '0') {
                    swal('Aviso', 'Seleccione un paciente primero.', 'warning');
                    return;
                }

                $('#vitals_display_area').fadeIn();
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
                                var val = parseFloat(data);
                                return val ? val + '/' + (val * 2.20462).toFixed(2) : data;
                            }
                        },
                        {
                            data: 'stature',
                            render: function(data) {
                                var val = parseFloat(data);
                                return val ? val + '/' + (val / 2.54).toFixed(2) : data;
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
                                var val = parseFloat(data);
                                return val ? val + '/' + ((val * 9 / 5) + 32).toFixed(1) : data;
                            }
                        },
                        {
                            data: 'glucose',
                            render: function(data) {
                                var val = parseFloat(data);
                                return val ? val + '/' + (val / 18).toFixed(2) : data;
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                let stack = '<div class="sv-actions-cell-inner">';
                                stack += '<button type="button" class="register-btn btn-pdf-individual" data-id="' + row.id + '">PDF</button>';
                                stack += '</div>';
                                return stack;
                            }
                        }
                    ],
                    language: {
                        processing: 'Procesando...',
                        lengthMenu: 'Mostrar _MENU_ registros',
                        zeroRecords: 'Ningun dato disponible',
                        emptyTable: 'Ningun dato disponible',
                        info: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
                        infoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
                        infoFiltered: '(filtrado de un total de _MAX_ registros)',
                        infoPostFix: '',
                        search: 'Buscar:',
                        infoThousands: ',',
                        loadingRecords: 'Cargando...',
                        paginate: {
                            first: 'Primero',
                            last: 'Último',
                            next: 'Siguiente',
                            previous: 'Anterior'
                        },
                        aria: {
                            sortAscending: ': Activar para ordenar la columna de manera ascendente',
                            sortDescending: ': Activar para ordenar la columna de manera descendente'
                        },
                        buttons: {
                            copy: 'Copiar',
                            colvis: 'Visibilidad'
                        }
                    },
                    /* rtip: info solo abajo (irtip duplicaba "Mostrando registros…") */
                    dom: '<"sv-dt-toolbar-row"Bf>rtip',
                    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    ordering: false,
                    scrollX: true,
                    initComplete: function () {
                        this.api().columns.adjust();
                    }
                });
            }

            $(document).on('click', '.btn-pdf-individual', function() {
                const signoId = $(this).data('id');
                const parsed = preclinicParsePatientValue($('#preclinic_patient_select').val());
                if (!parsed.tipo || !parsed.id || parsed.id === '0') {
                    swal('Aviso', 'Seleccione paciente y pulse Consultar antes de generar el PDF.', 'warning');
                    return;
                }
                const id = parsed.id;
                const amb = parsed.tipo === 'ambulatorio' ? '&tipo=ambulatorio' : '';
                const url = '../pacientes/generate_signos_vitales_pdf.php?idpa=' + encodeURIComponent(id) + '&signo_id=' + signoId + amb;
                window.open(url, '_blank');
            });

            // Guardar Vitals
            $('#vitals-form').submit(function(e) {
                e.preventDefault();
                const parsed = preclinicParsePatientValue($('#preclinic_patient_select').val());
                const tipo = parsed.tipo;
                const id = parsed.id != null ? String(parsed.id).trim() : '';

                if (!tipo || !id || id === '0') {
                    swal('Aviso', 'Seleccione un paciente y pulse Consultar antes de guardar.', 'warning');
                    return false;
                }

                // Construir Presión Arterial uniendo Sistólica y Diastólica
                const bp_sys = $('#bp_sys').val();
                const bp_dia = $('#bp_dia').val();
                const blood_pressure = bp_sys + '/' + bp_dia;

                const formData = {
                    tipo_paciente: tipo,
                    id_paciente: id,
                    weight: $('#weight').val(),
                    stature: $('#stature').val(),
                    blood_pressure: blood_pressure,
                    map_pressure: $('#map_pressure').val(),
                    heart_rate: $('#heart_rate').val(),
                    respiratory_rate: $('#respiratory_rate').val(),
                    oxygen_saturation: $('#oxygen_saturation').val(),
                    temperature: $('#temperature').val(),
                    glucose: $('#glucose').val()
                };

                $.post(PRECLINICA_API + 'pre_clinica_save_vitals.php', formData, function(resp) {
                    if (resp && resp.success) {
                        swal('Éxito', resp.success, 'success');
                        $('#vitals-form')[0].reset();
                        $('#map_pressure').val('N/A');
                        cargarVitals(tipo, id);
                    } else {
                        swal('Error', (resp && resp.error) ? resp.error : 'No se pudo guardar', 'error');
                    }
                }, 'json').fail(function(xhr) {
                    var det = 'Respuesta no válida del servidor.';
                    if (xhr.responseText) {
                        det = xhr.responseText.substring(0, 500);
                    }
                    swal('Error', 'No se pudo guardar (' + (xhr.status || '?') + '). ' + det, 'error');
                });
            });
        });
    </script>
</body>
</html>