<?php
header('Content-Type: application/json');
// Incluir la hora de Honduras
date_default_timezone_set('America/Tegucigalpa');
// Incluir la conexión a la base de datos
require_once '../../backend/bd/Conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nombre-proveedor'])) {
    // Sanitización y validación
    $nombre_proveedor = strtoupper(trim($_POST['nombre-proveedor']));
    $especialidad = strtoupper(trim($_POST['especialidad']));
    $identidad = strtoupper(trim($_POST['identidad']));
    $colegiado = strtoupper(trim($_POST['colegiado']));
    $rtn = strtoupper(trim($_POST['rtn']));
    $celular = trim($_POST['celular']);
    $correo = strtoupper(trim($_POST['correo']));
    // Obtener valores de cuenta BAC
    $cuenta_bac = isset($_POST['cuenta-bac']) ? strtoupper($_POST['cuenta-bac']) : null;
    $cuenta_si = ($cuenta_bac === 'SI') ? strtoupper(trim($_POST['cuenta-si'])) : null; // Solo si tiene cuenta BAC
    $cuenta_no = ($cuenta_bac === 'NO') ? strtoupper(trim($_POST['cuenta-no'])) : null; // Solo si no tiene cuenta BAC
    $tipo_cuenta = isset($_POST['tipo-cuenta']) ? strtoupper(trim($_POST['tipo-cuenta'])) : null; // Solo un valor
    $constancia_pagos = isset($_POST['constancia-pagos']) ? strtoupper($_POST['constancia-pagos']) : null;
    $solicitud_constancia = isset($_POST['solicitud-constancia']) ? strtoupper($_POST['solicitud-constancia']) : null;
    $constancia_vigente = isset($_POST['constancia-vigente']) ? strtoupper($_POST['constancia-vigente']) : null;
    $fecha_registro = date('Y-m-d H:i:s');
    
    // Validar el correo electrónico
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Formato de correo no válido.']);
        exit;
    }
    
    // Manejo del archivo "firma_digital"
    $firma_digital = null;
    if (isset($_FILES['firma-digital']) && $_FILES['firma-digital']['error'] == UPLOAD_ERR_OK) {
        $firmas_dir = '/home4/medic9ue/medidata.medicasa.hn/uploads/firmas/';
        $nuevo_nombre_firma = $nombre_proveedor . '.png'; 
        $firma_ruta = $firmas_dir . $nuevo_nombre_firma;
        if (move_uploaded_file($_FILES['firma-digital']['tmp_name'], $firma_ruta)) {
            $firma_digital = '/uploads/firmas/' . $nuevo_nombre_firma;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al subir la firma digital.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se recibió la firma digital.']);
        exit;
    }

    // Manejo de archivo adjunto (se realiza después del registro)
    $archivo_constancia = null;
    try {
        // Verificar si ya existe un proveedor con el mismo nombre o número de identidad
        $sql_check = "SELECT 1 FROM proveedor_data WHERE identidad = :identidad OR nombre_proveedor = :nombre_proveedor";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bindParam(':identidad', $identidad);
        $stmt_check->bindParam(':nombre_proveedor', $nombre_proveedor);
        $stmt_check->execute();
        if ($stmt_check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'El proveedor ya está registrado.']);
        } else {
            // Preparar la consulta SQL para la inserción
            $sql = "INSERT INTO proveedor_data (nombre_proveedor, especialidad, identidad, colegiado, rtn, celular, correo, cuenta_bac, cuenta_si, cuenta_no, tipo_cuenta, constancia_pagos, solicitud_constancia, constancia_vigente, fecha_registro, archivo_constancia, firma_digital) 
                    VALUES (:nombre_proveedor, :especialidad, :identidad, :colegiado, :rtn, :celular, :correo, :cuenta_bac, :cuenta_si, :cuenta_no, :tipo_cuenta, :constancia_pagos, :solicitud_constancia, :constancia_vigente, :fecha_registro, :archivo_constancia, :firma_digital)";
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(':nombre_proveedor', $nombre_proveedor);
            $stmt->bindParam(':especialidad', $especialidad);
            $stmt->bindParam(':identidad', $identidad);
            $stmt->bindParam(':colegiado', $colegiado);
            $stmt->bindParam(':rtn', $rtn);
            $stmt->bindParam(':celular', $celular);
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':cuenta_bac', $cuenta_bac);
            $stmt->bindParam(':cuenta_si', $cuenta_si);
            $stmt->bindParam(':cuenta_no', $cuenta_no);
            $stmt->bindParam(':tipo_cuenta', $tipo_cuenta);
            $stmt->bindParam(':constancia_pagos', $constancia_pagos);
            $stmt->bindParam(':solicitud_constancia', $solicitud_constancia);
            $stmt->bindParam(':constancia_vigente', $constancia_vigente);
            $stmt->bindParam(':fecha_registro', $fecha_registro);
            $stmt->bindParam(':archivo_constancia', $archivo_constancia);
            $stmt->bindParam(':firma_digital', $firma_digital);
            if ($stmt->execute()) {
                // Si el registro fue exitoso, se procede a manejar el archivo de constancia
                if (isset($_FILES['archivo-constancia']) && $_FILES['archivo-constancia']['error'] == UPLOAD_ERR_OK) {
                    $upload_dir = '/home4/medic9ue/medidata.medicasa.hn/uploads/';
                    $nombre_proveedor_sanitizado = str_replace('_', ' ', trim($nombre_proveedor));
                    $archivo_extension = pathinfo($_FILES['archivo-constancia']['name'], PATHINFO_EXTENSION);
                    $nuevo_nombre_archivo = $nombre_proveedor_sanitizado . '.' . $archivo_extension;
                    $archivo_ruta = $upload_dir . $nuevo_nombre_archivo;
                    if (move_uploaded_file($_FILES['archivo-constancia']['tmp_name'], $archivo_ruta)) {
                        $archivo_constancia = '/uploads/' . $nuevo_nombre_archivo;
                        // Actualizar el registro con la ruta del archivo
                        $sql_update = "UPDATE proveedor_data SET archivo_constancia = :archivo_constancia WHERE identidad = :identidad";
                        $stmt_update = $connect->prepare($sql_update);
                        $stmt_update->bindParam(':archivo_constancia', $archivo_constancia);
                        $stmt_update->bindParam(':identidad', $identidad);
                        $stmt_update->execute();
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error al subir el archivo de constancia.']);
                        exit;
                    }
                }
                echo json_encode(['success' => true, 'message' => 'Registro guardado con éxito.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ocurrió un error al guardar el registro. Inténtalo de nuevo.']);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ocurrió un problema con la base de datos: ' . $e->getMessage()]);
    }
}