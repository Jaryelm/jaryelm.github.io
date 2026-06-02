<?php
/**
 * Pre-clínica: listado JSON de signos vitales por paciente hospitalario u ambulatorio.
 * Consume la vista frontend/enfermeria/pre_clinica*.php desde backend (evita APIs en frontend).
 */
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/bd/Conexion.php';

// Siempre HTTP 200 con JSON: DataTables/jQuery tratan 4xx como fallo de Ajax y no ejecutan dataSrc
// (ver https://datatables.net/tn/7).
if (!isset($_SESSION['id']) || (int) $_SESSION['id'] < 1) {
    echo json_encode(['error' => 'Sesión no válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $idRaw = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
    $tipo = isset($_GET['tipo']) ? trim((string) $_GET['tipo']) : 'paciente';
    $idInt = (int) $idRaw;

    if ($idRaw === '' || $idInt < 1) {
        throw new RuntimeException('El ID del paciente es obligatorio.');
    }

    if ($tipo === 'paciente') {
        $sql = 'SELECT * FROM signos_vitales WHERE idpa = :id ORDER BY created_at DESC';
    } elseif ($tipo === 'ambulatorio') {
        $sql = 'SELECT * FROM signos_vitales_outpatients WHERE id_outpatient = :id ORDER BY created_at DESC';
    } else {
        throw new RuntimeException('Tipo de paciente no reconocido.');
    }

    $stmt = $connect->prepare($sql);
    $stmt->execute([':id' => $idInt]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($records, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
