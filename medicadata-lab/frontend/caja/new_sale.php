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

    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../caja/menu.php';
// incuir el archivo menu principal
?>

<!-- NAVBAR -->
<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar' ></i>
        <form action="#">
            <div class="form-group"></div>
        </form>
        <span class="divider"></span>
        <?php include_once '../caja/perfil.php'; ?>
    </nav>
    <!-- NAVBAR -->

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <!-- Botones de Navegación -->
        <button class="button" onclick="cambiarColor(this, '../caja/new_sale.php')">Nueva Venta</button>
        <button class="button" onclick="cambiarColor(this, '../caja/cart.php')">Procesar Venta</button>
        <button class="button" onclick="cambiarColor(this, '#')">Cotizaciones</button>
        <button class="button" onclick="cambiarColor(this, '#')">Estados de Cuenta</button>
        <button class="button" onclick="cambiarColor(this, '../caja/venta.php')">Resumen de Ventas</button>
        <button class="button" onclick="cambiarColor(this, '../caja/mostrar.php')">Resumen de Citas</button>

        <!-- Tabla de Servicios -->
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Servicios</h3>
                </div>

                <div class="table-responsive" style="overflow-x:auto;">
                <?php
                $stmt_servicios = $connect->prepare("SELECT * FROM servicios_hospital WHERE COALESCE(estado, 'habilitado') = 'habilitado' ORDER BY fecha_creacion DESC");
                $stmt_servicios->execute();
                $servicios = $stmt_servicios->fetchAll(PDO::FETCH_OBJ);
                ?>

                <?php if(count($servicios) > 0): ?>
                    <table id="servicios_table" class="responsive-table">
                        <thead>
                            <tr>
                                <th>Código de Servicio</th>
                                <th>Cuenta del Servicio</th>
                                <th>Nombre del Servicio</th>
                                <th>Categoria Servicio</th>
                                <th>Uso Servicio</th>
                                <th>Impuesto</th>
                                <th>Precio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($servicios as $servicio): ?>
                                <tr>
                                    <td><?php echo $servicio->codigo_servicio; ?></td>
                                    <td><?php echo $servicio->nombre_servicio; ?></td>
                                    <td><?php echo $servicio->nomservicio; ?></td>
                                    <td><?php echo $servicio->categoria_servicio; ?></td>
                                    <td><?php echo $servicio->uso_servicio; ?></td>
                                    <td><?php echo $servicio->impuesto; ?></td>
                                    <td>LPS. <?php echo number_format($servicio->total, 2); ?></td>
                                    <td style="width:260px;">
<form class="form-inline" method="post" action="">
    <input type="hidden" name="prdt" value="<?php echo $servicio->id; ?>"> <!-- ID único de servicios_hospital -->
    <input type="hidden" name="pdrus" value="<?php echo $_SESSION['id']; ?>">
    <input type="hidden" name="name" value="<?php echo $servicio->nombre_servicio; ?>">
    <input type="hidden" name="name" value="<?php echo $servicio->nomservicio; ?>">
    <input type="hidden" name="prec" value="<?php echo $servicio->total; ?>">
    <input type="hidden" name="type" value="servicio"> <!-- Tipo de item -->
    <div class="form-group">
        <input type="number" name="p_qty" value="1" style="width:100px;" min="1" class="form-control" placeholder="Cantidad">
    </div>
    <button type="submit" name="add_to_cart" class="registerbtn">Agregar</button>
</form>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert">
                        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                        <strong>¡Atención!</strong> No hay datos en Servicios.
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

                <!-- Tabla de Productos -->
                <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Almacén General</h3>
                </div>
                
                <div class="table-responsive" style="overflow-x:auto;">
                <?php
                $sentencia = $connect->prepare("SELECT product.precio_venta, product.idprcd, product.codpro, product.nompro, category.nomcat, product.stock, product.impuesto 
                                                FROM product 
                                                LEFT JOIN category ON product.idcat = category.idcat 
                                                GROUP BY product.idprcd;");
                $sentencia->execute();
                $data = $sentencia->fetchAll(PDO::FETCH_OBJ);
                ?>

                <?php if(count($data) > 0): ?>
                    <table id="productos_table" class="responsive-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Stock Disponible</th>
                                <th>Impuesto</th>
                                <th>Precio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data as $d): ?>
                                <tr>
                                    <th><?php echo $d->codpro; ?></th>
                                    <td><?php echo $d->nompro; ?></td>
                                    <td>
                                        <?php echo $d->stock; ?>
                                        <?php if ($d->stock <= 5): ?>
                                            <div style="color: #d9534f; font-weight: bold;">
                                                ⚠️ Stock Agotado
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $d->impuesto; ?></td>
                                    <td>LPS. <?php echo $d->precio_venta; ?></td>
                                    <td style="width:260px;">
