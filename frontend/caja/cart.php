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
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                    
                </div>
            </form>
            
           
            <span class="divider"></span>
<?php
include_once '../caja/perfil.php';
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

    <!-- Botones de Navegación -->
    <button class="button" onclick="cambiarColor(this, '../caja/new_sale.php')">Nueva Venta</button>
    <button class="button" onclick="cambiarColor(this, '../caja/cart.php')">Procesar Venta</button>
    <button class="button" onclick="cambiarColor(this, '#')">Cotizaciones</button>
    <button class="button" onclick="cambiarColor(this, '#')">Estados de Cuenta</button>
    <button class="button" onclick="cambiarColor(this, '../caja/venta.php')">Resumen de Ventas</button>
    <button class="button" onclick="cambiarColor(this, '../caja/mostrar.php')">Resumen de Citas</button>

    <div class="data">
    <div class="content-data">
        <div class="head">
            <h3>Procesar Venta</h3>
        </div>
        <div class="table-responsive" style="overflow-x:auto;">
            <table id="example" class="responsive-table">
            <thead>
    <tr>
        <th scope="col">Código</th>
        <th scope="col">Nombre</th>
        <th scope="col">Precio</th>
        <th scope="col">Stock</th>
        <th scope="col">Cantidad</th>
        <th scope="col">Descuento General %</th>
        <th scope="col">3ra Edad (30%)</th>
        <th scope="col">4ta Edad (40%)</th>
        <th scope="col">Promoción %</th>
        <th scope="col">Otros (LPS.)</th>
        <th scope="col">Subtotal</th>
        <th scope="col">Descuento Aplicado</th>
        <th scope="col">Total con Descuento</th>
        <th scope="col">Eliminar</th>
    </tr>
