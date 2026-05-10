<?php
session_start();
require_once '../../backend/bd/Conexion.php'; // $connect está definido aquí como un objeto PDO
header('Content-Type: application/json');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id'])) {
    error_log("Sesión no válida: " . print_r($_SESSION, true));
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit;
}

$userId = intval($_SESSION['id']);
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['signature']) || empty($data['signature'])) {
    error_log("Firma no proporcionada: " . print_r($data, true));
    echo json_encode(['success' => false, 'message' => 'La firma no fue proporcionada.']);
    exit;
}

$signatureBinary = base64_decode(str_replace('data:image/png;base64,', '', $data['signature']));

try {
    // Verificar si ya existe una firma para este usuario
    $checkQuery = "SELECT COUNT(*) FROM user_signatures WHERE user_id = :user_id";
    $checkStmt = $connect->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existingSignatures = $checkStmt->fetchColumn();

    if ($existingSignatures > 0) {
        // Si ya existe una firma, mostrar un mensaje de error
        echo json_encode(['success' => false, 'message' => 'Solo es admitida una firma digital por usuario.']);
        exit;
    }

    // Insertar la nueva firma si no existe ninguna
    $query = "INSERT INTO user_signatures (user_id, signature) VALUES (:user_id, :signature)";
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':signature', $signatureBinary, PDO::PARAM_LOB);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Firma guardada exitosamente.']);
    } else {
        error_log("Error al ejecutar la consulta: " . implode(", ", $stmt->errorInfo()));
        echo json_encode(['success' => false, 'message' => 'Error al guardar la firma.']);
    }
} catch (Exception $e) {
    error_log("Error al guardar la firma: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
