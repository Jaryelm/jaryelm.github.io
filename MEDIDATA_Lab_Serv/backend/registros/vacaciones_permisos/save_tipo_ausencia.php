<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    global $connect;
    if (!$connect) throw new Exception("No db connection");
    $pdo = $connect;

    $type_id           = $_POST['type_id'] ?? '';
    $code              = trim($_POST['code'] ?? '');
    $name              = trim($_POST['name'] ?? '');
    $category          = $_POST['category'] ?? '';
    $is_paid           = isset($_POST['is_paid']) && $_POST['is_paid'] ? 1 : 0;
    $deducts_vacation  = isset($_POST['deducts_vacation']) && $_POST['deducts_vacation'] ? 1 : 0;
    $requires_document = isset($_POST['requires_document']) && $_POST['requires_document'] ? 1 : 0;
    $requires_special_auth = isset($_POST['requires_special_auth']) && $_POST['requires_special_auth'] ? 1 : 0;

    if ($code === '' || $name === '' || $category === '') {
        echo json_encode(['success' => false, 'message' => 'El código, nombre y categoría son obligatorios.']);
        exit;
    }
    if (!in_array($category, ['Vacation', 'Permission', 'Medical_Leave', 'License'], true)) {
        echo json_encode(['success' => false, 'message' => 'Categoría inválida.']);
        exit;
    }

    if (empty($type_id)) {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO medic9ue_hr_leaves.hr_absence_types
            (code, name, category, is_paid, deducts_vacation, requires_document, requires_special_auth, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$code, $name, $category, $is_paid, $deducts_vacation, $requires_document, $requires_special_auth]);
        echo json_encode(['success' => true, 'message' => 'Tipo de ausencia agregado correctamente.']);
    } else {
        // Update
        $stmt = $pdo->prepare("UPDATE medic9ue_hr_leaves.hr_absence_types
            SET code = ?, name = ?, category = ?, is_paid = ?, deducts_vacation = ?, requires_document = ?, requires_special_auth = ?
            WHERE type_id = ?");
        $stmt->execute([$code, $name, $category, $is_paid, $deducts_vacation, $requires_document, $requires_special_auth, $type_id]);
        echo json_encode(['success' => true, 'message' => 'Tipo de ausencia actualizado correctamente.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
