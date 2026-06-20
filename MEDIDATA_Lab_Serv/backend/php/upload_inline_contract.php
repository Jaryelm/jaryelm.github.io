<?php
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/staff_colaborador_bootstrap.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $table = trim($_POST['table'] ?? '');
    $id_col = trim($_POST['idcol'] ?? '');

    $allowed_tables = ['staff_administrative', 'staff_general_services', 'nurse', 'doctor'];

    if (!in_array($table, $allowed_tables)) {
        echo json_encode(['status' => 'error', 'message' => 'Tabla no permitida']);
        exit;
    }

    if ($id <= 0 || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Archivo inválido o ID incorrecto']);
        exit;
    }

    try {
        $fileContent = file_get_contents($_FILES['file']['tmp_name']);
        
        $sql = "UPDATE {$table} SET url_contrato = :file WHERE {$id_col} = :id";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':file', $fileContent, PDO::PARAM_LOB);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['status' => 'success', 'message' => 'Contrato subido']);
    } catch (Exception $e) {
        error_log('Error en upload_inline_contract.php: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar el archivo']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
