<?php
require_once '../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($connect_hr_leaves)) throw new Exception("Sin conexión.");
        $min = $_POST['min_seniority_years'] ?? '';
        $max = $_POST['max_seniority_years'] ?? '';
        $days = $_POST['granted_days'] ?? '';
        $accum = !empty($_POST['max_accumulated_days']) ? $_POST['max_accumulated_days'] : null;
        $status = isset($_POST['status']) ? 1 : 0;
        
        if ($min === '' || $max === '' || $days === '') throw new Exception("Campos requeridos faltantes.");
        
        $stmt = $connect_hr_leaves->prepare("INSERT INTO hr_vacation_policies (min_seniority_years, max_seniority_years, granted_days, max_accumulated_days, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$min, $max, $days, $accum, $status]);
        echo json_encode(['status' => 'success', 'message' => 'Política agregada correctamente.']);
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Internal error: ' . $e->getMessage()]);
    }
}

