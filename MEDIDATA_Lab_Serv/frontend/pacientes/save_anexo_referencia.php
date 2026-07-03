<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

try {
    if (
        empty($_POST['idpa']) || empty($_POST['medico_ref']) || empty($_POST['hospital_ref']) ||
        empty($_POST['servicio_ref']) || empty($_POST['resumen_clinico']) || empty($_POST['diagnostico_ref']) ||
        empty($_POST['ta_ref']) || empty($_POST['llenado_capilar']) || empty($_POST['motivo_ref']) ||
        empty($_POST['procesado_por'])
    ) {
        throw new Exception("Faltan datos obligatorios.");
    }

    $idpa = intval($_POST['idpa']);
    $medico_ref = trim($_POST['medico_ref']);
    $hospital_ref = trim($_POST['hospital_ref']);
    $servicio_ref = trim($_POST['servicio_ref']);
    $resumen_clinico = trim($_POST['resumen_clinico']);
    $diagnostico_ref = trim($_POST['diagnostico_ref']);
    $ta_ref = trim($_POST['ta_ref']);
    $llenado_capilar = trim($_POST['llenado_capilar']);
    $motivo_ref = trim($_POST['motivo_ref']);
    $procesado_por = trim($_POST['procesado_por']);
    $fecha_registro = date('Y-m-d');
    $hora_registro = date('H:i:s');

    // ✅ Verificar si ya existe un registro para este paciente
    $stmtCheck = $connect->prepare("SELECT COUNT(*) FROM anexo_referencia WHERE idpa = :idpa");
    $stmtCheck->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmtCheck->execute();
    $registroExiste = $stmtCheck->fetchColumn();

    if ($registroExiste > 0) {
        throw new Exception("Ya existe un registro de anexo de referencia para este paciente. No se permite más de uno.");
    }

    // ✅ Asegurar que valores numéricos sean cadenas vacías si están vacíos
    $temperatura_ref = isset($_POST['temperatura_ref']) && $_POST['temperatura_ref'] !== '' ? trim($_POST['temperatura_ref']) : 'N/A';
    $fc_ref = isset($_POST['fc_ref']) && $_POST['fc_ref'] !== '' ? trim($_POST['fc_ref']) : 'N/A';
    $fr_ref = isset($_POST['fr_ref']) && $_POST['fr_ref'] !== '' ? trim($_POST['fr_ref']) : 'N/A';
    $pa_ref = isset($_POST['pa_ref']) && $_POST['pa_ref'] !== '' ? trim($_POST['pa_ref']) : 'N/A';
    $escala_glasgow_ref = isset($_POST['escala_glasgow_ref']) && $_POST['escala_glasgow_ref'] !== '' ? trim($_POST['escala_glasgow_ref']) : 'N/A';
    $spo2_ref = isset($_POST['spo2_ref']) && $_POST['spo2_ref'] !== '' ? trim($_POST['spo2_ref']) : 'N/A';

    // ✅ Insertar datos en la tabla anexo_referencia
    $stmt = $connect->prepare("
        INSERT INTO anexo_referencia (
            idpa, medico_ref, hospital_ref, servicio_ref, resumen_clinico, diagnostico_ref, 
            temperatura_ref, fc_ref, fr_ref, pa_ref, ta_ref, llenado_capilar, spo2_ref, 
            escala_glasgow_ref, motivo_ref, procesado_por, fecha_registro, hora_registro
        ) VALUES (
            :idpa, :medico_ref, :hospital_ref, :servicio_ref, :resumen_clinico, :diagnostico_ref, 
            :temperatura_ref, :fc_ref, :fr_ref, :pa_ref, :ta_ref, :llenado_capilar, :spo2_ref, 
            :escala_glasgow_ref, :motivo_ref, :procesado_por, :fecha_registro, :hora_registro
        )
    ");

    // Bind de parámetros, asegurando valores NULL en campos opcionales
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':medico_ref', $medico_ref, PDO::PARAM_STR);
    $stmt->bindParam(':hospital_ref', $hospital_ref, PDO::PARAM_STR);
    $stmt->bindParam(':servicio_ref', $servicio_ref, PDO::PARAM_STR);
    $stmt->bindParam(':resumen_clinico', $resumen_clinico, PDO::PARAM_STR);
    $stmt->bindParam(':diagnostico_ref', $diagnostico_ref, PDO::PARAM_STR);
    $stmt->bindParam(':temperatura_ref', $temperatura_ref, PDO::PARAM_STR);
    $stmt->bindParam(':fc_ref', $fc_ref, PDO::PARAM_STR);
    $stmt->bindParam(':fr_ref', $fr_ref, PDO::PARAM_STR);
    $stmt->bindParam(':pa_ref', $pa_ref, PDO::PARAM_STR);
    $stmt->bindParam(':ta_ref', $ta_ref, PDO::PARAM_STR);
    $stmt->bindParam(':llenado_capilar', $llenado_capilar, PDO::PARAM_STR);
    $stmt->bindParam(':spo2_ref', $spo2_ref, PDO::PARAM_STR);
    $stmt->bindParam(':escala_glasgow_ref', $escala_glasgow_ref, PDO::PARAM_STR);
    $stmt->bindParam(':motivo_ref', $motivo_ref, PDO::PARAM_STR);
    $stmt->bindParam(':procesado_por', $procesado_por, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_registro', $fecha_registro);
    $stmt->bindParam(':hora_registro', $hora_registro);

    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Anexo de referencia guardado correctamente."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