<form class="form-inline" method="post" action="">
    <input type="hidden" name="prdt" value="<?php echo $d->idprcd; ?>">
    <input type="hidden" name="pdrus" value="<?php echo $_SESSION['id']; ?>">
    <input type="hidden" name="name" value="<?php echo $d->nompro; ?>">
    <input type="hidden" name="prec" value="<?php echo $d->precio_venta; ?>">
    <input type="hidden" name="type" value="producto"> <!-- Tipo de item -->
    <div class="form-group">
        <input type="number" name="p_qty" value="1" style="width:100px;" min="1" class="form-control" placeholder="Cantidad">
    </div>
    <button type="submit" name="add_to_cart" class="registerbtn">Agregar</button>
</form>  
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert">
                        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                        <strong>¡Atención!</strong> No hay datos.
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabla de Productos del Almacén Hospitalario -->
        <!--
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Almacén Hospitalario</h3>
                </div>
                
                <div class="table-responsive" style="overflow-x:auto;">
                <?php
                $sentencia_hospitalario = $connect->prepare("SELECT almacen_hospitalario.precio_venta, almacen_hospitalario.idprcd, almacen_hospitalario.codpro, almacen_hospitalario.nompro, category.nomcat, almacen_hospitalario.stock, almacen_hospitalario.impuesto 
                                                FROM almacen_hospitalario 
                                                LEFT JOIN category ON almacen_hospitalario.idcat = category.idcat 
                                                GROUP BY almacen_hospitalario.idprcd;");
                $sentencia_hospitalario->execute();
                $data_hospitalario = $sentencia_hospitalario->fetchAll(PDO::FETCH_OBJ);
                ?>

                <?php if(count($data_hospitalario) > 0): ?>
                    <table id="productos_hospitalario_table" class="responsive-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Stock Disponible</th>
                                <th>Impuesto</th>
                                <th>Precio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data_hospitalario as $d): ?>
                                <tr>
                                    <th><?php echo $d->codpro; ?></th>
                                    <td><?php echo $d->nompro; ?></td>
                                    <td>
                                        <?php echo $d->stock; ?>
                                        <?php if ($d->stock <= 5): ?>
                                            <div style="color: #d9534f; font-weight: bold;">
                                                ⚠️ Stock Agotado
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $d->impuesto; ?></td>
                                    <td>LPS. <?php echo $d->precio_venta; ?></td>
                                    <td style="width:260px;">
<form class="form-inline" method="post" action="">
    <input type="hidden" name="prdt" value="<?php echo $d->idprcd; ?>">
    <input type="hidden" name="pdrus" value="<?php echo $_SESSION['id']; ?>">
    <input type="hidden" name="name" value="<?php echo $d->nompro; ?>">
    <input type="hidden" name="prec" value="<?php echo $d->precio_venta; ?>">
    <input type="hidden" name="type" value="producto_hospitalario">
    <div class="form-group">
        <input type="number" name="p_qty" value="1" style="width:100px;" min="1" class="form-control" placeholder="Cantidad">
    </div>
    <button type="submit" name="add_to_cart" class="registerbtn">Agregar</button>
</form>  
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert">
                        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
                        <strong>¡Atención!</strong> No hay datos en el Almacén Hospitalario.
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
        -->

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

<script type="text/javascript">
$(document).ready(function() {
    function configurarTabla(tablaId) {
        let table = $('#' + tablaId).DataTable({
            paging: true,
            ordering: false,
            info: false,
            searching: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                sProcessing: "Procesando...",
                sLengthMenu: "Mostrar _MENU_ registros",
                sZeroRecords: "No se encontraron resultados",
                sInfoEmpty: "Mostrando 0 de 0 registros",
                sSearch: "Buscar:",
                oPaginate: {sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior"}
            },
            drawCallback: function(settings) {
                let inputBusqueda = $('#' + tablaId + '_filter input').val();
                if (!inputBusqueda) {
                    $('#' + tablaId + ' tbody tr').hide();
                } else {
                    $('#' + tablaId + ' tbody tr').show();
                }
            }
        });

        $('#' + tablaId + '_filter input').on('keyup', function() {
            let inputBusqueda = $(this).val();
            if (inputBusqueda) {
                $('#' + tablaId + ' tbody tr').show();
            } else {
                $('#' + tablaId + ' tbody tr').hide();
            }
        });
    }

    // Configurar todas las tablas
    configurarTabla('productos_table');
    configurarTabla('servicios_table');
    configurarTabla('productos_hospitalario_table'); // Agregar la nueva tabla
});
</script>

<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src='../../backend/registros/script/cierre_caja.js'></script>
<?php include_once '../../backend/php/add_cart.php'; ?>
</body>
</html>