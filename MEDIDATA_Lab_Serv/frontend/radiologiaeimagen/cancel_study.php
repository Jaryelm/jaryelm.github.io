<?php
require_once('../../backend/bd/Conexion.php');
session_start();
header('Content-Type: application/json');

date_default_timezone_set('America/Tegucigalpa');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $study_id = $data['study_id'] ?? null;
    $reason = $data['reason'] ?? null;
    $comments = $data['comments'] ?? '';
    $user_id = $_SESSION['id'] ?? null;

    if (empty($study_id) || empty($reason) || empty($user_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
        exit;
    }

    // Obtener el nombre del usuario autenticado
    $stmt = $connect->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }
    $user_name = $user['name'];
    $local_time = date('Y-m-d H:i:s');

    // Verificar si el estudio ya está cancelado
    $stmt = $connect->prepare("SELECT status FROM worklist WHERE id = ?");
    $stmt->execute([$study_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'El estudio ya está cancelado.']);
        exit;
    }

    // Insertar registro de cancelación
    $stmt = $connect->prepare("INSERT INTO study_cancellations (study_id, reason, comments, user_id, user_name, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$study_id, $reason, $comments, $user_id, $user_name, $local_time]);

    // Actualizar estado en worklist
    $stmt = $connect->prepare("UPDATE worklist SET status = 'cancelled', updated_at = ?, technician_id = ? WHERE id = ?");
    $stmt->execute([$local_time, $user_id, $study_id]);

    echo json_encode(['success' => true, 'message' => 'Estudio cancelado correctamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cancelar el estudio: ' . $e->getMessage()]);
} 