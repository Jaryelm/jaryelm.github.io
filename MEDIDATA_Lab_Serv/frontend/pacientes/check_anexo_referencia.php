<?php
require('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'] ?? null;

    if (!$idpa) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    $stmt = $connect->prepare("
        SELECT COUNT(*) as total 
        FROM anexo_referencia 
        WHERE idpa = :idpa 
        AND medico_ref IS NOT NULL 
        AND hospital_ref IS NOT NULL 
        AND servicio_ref IS NOT NULL 
        AND resumen_clinico IS NOT NULL 
        AND diagnostico_ref IS NOT NULL
        AND motivo_ref IS NOT NULL
        AND temperatura_ref IS NOT NULL
        AND fc_ref IS NOT NULL
        AND fr_ref IS NOT NULL
        AND pa_ref IS NOT NULL
        AND ta_ref IS NOT NULL
        AND llenado_capilar IS NOT NULL
        AND spo2_ref IS NOT NULL
        AND escala_glasgow_ref IS NOT NULL
    ");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $isComplete = $result['total'] > 0;

    echo json_encode(["success" => true, "hasData" => $isComplete]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
