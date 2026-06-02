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
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    
    <title>MEDIDATA</title>
</head>
<body>

<?php include_once '../enfermeria/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#">
            <div class="form-group"></div>
        </form>
        <span class="divider"></span>
        <?php include_once '../enfermeria/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : 
                 (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <!-- Título y botón de nueva requisición -->
        <div class="table-title">
            <h1>Lista de Requisiciones</h1>
            <button class="button" onclick="window.location.href='requisiciones_user.php'">
                <i class='bx bx-plus'></i> Nueva Requisición
            </button>
        </div>

<!-- Tabla de Requisiciones -->
<div class="table-container">
    <table id="example" class="display responsive-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Solicitante</th>
                <th>Tipo</th>
                <th>Paciente</th>
                <th>Bodega Descargo</th>
                <th>Bodega Cargo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT r.*, 
                     CONCAT(u1.name) as nombre_solicitante,
                     CONCAT(u2.name) as nombre_autorizador
                     FROM requisiciones r
                     LEFT JOIN users u1 ON r.solicitante_id = u1.id
                     LEFT JOIN users u2 ON r.usuario_autorizacion = u2.id
                     ORDER BY r.fecha_solicitud DESC";
            
            $stmt = $connect->query($query);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($row['fecha_solicitud'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['nombre_solicitante']) . '</td>';
                echo '<td>' . ($row['tipo'] === 'externa' ? 'Externa' : 'Interna') . '</td>';
                echo '<td>' . (!empty($row['nombre_paciente']) ? htmlspecialchars($row['nombre_paciente']) : 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['bodega_descargo']) . '</td>';
                echo '<td>' . htmlspecialchars($row['bodega_cargo']) . '</td>';
                echo '<td>';
                switch ($row['estado']) {
                    case 'pendiente':
                        echo '<span class="badge badge-warning">Pendiente</span>';
                        break;
                    case 'aprobado':
                        echo '<span class="badge badge-success">Aprobado</span>';
                        break;
                    case 'rechazado':
                        echo '<span class="badge badge-danger">Rechazado</span>';
                        break;
                }
                echo '</td>';
                echo '<td>';
                echo '<button onclick="verDetalles(' . $row['id'] . ')" class="btn-ver" title="Ver detalles">
                        <i class="bx bx-show"></i>
                      </button>';
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

        <!-- Modal para ver detalles -->
        <div id="detallesModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Detalles de la Requisición</h2>
                <div id="detallesContenido"></div>
            </div>
        </div>
    </main>
</section>

<!-- Scripts -->
<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/datatable.js"></script>
<script src="../../backend/js/datatablebuttons.js"></script>
<script src="../../backend/js/jszip.js"></script>
<script src="../../backend/js/pdfmake.js"></script>
<script src="../../backend/js/vfs_fonts.js"></script>
<script src="../../backend/js/buttonshtml5.js"></script>
<script src="../../backend/js/buttonsprint.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<!-- Plugin para ordenamiento de fechas -->
<script src="https://cdn.datatables.net/plug-ins/1.10.24/sorting/datetime-moment.js"></script>
    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

<script>
$(document).ready(function() {
    $('#example').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        order: [[1, 'desc']], // Ordenar por columna fecha (índice 1) descendente
        pageLength: 10, // Número de registros por página
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]], // Opciones de registros por página
        pagingType: "full_numbers", // Tipo de paginación completa
        columnDefs: [
            {
                targets: 1,
                type: 'date-euro',
                render: function(data, type, row) {
                    if (type === 'sort') {
                        const parts = data.split(' ')[0].split('/');
                        const time = data.split(' ')[1];
                        return `${parts[2]}-${parts[1]}-${parts[0]} ${time}`;
                    }
                    return data;
                }
            }
        ],
        ordering: true,
        drawCallback: function(settings) {
            $(this).DataTable().order([[1, 'desc']]).draw(false); // Agregamos false para evitar el redibujado infinito
        }
    });
});

function verDetalles(id) {
    $.ajax({
        url: 'obtener_detalles_requisicion_user.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            $('#detallesContenido').html(response);
            $('#detallesModal').show();
        }
    });
}

function aprobarRequisicion(id) {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "¿Deseas aprobar esta requisición?",
        icon: "warning",
        buttons: ["Cancelar", "Aprobar"],
        dangerMode: false,
    })
    .then((willApprove) => {
        if (willApprove) {
            $.ajax({
                url: 'procesar_requisicion.php',
                type: 'POST',
                data: { 
                    id: id,
                    accion: 'aprobar'
                },
                success: function(response) {
                    Swal.fire("¡Aprobado!", "La requisición ha sido aprobada.", "success")
                    .then(() => {
                        location.reload();
                    });
                }
            });
        }
    });
}

function rechazarRequisicion(id) {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "¿Deseas rechazar esta requisición?",
        icon: "warning",
        buttons: ["Cancelar", "Rechazar"],
        dangerMode: true,
    })
    .then((willReject) => {
        if (willReject) {
            $.ajax({
                url: 'procesar_requisicion.php',
                type: 'POST',
                data: { 
                    id: id,
                    accion: 'rechazar'
                },
                success: function(response) {
                    Swal.fire("Rechazado", "La requisición ha sido rechazada.", "success")
                    .then(() => {
                        location.reload();
                    });
                }
            });
        }
    });
}

// Modal
var modal = document.getElementById("detallesModal");
var span = document.getElementsByClassName("close")[0];

span.onclick = function() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<style>
/* Estilos para la tabla */
.table-container {
    margin: 20px 0;
    overflow-x: auto;
}

.table-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.responsive-table {
    width: 100%;
    border-collapse: collapse;
}

.responsive-table th {
    background-color: #06adbf;
    color: white;
    padding: 12px;
    text-align: left;
}

.responsive-table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

/* Badges */
.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.badge-warning {
    background-color: #ffc107;
    color: #000;
}

.badge-success {
    background-color: #28a745;
    color: #fff;
}

.badge-danger {
    background-color: #dc3545;
    color: #fff;
}

/* Botones de acción */
.btn-ver, .btn-aprobar, .btn-rechazar {
    padding: 5px 10px;
    margin: 0 2px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-ver {
    background-color: #06adbf;
    color: white;
}

.btn-aprobar {
    background-color: #28a745;
    color: white;
}

.btn-rechazar {
    background-color: #dc3545;
    color: white;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 8px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

@media (max-width: 768px) {
    .table-title {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn-ver, .btn-aprobar, .btn-rechazar {
        padding: 4px 8px;
    }
}
</style>

</body>
</html> 