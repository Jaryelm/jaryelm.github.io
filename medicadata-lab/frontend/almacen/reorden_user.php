<?php
require_once('../../backend/bd/Conexion.php');
?>
<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />



    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../almacen/menu.php';
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
include_once '../almacen/perfil.php';
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


        <!-- Opciones de Navegación -->
        <button class="button" onclick="cambiarColor(this, 'compra_unificada.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras_user.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_user.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'reorden_user.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones_user.php')">Requisiciones</button>


<form action="" method="POST" autocomplete="off" id="reorderForm">
    <div class="containerss">
        <h1>Punto de Reorden</h1>
        <br>
        <div class="alert-danger">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
        </div>
        <hr>
        <br>

        <!-- Tabla Dinámica para Agregar Productos -->
        <table id="productosTable" class="responsive-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Precio</th>
                    <th>Proveedor</th>
                    <th>Cantidad Solicitada</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <!-- Filas se agregarán dinámicamente -->
            </tbody>
        </table>

        <div style="margin: 10px 0;">
            <button type="button" id="toggleMode" class="button" style="background-color: #06adbf;">
                <i class='bx bxs-edit'></i> Manual
            </button>
            <button type="button" id="addProductButton" class="button">Agregar Producto</button>
        </div>

        <br><br>

        <!-- Campo Justificación -->
        <label for="justificacion"><b>Justificación</b></label>
        <textarea id="justificacion" name="justificacion" rows="4"></textarea>

        <hr>
        <button type="submit" name="submit_reorden" class="registerbtn">Enviar Solicitud</button>
    </div>
</form>

<script>
    $(document).ready(function () {
        let productCounter = 0;
        let isManualMode = false;

        // Función para cambiar el modo
        function toggleMode() {
            isManualMode = !isManualMode;
            const toggleButton = $('#toggleMode');
            const icon = toggleButton.find('i');
            
            if (isManualMode) {
                toggleButton.html('<i class="bx bxs-check-circle"></i> Automático');
                toggleButton.css('background-color', '#f39c12');
                $('input[name$="[stock_actual]"], input[name$="[precio_producto]"], input[name$="[proveedor]"]').prop('readonly', false);
            } else {
                toggleButton.html('<i class="bx bxs-edit"></i> Manual');
                toggleButton.css('background-color', '#06adbf');
                $('input[name$="[stock_actual]"], input[name$="[precio_producto]"], input[name$="[proveedor]"]').prop('readonly', true);
            }
        }

        // Evento para el botón de toggle
        $('#toggleMode').on('click', toggleMode);

        // Función para agregar una nueva fila al formulario
        function addProductRow() {
            const rowId = 'product_' + productCounter;
            const newRow = `
                <tr id="${rowId}">
                    <td>
                        <select class="select2 product-select" name="productos[${productCounter}][id]" required style="width: 100%;">
                            <option value="">Seleccione un producto</option>
                            ${getProductOptions()}
                        </select>
                    </td>
                    <td><input type="number" name="productos[${productCounter}][stock_actual]" ${!isManualMode ? 'readonly' : ''} required></td>
                    <td><input type="text" name="productos[${productCounter}][precio_producto]" ${!isManualMode ? 'readonly' : ''} required></td>
                    <td><input type="text" name="productos[${productCounter}][proveedor]" ${!isManualMode ? 'readonly' : ''} required></td>
                    <td><input type="number" name="productos[${productCounter}][cantidad_solicitada]" min="1" required></td>
                    <td><button type="button" class="removeProductButton" data-row-id="${rowId}">Eliminar</button></td>
                </tr>
            `;
            $('#productosTable tbody').append(newRow);
            $('.select2').select2();
            productCounter++;
        }

        function getProductOptions() {
    let options = '';
    <?php
    $stmt = $connect->query("
        SELECT 
            p.idprcd, 
            p.nompro, 
            p.stock, 
            p.preprd,
            p.stock_minimo,
            CASE 
                WHEN rs.producto_id IS NOT NULL THEN 'solicitado' 
                ELSE 'no_solicitado' 
            END AS estado_reorden
        FROM product p
        LEFT JOIN reorden_solicitudes rs 
            ON p.idprcd = rs.producto_id AND rs.estado = 'pendiente'
        WHERE p.stock <= p.stock_minimo
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $disabled = ($row['estado_reorden'] === 'solicitado') ? 'disabled' : '';
        $style = ($row['estado_reorden'] === 'solicitado') ? 'color: gray; font-style: italic;' : '';
        echo "options += '<option value=\"{$row['idprcd']}\" data-stock=\"{$row['stock']}\" data-price=\"{$row['preprd']}\" data-stock-minimo=\"{$row['stock_minimo']}\" {$disabled} style=\"{$style}\">{$row['nompro']} (Stock: {$row['stock']}, Mínimo: {$row['stock_minimo']}, Precio: LPS. {$row['preprd']})";
        if ($row['estado_reorden'] === 'solicitado') {
            echo ' - Solicitado';
        }
        echo "</option>';";
    }
    ?>
    return options;
}

        // Evento para agregar una nueva fila
        $('#addProductButton').on('click', function () {
            addProductRow();
        });

        // Evento para eliminar una fila
        $(document).on('click', '.removeProductButton', function () {
            const rowId = $(this).data('row-id');
            $('#' + rowId).remove();
        });

        // Evento para manejar la selección de productos
        $(document).on('select2:select', '.product-select', function (e) {
            if (!isManualMode) {
                const selectedOption = e.params.data.element;
                const stockActual = $(selectedOption).data('stock');
                const precioProducto = $(selectedOption).data('price');
                const productoNombre = $(selectedOption).text().split('(')[0].trim();

                const row = $(this).closest('tr');
                row.find('input[name$="[stock_actual]"]').val(stockActual);
                row.find('input[name$="[precio_producto]"]').val(precioProducto);

                // Obtener el proveedor mediante AJAX
                $.ajax({
                    url: 'obtener_proveedor.php',
                    method: 'POST',
                    data: { producto_nombre: productoNombre },
                    success: function (response) {
                        row.find('input[name$="[proveedor]"]').val(response);
                    },
                    error: function () {
                        row.find('input[name$="[proveedor]"]').val('Proveedor no encontrado');
                    }
                });
            }
        });

        // Serializar el formulario antes de enviarlo
        $('#reorderForm').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();
            console.log("Datos enviados:", formData); // Depuración: Verifica los datos antes de enviarlos

            $.ajax({
                url: '../../backend/php/reorden_solicitudes.php',
                method: 'POST',
                data: formData,
                success: function (response) {
                    swal("Éxito", "Solicitud de reorden enviada correctamente", "success").then(() => location.reload());
                },
                error: function (xhr, status, error) {
                    console.error("Error en la solicitud:", xhr.responseText);
                    swal("Error", "Hubo un problema al procesar la solicitud", "error");
                }
            });
        });
    });
