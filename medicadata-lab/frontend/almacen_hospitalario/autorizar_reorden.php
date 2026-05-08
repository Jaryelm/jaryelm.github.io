<?php
// Configurar la zona horaria de Tegucigalpa, Honduras
date_default_timezone_set('America/Tegucigalpa');
$currentDateTime = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

require_once('../../backend/bd/Conexion.php');

// Acceder a la conexión global
$connect = $GLOBALS['connect'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener los datos enviados desde el frontend
        $id = trim($_POST['id']);
        $estado = trim($_POST['estado']); // 'autorizado' o 'rechazado'
        $usuario_autorizacion = $_SESSION['name']; // Usuario que realiza la acción

        // Validar que el ID y el estado no estén vacíos
        if (empty($id) || !in_array($estado, ['autorizado', 'rechazado'])) {
            throw new Exception("Datos inválidos.");
        }

        // Actualizar el estado de la solicitud en la base de datos
        $stmt_update = $connect->prepare("
            UPDATE reorden_solicitudes 
            SET estado = :estado, fecha_autorizacion = :fecha_autorizacion, usuario_autorizacion = :usuario_autorizacion 
            WHERE id = :id
        ");

        $stmt_update->bindParam(':estado', $estado);
        $stmt_update->bindParam(':fecha_autorizacion', $currentDateTime);
        $stmt_update->bindParam(':usuario_autorizacion', $usuario_autorizacion);
        $stmt_update->bindParam(':id', $id);

        // Ejecutar la consulta
        $stmt_update->execute();

        // Mostrar mensaje de éxito
        echo json_encode(['success' => true, 'message' => "Solicitud {$estado} correctamente."]);

    } catch (Exception $e) {
        // Mostrar mensaje de error
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>