</thead>
<tbody>
    <?php
    $grand_total = 0;

    $select_cart = $connect->prepare("SELECT cart.idv, cart.user_id, cart.name, cart.price, cart.quantity, cart.type, cart.discount, cart.age_discount_30, cart.age_discount_40, cart.promotion_discount, cart.other_discount,
        product.codpro AS prod_cod, product.stock AS prod_stock,
        servicios_hospital.codigo_servicio AS serv_cod,
        almacen_hospitalario.codpro AS hosp_cod, almacen_hospitalario.stock AS hosp_stock
        FROM cart
        LEFT JOIN product ON cart.idprcd = product.idprcd AND cart.type = 'producto'
        LEFT JOIN servicios_hospital ON cart.id_servicio = servicios_hospital.id AND cart.type = 'servicio'
        LEFT JOIN almacen_hospitalario ON cart.id_producto_hospitalario = almacen_hospitalario.idprcd AND cart.type = 'producto_hospitalario'
        WHERE cart.user_id = ?");
    $select_cart->execute([$_SESSION['id']]);

    if ($select_cart->rowCount() > 0) {
        while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
            $is_product = $fetch_cart['type'] === 'producto';
            $is_hospitalario = $fetch_cart['type'] === 'producto_hospitalario';
            
            // Determinar el código y stock según el tipo
            if ($is_product) {
                $codigo = $fetch_cart['prod_cod'];
                $stock = $fetch_cart['prod_stock'];
            } elseif ($is_hospitalario) {
                $codigo = $fetch_cart['hosp_cod'];
                $stock = $fetch_cart['hosp_stock'];
            } else {
                $codigo = $fetch_cart['serv_cod'];
                $stock = 'N/A';
            }
            
            $sub_total = $fetch_cart['price'] * $fetch_cart['quantity'];

            // Descuentos
            $discount_percent = $fetch_cart['discount'] ?? 0;
            $age_discount_30 = $fetch_cart['age_discount_30'] ? 30 : 0;
            $age_discount_40 = $fetch_cart['age_discount_40'] ? 40 : 0;
            $promotion_discount = $fetch_cart['promotion_discount'] ?? 0;
            // Otros descuento ahora es monto fijo en Lempiras, no porcentaje
            $other_discount_amount = floatval($fetch_cart['other_discount'] ?? 0);

            // Calcular el descuento total
            // Descuentos en porcentaje
            $total_discount_percent = $discount_percent + $age_discount_30 + $age_discount_40 + $promotion_discount;
            $discount_amount_percent = ($sub_total * $total_discount_percent) / 100;
            // Descuento "Otros" es monto fijo, se suma directamente
            $discount_amount = $discount_amount_percent + $other_discount_amount;
            $sub_total_after_discount = $sub_total - $discount_amount;
            $grand_total += $sub_total_after_discount;
    ?>
    <tr>
        <th scope="row"><?= htmlspecialchars($codigo); ?></th>
        <td data-title="Nombre"><?= htmlspecialchars($fetch_cart['name']); ?></td>
        <td data-title="Precio">LPS. <?= number_format($fetch_cart['price'], 2); ?></td>
        <td data-title="Stock"><?= $stock; ?></td>
        <td data-title="Cantidad">
            <form action="" method="POST">
                <input type="hidden" name="prdt" value="<?= htmlspecialchars($fetch_cart['idv']); ?>">
                <input type="number" name="p_qty" value="<?= htmlspecialchars($fetch_cart['quantity']); ?>" style="width:100px;" min="1" <?= $is_product ? "max='" . htmlspecialchars($fetch_cart['prod_stock']) . "'" : ""; ?> class="form-control" placeholder="Cantidad">
                <button type="submit" name="update_qty" class="btn btn-primary"><i class="fa fa-refresh"></i></button>
            </form>
        </td>
        <td data-title="Descuento %">
            <form action="" method="POST">
                <input type="hidden" name="prdt" value="<?= htmlspecialchars($fetch_cart['idv']); ?>">
                <input type="number" name="discount" value="<?= htmlspecialchars($fetch_cart['discount'] ?? 0); ?>" style="width:80px;" min="0" max="100" class="form-control" placeholder="% Descuento">
                <button type="submit" name="update_discount" class="btn btn-primary"><i class="fa fa-refresh"></i></button>
            </form>
        </td>
        <td data-title="3ra Edad (30%)">
    <form action="" method="POST">
        <input type="hidden" name="prdt" value="<?= htmlspecialchars($fetch_cart['idv']); ?>">
        <select name="age_discount_30" class="form-control">
            <option value="0" <?= !$fetch_cart['age_discount_30'] ? 'selected' : ''; ?>>Desactivado</option>
            <option value="1" <?= $fetch_cart['age_discount_30'] ? 'selected' : ''; ?>>Activado</option>
        </select>
        <button type="submit" name="update_age_discount_30" class="btn btn-primary"><i class="fa fa-refresh"></i></button>
    </form>
</td>
<td data-title="4ta Edad (40%)">
    <form action="" method="POST">
        <input type="hidden" name="prdt" value="<?= htmlspecialchars($fetch_cart['idv']); ?>">
        <select name="age_discount_40" class="form-control">
            <option value="0" <?= !$fetch_cart['age_discount_40'] ? 'selected' : ''; ?>>Desactivado</option>
            <option value="1" <?= $fetch_cart['age_discount_40'] ? 'selected' : ''; ?>>Activado</option>
        </select>
        <button type="submit" name="update_age_discount_40" class="btn btn-primary"><i class="fa fa-refresh"></i></button>
    </form>
</td>
        <td data-title="Promoción %">
            <form action="" method="POST">
                <input type="hidden" name="prdt" value="<?= htmlspecialchars($fetch_cart['idv']); ?>">
                <input type="number" name="promotion_discount" value="<?= htmlspecialchars($fetch_cart['promotion_discount'] ?? 0); ?>" style="width:80px;" min="0" max="100" class="form-control" placeholder="% Promoción">
                <button type="submit" name="update_promotion" class="btn btn-primary"><i class="fa fa-refresh"></i></button>
            </form>
        </td>
        <td data-title="Otros (LPS.)">
            <form action="" method="POST">
                <input type="hidden" name="prdt" value="<?= htmlspecialchars($fetch_cart['idv']); ?>">
                <input type="number" name="other_discount" value="<?= htmlspecialchars($fetch_cart['other_discount'] ?? 0); ?>" style="width:100px;" min="0" step="0.01" class="form-control" placeholder="LPS. 0.00">
                <button type="submit" name="update_other" class="btn btn-primary"><i class="fa fa-refresh"></i></button>
            </form>
        </td>
        <td data-title="Subtotal">LPS. <?= number_format($sub_total, 2); ?></td>
        <td data-title="Descuento LPS.">LPS. <?= number_format($discount_amount, 2); ?></td>
        <td data-title="Total con Descuento">LPS. <?= number_format($sub_total_after_discount, 2); ?></td>
        <td>
            <a title="Eliminar" onclick="return confirm('Eliminar del carrito?');" href="eliminar.php?id=<?= htmlspecialchars($fetch_cart['idv']); ?>" class="fa fa-trash"></a>
        </td>
    </tr>
    <?php
        }
    } else {
        echo '<p class="alert alert-warning">Tus Ventas Estan Vacias</p>';
    }
    ?>
</tbody>
            </table>
            <h1 style="font-size:42px; color:#000000;">Precio Total: LPS.<?php echo number_format($grand_total, 2); ?></h1>
        </div>
        <div>
            <button onclick="location.href='new_sale.php'" class="registerbtn">CONTINUAR COMPRANDO </button>
            <button class="pabtn <?= ($grand_total > 1) ? '' : 'disabled'; ?>" onclick="location.href='checkout.php'">PROCEDER PAGO</button>
        </div>
    </div>
</div>

<?php

