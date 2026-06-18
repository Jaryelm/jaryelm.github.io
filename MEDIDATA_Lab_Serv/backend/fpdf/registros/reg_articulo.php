<?php
header('Content-Type: application/json');
// Incluir la hora de Honduras
date_default_timezone_set('America/Tegucigalpa');
// Incluir la conexión a la base de datos
require_once '../../backend/bd/Conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre'])) {
    // Sanitización y validación
    $linea = strtoupper(trim($_POST['linea']));
    $sub_linea = strtoupper(trim($_POST['sub_linea']));
    $sucursal_bodega = strtoupper(trim($_POST['sucursal_bodega']));
    $envase = strtoupper(trim($_POST['envase']));
    $farmaceutica = strtoupper(trim($_POST['farmaceutica']));
    $concentracion = strtoupper(trim($_POST['concentracion']));
    $via_administracion = strtoupper(trim($_POST['via_administracion']));
    $codigo_articulo = strtoupper(trim($_POST['codigo_articulo']));
    $nombre = strtoupper(trim($_POST['nombre']));
    $descripcion = strtoupper(trim($_POST['descripcion']));
    $precio_venta = trim($_POST['precio_venta']);
    $margen_ganancia = trim($_POST['margen_ganancia']);
    $precio_maximo_venta = trim($_POST['precio_maximo_venta']);
    $existencia_minima = trim($_POST['existencia_minima']);
    $existencia_maxima = trim($_POST['existencia_maxima']);
    $comision = trim($_POST['comision']);
    $fecha_vence = trim($_POST['fecha_vence']);
    $costo = trim($_POST['costo']);
    $impuestos = trim($_POST['impuestos']);
    $lote = trim($_POST['lote']);
    $fecha_registro = date('Y-m-d H:i:s');

    // Verificar si el lote ya existe
    $checkSql = "SELECT COUNT(*) FROM registros_articulos WHERE lote = :lote";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bindParam(':lote', $lote);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'El número de lote ya existe.']);
    } else {
        // Preparar la consulta SQL para la inserción
        $sql = "INSERT INTO registros_articulos (linea, sub_linea, sucursal_bodega, envase, farmaceutica, concentracion, via_administracion, codigo_articulo, nombre, descripcion, precio_venta, margen_ganancia, precio_maximo_venta, existencia_minima, existencia_maxima, comision, fecha_vence, costo, impuestos, lote, fecha_registro) 
                VALUES (:linea, :sub_linea, :sucursal_bodega, :envase, :farmaceutica, :concentracion, :via_administracion, :codigo_articulo, :nombre, :descripcion, :precio_venta, :margen_ganancia, :precio_maximo_venta, :existencia_minima, :existencia_maxima, :comision, :fecha_vence, :costo, :impuestos, :lote, :fecha_registro)";
        
        try {
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(':linea', $linea);
            $stmt->bindParam(':sub_linea', $sub_linea);
            $stmt->bindParam(':sucursal_bodega', $sucursal_bodega);
            $stmt->bindParam(':envase', $envase);
            $stmt->bindParam(':farmaceutica', $farmaceutica);
            $stmt->bindParam(':concentracion', $concentracion);
            $stmt->bindParam(':via_administracion', $via_administracion);
            $stmt->bindParam(':codigo_articulo', $codigo_articulo);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':precio_venta', $precio_venta);
            $stmt->bindParam(':margen_ganancia', $margen_ganancia);
            $stmt->bindParam(':precio_maximo_venta', $precio_maximo_venta);
            $stmt->bindParam(':existencia_minima', $existencia_minima);
            $stmt->bindParam(':existencia_maxima', $existencia_maxima);
            $stmt->bindParam(':comision', $comision);
            $stmt->bindParam(':fecha_vence', $fecha_vence);
            $stmt->bindParam(':costo', $costo);
            $stmt->bindParam(':impuestos', $impuestos);
            $stmt->bindParam(':lote', $lote);        
            $stmt->bindParam(':fecha_registro', $fecha_registro);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Nuevo artículo registrado exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el registro.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Ocurrió un problema con la base de datos: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido o datos incompletos.']);
}
