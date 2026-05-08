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
    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <title>MEDIDATA</title>
    <style>
        .form-control {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
        .input-percent {
            width: 80px;
            padding: 5px;
            text-align: right;
        }
        .btn-save {
            background-color: #06adbf;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            margin: 2px;
            transition: all 0.3s ease;
            min-width: 80px;
            white-space: nowrap;
        }
        .btn-save:hover {
            background-color: #035c67;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .btn-save:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .btn-save.btn-limpiar {
            background-color: #dc3545;
        }
        /* Columna Acción: botones apilados, mismo ancho y centrados */
        #servicios-table td:last-child .btn-save {
            display: block;
            margin: 3px auto;
            width: 92px;
            max-width: 100%;
            box-sizing: border-box;
        }
        .btn-save.btn-limpiar:hover {
            background-color: #c82333;
        }
        .tipo-activo {
            background-color: #06adbf;
            color: white;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }
        .tipo-activo.ninguno {
            background-color: #6c757d;
        }
        .input-percent:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        #servicios-table {
            border-collapse: collapse;
            width: 100%;
        }
        
        /* Eliminar bordes verticales globalmente */
        #servicios-table * {
            border-left: none !important;
            border-right: none !important;
        }
        #servicios-table th {
            background-color: #06adbf;
            color: white;
            border: none;
            border-left: none !important;
            border-right: none !important;
            padding: 12px 8px;
            text-align: center;
        }
        #servicios-table td {
            border: none;
            border-left: none !important;
            border-right: none !important;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }
        #servicios-table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        #servicios-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border: 1px solid #dee2e6;
            background-color: #fff;
            color: #06adbf;
            border-radius: 4px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background-color: #06adbf;
            color: white;
            border-color: #06adbf;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #06adbf;
            color: white;
            border-color: #06adbf;
        }
        
        /* Eliminar todas las líneas verticales de DataTables */
        .dataTables_wrapper table.dataTable thead th,
        .dataTables_wrapper table.dataTable thead td,
        .dataTables_wrapper table.dataTable tbody th,
        .dataTables_wrapper table.dataTable tbody td {
            border-left: none !important;
            border-right: none !important;
        }
        
        /* Asegurar que no haya bordes verticales en ninguna parte */
        #servicios-table.dataTable thead th,
        #servicios-table.dataTable thead td,
        #servicios-table.dataTable tbody th,
        #servicios-table.dataTable tbody td {
            border-left: none !important;
            border-right: none !important;
        }
    </style>
</head>
<body>

<?php
include_once '../admin/menu.php';
// Obtener doctores para el selector
$stmt = $connect->prepare("SELECT idodc, nodoc, apdoc FROM doctor ORDER BY nodoc ASC");
$stmt->execute();
$doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                    
                </div>
            </form>
            
           
            <span class="divider"></span>
            <?php
include_once '../admin/perfil.php';
// incuir el archivo menu principal
?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
<?php
// Obtener la hora actual
$hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = "Buenos Días";
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = "Buenas Tardes";
} else {
    $saludo = "Buenas Noches";
}
?>

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

<main>
<div class="data">
    <div class="content-data">
        <div class="head">
            <h3>Gestión de Honorarios por Médico y Servicio</h3>
            <p>Seleccione un médico para asignar o actualizar los porcentajes de honorario para cada servicio que realiza.</p>
        </div>

        <div class="form-group">
            <label for="doctor-selector"><b>Seleccione un Médico:</b></label>
            <select id="doctor-selector" class="form-control">
                <option value="">-- Elija un médico para empezar --</option>
                <?php foreach ($doctores as $doctor): ?>
                    <option value="<?php echo $doctor['idodc']; ?>">
                        <?php echo htmlspecialchars($doctor['nodoc'] . ' ' . $doctor['apdoc']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="servicios-container" style="display: none;">
            <div class="table-responsive" style="overflow-x:auto;">
                <table id="servicios-table" class="responsive-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Servicio</th>
                            <th>Cuota (LPS)</th>
                            <th>Porcentaje de Honorario (%)</th>
                            <th>Tipo Activo</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="servicios-tbody">
                        <!-- Contenido cargado con AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<!-- Data Tables -->
<script type="text/javascript" src="../../backend/js/datatable.js"></script>
<script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
<script type="text/javascript" src="../../backend/js/jszip.js"></script>
<script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
<script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
<script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
<script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
<!-- Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar Select2 en el selector de doctores
    $('#doctor-selector').select2({
        placeholder: '-- Elija un médico para empezar --',
        allowClear: true
    });

    $('#doctor-selector').on('change', function() {
        const doctorId = $(this).val();
        if (doctorId) {
            loadServiciosParaDoctor(doctorId);
            $('#servicios-container').slideDown();
        } else {
            $('#servicios-container').slideUp();
        }
    });
});

