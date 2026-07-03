<?php
header('Content-Type: application/json');
// Incluir la hora Honduras
date_default_timezone_set('America/Tegucigalpa');
// Incluir la conexión a la base de datos
require_once '../../backend/bd/Conexion.php';

// Manejar la eliminación
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cuenta-eliminar'])) {
    $cuenta_eliminar = trim($_POST['cuenta-eliminar']);
    try {
        // Verificar si existe la cuenta antes de eliminar
        $sql_check = "SELECT 1 FROM cuentas_catalogo WHERE cuenta = :cuenta";
        $stmt_check = $connect->prepare($sql_check);
        $stmt_check->bindParam(':cuenta', $cuenta_eliminar);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() === 0) {
            // Responder con un error si la cuenta no existe
            echo json_encode([
                'success' => false,
                'message' => 'La cuenta no existe.'
            ]);
        } else {
            // Preparar la consulta SQL para la eliminación
            $sql = "DELETE FROM cuentas_catalogo WHERE cuenta = :cuenta";
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(':cuenta', $cuenta_eliminar);
            
            if ($stmt->execute()) {
                // Responder con éxito
                echo json_encode([
                    'success' => true,
                    'message' => 'Cuenta eliminada con éxito.'
                ]);
            } else {
                // Responder con error de eliminación
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar la cuenta. Inténtalo de nuevo.'
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