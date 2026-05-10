<?php
require('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

if (!isset($_GET['idpa']) || empty($_GET['idpa'])) {
    echo json_encode(['success' => false, 'message' => 'ID del paciente no proporcionado']);
    exit;
}

$idpa = intval($_GET['idpa']);

$stmt = $connect->prepare("SELECT COUNT(*) FROM recuperacion WHERE idpa = :idpa");
$stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmt->execute();
$count = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'hasData' => $count > 0
]);
exit;