function loadServiciosParaDoctor(doctorId) {
    $.ajax({
        url: 'get_servicios_doctor_config.php',
        type: 'GET',
        data: { id_doctor: doctorId },
        dataType: 'json',
        beforeSend: function() {
            $('#servicios-tbody').html('<tr><td colspan="6">Cargando servicios...</td></tr>');
        },
        success: function(response) {
            if (response.error) {
                swal('Error', response.error, 'error');
                return;
            }

            const servicios = response.data;
            const tbody = $('#servicios-tbody');
            tbody.empty();
            
            if (servicios.length > 0) {
                servicios.forEach(function(servicio) {
                    const porcentaje = servicio.porcentaje_especifico || '0.00';
                    const cuotaFija = servicio.cuota_fija_especifica || '0.00';
                    
                    // Determinar qué tipo está activo
                    let tipoActivo = 'Ninguno';
                    let cuotaDisabled = '';
                    let porcentajeDisabled = '';
                    
                    let tipoClass = 'tipo-activo';
                    if (parseFloat(cuotaFija) > 0) {
                        tipoActivo = 'Cuota Fija';
                        porcentajeDisabled = 'disabled';
                    } else if (parseFloat(porcentaje) > 0) {
                        tipoActivo = 'Porcentaje';
                        cuotaDisabled = 'disabled';
                    } else {
                        tipoClass = 'tipo-activo ninguno';
                    }
                    
                    let row = `
                        <tr>
                            <td>${servicio.codigo_servicio || ''}</td>
                            <td>${servicio.descripcion}</td>
                            <td>
                                <input type="number" class="input-percent" id="cuota-${servicio.id}" 
                                       value="${parseFloat(cuotaFija).toFixed(2)}" min="0" step="0.01" 
                                       placeholder="0.00" ${cuotaDisabled}
                                       onchange="habilitarCampo('cuota', ${servicio.id})">
                            </td>
                            <td>
                                <input type="number" class="input-percent" id="porcentaje-${servicio.id}" 
                                       value="${parseFloat(porcentaje).toFixed(2)}" min="0" max="100" step="0.01" 
                                       placeholder="0.00" ${porcentajeDisabled}
                                       onchange="habilitarCampo('porcentaje', ${servicio.id})">
                            </td>
                            <td><span class="${tipoClass}" id="tipo-${servicio.id}">${tipoActivo}</span></td>
                            <td>
                                <button class="btn-save" onclick="guardarConfiguracion(${doctorId}, ${servicio.id})">Guardar</button>
                                <button class="btn-save btn-limpiar" onclick="limpiarConfiguracion(${doctorId}, ${servicio.id})">Limpiar</button>
                            </td>
                        </tr>`;
                    tbody.append(row);
                });
            } else {
                tbody.html('<tr><td colspan="6">No se encontraron servicios para configurar.</td></tr>');
            }

            // Reinicializar DataTables
            if ($.fn.DataTable.isDataTable('#servicios-table')) {
                $('#servicios-table').DataTable().destroy();
            }
            $('#servicios-table').DataTable({
                pageLength: 5,
                language: {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar: _MENU_",
                    "sZeroRecords": "No se encontraron resultados",
                    "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
                    "sSearch": "Buscar:",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Último",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    }
                }
            });
        },
        error: function() {
            swal('Error', 'No se pudo comunicar con el servidor.', 'error');
        }
    });
}

