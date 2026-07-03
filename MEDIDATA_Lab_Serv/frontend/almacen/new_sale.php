<?php
include_once '../../backend/registros/session_check.php';
include_once '../../backend/php/add_cart.php';
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
    
<?php include_once '../admin/menu.php'; ?>

<!-- NAVBAR -->
<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar' ></i>
        <form action="#">
            <div class="form-group"></div>
        </form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>
    <!-- NAVBAR -->

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <!-- Botones de Navegación -->
        <button class="button" onclick="cambiarColor(this, '../almacen/new_sale.php')">Nueva Venta</button>
        <button class="button" onclick="cambiarColor(this, '../almacen/cart.php')">Procesar Venta</button>
        <button class="button" onclick="cambiarColor(this, '#')">Cotizaciones</button>
        <button class="button" onclick="cambiarColor(this, '#')">Estados de Cuenta</button>
        <button class="button" onclick="cambiarColor(this, '../almacen/venta.php')">Resumen de Ventas</button>
        <button class="button" onclick="cambiarColor(this, '../citas/mostrar.php')">Resumen de Citas</button>

        <!-- Tabla de Servicios -->
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Servicios</h3>
                </div>

                <div class="table-responsive" style="overflow-x:auto;">
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
                        <tbody></tbody>
                    </table>
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
                        <tbody></tbody>
                    </table>
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
    function configurarTablaServer(tablaId, tableType) {
        const table = $('#' + tablaId).DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            searchDelay: 400,
            ordering: true,
            info: true,
            searching: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            ajax: {
                url: '../../backend/registros/new_sale_datatable.php',
                type: 'POST',
                data: function (d) {
                    return {
                        draw: d.draw,
                        start: d.start,
                        length: d.length,
                        search: d.search ? d.search.value : '',
                        order_column: (d.order && d.order[0]) ? d.order[0].column : 0,
                        order_dir: (d.order && d.order[0]) ? d.order[0].dir : 'asc',
                        table: tableType
                    };
                },
                dataFilter: function (rawResponse) {
                    const text = (rawResponse || '').toString().trim();
                    if (!text) return text;
                    const firstBrace = text.indexOf('{');
                    const lastBrace = text.lastIndexOf('}');
                    if (firstBrace !== -1 && lastBrace !== -1 && lastBrace > firstBrace) {
                        return text.slice(firstBrace, lastBrace + 1);
                    }
                    return text;
                },
                error: function (xhr, textStatus, errorThrown) {
                    console.error('new_sale_datatable error =>', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        textStatus,
                        errorThrown,
                        responseText: xhr.responseText
                    });
                }
            },
            language: {
                sProcessing: "Procesando...",
                sLengthMenu: "Mostrar _MENU_ registros",
                sZeroRecords: "No se encontraron resultados",
                sInfo: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                sInfoEmpty: "Mostrando 0 a 0 de 0 registros",
                sSearch: "Buscar:",
                oPaginate: {sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior"}
            }
        });

        const $wrapper = $('#' + tablaId + '_wrapper');
        const $searchInput = $wrapper.find('div.dataTables_filter input');

        function toggleRowsVisibility() {
            const hasSearch = ($searchInput.val() || '').trim().length > 0;
            const $tbodyRows = $('#' + tablaId + ' tbody tr');

            if (!hasSearch) {
                $tbodyRows.hide();
                $wrapper.find('.dataTables_info, .dataTables_paginate').hide();
            } else {
                $tbodyRows.show();
                $wrapper.find('.dataTables_info, .dataTables_paginate').show();
            }
        }

        // Mantener comportamiento anterior: oculto hasta que el usuario busque.
        toggleRowsVisibility();
        table.on('draw', toggleRowsVisibility);

        // Buscar solo al presionar Enter (menos carga al servidor).
        $searchInput.off('.DT');
        $searchInput.on('keyup.DT', function (e) {
            if (e.key === 'Enter') {
                table.search(this.value).draw();
            }
            // Al limpiar manualmente el input, ocultar filas de inmediato.
            if ((this.value || '').trim() === '') {
                toggleRowsVisibility();
            }
        });
    }

    configurarTablaServer('productos_table', 'productos');
    configurarTablaServer('servicios_table', 'servicios');

    // Hardening: algunos navegadores/renderizados de filas pueden enviar por GET.
    // Forzamos siempre POST al enviar formularios de "Agregar".
    $(document).on('submit', '#productos_table form, #servicios_table form', function () {
        this.method = 'post';
        this.action = 'new_sale.php';
        const addField = this.querySelector('input[name="add_to_cart"]');
        if (!addField) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'add_to_cart';
            hidden.value = '1';
            this.appendChild(hidden);
        }
    });
});
</script>

<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php if (!empty($_SESSION['swal_flash'])): ?>
<script>
Swal.fire({
    title: <?php echo json_encode($_SESSION['swal_flash']['title'] ?? 'Info', JSON_UNESCAPED_UNICODE); ?>,
    text: <?php echo json_encode($_SESSION['swal_flash']['text'] ?? '', JSON_UNESCAPED_UNICODE); ?>,
    icon: <?php echo json_encode($_SESSION['swal_flash']['icon'] ?? 'info', JSON_UNESCAPED_UNICODE); ?>,
    confirmButtonText: 'Aceptar'
});
</script>
<?php unset($_SESSION['swal_flash']); endif; ?>
</body>
</html>