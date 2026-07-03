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

<?php
include_once __DIR__ . '/menu.php';
?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">

                </div>
            </form>


            <span class="divider"></span>
            <?php
include_once __DIR__ . '/perfil.php';
?>
        </nav>

        <main>
        <?php
$hora_actual = date('H');

if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = "Buenos Días";
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = "Buenas Tardes";
} else {
    $saludo = "Buenas Noches";
}
?>

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'lista_inventario_user.php')">Lista de Inventario</button>

    <div class="table-title">
        <h1>Lista de Inventario</h1>
    </div>
    <div class="medidata-dt-host medidata-dt-host--pending">
        <table id="directorio-table" class="display" style="width:100%;min-width:1400px">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Código de Barras</th>
                    <th>Linea</th>
                    <th>Sub Linea</th>
                    <th>Presentación</th>
                    <th>Forma Farmaceutica</th>
                    <th>Concentración</th>
                    <th>Vía Administración</th>
                    <th>Nombre</th>
                    <th>Principio Activo</th>
                    <th>Precio</th>
                    <th>Precio de Venta</th>
                    <th>Margen de Ganacias</th>
                    <th>Impuesto %</th>
                    <th>Categoria</th>
                    <th>Stock</th>
                    <th>Fecha de Registro</th>
                    <th>Fecha Vence</th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script src="../../backend/registros/script/tabla_almacen.js"></script>
    <script src='../../backend/js/submenu.js'></script>

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
 <?php include_once '../../backend/php/delete_medicine.php' ?>
</body>
</html>