function habilitarCampo(tipo, servicioId) {
    const cuotaInput = $(`#cuota-${servicioId}`);
    const porcentajeInput = $(`#porcentaje-${servicioId}`);
    const tipoSpan = $(`#tipo-${servicioId}`);
    
    if (tipo === 'cuota') {
        const cuotaValue = parseFloat(cuotaInput.val()) || 0;
        if (cuotaValue > 0) {
            porcentajeInput.prop('disabled', true).val('0.00');
            tipoSpan.removeClass('ninguno').text('Cuota Fija');
        } else {
            porcentajeInput.prop('disabled', false);
            tipoSpan.addClass('ninguno').text('Ninguno');
        }
    } else if (tipo === 'porcentaje') {
        const porcentajeValue = parseFloat(porcentajeInput.val()) || 0;
        if (porcentajeValue > 0) {
            cuotaInput.prop('disabled', true).val('0.00');
            tipoSpan.removeClass('ninguno').text('Porcentaje');
        } else {
            cuotaInput.prop('disabled', false);
            tipoSpan.addClass('ninguno').text('Ninguno');
        }
    }
}

function guardarConfiguracion(doctorId, servicioId) {
    const cuotaInput = $(`#cuota-${servicioId}`);
    const porcentajeInput = $(`#porcentaje-${servicioId}`);
    const cuotaValue = parseFloat(cuotaInput.val()) || 0;
    const porcentajeValue = parseFloat(porcentajeInput.val()) || 0;
    
    // Validar que solo uno esté activo
    if (cuotaValue > 0 && porcentajeValue > 0) {
        swal('Error', 'No puede tener activos tanto la cuota fija como el porcentaje al mismo tiempo.', 'warning');
        return;
    }
    
    if (cuotaValue === 0 && porcentajeValue === 0) {
        swal('Error', 'Debe especificar una cuota fija o un porcentaje.', 'warning');
        return;
    }
    
    let datos = {
        id_doctor: doctorId,
        id_servicio: servicioId
    };
    
    if (cuotaValue > 0) {
        datos.tipo_honorario = 'cuota';
        datos.cuota_fija = cuotaValue;
    } else {
        datos.tipo_honorario = 'porcentaje';
        datos.porcentaje = porcentajeValue;
    }

    $.ajax({
        url: 'update_honorario_config.php',
        type: 'POST',
        data: datos,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                swal('¡Guardado!', response.message, 'success');
                
                // Actualizar estado visual
                if (cuotaValue > 0) {
                    $(`#tipo-${servicioId}`).removeClass('ninguno').text('Cuota Fija');
                } else {
                    $(`#tipo-${servicioId}`).removeClass('ninguno').text('Porcentaje');
                }
            } else {
                swal('Error', response.message, 'error');
            }
        },
        error: function() {
            swal('Error', 'Error de comunicación con el servidor.', 'error');
        }
    });
}

function limpiarConfiguracion(doctorId, servicioId) {
    swal({
        title: "¿Limpiar configuración?",
        text: "Esto eliminará tanto la cuota fija como el porcentaje para este servicio.",
        icon: "warning",
        buttons: ["Cancelar", "Sí, limpiar"]
    }).then((isConfirm) => {
        if (isConfirm) {
            $.ajax({
                url: 'update_honorario_config.php',
                type: 'POST',
                data: {
                    id_doctor: doctorId,
                    id_servicio: servicioId,
                    tipo_honorario: 'porcentaje',
                    porcentaje: 0
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                                                 $(`#cuota-${servicioId}`).val('0.00').prop('disabled', false);
                         $(`#porcentaje-${servicioId}`).val('0.00').prop('disabled', false);
                         $(`#tipo-${servicioId}`).addClass('ninguno').text('Ninguno');
                        swal('¡Limpiado!', 'Configuración eliminada correctamente.', 'success');
                    } else {
                        swal('Error', response.message, 'error');
                    }
                },
                error: function() {
                    swal('Error', 'Error de comunicación con el servidor.', 'error');
                }
            });
        }
    });
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html> 