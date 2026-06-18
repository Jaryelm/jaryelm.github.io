<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['id']) && isset($_POST['state'])) {
    $id = (int)$_POST['id'];
    $state = (int)$_POST['state'];

    try {
        // First, check if column exists (Kill switch requires 'state')
        // We'll try to update and if it fails because column doesn't exist, we might need a migration
        // But for now, we assume the table should have it or we create it here via PHP if needed
        
        $sql = "UPDATE positions SET state = ? WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $result = $stmt->execute([$state, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado']);
        }
    } catch (Exception $e) {
        // Fallback: If 'state' column doesn't exist, add it
        if (strpos($e->getMessage(), "Unknown column 'state'") !== false) {
             $connect->exec("ALTER TABLE positions ADD COLUMN state TINYINT(1) DEFAULT 1");
             $stmt = $connect->prepare("UPDATE positions SET state = ? WHERE id = ?");
             $stmt->execute([$state, $id]);
             echo json_encode(['success' => true, 'message' => 'Estado actualizado (columna creada)']);
        } else {
            error_log("Error toggle_position_state: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>
