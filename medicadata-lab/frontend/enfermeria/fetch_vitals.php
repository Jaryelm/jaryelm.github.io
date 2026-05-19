<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $id = $_GET['id'] ?? '';
    $tipo = $_GET['tipo'] ?? 'paciente';

    if (empty($id)) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    if ($tipo === 'paciente') {
        $sql = "SELECT * FROM signos_vitales WHERE idpa = :id ORDER BY created_at DESC";
    } else {
        $sql = "SELECT * FROM signos_vitales_outpatients WHERE id_outpatient = :id ORDER BY created_at DESC";
    }

    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>