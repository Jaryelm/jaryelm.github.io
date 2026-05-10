<?php
require_once __DIR__ . '/../../backend/bd/Conexion.php';
header('Content-Type: application/json');

$id_doctor = isset($_POST['id_doctor']) ? intval($_POST['id_doctor']) : 0;
$id_servicio = isset($_POST['id_servicio']) ? intval($_POST['id_servicio']) : 0;
$tipo_honorario = isset($_POST['tipo_honorario']) ? $_POST['tipo_honorario'] : '';

if ($id_doctor <= 0 || $id_servicio <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de doctor y servicio son requeridos.']);
    exit;
}

try {
    if ($tipo_honorario === 'porcentaje') {
        $porcentaje = isset($_POST['porcentaje']) ? floatval($_POST['porcentaje']) : 0;
        
        if ($porcentaje < 0 || $porcentaje > 100) {
            echo json_encode(['success' => false, 'message' => 'El porcentaje debe estar entre 0 y 100.']);
            exit;
        }
        
        $sql = "INSERT INTO honorarios_configuracion (id_doctor, id_servicio, porcentaje_honorario, cuota_fija)
                VALUES (?, ?, ?, 0.00)
                ON DUPLICATE KEY UPDATE 
                    porcentaje_honorario = VALUES(porcentaje_honorario),
                    cuota_fija = 0.00";
        
        $stmt = $connect->prepare($sql);
        $success = $stmt->execute([$id_doctor, $id_servicio, $porcentaje]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Porcentaje actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar porcentaje.']);
        }
        
    } elseif ($tipo_honorario === 'cuota') {
        $cuota_fija = isset($_POST['cuota_fija']) ? floatval($_POST['cuota_fija']) : 0;
        
        if ($cuota_fija < 0) {
            echo json_encode(['success' => false, 'message' => 'La cuota fija debe ser mayor o igual a 0.']);
            exit;
        }
        
        $sql = "INSERT INTO honorarios_configuracion (id_doctor, id_servicio, porcentaje_honorario, cuota_fija)
                VALUES (?, ?, 0.00, ?)
                ON DUPLICATE KEY UPDATE 
                    porcentaje_honorario = 0.00,
                    cuota_fija = VALUES(cuota_fija)";
        
        $stmt = $connect->prepare($sql);
        $success = $stmt->execute([$id_doctor, $id_servicio, $cuota_fija]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Cuota fija actualizada correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar cuota fija.']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo de honorario no válido.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 