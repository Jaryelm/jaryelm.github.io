<?php
require('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'] ?? null;

    if (!$idpa) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    // Consulta para verificar si existen registros completos en transfusion_hemoderivados
    $stmt = $connect->prepare("
        SELECT COUNT(*) as total 
        FROM transfusion_hemoderivados 
        WHERE idpa = :idpa 
        AND tipo_rh IS NOT NULL 
        AND diagnostico_hemoderivados IS NOT NULL 
        AND medico_tratante_hemoderivados IS NOT NULL 
        AND enfermero_responsable_hemoderivados IS NOT NULL 
        AND cantidad_unidades_hemoderivados IS NOT NULL 
        AND hora_inicio_hemoderivados IS NOT NULL 
        AND hora_finalizacion_hemoderivados IS NOT NULL 
        AND pa_antes_transfundir IS NOT NULL 
        AND fc_antes_transfundir IS NOT NULL 
        AND ta_antes_transfundir IS NOT NULL 
        AND fr_antes_transfundir IS NOT NULL 
        AND spo2_antes_transfundir IS NOT NULL
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
