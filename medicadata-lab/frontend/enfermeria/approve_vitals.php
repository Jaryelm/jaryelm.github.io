<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';

date_default_timezone_set('America/Tegucigalpa');
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['error' => 'Sesión no válida. Reinicie sesión e intente de nuevo.']);
        exit;
    }

    $userIdSession = (int) $_SESSION['id'];
    $idRegistro = $_POST['id'] ?? '';
    $tipo_paciente = $_POST['tipo'] ?? 'paciente';

    if (empty($idRegistro)) {
        throw new Exception("El ID del registro es obligatorio.");
    }

    // Obtener el nombre del usuario logueado para registrarlo en reviews_by
    $stmtName = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stmtName->execute([$userIdSession]);
    $nombreRevisor = trim((string) $stmtName->fetchColumn());

    if ($nombreRevisor === '') {
        throw new Exception("No se pudo identificar al revisor.");
    }

    if ($tipo_paciente === 'paciente') {
        $sql = "UPDATE signos_vitales SET reviews_by = :revisor WHERE id = :id";
    } else {
        $sql = "UPDATE signos_vitales_outpatients SET reviews_by = :revisor WHERE id = :id";
    }

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':revisor' => $nombreRevisor,
        ':id' => $idRegistro
    ]);

    echo json_encode([
        'success' => 'Signos vitales aprobados correctamente.'
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>