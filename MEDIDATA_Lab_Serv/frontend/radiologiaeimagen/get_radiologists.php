<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    // Solo médicos con especialidad RADIOLOGIA
    $stmt = $connect->prepare("SELECT idodc, CONCAT(nodoc, ' ', apdoc) AS name, nomesp AS especialidad FROM doctor WHERE UPPER(nomesp) LIKE '%RADIOLOGIA%'");
    $stmt->execute();
    $doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($doctores as $doc) {
        $result[] = [
            'id' => $doc['idodc'],
            'name' => $doc['name'],
            'especialidad' => $doc['especialidad']
        ];
    }
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
