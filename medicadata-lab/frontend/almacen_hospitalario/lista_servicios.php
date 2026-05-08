<?php
include_once '../../backend/registros/session_check.php';
require_once('../../backend/bd/Conexion.php');
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

    <style>
        /* Color de fondo para la columna principal */
        .codigo-column {
            background-color: #06adbf;
            color: #ffffff;
            padding: 10px;
        }
        
        /* Estilos generales para las celdas */
        #example tbody td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        /* Alternar colores de las filas */
        #example tbody tr:nth-child(even) {
            background-color: #e6f7f8;
        }

        #example tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        /* Botón de detalles */
        .btn_ver_detalles {
            background-color: #035c67;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn_ver_detalles:hover {
            background-color: #06adbf;
        }
    </style>
</head>
<body>

<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

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

        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>


        <!-- Botones de navegación -->
        <button class="button" onclick="cambiarColor(this, '../almacen/compra_unificada.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'categoria_new.php')">Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'categoria.php')">Lista de Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'nuevo_servicio.php')">Registrar Servicio</button>
        <button class="button" onclick="cambiarColor(this, 'lista_servicios.php')">Lista de Servicios</button>
        <button class="button" onclick="cambiarColor(this, 'reorden.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden_admin.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones.php')">Requisiciones</button>

        <!-- Título centrado -->
        <div class="table-title">
            <h1>Lista de Servicios Hospitalarios</h1>
        </div>

        <!-- Tabla para mostrar los datos -->
        <div class="table-container">
            <table id="example" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th class="codigo-column">Código de Servicio</th>
                        <th class="codigo-column">Nombre del Servicio</th>
                        <th class="codigo-column">Categoria</th>
                        <th class="codigo-column">Uso Servicio</th>
                        <th class="codigo-column">Precio Costo</th>
                        <th class="codigo-column">Margen de Ganancia (%)</th>
                        <th class="codigo-column">Impuesto</th>
                        <th class="codigo-column">Precio de Venta</th>
                        <th class="codigo-column">Total</th>
                        <th class="codigo-column">Fecha de Registro</th>
                    </tr>
                </thead>
                <tbody>
<?php
$stmt = $connect->prepare("SELECT * FROM servicios_hospital ORDER BY fecha_creacion DESC");
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['codigo_servicio']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nombre_servicio']) . "</td>";
    echo "<td>" . htmlspecialchars($row['categoria_servicio']) . "</td>";
    echo "<td>" . htmlspecialchars($row['uso_servicio']) . "</td>";

    // Formateo condicional para campos numéricos
    echo "<td>" . formatNumber($row['precio_costo']) . "</td>";
    echo "<td>" . formatNumber($row['margen_ganancia'], '%') . "</td>";
    echo "<td>" . htmlspecialchars($row['impuesto']) . "</td>";
    echo "<td>" . formatNumber($row['precio_venta']) . "</td>";
    echo "<td>" . formatNumber($row['total']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fecha_creacion']) . "</td>";
    echo "</tr>";
}

// Función para formatear números
function formatNumber($value, $suffix = '') {
    if (is_numeric($value)) {
        return number_format((float)$value, 2) . $suffix;
    }
    return 'N/A'; // Valor por defecto si no es numérico
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

<script type="text/javascript">
$(document).ready(function() {
    $('#example').DataTable({
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        order: [[0, 'desc']], // Orden descendente en la primera columna
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
});
</script>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

</body>
</html>
