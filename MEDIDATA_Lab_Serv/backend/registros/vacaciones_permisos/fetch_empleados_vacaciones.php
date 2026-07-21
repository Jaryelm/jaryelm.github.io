<?php
require_once '../../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id_user, CONCAT(nomadm, ' ', apeadm) as text FROM staff_administrative WHERE state = 1");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

