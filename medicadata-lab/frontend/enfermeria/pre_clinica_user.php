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

    <style>
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

        /* Estilo para la tabla condensada */
        .table-condensed {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .table-condensed th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .table-condensed td {
            padding: 10px;
            border-bottom: 1px solid #eee;
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
            margin: 5% auto;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 1200px;
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
        
        @media (max-width: 768px) {
            #vitals-form-grid {
                grid-template-columns: 1fr !important;
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
            <div id="vitals_display_area" style="display: none;">
                <div class="pre-clinica-container">
                    <h3>Registrar Nuevos Signos Vitales</h3>
                    <hr><br>
                    <!-- Formulario con máximo 2 columnas -->
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
                        <br>
                        <button type="submit" class="search-btn" style="width: 100%; justify-content: center; background-color: #28a745; margin-top: 10px;">
                            <i class='bx bx-save'></i> Guardar Signos Vitales
                        </button>
                    </form>

                    <div style="margin-top: 50px;">
                        <h3>Últimos Registros (Resumen)</h3>
                        <hr><br>
                        <table class="table-condensed">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Realizado Por</th>
                                    <th>Revisado Por</th>
                                    <th>FC / FR</th>
                                    <th>PA / SAT</th>
                                </tr>
                            </thead>
                            <tbody id="summary-body">
                                <tr><td colspan="5" style="text-align:center;">Cargando...</td></tr>
                            </tbody>
                        </table>
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
                <span class="close-modal">&times;</span>
            </div>
            <div class="table-responsive">
                <table id="table_detalles" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Realizado Por</th>
                            <th>Revisado Por</th>
                            <th>Peso</th>
                            <th>Talla</th>
                            <th>PA</th>
                            <th>PAM</th>
                            <th>FC</th>
                            <th>FR</th>
                            <th>SAT</th>
                            <th>TEMP</th>
                            <th>GLUCOSA</th>
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
    
    <!-- Componentes de Carga -->
    <script src="../../backend/js/enfermeria/patients/cat_patients.js"></script>
    <script src="../../backend/js/enfermeria/patients/cat_outpatients.js"></script>

    <script>
        $(document).ready(function() {
            let dataTableDetalles = null;

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
                $('#vitals_display_area').hide();
                $('#btn_ver_detalles').hide();
            });

            // Consultar datos
            $('#btn_fetch_vitals').click(function() {
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                if (!id || id === '0') {
                    swal('Aviso', 'Seleccione un paciente primero.', 'warning');
                    return;
                }

                // Mostrar el área de vitales y el botón de detalles
                $('#vitals_display_area').fadeIn();
                $('#btn_ver_detalles').fadeIn();
                cargarVitals(tipo, id);
            });

            function cargarVitals(tipo, id) {
                $.get('fetch_vitals.php', { id, tipo }, function(data) {
                    let rows = '';
                    if (data && data.length > 0) {
                        data.slice(0, 5).forEach(item => {
                            const aprobado = item.reviews_by ? '<span class="badge" style="background:#28a745; color:white; padding:4px 8px; border-radius:10px;">' + item.reviews_by + '</span>' : '<span class="badge" style="background:#ffc107; color:#333; padding:4px 8px; border-radius:10px;">Pendiente</span>';
                            rows += `<tr>
                                <td>${item.fecha} - ${item.hora}</td>
                                <td>${item.processed_by}</td>
                                <td>${aprobado}</td>
                                <td>${item.heart_rate} bpm / ${item.respiratory_rate} rpm</td>
                                <td>${item.blood_pressure} / ${item.oxygen_saturation}%</td>
                            </tr>`;
                        });
                    } else {
                        rows = '<tr><td colspan="5" style="text-align:center;">No hay registros previos para este paciente</td></tr>';
                    }
                    $('#summary-body').html(rows);
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
                        { data: 'reviews_by', defaultContent: '-' },
                        { data: 'weight' },
                        { data: 'stature' },
                        { data: 'blood_pressure' },
                        { data: 'map_pressure' },
                        { data: 'heart_rate' },
                        { data: 'respiratory_rate' },
                        { data: 'oxygen_saturation' },
                        { data: 'temperature' },
                        { data: 'glucose' }
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

            // Cerrar Modal
            $('.close-modal').click(function() {
                $('#modal_detalles').fadeOut();
            });

            // Guardar Vitals
            $('#vitals-form').submit(function(e) {
                e.preventDefault();
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const id = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

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