</script>

<style>
    .select2-results__option[aria-disabled="true"] {
        color: gray !important;
        font-style: italic;
    }
</style>

<style>
    /* Estilo para el botón "Eliminar" */
    .removeProductButton {
        background-color: #e74c3c; /* Rojo */
        color: white; /* Texto blanco */
        border: none; /* Sin borde */
        padding: 8px 12px; /* Espaciado interno */
        border-radius: 4px; /* Bordes redondeados */
        cursor: pointer; /* Cursor de puntero */
        font-size: 14px; /* Tamaño de fuente */
        transition: background-color 0.3s ease; /* Transición suave */
    }

    /* Cambio de color al pasar el mouse */
    .removeProductButton:hover {
        background-color: #c0392b; /* Rojo más oscuro */
    }

    /* Diseño responsivo para pantallas pequeñas */
    @media (max-width: 768px) {
        .removeProductButton {
            padding: 6px 10px; /* Reducir espaciado en pantallas pequeñas */
            font-size: 12px; /* Reducir tamaño de fuente */
        }
    }

    #toggleMode {
        margin-right: 10px;
        transition: all 0.3s ease;
    }

    #toggleMode i {
        margin-right: 5px;
    }

    input[readonly] {
        background-color: #f5f5f5;
    }

    input:not([readonly]) {
        background-color: #fff;
    }
</style>
           
        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/cat.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/cat_cuentas.js"></script>
    <script src="../../backend/js/cat_cuentas_reg.js"></script>
    <script src="../../backend/js/cat_proveedores.js"></script>
    <script src="../../backend/js/cat_descripcion.js"></script>
    <script src="../../backend/js/linea_mostrar_campos.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- activa y desactiva campos 
    <script src='../../backend/js/active_desactive_campos.js'></script>-->

    <!-- Include jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

</body>
</html>