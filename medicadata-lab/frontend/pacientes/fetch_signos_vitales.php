<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $idpaRaw = isset($_GET['idpa']) ? trim((string) $_GET['idpa']) : '';
    $tipo = isset($_GET['tipo']) ? trim((string) $_GET['tipo']) : '';
    $idpa = (int) $idpaRaw;

    if ($idpaRaw === '' || $idpa < 1) {
        throw new Exception('El ID del paciente es obligatorio.');
    }

    if ($tipo === 'ambulatorio') {
        $sql = 'SELECT * FROM signos_vitales_outpatients WHERE id_outpatient = :id ORDER BY created_at DESC';
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':id', $idpa, PDO::PARAM_INT);
    } else {
        $sql = 'SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC';
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':idpa', $idpa, PDO::PARAM_INT);
    }

    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
