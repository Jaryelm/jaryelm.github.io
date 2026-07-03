<?php
header('Content-Type: application/json');
// Incluir la hora Honduras
date_default_timezone_set('America/Tegucigalpa');
// Incluir la conexión a la base de datos
require_once '../../backend/bd/Conexion.php';

if (!$connect) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo conectar a la base de datos.'
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitizar y validar los campos
    $cuenta = strtoupper(filter_var(trim($_POST['cuenta']), FILTER_SANITIZE_STRING));
    $balance = filter_var(trim($_POST['balance']), FILTER_VALIDATE_FLOAT);
    $impuestos = filter_var(trim($_POST['impuestos']), FILTER_VALIDATE_FLOAT);
    $proveedor_RTN = strtoupper(filter_var(trim($_POST['proveedor_RTN']), FILTER_SANITIZE_STRING));
    $cheque_no = strtoupper(filter_var(trim($_POST['cheque']), FILTER_SANITIZE_STRING));
    $pagar = strtoupper(filter_var(trim($_POST['pagar']), FILTER_SANITIZE_STRING));
    $fecha = trim($_POST['fecha']);
    $cantidad = filter_var(trim($_POST['cantidad']), FILTER_VALIDATE_FLOAT);
    $concepto = strtoupper(filter_var(trim($_POST['concepto']), FILTER_SANITIZE_STRING));
    $asignar_monto = strtoupper(filter_var(trim($_POST['asignar_monto']), FILTER_SANITIZE_STRING)); // Campo de selección
    $monto = filter_var(trim($_POST['monto']), FILTER_VALIDATE_FLOAT);
    $proyecto = strtoupper(filter_var(trim($_POST['proyecto']), FILTER_SANITIZE_STRING));
    $imp_ventas = filter_var(trim($_POST['imp_ventas']), FILTER_VALIDATE_FLOAT);
    $total_asignado = filter_var(trim($_POST['total_asignado']), FILTER_VALIDATE_FLOAT);
    $impuesto = filter_var(trim($_POST['impuesto']), FILTER_VALIDATE_FLOAT);
    $fuera_balance = filter_var(trim($_POST['fuera_balance']), FILTER_VALIDATE_FLOAT);
    $total_pagado = filter_var(trim($_POST['total_pagado']), FILTER_VALIDATE_FLOAT);

    // Validar si algunos campos no son válidos
    if ($balance === false || $impuestos === false || $cantidad === false || 
        $asignar_monto === false || $monto === false || $imp_ventas === false ||
        $total_asignado === false || $impuesto === false || $fuera_balance === false || 
        $total_pagado === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Algunos campos tienen valores incorrectos. Por favor, verifica los datos.'
        ]);
        exit;
    }

    try {
        // Verificar si ya existe un cheque con el mismo número
        $sql_check = "SELECT 1 FROM emitir_cheques WHERE cheque_no = :cheque_no";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bindParam(':cheque_no', $cheque_no);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'El número de cheque ya está registrado.'
            ]);
        } else {
            // Insertar los datos en la base de datos, incluyendo la fecha con la zona horaria correcta
            $sql = "INSERT INTO emitir_cheques (cuenta, balance, impuestos, proveedor_RTN, cheque_no, pagar, fecha, cantidad, concepto, asignar_monto, monto, proyecto, imp_ventas, total_asignado, impuesto, fuera_balance, total_pagado)
                    VALUES (:cuenta, :balance, :impuestos, :proveedor_RTN, :cheque_no, :pagar, :fecha, :cantidad, :concepto, :asignar_monto, :monto, :proyecto, :imp_ventas, :total_asignado, :impuesto, :fuera_balance, :total_pagado)";
            $stmt = $connect->prepare($sql);

            // Bindear los valores
            $stmt->bindParam(':cuenta', $cuenta); // Campo de selección
            $stmt->bindParam(':balance', $balance);
            $stmt->bindParam(':impuestos', $impuestos);
            $stmt->bindParam(':proveedor_RTN', $proveedor_RTN);
            $stmt->bindParam(':cheque_no', $cheque_no);
            $stmt->bindParam(':pagar', $pagar);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':concepto', $concepto);
            $stmt->bindParam(':asignar_monto', $asignar_monto); // Campo de selección
            $stmt->bindParam(':monto', $monto);
            $stmt->bindParam(':proyecto', $proyecto);
            $stmt->bindParam(':imp_ventas', $imp_ventas);
            $stmt->bindParam(':total_asignado', $total_asignado);
            $stmt->bindParam(':impuesto', $impuesto);
            $stmt->bindParam(':fuera_balance', $fuera_balance);
            $stmt->bindParam(':total_pagado', $total_pagado);

            $stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => 'El cheque se ha registrado con éxito.'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al registrar el cheque: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método de solicitud no válido.'
    ]);
}
