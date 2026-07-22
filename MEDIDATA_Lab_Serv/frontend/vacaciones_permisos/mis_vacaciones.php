<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: ../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes - MEDIDATA</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    
    <!-- DataTables & SweetAlert -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-row input, .form-row select, .form-row textarea { 
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .balance-box {
            background: #f8fbff;
            border: 1px solid #cce5ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .balance-item span { display: block; font-size: 1.2em; font-weight: bold; color: var(--blue); }
        .balance-item small { color: #555; }
        .btn-new { background-color: var(--blue); color: #fff; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-bottom: 20px;}
        .btn-new:hover { background-color: var(--dark-blue); }
        
        /* Modal Custom */
        #modalNuevaSolicitud { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 5% auto; padding: 20px; width: 50%; border-radius: 8px; position: relative; max-height: 80vh; overflow-y: auto;}
        .close-btn { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #aaa; }
        .close-btn:hover { color: #333; }
        .modal-footer { text-align: right; margin-top: 20px; }
        .btn-cancel { background: #ccc; color: #333; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;}
        .btn-submit { background: var(--blue); color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;}
    </style>
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
</head>
<body>
    <?php include 'menu_router.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? '') . '</strong>'; ?></h1>
        
            <div class="page-actions">
                <button class="button" onclick="abrirModal()">
                    <i class='bx bx-plus'></i> Nueva Solicitud
                </button>
            </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <table id="tabla-solicitudes" class="responsive-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Días</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Llenado vía DataTables Ajax si fuera necesario, para esta fase puede estar vacío o cargarse en el futuro -->
                </tbody>
            </table>
        </div>
        </div>
        </main>
    </section>

    <!-- Modal Nueva Solicitud -->
    <div id="modalNuevaSolicitud">
        <div class="modal-content">
            <span class="close-btn" onclick="cerrarModal()">&times;</span>
            <h2>Generar Solicitud</h2>
            
            <div class="balance-box">
                <div class="balance-item">
                    <small>Días Disponibles</small>
                    <span id="ui_disponibles">0</span>
                </div>
                <div class="balance-item">
                    <small>Días Solicitados</small>
                    <span id="ui_solicitados">0</span>
                </div>
                <div class="balance-item">
                    <small>Saldo Restante</small>
                    <span id="ui_saldo">0</span>
                </div>
            </div>

            <form id="formSolicitud">
                <div class="form-row">
                    <label>Tipo de Ausencia</label>
                    <select name="type_id" id="type_id" required>
                        <option value="">Seleccione...</option>
                    </select>
                </div>
                
                <div class="form-row" id="div_pago" style="display:none;">
                    <label>
                        <input type="checkbox" name="is_paid_vacation" id="is_paid_vacation" value="1" style="width:auto;"> 
                        Solicitud de pago en efectivo (Vacaciones Pagadas)
                    </label>
                </div>

                <div class="form-row" style="display: flex; gap:15px;">
                    <div style="flex:1;">
                        <label>Fecha de Inicio</label>
                        <input type="date" name="start_date" id="start_date" required>
                    </div>
                    <div style="flex:1;">
                        <label>Fecha de Finalización</label>
                        <input type="date" name="end_date" id="end_date" required>
                    </div>
                </div>

                <div class="form-row">
                    <label>Cantidad de días (Cálculo Automático)</label>
                    <input type="number" name="days_amount" id="days_amount" step="0.5" readonly required>
                </div>

                <div class="form-row">
                    <label>Comentarios</label>
                    <textarea name="comments" rows="3"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>

    </div>
    <script src="../../backend/js/script.js"></script>

    <script>
    let diasDisponiblesTotales = 0;
    const userId = <?php echo $_SESSION['id']; ?>;

    $(document).ready(function() {
        $('#tabla-solicitudes').DataTable({
                            "dom": 'Bfrtip',
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                "buttons": [
                    { extend: 'copy', className: 'button' },
                    { extend: 'csv', className: 'button' },
                    { extend: 'excel', className: 'button' },
                    { extend: 'print', className: 'button' }
                ],
                "language": {
                    "processing": 'Cargando...',
                    "lengthMenu": 'Mostrar _MENU_ registros',
                    "zeroRecords": 'No se encontraron resultados',
                    "emptyTable": 'No hay datos disponibles.',
                    "info": 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    "infoEmpty": 'Mostrando 0 a 0 de 0 registros',
                    "infoFiltered": '(filtrado de _MAX_ registros totales)',
                    "search": 'Buscar:',
                    "paginate": { "first": 'Primero', "last": '�ltimo', "next": 'Siguiente', "previous": 'Anterior' }
                }
        });
        
        // Cargar Tipos de Ausencia
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_absence_types.php', function(data) {
            if(!data.error) {
                data.forEach(function(tipo) {
                    $('#type_id').append(`<option value="${tipo.type_id}" data-category="${tipo.category}">${tipo.name}</option>`);
                });
            }
        });
        
        // Evento Tipo Ausencia
        $('#type_id').change(function() {
            let cat = $(this).find(':selected').data('category');
            if (cat === 'Vacation') {
                $('#div_pago').show();
            } else {
                $('#div_pago').hide();
                $('#is_paid_vacation').prop('checked', false);
            }
        });
        
        // Evento Fechas -> Calcular Días
        $('#start_date, #end_date').change(calcularDias);

        // Enviar Formulario
        $('#formSolicitud').submit(function(e) {
            e.preventDefault();
            
            // Validar si los dias son validos
            if(parseFloat($('#ui_saldo').text()) < 0) {
                Swal.fire('Atención', 'No tienes suficientes días disponibles para esta solicitud.', 'warning');
                return;
            }

            $.ajax({
                url: '../../backend/registros/vacaciones_permisos/submit_absence_request.php',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(res) {
                    let data = typeof res === 'string' ? JSON.parse(res) : res;
                    if(data.status === 'success') {
                        Swal.fire('Éxito', data.message, 'success').then(() => {
                            cerrarModal();
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }
            });
        });
    });

    function abrirModal() {
        $('#formSolicitud')[0].reset();
        $('#div_pago').hide();
        $('#ui_solicitados').text('0');
        $('#ui_saldo').text('0');
        $('#days_amount').val('');
        
        // Cargar saldo en vivo
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_vacation_profile.php?user_id=' + userId, function(res) {
            if(!res.error) {
                diasDisponiblesTotales = parseFloat(res.info_vacaciones.dias_pendientes) || 0;
                $('#ui_disponibles').text(diasDisponiblesTotales);
                $('#ui_saldo').text(diasDisponiblesTotales);
            }
        });

        $('#modalNuevaSolicitud').fadeIn();
    }

    function cerrarModal() {
        $('#modalNuevaSolicitud').fadeOut();
    }

    function calcularDias() {
        let start = $('#start_date').val();
        let end = $('#end_date').val();
        
        if(start && end) {
            let d1 = new Date(start);
            let d2 = new Date(end);
            
            if(d2 < d1) {
                $('#days_amount').val(0);
                $('#ui_solicitados').text(0);
                $('#ui_saldo').text(diasDisponiblesTotales);
                return;
            }
            
            // Calculo simple de dias de diferencia (asumiendo días calendario, ajustar si son hábiles)
            let diffTime = Math.abs(d2 - d1);
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 para incluir el día de fin
            
            $('#days_amount').val(diffDays);
            $('#ui_solicitados').text(diffDays);
            
            let saldo = diasDisponiblesTotales - diffDays;
            $('#ui_saldo').text(saldo);
            
            if (saldo < 0) {
                $('#ui_saldo').css('color', 'red');
            } else {
                $('#ui_saldo').css('color', 'var(--blue)');
            }
        }
    }
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>