// Obtener valores de POST
$cart_id = $_POST['prdt'] ?? null; // ID del producto/servicio en el carrito
$new_quantity = $_POST['p_qty'] ?? null; // Cantidad actualizada
$new_discount = $_POST['discount'] ?? null; // Descuento general
$new_age_discount_30 = $_POST['age_discount_30'] ?? 0; // Dropdown 3ra Edad (0 o 1)
$new_age_discount_40 = $_POST['age_discount_40'] ?? 0; // Dropdown 4ta Edad (0 o 1)
$new_promotion_discount = $_POST['promotion_discount'] ?? null; // Descuento Promoción
$new_other_discount = $_POST['other_discount'] ?? null; // Descuento Otros

try {
    // Actualizar la cantidad en el carrito
    if (isset($_POST['update_qty']) && $cart_id && $new_quantity !== null) {
        $update_cart_stmt = $connect->prepare("UPDATE cart SET quantity = ? WHERE idv = ?");
        $update_cart_stmt->execute([(int)$new_quantity, $cart_id]);
        echo '<script type="text/javascript">
            swal("Actualizado!", "Cantidad actualizada correctamente.", "success")
                .then(function() {
                    window.location.href = window.location.pathname; // Recarga la página para reflejar los cambios
                });
        </script>';
        exit;
    }

    // Actualizar el descuento general
    if (isset($_POST['discount']) && $cart_id && $new_discount !== null) {
        $update_discount_stmt = $connect->prepare("UPDATE cart SET discount = ? WHERE idv = ?");
        $update_discount_stmt->execute([(float)$new_discount, $cart_id]);
        echo '<script type="text/javascript">
            swal("Actualizado!", "Descuento general aplicado correctamente.", "success")
                .then(function() {
                    window.location.href = window.location.pathname; // Recarga la página para reflejar los cambios
                });
        </script>';
        exit;
    }

    // Manejar descuento del 30% (3ra Edad)
    if (isset($_POST['update_age_discount_30']) && $cart_id) {
        $new_age_discount_30 = $_POST['age_discount_30'] ?? 0; // Dropdown value
        $update_age30_stmt = $connect->prepare("UPDATE cart SET age_discount_30 = ? WHERE idv = ?");
        $update_age30_stmt->execute([(int)$new_age_discount_30, $cart_id]);
        echo '<script type="text/javascript">
            swal("Actualizado!", "Descuento de 3ra Edad aplicado correctamente.", "success")
                .then(function() {
                    window.location.href = window.location.pathname; // Recarga la página para reflejar los cambios
                });
        </script>';
        exit;
    }

    // Manejar descuento del 40% (4ta Edad)
    if (isset($_POST['update_age_discount_40']) && $cart_id) {
        $new_age_discount_40 = $_POST['age_discount_40'] ?? 0; // Dropdown value
        $update_age40_stmt = $connect->prepare("UPDATE cart SET age_discount_40 = ? WHERE idv = ?");
        $update_age40_stmt->execute([(int)$new_age_discount_40, $cart_id]);
        echo '<script type="text/javascript">
            swal("Actualizado!", "Descuento de 4ta Edad aplicado correctamente.", "success")
                .then(function() {
                    window.location.href = window.location.pathname; // Recarga la página para reflejar los cambios
                });
        </script>';
        exit;
    }

    // Actualizar el descuento de Promoción
    if (isset($_POST['promotion_discount']) && $cart_id && $new_promotion_discount !== null) {
        $update_promotion_stmt = $connect->prepare("UPDATE cart SET promotion_discount = ? WHERE idv = ?");
        $update_promotion_stmt->execute([(float)$new_promotion_discount, $cart_id]);
        echo '<script type="text/javascript">
            swal("Actualizado!", "Descuento de Promoción aplicado correctamente.", "success")
                .then(function() {
                    window.location.href = window.location.pathname; // Recarga la página para reflejar los cambios
                });
        </script>';
        exit;
    }

    // Actualizar el descuento de Otros
    if (isset($_POST['other_discount']) && $cart_id && $new_other_discount !== null) {
        $update_other_stmt = $connect->prepare("UPDATE cart SET other_discount = ? WHERE idv = ?");
        $update_other_stmt->execute([(float)$new_other_discount, $cart_id]);
        echo '<script type="text/javascript">
            swal("Actualizado!", "Descuento de Otros aplicado correctamente.", "success")
                .then(function() {
                    window.location.href = window.location.pathname; // Recarga la página para reflejar los cambios
                });
        </script>';
        exit;
    }

} catch (PDOException $e) {
    // Manejo de errores SQL
    echo '<script type="text/javascript">
        swal("Error!", "No se pudo actualizar el carrito: ' . htmlspecialchars($e->getMessage()) . '", "error");
    </script>';
}
?>

</main>

        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
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
});
</script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- Función Cierre de Caja -->
    <script src='../../backend/registros/script/cierre_caja.js'></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<?php include_once '../../backend/php/upd_cart.php' ?>
</body>
</html>




