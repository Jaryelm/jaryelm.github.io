<?php
session_start();
require_once '../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit;
}

$userId = intval($_SESSION['id']);

try {
    // Consulta para obtener solo las firmas del usuario autenticado
    $query = "
        SELECT us.id, us.user_id, u.name, 
               TO_BASE64(us.signature) AS signature
        FROM user_signatures us
        INNER JOIN users u ON us.user_id = u.id
        WHERE us.user_id = :user_id
        ORDER BY us.created_at DESC
    ";

    $stmt = $connect->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($signatures);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener las firmas: ' . $e->getMessage()]);
}
