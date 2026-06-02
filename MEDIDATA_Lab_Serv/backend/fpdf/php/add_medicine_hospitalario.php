<?php
// Configurar la zona horaria de Tegucigalpa, Honduras
date_default_timezone_set('America/Tegucigalpa');
$currentDateTime = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

require_once('../../backend/bd/Conexion.php');

if (isset($_POST['add_medicine'])) {
    try {
        // Obtener y limpiar datos del formulario
        $codpro = strtoupper(trim($_POST['codpro'] ?? ''));
        $codbars = strtoupper(trim($_POST['codbars'] ?? ''));
        $nompro = strtoupper(trim($_POST['mediname'] ?? ''));
        $principio_activo = strtoupper(trim($_POST['principio_activo'] ?? ''));
        $idcat = trim($_POST['medicate'] ?? null);
        $preprd = trim($_POST['mediprec'] ?? 0);
        $stock = trim($_POST['medistoc'] ?? 0);
        $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
        $via_administracion = strtoupper(trim($_POST['via_administracion'] ?? ''));
        $concentracion = strtoupper(trim($_POST['concentracion'] ?? ''));
        $forma_farmaceutica = strtoupper(trim($_POST['forma_farmaceutica'] ?? ''));
        $presentacion = strtoupper(trim($_POST['presentacion'] ?? ''));
        $sub_linea = strtoupper(trim($_POST['sub_linea'] ?? ''));
        $linea = strtoupper(trim($_POST['linea'] ?? ''));
        $margen_ganancia = floatval($_POST['margen_ganancia'] ?? 0);
        $impuesto = trim($_POST['impuesto'] ?? ''); // 'G' o 'E'
        $adj_foto = null;

        // Validar que el impuesto sea válido
        if (!in_array($impuesto, ['G', 'E'], true)) {
            echo '<script>
                Swal.fire({
                    title: "Error",
                    text: "Debe seleccionar un impuesto válido (Gravado o Exento).",
                    icon: "error",
                    button: "Aceptar"
                }).then(function() {
                    window.location = "../almacen_hospitalario/nuevo_user.php";
                });
            </script>';
            exit;
        }

        // Calcular el precio de venta
        $precioVenta = $preprd + ($preprd * ($margen_ganancia / 100));
        if ($impuesto === 'G') {
            $precioVenta += $precioVenta * 0.15; // 15% de impuesto si es Gravado
        }

        // Procesar y mover la imagen si se proporciona
        if (isset($_FILES['adj_foto']) && $_FILES['adj_foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['adj_foto']['tmp_name'];
            $origName = (string) $_FILES['adj_foto']['name'];

            // Sanear nombre del archivo: tabla_almacen_hospitalario.js (igual
            // que tabla_almacen.js) elimina con /[^a-zA-Z0-9._\-\/]/g cualquier
            // caracter no alfanumérico, así que debemos guardar el archivo con
            // ese mismo formato para que la URL coincida con el archivo en disco.
            $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));
            $ext = preg_replace('/[^a-z0-9]/', '', $ext);
            if ($ext === '') {
                $ext = 'jpg';
            }
            $base = (string) pathinfo($origName, PATHINFO_FILENAME);
            $base = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base;
            $base = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $base);
            $base = trim($base, '_.');
            if ($base === '') {
                $base = 'foto';
            }
            try {
                $unique = bin2hex(random_bytes(3));
            } catch (Throwable $eRnd) {
                $unique = substr(md5(uniqid('', true)), 0, 6);
            }
            $fileName = $base . '_' . date('YmdHis') . '_' . $unique . '.' . $ext;

            $uploadFileDir = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR
                . 'uploads' . DIRECTORY_SEPARATOR . 'adj_foto' . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadFileDir)) {
                @mkdir($uploadFileDir, 0755, true);
            }
            $destPath = $uploadFileDir . $fileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $adj_foto = 'uploads/adj_foto/' . $fileName;
            } else {
                throw new Exception("Error al subir la imagen");
            }
        }

        // Insertar un nuevo registro en la base de datos
        $stmt_insert = $connect->prepare("INSERT INTO almacen_hospitalario(
            codpro, codbars, nompro, principio_activo, idcat, preprd, stock, state, fecha_vencimiento, via_administracion, 
            concentracion, forma_farmaceutica, presentacion, sub_linea, linea, margen_ganancia, 
            impuesto, precio_venta, adj_foto, fere
        ) VALUES (
            :codpro, :codbars, :nompro, :principio_activo, :idcat, :preprd, :stock, '1', :fecha_vencimiento, :via_administracion, 
            :concentracion, :forma_farmaceutica, :presentacion, :sub_linea, :linea, :margen_ganancia, 
            :impuesto, :precio_venta, :adj_foto, :fere
        )");

        $stmt_insert->bindParam(':codpro', $codpro);
        $stmt_insert->bindParam(':codbars', $codbars);
        $stmt_insert->bindParam(':nompro', $nompro);
        $stmt_insert->bindParam(':principio_activo', $principio_activo);
        $stmt_insert->bindParam(':idcat', $idcat);
        $stmt_insert->bindParam(':preprd', $preprd);
        $stmt_insert->bindParam(':stock', $stock);
        $stmt_insert->bindParam(':fecha_vencimiento', $fecha_vencimiento);
        $stmt_insert->bindParam(':via_administracion', $via_administracion);
        $stmt_insert->bindParam(':concentracion', $concentracion);
        $stmt_insert->bindParam(':forma_farmaceutica', $forma_farmaceutica);
        $stmt_insert->bindParam(':presentacion', $presentacion);
        $stmt_insert->bindParam(':sub_linea', $sub_linea);
        $stmt_insert->bindParam(':linea', $linea);
        $stmt_insert->bindParam(':margen_ganancia', $margen_ganancia);
        $stmt_insert->bindParam(':impuesto', $impuesto);
        $stmt_insert->bindParam(':precio_venta', $precioVenta);
        $stmt_insert->bindParam(':adj_foto', $adj_foto);
        $stmt_insert->bindParam(':fere', $currentDateTime);
        $stmt_insert->execute();

        echo '<script>
            Swal.fire({
                title: "¡Éxito!",
                text: "Producto registrado correctamente",
                icon: "success",
                button: "Aceptar"
            }).then(function() {
                window.location = "../almacen_hospitalario/mostrar_user.php";
            });
        </script>';

    } catch (Exception $e) {
        echo '<script>
            Swal.fire({
                title: "Error",
                text: "' . $e->getMessage() . '",
                icon: "error",
                button: "Aceptar"
            }).then(function() {
                window.location = "../almacen_hospitalario/nuevo_user.php";
            });
        </script>';
    }
}
?>