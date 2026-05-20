<?php
/**
 * Pre-clínica: listado JSON de signos vitales por paciente hospitalario u ambulatorio.
 * Consume la vista frontend/enfermeria/pre_clinica*.php desde backend (evita APIs en frontend).
 */
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bd/Conexion.php';

if (!isset($_SESSION['id']) || (int) $_SESSION['id'] < 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Sesión no válida.']);
    exit;
}

try {
    $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
    $tipo = isset($_GET['tipo']) ? trim((string) $_GET['tipo']) : 'paciente';

    if ($id === '') {
        throw new RuntimeException('El ID del paciente es obligatorio.');
    }

    if ($tipo === 'paciente') {
        $sql = 'SELECT * FROM signos_vitales WHERE idpa = :id ORDER BY created_at DESC';
    } else {
        $sql = 'SELECT * FROM signos_vitales_outpatients WHERE id_outpatient = :id ORDER BY created_at DESC';
    }

    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
