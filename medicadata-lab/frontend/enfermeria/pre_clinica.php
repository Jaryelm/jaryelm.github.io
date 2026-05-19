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

    <style>
        .pre-clinica-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .selection-group {
            margin-bottom: 25px;
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

                    <div class="action-group">
                        <button type="button" id="btn_fetch_vitals" class="search-btn">
                            <i class='bx bx-pulse'></i> Consultar Signos Vitales
                        </button>
                    </div>
                </form>
            </div>

            <div id="vitals_display_area" style="margin-top: 30px;">
                <!-- El formulario de signos vitales se cargará aquí -->
            </div>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src='../../backend/js/submenu.js'></script>
    
    <!-- Importación de componentes de pacientes -->
    <script src="../../backend/js/enfermeria/patients/cat_patients.js"></script>
    <script src="../../backend/js/enfermeria/patients/cat_outpatients.js"></script>

    <script>
        $(document).ready(function() {
            // Cambio entre tipos de paciente
            $('input[name="tipo_paciente"]').change(function() {
                if ($(this).val() === 'paciente') {
                    $('#wrapper_patients').show();
                    $('#wrapper_outpatients').hide();
                } else {
                    $('#wrapper_patients').hide();
                    $('#wrapper_outpatients').show();
                }
                $('#vitals_display_area').empty();
            });

            // Acción de búsqueda
            $('#btn_fetch_vitals').click(function() {
                const tipo = $('input[name="tipo_paciente"]:checked').val();
                const idpa = (tipo === 'paciente') ? $('#patients').val() : $('#outpatients').val();

                if (!idpa || idpa === '0') {
                    alert('Por favor seleccione un paciente válido.');
                    return;
                }

                // Emitir la acción de búsqueda
                buscarSignosVitales(tipo, idpa);
            });

            function buscarSignosVitales(tipo, id) {
                console.log('Iniciando búsqueda de SV para:', tipo, id);
                $('#vitals_display_area').html('<div class="alert alert-success">Cargando historial de signos vitales para ' + tipo + ' (ID: ' + id + ')...</div>');
                
                // Aquí se integrará la lógica para cargar el formulario o tabla de SV
                // que el usuario proporcionará más adelante.
            }
        });
    </script>
</body>
</html>