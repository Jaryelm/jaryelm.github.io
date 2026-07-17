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
    <link rel="stylesheet" href="../../backend/css/reporte_compras_datatable.css">
    <title>MEDIDATA</title>
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

        <div class="catalog-container">
            <h2 class="catalog-title">Lista de Servicios Hospitalarios</h2>

            <div class="table-container">
                <div class="table-responsive">
                    <table id="example" class="display responsive-table dt-medidata-unificado">
                        <thead>
                            <tr>
                                <th>Código de Servicio</th>
                                <th>Nombre del Servicio</th>
                                <th>Categoria</th>
                                <th>Uso Servicio</th>
                                <th>Precio Costo</th>
                                <th>Margen de Ganancia (%)</th>
                                <th>Impuesto</th>
                                <th>Precio de Venta</th>
                                <th>Total</th>
                                <th>Fecha de Registro</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
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
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: false,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        ajax: {
            url: '../../backend/registros/get_servicios_hospital.php',
            type: 'GET',
            data: function(d) { d.layout = 'simple'; }
        },
        order: [[9, 'desc']],
        columns: [
            { data: 'codigo_servicio' },
            { data: 'nombre_servicio' },
            { data: 'categoria_servicio' },
            { data: 'uso_servicio' },
            {
                data: 'precio_costo',
                render: function(data, type, row) {
                    return type === 'display' ? row.precio_costo_fmt : data;
                }
            },
            {
                data: 'margen_ganancia',
                render: function(data, type, row) {
                    return type === 'display' ? row.margen_ganancia_fmt : data;
                }
            },
            { data: 'impuesto' },
            {
                data: 'precio_venta',
                render: function(data, type, row) {
                    return type === 'display' ? row.precio_venta_fmt : data;
                }
            },
            {
                data: 'total',
                render: function(data, type, row) {
                    return type === 'display' ? row.total_fmt : data;
                }
            },
            { data: 'fecha_creacion' }
        ],
        language: {
            sProcessing: 'Procesando...',
            sLengthMenu: 'Mostrar _MENU_ registros',
            sZeroRecords: 'No se encontraron resultados',
            sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            sInfoEmpty: 'Mostrando 0 a 0 de 0 registros',
            sInfoFiltered: '(filtrado de _MAX_ registros totales)',
            sSearch: 'Buscar:',
            oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' }
        }
    });
});
</script>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="/backend/vendor/sweetalert/sweetalert.min.js"></script>

</body>
</html>
