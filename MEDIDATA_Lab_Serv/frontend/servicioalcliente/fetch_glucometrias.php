<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'];
    if (empty($idpa)) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    // Consulta para obtener los registros
    $sql = "SELECT fecha, turno, hora, glucometria, insulina_cristalina, nph, procesado_por, firma FROM glucometrias WHERE idpa = :idpa ORDER BY created_at DESC";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir firma (BLOB) a Base64
    foreach ($records as &$record) {
        if (!empty($record['firma'])) {
            $record['firma'] = base64_encode($record['firma']);
        }
    }

    // Devuelve los registros en JSON
    echo json_encode(['data' => $records]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
