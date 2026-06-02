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
    $technician_id = $_SESSION['id'] ?? null;
    $local_time = date('Y-m-d H:i:s');

    if (empty($study_id) || empty($reason) || empty($technician_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
        exit;
    }

    // Obtener el nombre del usuario autenticado
    $stmt = $connect->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$technician_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }
    $user_name = $user['name'];

    // Verificar si ya existe una repetición para este estudio
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM study_repeats WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        // Actualizar el registro existente
        $updateStmt = $connect->prepare("
            UPDATE study_repeats 
            SET reason = ?, comments = ?, user_name = ?, created_at = ?
            WHERE study_id = ?
        ");
        $updateStmt->execute([
            $reason,
            $comments,
            $user_name,
            $local_time,
            $study_id
        ]);
        echo json_encode(['success' => true, 'message' => 'Repetición actualizada correctamente']);
        actualizarEstadoEstudio($connect, $study_id);
    } else {
        // Insertar un nuevo registro
        $insertStmt = $connect->prepare("
            INSERT INTO study_repeats (
                study_id,
                reason,
                comments,
                user_name,
                created_at
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $study_id,
            $reason,
            $comments,
            $user_name,
            $local_time
        ]);
        echo json_encode(['success' => true, 'message' => 'Repetición guardada correctamente']);
        actualizarEstadoEstudio($connect, $study_id);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la repetición']);
}

// --- FUNCIÓN CENTRALIZADA PARA ACTUALIZAR ESTADO ---
if (!function_exists('actualizarEstadoEstudio')) {
function actualizarEstadoEstudio($connect, $study_id) {
    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM quality_control WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $qc = $stmt->fetch(PDO::FETCH_ASSOC);
    $tieneQC = $qc && $qc['total'] > 0;

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM radiation_dose WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $dose = $stmt->fetch(PDO::FETCH_ASSOC);
    $tieneDosis = $dose && $dose['total'] > 0;

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM study_repeats WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $repeat = $stmt->fetch(PDO::FETCH_ASSOC);
    $hayRepeticion = $repeat && $repeat['total'] > 0;

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM incidents WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);
    $hayIncidencia = $incident && $incident['total'] > 0;

    $stmt = $connect->prepare("SELECT status FROM worklist WHERE id = ?");
    $stmt->execute([$study_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadoActual = $row ? $row['status'] : null;

    if ($estadoActual === 'pending') {
        $nuevoEstado = 'in_progress';
    } else if ($estadoActual === 'in_progress' || $estadoActual === 'completed') {
        if ($tieneQC && $tieneDosis && !$hayRepeticion && !$hayIncidencia) {
            $nuevoEstado = 'completed';
        } else {
            $nuevoEstado = 'in_progress';
        }
    } else {
        return;
    }

    if ($nuevoEstado !== $estadoActual) {
        $stmt = $connect->prepare("UPDATE worklist SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nuevoEstado, $study_id]);
    }
}
}
?> 