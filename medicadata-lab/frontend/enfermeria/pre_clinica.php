<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';
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
    
    <!-- Estilos personalizados globales de DataTables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/custom-datatable.css">

    <style>
        /* Estilos para que Select2 se vea igual a los inputs del sistema */
        .select2-container--default .select2-selection--single {
            height: 40px !important;
            border: 1px solid #ddd !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
        }
        
        .pre-clinica-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .selection-group {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #06adbf;
        }
        .radio-options {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .radio-item input {
            width: auto;
            margin: 0;
        }
        .search-btn {
            background-color: #06adbf;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s;
        }
        .search-btn:hover { background-color: #035c67; }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        /* Modal Styles */
        .modal-custom {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .modal-content-custom {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 25px;
            border-radius: 12px;
            width: 95%;
            max-width: 1400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .close-modal:hover { color: black; }

        /* Asegurar que las tablas no se desborden */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 768px) {
            #vitals-form-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Layout lateral para registro y tabla */
        .vitals-flex-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .vitals-form-section {
            flex: 0 0 40%;
            min-width: 450px;
        }
        .vitals-table-section {
            flex: 1;
            min-width: 500px;
        }
        @media (max-width: 1200px) {
            .vitals-form-section, .vitals-table-section {
                flex: 0 0 100%;
                min-width: 100%;
            }
        }
    </style>

    <title>MEDIDATA - Pre-Clínica</title>
</head>
<body>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/admin/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/frontend/admin/perfil.php'; ?>
        </nav>

        <main>
            <h1 class="title">Pre-Clínica</h1>
            <p class="subtitle">Selección de paciente para triaje y signos vitales</p>
            <br>

            <!-- Formulario General (Original) -->
            <div class="pre-clinica-container">
                <form id="form-pre-clinica">
                    <div class="selection-group">
                        <h3>Tipo de Paciente</h3>
                        <br>
                        <div class="radio-options">
                            <label class="radio-item">
                                <input type="radio" name="tipo_paciente" value="paciente" checked>
                                <span>Hospitalario (Interno)</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="tipo_paciente" value="ambulatorio">
                                <span>Ambulatorio (Externo)</span>
                            </label>
                        </div>

                        <div id="wrapper_patients" class="form-group">
                            <label for="patients">Seleccionar Paciente Hospitalario:</label>
                            <select name="id_paciente_hosp" id="patients" class="select2">
                                <option value="">Cargando pacientes...</option>
                            </select>
                        </div>

                        <div id="wrapper_outpatients" class="form-group" style="display: none;">
                            <label for="outpatients">Seleccionar Paciente Ambulatorio:</label>
                            <select name="id_paciente_amb" id="outpatients" class="select2">
                                <option value="">Cargando pacientes...</option>
                            </select>
                        </div>
                    </div>

                    <div class="action-group" style="display: flex; gap: 15px; margin-top: 20px;">
                        <button type="button" id="btn_fetch_vitals" class="search-btn">
                            <i class='bx bx-pulse'></i> Consultar Signos Vitales
                        </button>
                        <button type="button" id="btn_ver_detalles" class="search-btn" style="background-color: #3C91E6; display: none;">
                            <i class='bx bx-list-ul'></i> Ver Detalles de Signos Vitales
                        </button>
                    </div>
                </form>
            </div>

            <!-- Formulario de Signos Vitales y Tabla Condensada -->
            <div id="vitals_display_area">
                <div class="vitals-flex-container">
                    <!-- Columna Formulario (40%) -->
                    <div class="vitals-form-section">
                        <div class="pre-clinica-container">
                            <h3>Registrar Nuevos Signos Vitales</h3>
                            <hr><br>
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
                                <br>
                                <button type="submit" id="btn_save_vitals" class="search-btn" style="width: 100%; justify-content: center; background-color: #28a745; margin-top: 10px;" disabled>
                                    <i class='bx bx-save'></i> Guardar Signos Vitales
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Columna Tabla (60%) -->
                    <div class="vitals-table-section">
                        <div class="pre-clinica-container">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <h3>Últimos Registros (Resumen)</h3>
                                <button type="button" class="search-btn" id="btn_descargar_pdf_resumen" style="background-color: #e74c3c; display: none;">
                                    <i class='bx bxs-file-pdf'></i> Descargar Hoja de Signos Vitales
                                </button>
                            </div>
                            <hr><br>
                            <div class="table-responsive">
                                <table id="table_resumen" class="dataTable display table-condensed" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha y Hora</th>
                                            <th>Realizado Por</th>
                                            <th>Revisado Por</th>
                                            <th>FC / FR</th>
                                            <th>PA / SAT</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="summary-body">
                                        <tr><td colspan="6" style="text-align:center;">Seleccione un paciente y pulse consultar</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </section>

    <!-- Modal Detalles -->
    <div id="modal_detalles" class="modal-custom">
        <div class="modal-content-custom">
            <div class="modal-header">
                <h2>Historial Detallado de Signos Vitales</h2>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="search-btn" id="btn_descargar_pdf_detalle" style="background-color: #e74c3c; padding: 8px 15px;">
                        <i class='bx bxs-file-pdf'></i> Descargar PDF General
                    </button>
                    <span class="close-modal">&times;</span>
                </div>
            </div>
            <div class="table-responsive">
                <table id="table_detalles" class="dataTable display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Realizado Por</th>
                            <th>Revisado Por</th>
                            <th>Peso (kg/lb)</th>
                            <th>Talla (cm/in)</th>
                            <th>PA (mmHg)</th>
                            <th>PAM</th>
                            <th>FC (lpm)</th>
                            <th>FR (rpm)</th>
                            <th>SAT (SatO2)</th>
                            <th>TEMP (°C/°F)</th>
                            <th>GLUCOSA (mg/dL / mmol/L)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

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
    <script src="../../backend/vendor/datatables/pdfmake.min.js"></script>
    <script src="../../backend/vendor/datatables/vfs_fonts.js"></script>
    <script src="../../backend/vendor/datatables/html5.min.js"></script>
    <script src="../../backend/vendor/datatables/buttons.print.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    
    <!-- Componentes de Carga -->
    <script src="../../backend/js/enfermeria/patients/cat_patients.js"></script>
    <script src="../../backend/js/enfermeria/patients/cat_outpatients.js"></script>

    <script>
        $(document).ready(function() {
            let dataTableDetalles = null;
            let dataTableResumen = null;

            // Conversiones automáticas
            // Peso KG <-> LB
            $('#weight_kg').on('input', function() {
                let kg = $(this).val();
                if (kg) $('#weight_lb').val((kg * 2.20462).toFixed(2));
                else $('#weight_lb').val('');
            });
            $('#weight_lb').on('input', function() {
                let lb = $(this).val();
                if (lb) $('#weight_kg').val((lb / 2.20462).toFixed(2));
                else $('#weight_kg').val('');
            });

            // Talla CM <-> IN
            $('#stature_cm').on('input', function() {
                let cm = $(this).val();
                if (cm) $('#stature_in').val((cm / 2.54).toFixed(2));
                else $('#stature_in').val('');
            });
            $('#stature_in').on('input', function() {
                let inch = $(this).val();
                if (inch) $('#stature_cm').val((inch * 2.54).toFixed(2));
                else $('#stature_cm').val('');
            });

            // Temperatura °C <-> °F
            $('#temp_c').on('input', function() {
                let c = $(this).val();
                if (c) $('#temp_f').val(((c * 9/5) + 32).toFixed(1));
                else $('#temp_f').val('');
            });
            $('#temp_f').on('input', function() {
                let f = $(this).val();
                if (f) $('#temp_c').val(((f - 32) * 5/9).toFixed(1));
                else $('#temp_c').val('');
            });

            // Glucosa mg/dL <-> mmol/L
            $('#glucose_mg').on('input', function() {
                let mg = $(this).val();
                if (mg) $('#glucose_mmol').val((mg / 18).toFixed(2));
                else $('#glucose_mmol').val('');
            });
            $('#glucose_mmol').on('input', function() {
                let mmol = $(this).val();
                if (mmol) $('#glucose_mg').val((mmol * 18).toFixed(2));
                else $('#glucose_mg').val('');
            });

            // Inicializar Select2 con un pequeño delay para permitir que los scripts de carga inyecten los datos
            setTimeout(() => {
                $('#patients, #outpatients').select2({
                    placeholder: "Seleccione un paciente",
                    allowClear: true,
                    width: '100%'
                });
            }, 500);

            // Cambio de tipo de paciente
            $('input[name="tipo_paciente"]').change(function() {
                const tipo = $(this).val();
                if (tipo === 'paciente') {
                    $('#wrapper_patients').show();
                    $('#wrapper_outpatients').hide();
                } else {
                    $('#wrapper_patients').hide();
                    $('#wrapper_outpatients').show();
                }
                $('#btn_ver_detalles').hide();
                $('#btn_descargar_pdf_resumen').hide();
                $('#btn_save_vitals').prop('disabled', true);
                if (dataTableResumen) {
                    dataTableResumen.clear().draw();
                }
            });

            // Resetear estado al cambiar de paciente en el select
            $('#patients, #outpatients').on('change', function() {
                $('#btn_ver_detalles').hide();
                $('#btn_descargar_pdf_resumen').hide();
                $('#btn_save_vitals').prop('disabled', true);
                if (dataTableResumen) {
                    dataTableResumen.clear().draw();
                }
            });

            // Consultar datos
            $('#btn_fetch_vitals').click(function() {
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                if (!id || id === '0') {
                    swal('Aviso', 'Seleccione un paciente primero.', 'warning');
                    return;
                }

                // Habilitar el botón de salvar y el de ver detalles
                $('#btn_save_vitals').prop('disabled', false);
                $('#btn_ver_detalles').fadeIn();
                $('#btn_descargar_pdf_resumen').fadeIn();
                cargarVitals(tipo, id);
            });

            function cargarVitals(tipo, id) {
                if (dataTableResumen) {
                    dataTableResumen.destroy();
                }

                dataTableResumen = $('#table_resumen').DataTable({
                    ajax: {
                        url: 'fetch_vitals.php',
                        data: { id, tipo },
                        dataSrc: ''
                    },
                    columns: [
                        { 
                            data: null,
                            render: function(data) {
                                return `${data.fecha} - ${data.hora}`;
                            }
                        },
                        { data: 'processed_by' },
                        { 
                            data: 'reviews_by', 
                            render: function(data) {
                                return data ? `<span class="badge" style="background:#28a745; color:white; padding:4px 8px; border-radius:10px;">${data}</span>` : '<span class="badge" style="background:#ffc107; color:#333; padding:4px 8px; border-radius:10px;">Pendiente</span>';
                            }
                        },
                        { 
                            data: null,
                            render: function(data) {
                                return `${data.heart_rate} bpm / ${data.respiratory_rate} rpm`;
                            }
                        },
                        { 
                            data: null,
                            render: function(data) {
                                return `${data.blood_pressure} / ${data.oxygen_saturation}%`;
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {
                                let btns = '';
                                if (!row.reviews_by) {
                                    btns += `<button class="search-btn btn-aprobar" data-id="${row.id}" style="padding: 5px 10px; font-size: 0.8rem; background-color: #28a745; display:inline-flex; margin-right: 5px;"><i class='bx bx-check-shield'></i> Aprobar</button>`;
                                }
                                btns += `<button class="search-btn btn-pdf-individual" data-id="${row.id}" style="padding: 5px 10px; font-size: 0.8rem; background-color: #e74c3c; display:inline-flex;"><i class='bx bxs-file-pdf'></i> PDF</button>`;
                                return btns;
                            }
                        }
                    ],
                    pageLength: 5,
                    lengthMenu: [5, 10, 25, 50],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                    },
                    dom: 'tp' 
                });
            }

            // Ver Detalles (Modal)
            $('#btn_ver_detalles').click(function() {
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                $('#modal_detalles').fadeIn();
                
                if (dataTableDetalles) {
                    dataTableDetalles.destroy();
                }

                dataTableDetalles = $('#table_detalles').DataTable({
                    ajax: {
                        url: 'fetch_vitals.php',
                        data: { id, tipo },
                        dataSrc: ''
                    },
                    columns: [
                        { data: 'fecha' },
                        { data: 'hora' },
                        { data: 'processed_by' },
                        { 
                            data: 'reviews_by', 
                            render: function(data) {
                                return data ? `<span class="badge" style="background:#28a745; color:white; padding:4px 8px; border-radius:10px;">${data}</span>` : '<span class="badge" style="background:#ffc107; color:#333; padding:4px 8px; border-radius:10px;">Pendiente</span>';
                            }
                        },
                        { 
                            data: 'weight',
                            render: function(data) {
                                let val = parseFloat(data);
                                return val ? `${val}/${(val * 2.20462).toFixed(2)}` : data;
                            }
                        },
                        { 
                            data: 'stature',
                            render: function(data) {
                                let val = parseFloat(data);
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
                                let val = parseFloat(data);
                                return val ? `${val}/${((val * 9/5) + 32).toFixed(1)}` : data;
                            }
                        },
                        { 
                            data: 'glucose',
                            render: function(data) {
                                let val = parseFloat(data);
                                return val ? `${val}/${(val / 18).toFixed(2)}` : data;
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {
                                let btns = '';
                                if (!row.reviews_by) {
                                    btns += `<button class="search-btn btn-aprobar" data-id="${row.id}" style="padding: 5px 10px; font-size: 0.8rem; background-color: #28a745; display:inline-flex; margin-right: 5px;"><i class='bx bx-check-shield'></i> Aprobar</button>`;
                                }
                                btns += `<button class="search-btn btn-pdf-individual" data-id="${row.id}" style="padding: 5px 10px; font-size: 0.8rem; background-color: #e74c3c; display:inline-flex;"><i class='bx bxs-file-pdf'></i> PDF</button>`;
                                return btns;
                            }
                        }
                    ],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
                    },
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });
            });

            // Lógica de Descarga PDF General
            function descargarPDFSignosVitales() {
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                if (!id || id === '0') {
                    swal('Aviso', 'Seleccione un paciente primero.', 'warning');
                    return;
                }
                if (tipo !== 'paciente') {
                    swal('Aviso', 'La generación de PDF con membrete actualmente solo está soportada para pacientes hospitalarios.', 'info');
                    return;
                }
                const url = `../pacientes/generate_signos_vitales_pdf.php?idpa=${id}`;
                window.open(url, '_blank');
            }

            $('#btn_descargar_pdf_resumen, #btn_descargar_pdf_detalle').click(descargarPDFSignosVitales);

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
                        $.post('approve_vitals.php', { id: idRegistro, tipo: tipo }, function(resp) {
                            if (resp.success) {
                                swal("Éxito", resp.success, "success");
                                cargarVitals(tipo, idPaciente);
                                if (dataTableDetalles) dataTableDetalles.ajax.reload();
                            } else {
                                swal("Error", resp.error || "No se pudo aprobar", "error");
                            }
                        }, 'json');
                    }
                });
            });

            // Cerrar Modal
            $('.close-modal').click(function() {
                $('#modal_detalles').fadeOut();
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

                $.post('save_vitals.php', formData, function(resp) {
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