<?php
header('Content-Type: application/json');
// Incluir la hora Honduras
date_default_timezone_set('America/Tegucigalpa');
// Incluir la conexión a la base de datos
require_once '../../backend/bd/Conexion.php';

// Manejar el registro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tipo-cuenta'])) {
    $tipo_cuenta = trim($_POST['tipo-cuenta']);
    $cuenta = trim($_POST['cuenta']);
    $nombre = trim($_POST['nombre']);
    $fecha_registro = date('Y-m-d H:i:s');

    try {
        // Verificar si ya existe una cuenta con el mismo nombre o número
        $sql_check = "SELECT 1 FROM cuentas_catalogo WHERE cuenta = :cuenta OR nombre = :nombre";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bindParam(':cuenta', $cuenta);
        $stmt_check->bindParam(':nombre', $nombre);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            // Responder con un error si ya existe
            echo json_encode([
                'success' => false,
                'message' => 'La cuenta o el nombre ya están registrados.'
            ]);
        } else {
            // Preparar la consulta SQL para la inserción
            $sql = "INSERT INTO cuentas_catalogo (tipo_cuenta, cuenta, nombre, fecha_registro) 
                    VALUES (:tipo_cuenta, :cuenta, :nombre, :fecha_registro)";
            
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(':tipo_cuenta', $tipo_cuenta);
            $stmt->bindParam(':cuenta', $cuenta);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':fecha_registro', $fecha_registro);

            if ($stmt->execute()) {
                // Responder con éxito
                echo json_encode([
                    'success' => true,
                    'message' => 'Registro Guardado.'
                ]);
            } else {
                // Responder con error de inserción
                echo json_encode([
                    'success' => false,
                    'message' => 'Inténtalo de Nuevo.'
                ]);
            }
        }
    } catch (PDOException $e) {
        // Responder con un error de base de datos
        echo json_encode([
            'success' => false,
            'message' => 'Ocurrió un problema con la base de datos: ' . $e->getMessage()
        ]);
    }
}
