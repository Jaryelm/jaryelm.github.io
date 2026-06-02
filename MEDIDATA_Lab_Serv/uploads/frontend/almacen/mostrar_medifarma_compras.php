<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA - MEDIFARMA</title>
</head>
<body>
    
<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
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

        <!-- Botones de navegación -->
        <button class="button" onclick="cambiarColor(this, 'medifarma_compras_seg.php')">Registrar Compras Medifarma</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_medifarma_compras.php')">Compras Medifarma Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'nuevo.php')">Registrar Inventario</button>
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
        <h1>Compras Medifarma Registradas</h1>
    </div>
        <br>
<!-- Agrega el contenedor alrededor de la tabla -->
<div style="overflow-x: auto; max-width: 100%;">
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
</div>

<style>
    /* Contenedor para la tabla con scroll horizontal */
#compras_table {
    min-width: 1500px; /* Ajusta el ancho mínimo según el número de columnas */
}

#compras_table_wrapper {
    overflow-x: auto;
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
        url: "../../backend/registros/obtener_medifarma_compras.php",
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
                        <td>${compra.dias_credito || '-'}</td>
                        <td>${compra.porcentaje_prima || '-'}</td>
                        <td>${compra.cuotas_pendientes || '-'}</td>
                        <td>${compra.fech_vence}</td>
                        <td>${compra.isv_global}</td>
                        <td>${compra.sub_total}</td>
                        <td>${compra.total}</td>
                        <td>${compra.fecha_registro}</td>
                        <td><button class="btn_ver_detalles" onclick="verDetalles(${compra.id_compra})">Ver Detalles</button></td>
                    </tr>
                `;
                tbody.append(row);
            });
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar las compras:", error);
        }
    });
}

        // Función para ver detalles de una compra
        window.verDetalles = function(idCompra) {
            $.ajax({
                url: "../../backend/registros/obtener_medifarma_detalle_compras.php",
                type: "POST",
                data: { id_compra: idCompra },
                success: function(data) {
                    const detalles = JSON.parse(data);
                    let detallesHTML = '<h3>Detalles de la Compra</h3><table border="1" style="width:100%; border-collapse: collapse;">';
                    detallesHTML += '<tr><th>Código</th><th>Cantidad</th><th>Unidad</th><th>Descripción</th><th>Precio Unitario</th><th>ISV</th><th>Subtotal</th><th>Total Item</th></tr>';
                    
                    detalles.forEach(detalle => {
                        detallesHTML += `<tr>
                            <td>${detalle.codigo_producto}</td>
                            <td>${detalle.cantidad}</td>
                            <td>${detalle.unidad}</td>
                            <td>${detalle.descripcion}</td>
                            <td>${detalle.precio_unitario}</td>
                            <td>${detalle.isv}</td>
                            <td>${detalle.subtotal}</td>
                            <td>${detalle.total_item}</td>
                        </tr>`;
                    });
                    
                    detallesHTML += '</table>';
                    
                    // Mostrar en un modal o alert
                    alert(detallesHTML);
                },
                error: function(xhr, status, error) {
                    console.error("Error al cargar los detalles:", error);
                }
            });
        };
    });

    // SubMenu
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
</script>

<style>
    .btn_ver_detalles {
        background-color: #06adbf;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 12px;
    }

    .btn_ver_detalles:hover {
        background-color: #035c67;
    }

    .table-title {
        text-align: center;
        margin-bottom: 20px;
    }

    .table-title h1 {
        color: #06adbf;
        font-size: 24px;
        margin: 0;
    }
</style>
</body>
</html> 