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

    <style>
        /* Modal de detalle de compra (mismo enfoque que Diario General) */
        .swal2-detalle-compra {
            width: min(70rem, calc(100vw - 30px)) !important;
        }

        .swal2-detalle-compra-html {
            text-align: left !important;
            margin: 0 !important;
            padding: 0.5rem 0 !important;
        }

        /* Estilos para la tabla de compras */
        #compras_table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #f9f9f9;
            text-align: left;
        }

        #compras_table thead th {
            background-color: #06adbf;
            color: white;
            padding: 10px;
            border-bottom: 2px solid #035c67;
        }

        #compras_table tbody td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        #compras_table tbody tr:nth-child(even) {
            background-color: #e6f7f8;
        }

        #compras_table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

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
        <?php
include_once '../admin/perfil.php';
// incuir el archivo menu principal
?>
    </nav>

    <!-- MAIN -->
    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>


        <!-- Botones de navegación -->
        <button class="button" onclick="cambiarColor(this, 'compra_unificada.php')">Compra e inventario</button>
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
        <h1>Compras Registradas</h1>
    </div>
        <br>
    <table id="compras_table">
        <thead>
            <tr>
                <th>ID Compra</th>
                <th>Sucursal</th>
                <th>Ubicación</th>
                <th>Proveedor</th>
                <th>Factura No.</th>
                <th>Fecha Factura</th>
                <th>Términos de Pago</th>
                <th>Días de Credito</th>
                <th>% Prima</th>
                <th>Cuotas Pendientes</th>
                <th>Fecha Vencimiento</th>
                <th>ISV Total</th>
                <th>Subtotal</th>
                <th>Total</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="compras_body">
            <!-- Los datos se cargarán dinámicamente con JavaScript -->
        </tbody>
    </table>

<style>
#compras_table {
    min-width: 1500px;
}
</style>

    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script>
    // Cargar datos de compras al cargar la página
    $(document).ready(function() {
        cargarCompras();

        function cargarCompras() {
    $.ajax({
        url: "../../backend/registros/obtener_compras.php",
        type: "GET",
        success: function(data) {
            const compras = JSON.parse(data);
            const tbody = $("#compras_body");
            tbody.empty(); // Limpiar cualquier fila existente

            // Agregar las filas dinámicamente
            compras.forEach(compra => {
                const row = `
                    <tr>
                        <td>${compra.id_compra}</td>
                        <td>${compra.sucursal}</td>
                        <td>${compra.bodega}</td>
                        <td>${compra.prov_datos}</td>
                        <td>${compra.dato_fac}</td>
                        <td>${compra.fecha_emision}</td>
                        <td>${compra.cred_cont}</td>
                        <td>${compra.dias_credito}</td>
                        <td>${compra.porcentaje_prima}</td>
                        <td>${compra.cuotas_pendientes}</td>
                        <td>${compra.fech_vence}</td>
                        <td>${compra.isv_global}</td>
                        <td>${compra.sub_total.toLocaleString('es-ES', { style: 'currency', currency: 'HNL' })}</td>
                        <td>${compra.total.toLocaleString('es-ES', { style: 'currency', currency: 'HNL' })}</td>
                        <td>${compra.fecha_registro}</td>
                        <td>
                            <button class="btn_ver_detalles" onclick="verDetalles(${compra.id_compra})">Ver Detalles</button>
                        </td>

                    </tr>
                `;
                tbody.append(row);
            });

// Aplicar DataTables con paginación, búsqueda y exportación
$('#compras_table').DataTable({
    destroy: true,
    pageLength: 10,
    dom: 'Bfrtip',
    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
    order: [[15, 'desc']],  // Orden descendente en la columna de "Fecha Registro"
    scrollX: true,
    scrollCollapse: true,
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



        },
        error: function() {
            alert("Error al cargar los datos de compras.");
        }
    });
}


window.verDetalles = function(idCompra) {
    $.ajax({
        url: "../../backend/registros/obtener_detalle_compras.php",
        type: "POST",
        data: { id_compra: idCompra },
        success: function(data) {
            console.log("Respuesta de obtener_detalle_compras.php:", data);

            try {
                const detalles = (typeof data === 'string') ? JSON.parse(data) : data;
                if (detalles.length > 0) {
                    let detallesHTML = `
                        <div style="overflow-x:auto; width:100%; max-width:100%; box-sizing:border-box;">
                            <h3>ID: ${idCompra}</h3>
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Cuenta</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Código Producto</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Descripción</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Cantidad</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Unidad</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Precio Unitario</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">ISV</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Subtotal</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Descuento %</th>
                                        <th style="border: 1px solid #e0e0e0; padding: 8px; background-color: #06adbf; color: white;">Total</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    detalles.forEach(item => {
                        detallesHTML += `
                            <tr>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${item.cat_cuenta}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${item.codigo_producto}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${item.descripcion}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${item.cantidad}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${item.unidad}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${parseFloat(item.precio_unitario).toFixed(2)}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${parseFloat(item.isv).toFixed(2)}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${item.subtotal}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${parseFloat(item.descuento_porcentaje).toFixed(2)}</td>
                                <td style="border: 1px solid #e0e0e0; padding: 8px;">${item.total_item}</td>
                                
                            </tr>`;
                    });

                    detallesHTML += `</tbody></table></div>`;

                    // Modal de SweetAlert2 (mismo enfoque que Diario General)
                    Swal.fire({
                        title: "Detalles de la Compra",
                        html: detallesHTML,
                        width: "70rem",
                        confirmButtonText: "Cerrar",
                        customClass: {
                            popup: 'swal2-detalle-compra',
                            htmlContainer: 'swal2-detalle-compra-html'
                        }
                    });
                } else {
                    Swal.fire("No se encontraron detalles", "No hay detalles disponibles para esta compra.", "info");
                }
            } catch (e) {
                console.error("Error al analizar la respuesta:", e);
                Swal.fire("Error", "Hubo un problema al cargar los detalles.", "error");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error de AJAX:", status, error);
            Swal.fire("Error", "No se pudo cargar los detalles de la compra.", "error");
        }
    });
}



    });
</script>

<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- NAVBAR -->
    
    <script src="../../backend/js/script.js"></script>

</body>
</html>