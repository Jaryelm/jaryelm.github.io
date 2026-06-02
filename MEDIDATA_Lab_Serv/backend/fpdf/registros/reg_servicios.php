<?php
if (isset($_POST['add_service'])) {
    require '../../backend/bd/Conexion.php';

    // Configurar la zona horaria de Tegucigalpa, Honduras
    date_default_timezone_set('America/Tegucigalpa');
    $currentDateTime = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

    // Obtener y limpiar datos del formulario
    $nombre_servicio = strtoupper(trim($_POST['service_name']));
    $nomservicio = strtoupper(trim($_POST['nomservicio']));
    $codigo_servicio = strtoupper(trim($_POST['service_code']));
    $uso_servicio = strtoupper(trim($_POST['uso_servicio'])); // Nuevo campo: Uso del Servicio
    $categoria_servicio = strtoupper(trim($_POST['categoria_servicio'])); // Nuevo campo: Categoría del Servicio
    $precio_costo = floatval($_POST['cost_price']);
    $margen_ganancia = floatval($_POST['profit_margin']);
    $impuesto = $_POST['tax'] ?? ''; // Captura el valor de 'G' o 'E' directamente
    $precio_venta = floatval($_POST['sale_price']);
    $total = floatval($_POST['price_total']);

    // Validar el valor del impuesto
    if (!in_array($impuesto, ['G', 'E'], true)) {
        echo '<script>
            Swal.fire("ERROR!", "Impuesto seleccionado no es válido", "error")
            .then(function() {
                window.location = "nuevo_servicio.php";
            });
            </script>';
        exit;
    }

    try {
        // Validar campos vacíos
        if (empty($nombre_servicio) || empty($nomservicio) || empty($codigo_servicio) || empty($uso_servicio) || empty($categoria_servicio) || $precio_costo <= 0 || $precio_venta <= 0 || $total <= 0) {
            echo '<script>
                Swal.fire("ERROR!", "POR FAVOR COMPLETE TODOS LOS CAMPOS CORRECTAMENTE", "error")
                .then(function() {
                    window.location = "nuevo_servicio.php";
                });
                </script>';
            exit;
        }

        // Insertar el servicio directamente sin validar duplicidad de código
        $stmt = $connect->prepare("
            INSERT INTO servicios_hospital 
            (nombre_servicio, nomservicio, codigo_servicio, uso_servicio, categoria_servicio, precio_costo, margen_ganancia, impuesto, precio_venta, total, fecha_creacion) 
            VALUES 
            (:nombre_servicio, :nomservicio, :codigo_servicio, :uso_servicio, :categoria_servicio, :precio_costo, :margen_ganancia, :impuesto, :precio_venta, :total, :fecha_creacion)
        ");
    
        // Vinculación de parámetros
        $stmt->bindParam(':nombre_servicio', $nombre_servicio);
        $stmt->bindParam(':nomservicio', $nomservicio);
        $stmt->bindParam(':codigo_servicio', $codigo_servicio);
        $stmt->bindParam(':uso_servicio', $uso_servicio); 
        $stmt->bindParam(':categoria_servicio', $categoria_servicio);
        $stmt->bindParam(':precio_costo', $precio_costo);
        $stmt->bindParam(':margen_ganancia', $margen_ganancia);
        $stmt->bindParam(':impuesto', $impuesto); // Vincula el valor de 'G' o 'E'
        $stmt->bindParam(':precio_venta', $precio_venta);
        $stmt->bindParam(':total', $total);
        $stmt->bindParam(':fecha_creacion', $currentDateTime);

        if ($stmt->execute()) {
            echo '<script>
                Swal.fire("Agregado!", "Servicio Agregado", "success")
                .then(function() {
                    window.location = "nuevo_servicio.php";
                });
                </script>';
        } else {
            echo '<script>
                Swal.fire("Error!", "Hubo Problemas al Registrar el Servicio", "error")
                .then(function() {
                    window.location = "nuevo_servicio.php";
                });
                </script>';
        }
    } catch (PDOException $e) {
        echo '<script>
            Swal.fire("Error!", "Error: ' . strtoupper($e->getMessage()) . '", "error")
            .then(function() {
                window.location = "nuevo_servicio.php";
            });
            </script>';
    }
}