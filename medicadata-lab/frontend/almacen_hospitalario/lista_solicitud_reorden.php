<?php
include_once '../../backend/registros/session_check.php';
require_once('../../backend/bd/Conexion.php');
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

</head>
<body>

<?php
include_once '../almacen_hospitalario/menu.php';
// incuir el archivo menu principal
?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#">
            <div class="form-group"></div>
        </form>
        <span class="divider"></span>
        <?php include_once '../almacen_hospitalario/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <!-- Opciones de Navegación -->
        <button class="button" onclick="cambiarColor(this, '../almacen/compra_unificada.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_user.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'reorden_user.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones_user.php')">Requisiciones</button>

        <!-- Título centrado -->
        <div class="table-title">
            <h1>Autorización Compras Almacen</h1>
        </div>

<!-- Tabla de Solicitudes Pendientes -->
<div class="table-container">
    <table id="example" class="display responsive-table" style="width:100%">
        <thead>
            <tr>
                <th scope="col">Fecha Solicitud</th>
                <th scope="col">Fecha Autorización</th>
                <th scope="col">Material</th>
                <th scope="col">Proveedor</th>
                <th scope="col">Precio</th>
                <th scope="col">Existencia</th>
                <th scope="col">Cantidad Solicitada</th>
                <th scope="col">Solicitado Por</th>
                <th scope="col">Justificación</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones Realizadas Por</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Obtener las solicitudes pendientes desde la base de datos
            $stmt = $connect->query("SELECT * FROM reorden_solicitudes");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td data-title="Fecha Solicitud">' . htmlspecialchars($row['fecha_solicitud']) . '</td>';
                echo '<td data-title="Fecha Autoriazión">' . htmlspecialchars($row['fecha_autorizacion']) . '</td>';
                echo '<td data-title="Producto">' . htmlspecialchars($row['producto_nombre']) . '</td>';
                echo '<td data-title="Proveedor">' . htmlspecialchars($row['proveedor']) . '</td>';
                echo '<td data-title="Precio">' . htmlspecialchars($row['precio_producto']) . '</td>';
                echo '<td data-title="Stock Actual">' . htmlspecialchars($row['stock_actual']) . '</td>';
                echo '<td data-title="Cantidad Solicitada">' . htmlspecialchars($row['cantidad_solicitada']) . '</td>';
                echo '<td data-title="Solicitado Por">' . htmlspecialchars($row['usuario_solicitud']) . '</td>';
                echo '<td data-title="Justificación">' . htmlspecialchars($row['justificacion']) . '</td>';
                echo '<td data-title="Estado">';
                if ($row['estado'] === 'pendiente') {
                    echo '<span style="color:rgb(255, 0, 0);">Pendiente</span>';
                } elseif ($row['estado'] === 'autorizado') {
                    echo '<span style="color: #06adbf;">Autorizado</span>';
                } elseif ($row['estado'] === 'rechazado') {
                    echo '<span style="color:rgb(0, 0, 0);">Rechazado</span>';
                }
                echo '</td>';
                echo '<td data-title="Acciones Realizadas Por">';
                echo htmlspecialchars($row['usuario_autorizacion'] ?? 'N/A');
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

        </main>
    </section>

<!-- jQuery -->
<script src="../../backend/js/jquery.min.js"></script>

<!-- DataTables JS -->
<script src="../../backend/js/datatable.js"></script>
<script src="../../backend/js/datatablebuttons.js"></script>
<script src="../../backend/js/jszip.js"></script>
<script src="../../backend/js/pdfmake.js"></script>
<script src="../../backend/js/vfs_fonts.js"></script>
<script src="../../backend/js/buttonshtml5.js"></script>
<script src="../../backend/js/buttonsprint.js"></script>

<script>
        $(document).ready(function () {
            $('#example').DataTable({
                pageLength: 10,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                language: {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
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

            // Manejar clic en los botones de autorizar/rechazar
            $('.btn-autorizar, .btn-rechazar').on('click', function () {
                const id = $(this).data('id');
                const estado = $(this).data('estado');

                swal({
                    title: "¿Estás seguro?",
                    text: `¿Deseas ${estado} esta solicitud?`,
                    icon: "warning",
                    buttons: ["Cancelar", "Confirmar"],
                    dangerMode: true,
                }).then((confirm) => {
                    if (confirm) {
                        // Enviar solicitud al backend para actualizar el estado
                        $.ajax({
                            url: 'autorizar_reorden.php',
                            type: 'POST',
                            data: { id: id, estado: estado },
                            success: function (response) {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    swal(result.message, "", "success").then(() => {
                                        location.reload(); // Recargar la página para reflejar los cambios
                                    });
                                } else {
                                    swal("Error", result.message, "error");
                                }
                            },
                            error: function () {
                                swal("Error", "Ocurrió un error al procesar la solicitud.", "error");
                            }
                        });
                    }
                });
            });
        });
    </script>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="/backend/vendor/sweetalert/sweetalert.min.js"></script>

</body>
</html>

    <!-- Estilos CSS -->
    <style>
        /* Contenedor de la tabla */
        .table-container {
            margin: 20px 0;
            overflow-x: auto;
        }

        /* Estilo general de la tabla */
        .responsive-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        /* Encabezado de la tabla */
        .responsive-table thead {
            background-color: #06adbf; /* Color azul claro */
            color: white;
        }

        .responsive-table th {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        /* Filas de la tabla */
        .responsive-table tbody tr:nth-child(even) {
            background-color: #f9f9f9; /* Fondo gris claro para filas pares */
        }

        .responsive-table tbody tr:hover {
            background-color: #f1f1f1; /* Efecto hover en las filas */
        }

        .responsive-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        /* Botones de acciones */
        .btn-autorizar {
            background-color: #035c67; /* Verde para autorizar */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-autorizar:hover {
            background-color: #06adbf; /* Verde oscuro al pasar el mouse */
        }

        .btn-rechazar {
            background-color: #dc3545; /* Rojo para rechazar */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-rechazar:hover {
            background-color: #c82333; /* Rojo oscuro al pasar el mouse */
        }
    </style>